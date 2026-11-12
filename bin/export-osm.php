<?php
/**
 * Exports Kyiv reference geography from OpenStreetMap into data/osm/.
 *
 * Writes three GeoJSON files: district boundaries, water areas and named
 * streets split by district, then runs the sanity checks from --check.
 *
 * Usage:
 *   php bin/export-osm.php [--cache=DIR] [--check]
 *
 *   --cache=DIR  Keep raw Overpass responses in DIR and reuse them on the next
 *                run instead of querying the API again.
 *   --check      Skip the export and only check the files already in data/osm/.
 *
 * Requires PHP 8.1+ with the curl and json extensions.
 */

declare(strict_types=1);

const CITY_RELATION_ID = 421866;
const OVERPASS_ENDPOINTS = array(
	'https://overpass-api.de/api/interpreter',
	'https://overpass.private.coffee/api/interpreter',
	'https://overpass.kumi.systems/api/interpreter',
);
const USER_AGENT = 'real-estate-platform-osm-export/1.0 (reference data for a demo catalog)';
const PAUSE_SECONDS = 10;
const RETRY_DELAYS = array( 30, 60, 120 );

// Simplification tolerances in meters and coordinate precision.
const DISTRICT_TOLERANCE = 10.0;
const WATER_TOLERANCE = 10.0;
const STREET_TOLERANCE = 5.0;
const COORD_DECIMALS = 5;

// Water areas smaller than this are dropped (1 ha).
const WATER_MIN_AREA_M2 = 10000.0;

// Street classes kept: urban streets only, no motorways, service roads or paths.
const STREET_HIGHWAYS = array( 'trunk', 'primary', 'secondary', 'tertiary', 'unclassified', 'residential', 'living_street' );

// Approximate meters per degree at Kyiv latitude, used for local projection.
const M_PER_DEG_LAT = 111200.0;
const M_PER_DEG_LON = 71000.0;

$root_dir   = dirname( __DIR__ );
$output_dir = $root_dir . '/data/osm';
$options    = getopt( '', array( 'cache:', 'check' ) );

if ( ! isset( $options['check'] ) ) {
	$cache_dir = isset( $options['cache'] ) ? (string) $options['cache'] : null;
	export_all( $output_dir, $cache_dir );
}

exit( run_checks( $output_dir ) ? 0 : 1 );

/* -------------------------------------------------------------------------
 * Export
 * ---------------------------------------------------------------------- */

/**
 * Runs the queries and writes all files.
 */
function export_all( string $output_dir, ?string $cache_dir ): void {
	if ( ! is_dir( $output_dir ) && ! mkdir( $output_dir, 0755, true ) ) {
		fail( "Cannot create $output_dir" );
	}

	$area     = 'area(' . ( 3600000000 + CITY_RELATION_ID ) . ')->.city;';
	$highways = implode( '|', STREET_HIGHWAYS );

	$raw_districts = overpass(
		'districts',
		"[out:json][timeout:180];$area rel(area.city)[\"boundary\"=\"administrative\"][\"admin_level\"=\"10\"];out geom;",
		$cache_dir
	);
	$raw_city      = overpass(
		'city',
		'[out:json][timeout:180];rel(' . CITY_RELATION_ID . ');out geom;',
		$cache_dir
	);
	$raw_water     = overpass(
		'water',
		"[out:json][timeout:300];$area("
		. 'way(area.city)["natural"="water"];rel(area.city)["natural"="water"];'
		. 'way(area.city)["waterway"="riverbank"];rel(area.city)["waterway"="riverbank"];'
		. 'way(area.city)["landuse"="reservoir"];rel(area.city)["landuse"="reservoir"];'
		. ');out geom;',
		$cache_dir
	);
	$raw_streets   = overpass(
		'streets',
		"[out:json][timeout:300];{$area}way(area.city)[\"highway\"~\"^($highways)$\"][\"name\"];out tags geom;",
		$cache_dir
	);

	$exported_at = gmdate( 'Y-m-d\TH:i:s\Z' );
	if ( null !== $cache_dir && is_file( $cache_dir . '/districts.json' ) ) {
		$exported_at = gmdate( 'Y-m-d\TH:i:s\Z', (int) filemtime( $cache_dir . '/districts.json' ) );
	}

	$city_rings = array();
	foreach ( $raw_city['elements'] as $element ) {
		$city_rings = assemble_rings( $element['members'] ?? array() );
	}
	if ( array() === $city_rings ) {
		fail( 'City boundary is empty' );
	}
	$city_bbox = rings_bbox( $city_rings );

	$districts = build_districts( $raw_districts['elements'] );
	write_geojson(
		$output_dir . '/districts.geojson',
		metadata( $exported_at, 'Administrative districts of Kyiv (boundary=administrative, admin_level=10).', DISTRICT_TOLERANCE ),
		$districts
	);

	$water = build_water( $raw_water['elements'], $city_bbox );
	write_geojson(
		$output_dir . '/water.geojson',
		metadata( $exported_at, 'Water areas inside the Kyiv bounding box larger than 1 ha, clipped to that box.', WATER_TOLERANCE ),
		$water
	);

	$streets = build_streets( $raw_streets['elements'], $districts );
	write_geojson(
		$output_dir . '/streets.geojson',
		metadata( $exported_at, 'Named urban streets of Kyiv, one feature per street and district.', STREET_TOLERANCE ),
		$streets
	);
}

/**
 * Sends a query to the first Overpass endpoint that answers.
 *
 * @return array<string, mixed>
 */
function overpass( string $name, string $query, ?string $cache_dir ): array {
	static $first_request = true;

	$cache_file = null === $cache_dir ? null : rtrim( $cache_dir, '/' ) . "/$name.json";
	if ( null !== $cache_file && is_file( $cache_file ) ) {
		log_line( "Using cached $cache_file" );
		return decode_json( (string) file_get_contents( $cache_file ), $cache_file );
	}

	foreach ( OVERPASS_ENDPOINTS as $endpoint ) {
		foreach ( array_merge( array( 0 ), RETRY_DELAYS ) as $delay ) {
			$wait = $first_request ? $delay : max( $delay, PAUSE_SECONDS );
			if ( $wait > 0 ) {
				sleep( $wait );
			}
			$first_request = false;

			log_line( "Querying $name from $endpoint" );
			[ $status, $body ] = http_post( $endpoint, $query );

			if ( 200 === $status && str_starts_with( ltrim( $body ), '{' ) ) {
				$data = decode_json( $body, $endpoint );
				if ( isset( $data['remark'] ) && str_contains( (string) $data['remark'], 'error' ) ) {
					log_line( 'Overpass remark: ' . $data['remark'] );
					continue;
				}
				if ( null !== $cache_file ) {
					if ( ! is_dir( (string) $cache_dir ) ) {
						mkdir( (string) $cache_dir, 0755, true );
					}
					file_put_contents( $cache_file, $body );
				}
				return $data;
			}

			log_line( "HTTP $status from $endpoint" );
			// Only overload answers are worth retrying on the same endpoint.
			if ( ! in_array( $status, array( 429, 502, 503, 504 ), true ) ) {
				break;
			}
		}
	}

	fail( "All Overpass endpoints failed for $name" );
}

/**
 * Posts an Overpass query.
 *
 * @return array{0: int, 1: string}
 */
function http_post( string $url, string $query ): array {
	$handle = curl_init( $url );
	curl_setopt_array(
		$handle,
		array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => http_build_query( array( 'data' => $query ) ),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT      => USER_AGENT,
			CURLOPT_TIMEOUT        => 420,
			CURLOPT_CONNECTTIMEOUT => 30,
		)
	);
	$body   = curl_exec( $handle );
	$status = (int) curl_getinfo( $handle, CURLINFO_HTTP_CODE );

	return array( $status, is_string( $body ) ? $body : '' );
}

/**
 * Builds district features.
 *
 * @param array<int, array<string, mixed>> $elements Overpass relations.
 * @return array<int, array<string, mixed>>
 */
function build_districts( array $elements ): array {
	$features = array();

	foreach ( $elements as $element ) {
		$tags    = $element['tags'] ?? array();
		$name_uk = (string) ( $tags['name:uk'] ?? $tags['name'] ?? '' );
		$name_en = (string) ( $tags['name:en'] ?? '' );
		// OSM has both "District" and "district" here; use one spelling.
		$name_en = (string) preg_replace( '/ district$/', ' District', $name_en );
		$slug    = trim( (string) preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) preg_replace( '/ District$/', '', $name_en ) ) ), '-' );

		if ( '' === $slug ) {
			fail( "District relation {$element['id']} has no English name" );
		}

		$polygons = rings_to_polygons( simplify_rings( assemble_rings( $element['members'] ?? array() ), DISTRICT_TOLERANCE ) );

		$features[] = array(
			'type'       => 'Feature',
			'id'         => $slug,
			'properties' => array(
				'slug'     => $slug,
				'name_uk'  => $name_uk,
				'name_en'  => $name_en,
				'osm_id'   => 'relation/' . $element['id'],
				'area_km2' => round( polygons_area( $polygons ) / 1e6, 2 ),
			),
			'geometry'   => array(
				'type'        => 'MultiPolygon',
				'coordinates' => $polygons,
			),
		);
	}

	usort( $features, fn( $a, $b ) => strcmp( $a['id'], $b['id'] ) );

	return $features;
}

/**
 * Builds water features clipped to the city bounding box.
 *
 * @param array<int, array<string, mixed>> $elements Overpass ways and relations.
 * @param array{0: float, 1: float, 2: float, 3: float} $bbox Min lon, min lat, max lon, max lat.
 * @return array<int, array<string, mixed>>
 */
function build_water( array $elements, array $bbox ): array {
	$features = array();
	$seen     = array();

	foreach ( $elements as $element ) {
		$key = $element['type'] . '/' . $element['id'];
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;

		if ( 'way' === $element['type'] ) {
			$rings = assemble_rings( array( array( 'type' => 'way', 'role' => 'outer', 'geometry' => $element['geometry'] ?? array() ) ) );
		} elseif ( 'relation' === $element['type'] ) {
			$rings = assemble_rings( $element['members'] ?? array() );
		} else {
			continue;
		}

		$clipped = array();
		foreach ( $rings as $ring ) {
			$ring['points'] = clip_ring( $ring['points'], $bbox );
			if ( count( $ring['points'] ) >= 4 ) {
				$clipped[] = $ring;
			}
		}

		$polygons = rings_to_polygons( simplify_rings( $clipped, WATER_TOLERANCE ) );
		$area     = polygons_area( $polygons );
		if ( $area < WATER_MIN_AREA_M2 ) {
			continue;
		}

		$tags = $element['tags'] ?? array();
		$kind = $tags['water'] ?? ( isset( $tags['waterway'] ) ? 'river' : ( isset( $tags['landuse'] ) ? 'reservoir' : 'water' ) );

		$features[] = array(
			'type'       => 'Feature',
			'id'         => $key,
			'properties' => array(
				'osm_id'   => $key,
				'kind'     => $kind,
				'name_uk'  => (string) ( $tags['name:uk'] ?? $tags['name'] ?? '' ),
				'name_en'  => (string) ( $tags['name:en'] ?? '' ),
				'area_km2' => round( $area / 1e6, 3 ),
			),
			'geometry'   => array(
				'type'        => 'MultiPolygon',
				'coordinates' => $polygons,
			),
		);
	}

	usort( $features, fn( $a, $b ) => $b['properties']['area_km2'] <=> $a['properties']['area_km2'] ?: strcmp( $a['id'], $b['id'] ) );

	return $features;
}

/**
 * Splits named streets by district and builds one feature per street and district.
 *
 * @param array<int, array<string, mixed>> $elements  Overpass ways.
 * @param array<int, array<string, mixed>> $districts District features.
 * @return array<int, array<string, mixed>>
 */
function build_streets( array $elements, array $districts ): array {
	$indexes = array();
	foreach ( $districts as $district ) {
		$indexes[ $district['id'] ] = build_polygon_index( $district['geometry']['coordinates'] );
	}

	// [district][street name] => street data with raw segments.
	$streets = array();

	foreach ( $elements as $element ) {
		$tags    = $element['tags'] ?? array();
		$name_uk = trim( (string) ( $tags['name:uk'] ?? $tags['name'] ?? '' ) );
		$points  = array_map( fn( $p ) => array( (float) $p['lon'], (float) $p['lat'] ), $element['geometry'] ?? array() );
		if ( '' === $name_uk || count( $points ) < 2 || 'yes' === ( $tags['area'] ?? '' ) ) {
			continue;
		}
		// Nobody lives on a bridge or in a tunnel: skip those ways and named bridges and overpasses.
		if ( ! in_array( $tags['bridge'] ?? 'no', array( 'no', '' ), true ) || ! in_array( $tags['tunnel'] ?? 'no', array( 'no', '' ), true )
			|| preg_match( '/(^|\s)(міст|шляхопровід|мостовий перехід)(\s|$)/u', mb_strtolower( $name_uk ) ) ) {
			continue;
		}

		foreach ( $indexes as $slug => $index ) {
			$run = array();
			foreach ( $points as $point ) {
				if ( point_in_index( $point, $index ) ) {
					$run[] = $point;
					continue;
				}
				if ( count( $run ) >= 2 ) {
					$streets[ $slug ][ $name_uk ]['segments'][] = $run;
				}
				$run = array();
			}
			if ( count( $run ) >= 2 ) {
				$streets[ $slug ][ $name_uk ]['segments'][] = $run;
			}
			if ( isset( $streets[ $slug ][ $name_uk ] ) ) {
				// Ways of one street may disagree on name:en; the first non-empty one wins.
				if ( '' === ( $streets[ $slug ][ $name_uk ]['name_en'] ?? '' ) ) {
					$streets[ $slug ][ $name_uk ]['name_en'] = trim( (string) ( $tags['name:en'] ?? '' ) );
				}
				$streets[ $slug ][ $name_uk ]['highway'][ $tags['highway'] ] = true;
			}
		}
	}

	$features = array();
	ksort( $streets );
	foreach ( $streets as $slug => $by_name ) {
		uksort( $by_name, 'strcmp' );
		foreach ( $by_name as $name_uk => $street ) {
			$lines = array();
			foreach ( join_segments( $street['segments'] ) as $segment ) {
				$line = round_points( douglas_peucker( $segment, STREET_TOLERANCE ) );
				if ( count( $line ) >= 2 ) {
					$lines[] = $line;
				}
			}
			if ( array() === $lines ) {
				continue;
			}

			$name_en   = $street['name_en'];
			$en_source = 'osm';
			if ( '' === $name_en ) {
				$name_en   = transliterate_street( $name_uk );
				$en_source = 'transliteration';
			}

			$features[] = array(
				'type'       => 'Feature',
				'properties' => array(
					'district'       => $slug,
					'name_uk'        => $name_uk,
					'name_en'        => $name_en,
					'name_en_source' => $en_source,
					'highway'        => highest_class( array_keys( $street['highway'] ) ),
				),
				'geometry'   => array(
					'type'        => 'MultiLineString',
					'coordinates' => $lines,
				),
			);
		}
	}

	return $features;
}

/**
 * Returns the most important highway class from a list.
 *
 * @param array<int, string> $classes Highway values.
 */
function highest_class( array $classes ): string {
	foreach ( STREET_HIGHWAYS as $class ) {
		if ( in_array( $class, $classes, true ) ) {
			return $class;
		}
	}
	return $classes[0];
}

/**
 * Joins polylines that share end points.
 *
 * @param array<int, array<int, array{0: float, 1: float}>> $segments Polylines.
 * @return array<int, array<int, array{0: float, 1: float}>>
 */
function join_segments( array $segments ): array {
	$result = array();

	while ( array() !== $segments ) {
		$line   = array_shift( $segments );
		$joined = true;
		while ( $joined ) {
			$joined = false;
			foreach ( $segments as $i => $other ) {
				$first = $other[0];
				$last  = $other[ count( $other ) - 1 ];
				if ( $first === $line[ count( $line ) - 1 ] ) {
					$line = array_merge( $line, array_slice( $other, 1 ) );
				} elseif ( $last === $line[0] ) {
					$line = array_merge( $other, array_slice( $line, 1 ) );
				} elseif ( $last === $line[ count( $line ) - 1 ] ) {
					$line = array_merge( $line, array_slice( array_reverse( $other ), 1 ) );
				} elseif ( $first === $line[0] ) {
					$line = array_merge( array_reverse( $other ), array_slice( $line, 1 ) );
				} else {
					continue;
				}
				unset( $segments[ $i ] );
				$joined = true;
			}
		}
		$result[] = $line;
	}

	return $result;
}

/* -------------------------------------------------------------------------
 * Transliteration
 * ---------------------------------------------------------------------- */

/**
 * Builds an English street name: transliterated proper part plus an English street type.
 */
function transliterate_street( string $name_uk ): string {
	static $types = array(
		'вулиця'    => 'Street',
		'проспект'  => 'Avenue',
		'провулок'  => 'Lane',
		'бульвар'   => 'Boulevard',
		'площа'     => 'Square',
		'майдан'    => 'Square',
		'узвіз'     => 'Descent',
		'шосе'      => 'Highway',
		'набережна' => 'Embankment',
		'тупик'     => 'Dead End',
		'проїзд'    => 'Passage',
		'алея'      => 'Alley',
		'дорога'    => 'Road',
		'лінія'     => 'Line',
		'просіка'   => 'Clearing',
	);

	$words = preg_split( '/\s+/u', $name_uk ) ?: array();
	$type  = '';
	foreach ( $words as $i => $word ) {
		$lower = mb_strtolower( $word );
		if ( isset( $types[ $lower ] ) ) {
			$type = $types[ $lower ];
			unset( $words[ $i ] );
			break;
		}
	}

	$proper = transliterate( implode( ' ', $words ) );
	return trim( $proper . ( '' === $type ? '' : ' ' . $type ) );
}

/**
 * Transliterates Ukrainian text by the official system (Cabinet of Ministers resolution No. 55, 2010).
 */
function transliterate( string $text ): string {
	static $map = array(
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'h', 'ґ' => 'g', 'д' => 'd', 'е' => 'e',
		'є' => 'ie', 'ж' => 'zh', 'з' => 'z', 'и' => 'y', 'і' => 'i', 'ї' => 'i', 'й' => 'i',
		'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r',
		'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch',
		'ш' => 'sh', 'щ' => 'shch', 'ь' => '', 'ю' => 'iu', 'я' => 'ia',
		"'" => '', '’' => '', 'ʼ' => '',
	);
	// At the start of a word these letters are written differently.
	static $initial = array( 'є' => 'ye', 'ї' => 'yi', 'й' => 'y', 'ю' => 'yu', 'я' => 'ya' );

	$chars  = mb_str_split( $text );
	$result = '';
	$count  = count( $chars );

	for ( $i = 0; $i < $count; $i++ ) {
		$char  = $chars[ $i ];
		$lower = mb_strtolower( $char );
		$upper = $char !== $lower;

		if ( ! isset( $map[ $lower ] ) ) {
			$result .= $char;
			continue;
		}

		$prev     = $i > 0 ? mb_strtolower( $chars[ $i - 1 ] ) : '';
		$at_start = '' === $prev || ! isset( $map[ $prev ] );
		$latin    = $at_start && isset( $initial[ $lower ] ) ? $initial[ $lower ] : $map[ $lower ];

		// The combination "зг" is written "zgh" to keep it apart from "ж".
		if ( 'г' === $lower && 'з' === $prev ) {
			$latin = 'gh';
		}

		if ( $upper && '' !== $latin ) {
			$next_upper = $i + 1 < $count && mb_strtolower( $chars[ $i + 1 ] ) !== $chars[ $i + 1 ];
			$latin      = $next_upper ? strtoupper( $latin ) : ucfirst( $latin );
		}
		$result .= $latin;
	}

	return $result;
}

/* -------------------------------------------------------------------------
 * Geometry
 * ---------------------------------------------------------------------- */

/**
 * Stitches relation member ways into closed rings.
 *
 * @param array<int, array<string, mixed>> $members Relation members with geometry.
 * @return array<int, array{role: string, points: array<int, array{0: float, 1: float}>}>
 */
function assemble_rings( array $members ): array {
	$open  = array( 'outer' => array(), 'inner' => array() );
	$rings = array();

	foreach ( $members as $member ) {
		if ( 'way' !== ( $member['type'] ?? '' ) || empty( $member['geometry'] ) ) {
			continue;
		}
		$role   = 'inner' === ( $member['role'] ?? '' ) ? 'inner' : 'outer';
		$points = array();
		foreach ( $member['geometry'] as $node ) {
			if ( null !== $node ) {
				$points[] = array( (float) $node['lon'], (float) $node['lat'] );
			}
		}
		if ( count( $points ) >= 2 ) {
			$open[ $role ][] = $points;
		}
	}

	foreach ( $open as $role => $lines ) {
		while ( array() !== $lines ) {
			$ring = array_shift( $lines );
			while ( $ring[0] !== $ring[ count( $ring ) - 1 ] ) {
				$end   = $ring[ count( $ring ) - 1 ];
				$found = false;
				foreach ( $lines as $i => $line ) {
					if ( $line[0] === $end ) {
						$ring = array_merge( $ring, array_slice( $line, 1 ) );
					} elseif ( $line[ count( $line ) - 1 ] === $end ) {
						$ring = array_merge( $ring, array_slice( array_reverse( $line ), 1 ) );
					} else {
						continue;
					}
					unset( $lines[ $i ] );
					$found = true;
					break;
				}
				if ( ! $found ) {
					// An unclosed ring means the relation is broken or cut; close it as is.
					$ring[] = $ring[0];
				}
			}
			if ( count( $ring ) >= 4 ) {
				$rings[] = array(
					'role'   => $role,
					'points' => $ring,
				);
			}
		}
	}

	return $rings;
}

/**
 * Simplifies every ring and drops rings that collapse.
 *
 * @param array<int, array{role: string, points: array<int, array{0: float, 1: float}>}> $rings Rings.
 * @return array<int, array{role: string, points: array<int, array{0: float, 1: float}>}>
 */
function simplify_rings( array $rings, float $tolerance ): array {
	$result = array();
	foreach ( $rings as $ring ) {
		$points = round_points( douglas_peucker( $ring['points'], $tolerance ) );
		if ( count( $points ) >= 4 && abs( ring_area( $points ) ) > 0.0 ) {
			$result[] = array(
				'role'   => $ring['role'],
				'points' => $points,
			);
		}
	}
	return $result;
}

/**
 * Groups rings into GeoJSON polygons: each outer ring with the inner rings inside it.
 *
 * @param array<int, array{role: string, points: array<int, array{0: float, 1: float}>}> $rings Rings.
 * @return array<int, array<int, array<int, array{0: float, 1: float}>>>
 */
function rings_to_polygons( array $rings ): array {
	$polygons = array();
	$outers   = array();

	foreach ( $rings as $ring ) {
		if ( 'outer' === $ring['role'] ) {
			// GeoJSON (RFC 7946): outer rings counterclockwise.
			$points     = ring_area( $ring['points'] ) < 0 ? array_reverse( $ring['points'] ) : $ring['points'];
			$outers[]   = $points;
			$polygons[] = array( $points );
		}
	}
	foreach ( $rings as $ring ) {
		if ( 'inner' !== $ring['role'] ) {
			continue;
		}
		$points = ring_area( $ring['points'] ) > 0 ? array_reverse( $ring['points'] ) : $ring['points'];
		foreach ( $outers as $i => $outer ) {
			if ( point_in_ring( $points[0], $outer ) ) {
				$polygons[ $i ][] = $points;
				break;
			}
		}
	}

	return $polygons;
}

/**
 * Douglas-Peucker simplification with the tolerance in meters.
 *
 * @param array<int, array{0: float, 1: float}> $points Polyline.
 * @return array<int, array{0: float, 1: float}>
 */
function douglas_peucker( array $points, float $tolerance ): array {
	$count = count( $points );
	if ( $count < 3 ) {
		return $points;
	}

	$keep               = array_fill( 0, $count, false );
	$keep[0]            = true;
	$keep[ $count - 1 ] = true;
	$stack              = array( array( 0, $count - 1 ) );

	while ( array() !== $stack ) {
		[ $first, $last ] = array_pop( $stack );
		$max_distance     = 0.0;
		$index            = 0;
		for ( $i = $first + 1; $i < $last; $i++ ) {
			$distance = segment_distance( $points[ $i ], $points[ $first ], $points[ $last ] );
			if ( $distance > $max_distance ) {
				$max_distance = $distance;
				$index        = $i;
			}
		}
		if ( $max_distance > $tolerance ) {
			$keep[ $index ] = true;
			$stack[]        = array( $first, $index );
			$stack[]        = array( $index, $last );
		}
	}

	$result = array();
	foreach ( $points as $i => $point ) {
		if ( $keep[ $i ] ) {
			$result[] = $point;
		}
	}
	return $result;
}

/**
 * Distance in meters from a point to a segment.
 *
 * @param array{0: float, 1: float} $p Point.
 * @param array{0: float, 1: float} $a Segment start.
 * @param array{0: float, 1: float} $b Segment end.
 */
function segment_distance( array $p, array $a, array $b ): float {
	$px = ( $p[0] - $a[0] ) * M_PER_DEG_LON;
	$py = ( $p[1] - $a[1] ) * M_PER_DEG_LAT;
	$bx = ( $b[0] - $a[0] ) * M_PER_DEG_LON;
	$by = ( $b[1] - $a[1] ) * M_PER_DEG_LAT;

	$length = $bx * $bx + $by * $by;
	$t      = 0.0 === $length ? 0.0 : max( 0.0, min( 1.0, ( $px * $bx + $py * $by ) / $length ) );

	return hypot( $px - $t * $bx, $py - $t * $by );
}

/**
 * Signed ring area in square meters, positive for counterclockwise rings.
 *
 * @param array<int, array{0: float, 1: float}> $points Closed ring.
 */
function ring_area( array $points ): float {
	$sum   = 0.0;
	$count = count( $points );
	for ( $i = 0; $i < $count - 1; $i++ ) {
		$sum += $points[ $i ][0] * $points[ $i + 1 ][1] - $points[ $i + 1 ][0] * $points[ $i ][1];
	}
	return $sum / 2 * M_PER_DEG_LON * M_PER_DEG_LAT;
}

/**
 * Area of GeoJSON polygons in square meters.
 *
 * @param array<int, array<int, array<int, array{0: float, 1: float}>>> $polygons Polygons.
 */
function polygons_area( array $polygons ): float {
	$area = 0.0;
	foreach ( $polygons as $polygon ) {
		foreach ( $polygon as $ring ) {
			$area += ring_area( $ring );
		}
	}
	return $area;
}

/**
 * Clips a closed ring to a bounding box (Sutherland-Hodgman).
 *
 * @param array<int, array{0: float, 1: float}>         $points Closed ring.
 * @param array{0: float, 1: float, 2: float, 3: float} $bbox   Min lon, min lat, max lon, max lat.
 * @return array<int, array{0: float, 1: float}>
 */
function clip_ring( array $points, array $bbox ): array {
	array_pop( $points );
	$edges = array(
		array( 0, $bbox[0], true ),
		array( 0, $bbox[2], false ),
		array( 1, $bbox[1], true ),
		array( 1, $bbox[3], false ),
	);

	foreach ( $edges as [ $axis, $value, $is_min ] ) {
		$input  = $points;
		$points = array();
		$count  = count( $input );
		for ( $i = 0; $i < $count; $i++ ) {
			$current   = $input[ $i ];
			$previous  = $input[ ( $i + $count - 1 ) % $count ];
			$cur_in    = $is_min ? $current[ $axis ] >= $value : $current[ $axis ] <= $value;
			$prev_in   = $is_min ? $previous[ $axis ] >= $value : $previous[ $axis ] <= $value;
			if ( $cur_in !== $prev_in ) {
				$t        = ( $value - $previous[ $axis ] ) / ( $current[ $axis ] - $previous[ $axis ] );
				$crossing = array(
					$previous[0] + $t * ( $current[0] - $previous[0] ),
					$previous[1] + $t * ( $current[1] - $previous[1] ),
				);
				$crossing[ $axis ] = $value;
				$points[]          = $crossing;
			}
			if ( $cur_in ) {
				$points[] = $current;
			}
		}
		if ( array() === $points ) {
			return array();
		}
	}

	$points[] = $points[0];
	return $points;
}

/**
 * Bounding box of rings.
 *
 * @param array<int, array{role: string, points: array<int, array{0: float, 1: float}>}> $rings Rings.
 * @return array{0: float, 1: float, 2: float, 3: float}
 */
function rings_bbox( array $rings ): array {
	$bbox = array( INF, INF, -INF, -INF );
	foreach ( $rings as $ring ) {
		foreach ( $ring['points'] as $point ) {
			$bbox = array( min( $bbox[0], $point[0] ), min( $bbox[1], $point[1] ), max( $bbox[2], $point[0] ), max( $bbox[3], $point[1] ) );
		}
	}
	return $bbox;
}

/**
 * Even-odd point in ring test.
 *
 * @param array{0: float, 1: float}             $point Point.
 * @param array<int, array{0: float, 1: float}> $ring  Closed ring.
 */
function point_in_ring( array $point, array $ring ): bool {
	$inside = false;
	$count  = count( $ring );
	for ( $i = 0, $j = $count - 1; $i < $count; $j = $i++ ) {
		if ( ( $ring[ $i ][1] > $point[1] ) !== ( $ring[ $j ][1] > $point[1] )
			&& $point[0] < ( $ring[ $j ][0] - $ring[ $i ][0] ) * ( $point[1] - $ring[ $i ][1] ) / ( $ring[ $j ][1] - $ring[ $i ][1] ) + $ring[ $i ][0] ) {
			$inside = ! $inside;
		}
	}
	return $inside;
}

/**
 * Builds a latitude band index of polygon edges for fast point tests.
 *
 * @param array<int, array<int, array<int, array{0: float, 1: float}>>> $polygons GeoJSON polygons.
 * @return array{bbox: array{0: float, 1: float, 2: float, 3: float}, bands: array<int, array<int, array{0: float, 1: float, 2: float, 3: float}>>, step: float}
 */
function build_polygon_index( array $polygons ): array {
	$edges = array();
	$bbox  = array( INF, INF, -INF, -INF );
	foreach ( $polygons as $polygon ) {
		foreach ( $polygon as $ring ) {
			$count = count( $ring );
			for ( $i = 0; $i < $count - 1; $i++ ) {
				$edges[] = array( $ring[ $i ][0], $ring[ $i ][1], $ring[ $i + 1 ][0], $ring[ $i + 1 ][1] );
				$bbox    = array( min( $bbox[0], $ring[ $i ][0] ), min( $bbox[1], $ring[ $i ][1] ), max( $bbox[2], $ring[ $i ][0] ), max( $bbox[3], $ring[ $i ][1] ) );
			}
		}
	}

	$step  = max( ( $bbox[3] - $bbox[1] ) / 400, 1e-9 );
	$bands = array();
	foreach ( $edges as $edge ) {
		$from = (int) floor( ( min( $edge[1], $edge[3] ) - $bbox[1] ) / $step );
		$to   = (int) floor( ( max( $edge[1], $edge[3] ) - $bbox[1] ) / $step );
		for ( $band = $from; $band <= $to; $band++ ) {
			$bands[ $band ][] = $edge;
		}
	}

	return array(
		'bbox'  => $bbox,
		'bands' => $bands,
		'step'  => $step,
	);
}

/**
 * Point in polygon test against a band index.
 *
 * @param array{0: float, 1: float} $point Point.
 * @param array<string, mixed>      $index Index from build_polygon_index().
 */
function point_in_index( array $point, array $index ): bool {
	[ $x, $y ] = $point;
	$bbox      = $index['bbox'];
	if ( $x < $bbox[0] || $x > $bbox[2] || $y < $bbox[1] || $y > $bbox[3] ) {
		return false;
	}

	$inside = false;
	$band   = (int) floor( ( $y - $bbox[1] ) / $index['step'] );
	foreach ( $index['bands'][ $band ] ?? array() as [ $x1, $y1, $x2, $y2 ] ) {
		if ( ( $y1 > $y ) !== ( $y2 > $y ) && $x < ( $x2 - $x1 ) * ( $y - $y1 ) / ( $y2 - $y1 ) + $x1 ) {
			$inside = ! $inside;
		}
	}
	return $inside;
}

/**
 * Rounds coordinates and removes consecutive duplicates.
 *
 * @param array<int, array{0: float, 1: float}> $points Points.
 * @return array<int, array{0: float, 1: float}>
 */
function round_points( array $points ): array {
	$result = array();
	foreach ( $points as $point ) {
		$rounded = array( round( $point[0], COORD_DECIMALS ), round( $point[1], COORD_DECIMALS ) );
		if ( array() === $result || $result[ count( $result ) - 1 ] !== $rounded ) {
			$result[] = $rounded;
		}
	}
	return $result;
}

/* -------------------------------------------------------------------------
 * Output
 * ---------------------------------------------------------------------- */

/**
 * File level metadata stored as a GeoJSON foreign member.
 *
 * @return array<string, mixed>
 */
function metadata( string $exported_at, string $description, float $tolerance ): array {
	return array(
		'description'          => $description,
		'source'               => 'OpenStreetMap via the Overpass API, exported by bin/export-osm.php',
		'attribution'          => '© OpenStreetMap contributors',
		'license'              => 'ODbL-1.0',
		'license_url'          => 'https://opendatacommons.org/licenses/odbl/1-0/',
		'exported_at'          => $exported_at,
		'simplification_m'     => $tolerance,
		'coordinate_precision' => COORD_DECIMALS,
	);
}

/**
 * Writes a FeatureCollection with one feature per line, so diffs stay readable.
 *
 * @param array<string, mixed>             $metadata Foreign member "metadata".
 * @param array<int, array<string, mixed>> $features Features.
 */
function write_geojson( string $path, array $metadata, array $features ): void {
	$flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR;
	$lines = array();
	foreach ( $features as $feature ) {
		$lines[] = json_encode( $feature, $flags );
	}

	$content  = "{\n\"type\": \"FeatureCollection\",\n";
	$content .= '"metadata": ' . json_encode( $metadata, $flags ) . ",\n";
	$content .= "\"features\": [\n" . implode( ",\n", $lines ) . "\n]\n}\n";

	file_put_contents( $path, $content );
	log_line( sprintf( 'Wrote %s: %d features, %s KB', basename( $path ), count( $features ), number_format( strlen( $content ) / 1024, 1 ) ) );
}

/* -------------------------------------------------------------------------
 * Checks
 * ---------------------------------------------------------------------- */

/**
 * Checks the exported files and prints a report. Returns false if any check fails.
 */
function run_checks( string $output_dir ): bool {
	$ok = true;

	$check = function ( bool $passed, string $message ) use ( &$ok ): void {
		echo ( $passed ? 'PASS  ' : 'FAIL  ' ) . $message . "\n";
		$ok = $ok && $passed;
	};

	$districts = load_features( $output_dir . '/districts.geojson' );
	$water     = load_features( $output_dir . '/water.geojson' );
	$streets   = load_features( $output_dir . '/streets.geojson' );

	echo "\nDistricts\n";
	$check( 10 === count( $districts ), 'exactly 10 districts (' . count( $districts ) . ')' );
	$district_index = array();
	foreach ( $districts as $district ) {
		$p      = $district['properties'];
		$points = 0;
		foreach ( $district['geometry']['coordinates'] as $polygon ) {
			foreach ( $polygon as $ring ) {
				$points += count( $ring );
			}
		}
		$check(
			'' !== $p['name_uk'] && '' !== $p['name_en'] && $points >= 4,
			sprintf( '%-16s %s / %s, %d points, %.2f km2', $p['slug'], $p['name_uk'], $p['name_en'], $points, $p['area_km2'] )
		);
		$district_index[ $p['slug'] ] = build_polygon_index( $district['geometry']['coordinates'] );
	}

	// Sample a grid over the districts and count points covered by none or several.
	$bbox = array( INF, INF, -INF, -INF );
	foreach ( $district_index as $index ) {
		$bbox = array( min( $bbox[0], $index['bbox'][0] ), min( $bbox[1], $index['bbox'][1] ), max( $bbox[2], $index['bbox'][2] ), max( $bbox[3], $index['bbox'][3] ) );
	}
	$hits_at = function ( float $x, float $y ) use ( $district_index ): int {
		$hits = 0;
		foreach ( $district_index as $index ) {
			$hits += point_in_index( array( $x, $y ), $index ) ? 1 : 0;
		}
		return $hits;
	};
	$inside  = 0;
	$overlap = 0;
	$holes   = 0;
	for ( $x = $bbox[0]; $x <= $bbox[2]; $x += 0.0015 ) {
		for ( $y = $bbox[1]; $y <= $bbox[3]; $y += 0.001 ) {
			$hits     = $hits_at( $x, $y );
			$inside  += $hits > 0 ? 1 : 0;
			$overlap += $hits > 1 ? 1 : 0;
			// A point outside every district but surrounded by districts is a hole between them.
			if ( 0 === $hits && $hits_at( $x - 0.003, $y ) && $hits_at( $x + 0.003, $y ) && $hits_at( $x, $y - 0.002 ) && $hits_at( $x, $y + 0.002 ) ) {
				++$holes;
			}
		}
	}
	$check( $overlap / max( $inside, 1 ) < 0.001, sprintf( 'grid points in two districts at once: %d of %d', $overlap, $inside ) );
	$check( $holes / max( $inside, 1 ) < 0.001, sprintf( 'grid points in a gap between districts: %d of %d', $holes, $inside ) );

	echo "\nStreets per district\n";
	$per_district = array();
	$translit     = 0;
	$names        = array();
	foreach ( $streets as $street ) {
		$p                                = $street['properties'];
		$per_district[ $p['district'] ] = ( $per_district[ $p['district'] ] ?? 0 ) + 1;
		$translit                        += 'transliteration' === $p['name_en_source'] ? 1 : 0;
		$names[ $p['name_uk'] ][]         = $p['district'];
	}
	foreach ( array_keys( $district_index ) as $slug ) {
		$check( ( $per_district[ $slug ] ?? 0 ) > 0, sprintf( '%-16s %d streets', $slug, $per_district[ $slug ] ?? 0 ) );
	}
	echo sprintf( "      total %d street features, %d unique names, %d English names transliterated\n", count( $streets ), count( $names ), $translit );

	echo "\nKnown streets\n";
	$expect = array(
		'вулиця Хрещатик'        => array( 'shevchenkivskyi', 'pecherskyi' ),
		'Оболонський проспект'   => array( 'obolonskyi' ),
		'Андріївський узвіз'     => array( 'podilskyi' ),
		'Харківське шосе'        => array( 'darnytskyi', 'dniprovskyi' ),
		'Голосіївський проспект' => array( 'holosiivskyi' ),
	);
	foreach ( $expect as $name => $required ) {
		$found = $names[ $name ] ?? array();
		sort( $found );
		$check( array() === array_diff( $required, $found ), sprintf( '%s in %s (found: %s)', $name, implode( ', ', $required ), implode( ', ', $found ) ?: 'none' ) );
	}

	echo "\nWater\n";
	$water_index = array();
	foreach ( $water as $feature ) {
		$water_index[] = build_polygon_index( $feature['geometry']['coordinates'] );
	}
	$in_water = function ( float $lat, float $lon ) use ( $water_index ): bool {
		foreach ( $water_index as $index ) {
			if ( point_in_index( array( $lon, $lat ), $index ) ) {
				return true;
			}
		}
		return false;
	};
	$check( count( $water ) > 0, count( $water ) . ' water features' );
	$check( $in_water( 50.4400, 30.5660 ), 'Dnipro main channel between Metro and Paton bridges (50.4400, 30.5660) is water' );
	$check( $in_water( 50.4000, 30.5900 ), 'Dnipro near Pivdennyi bridge (50.4000, 30.5900) is water' );
	$check( ! $in_water( 50.4440, 30.5780 ), 'Hidropark island (50.4440, 30.5780) is not water' );
	$check( ! $in_water( 50.4501, 30.5234 ), 'Maidan Nezalezhnosti (50.4501, 30.5234) is not water' );
	$check( ! $in_water( 50.5000, 30.4980 ), 'Obolon residential area (50.5000, 30.4980) is not water' );

	echo "\nFiles\n";
	foreach ( array( 'districts', 'water', 'streets' ) as $name ) {
		echo sprintf( "      %-18s %8s KB\n", "$name.geojson", number_format( filesize( "$output_dir/$name.geojson" ) / 1024, 1 ) );
	}

	echo $ok ? "\nAll checks passed.\n" : "\nSome checks failed.\n";
	return $ok;
}

/**
 * Loads features from a GeoJSON file.
 *
 * @return array<int, array<string, mixed>>
 */
function load_features( string $path ): array {
	if ( ! is_file( $path ) ) {
		fail( "Missing $path, run the export first" );
	}
	return decode_json( (string) file_get_contents( $path ), $path )['features'] ?? array();
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * Decodes JSON or stops with an error.
 *
 * @return array<string, mixed>
 */
function decode_json( string $json, string $source ): array {
	$data = json_decode( $json, true );
	if ( ! is_array( $data ) ) {
		fail( "Invalid JSON from $source" );
	}
	return $data;
}

/**
 * Prints a progress line to stderr.
 */
function log_line( string $message ): void {
	fwrite( STDERR, $message . "\n" );
}

/**
 * Prints an error and exits.
 */
function fail( string $message ): never {
	fwrite( STDERR, "Error: $message\n" );
	exit( 1 );
}
