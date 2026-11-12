<?php
/**
 * Checks the property description reference set in data/descriptions/.
 *
 * Validates the structure and tags of every record, the length and content of
 * the Ukrainian and English texts, looks for near duplicates and prints the
 * coverage table the generator relies on.
 *
 * Usage:
 *   php bin/check-descriptions.php [--file=PATH] [--phrases]
 *
 *   --file=PATH  Check another file instead of data/descriptions/descriptions.json.
 *   --phrases    Also list the most repeated four-word phrases, to spot clichés.
 *
 * Exits with 1 when any check fails. Requires PHP 8.1+ with mbstring and json.
 */

declare(strict_types=1);

const MIN_RECORDS     = 300;
const MAX_RECORDS     = 500;
const MIN_WORDS       = 60;
const MAX_WORDS       = 160;
const MIN_COVERAGE    = 10;
const OPENING_WORDS   = 4;
const SHINGLE_WORDS   = 5;
const MAX_SIMILARITY  = 0.2;
const MAX_LATIN_IN_UK = 0;

const RECORD_KEYS = array( 'id', 'type', 'rooms', 'sizes', 'condition', 'floor', 'storeys', 'features', 'uk', 'en' );
const FLOORS      = array( 'low', 'high', 'top' );

// Money, units, contacts, links and location phrases the generator owns.
const BANNED_PATTERNS = array(
	'digit'            => '/\d/u',
	'currency'         => '/грн|гривн|\$|€|\bUSD\b|\bEUR\b|\bUAH\b|dollar|долар|hryvni|\beuros?\b|\bєвро\b/iu',
	'area unit'        => '/м²|м2|\bкв\.\s*м|квадратн\w* метр|\bsq\.?\s*(m|ft)\b|square met|сот(ок|ки|ка)\b|гектар|hectare/iu',
	'link or contact'  => '/https?:|www\.|\.(com|ua|net|org)\b|@|viber|telegram|instagram|facebook|вайбер|телеграм/iu',
	'city centre'      => '/центр\w* міста|історичн\w* центр|city cent(re|er)|historic cent(re|er)|downtown/iu',
	'river or bank'    => '/дніпр|dnipro|dnieper|лівобереж|правобереж|лівому березі|правому березі|left bank|right bank/iu',
	'mortgage program' => '/єоселя|yeoselia|eoselia/iu',
);

// Kyiv districts, neighbourhoods and nearby towns. Ukrainian stems are case sensitive,
// because some of them are also common words in lower case (поділ, майдан, липкий).
const PLACE_STEMS_UK = array( 'Дарниц', 'Деснян', 'Дніпровськ', 'Голосі', 'Оболон', 'Печерськ', 'Поділ', 'Подільськ', 'Шевченків', 'Солом', 'Святошин', 'Троєщин', 'Осокорк', 'Позняк', 'Харківськ', 'Виноградар', 'Борщагів', 'Теремк', 'Лук’янів', "Лук'янів", 'Липк', 'Русанів', 'Березняк', 'Нивк', 'Академміст', 'Відрадн', 'Соцміст', 'Хрещатик', 'Майдан', 'Лавр', 'Конча', 'Пуща', 'Бортнич', 'Біличі', 'Ірпін', 'Буч', 'Бровар', 'Вишгород' );
const PLACE_STEMS_EN = array( 'Darnyts', 'Desnian', 'Dniprovsk', 'Holosii', 'Obolon', 'Pechersk', 'Podil', 'Shevchenk', 'Solomian', 'Sviatoshyn', 'Troieshchyn', 'Osokork', 'Pozniak', 'Kharkivsk', 'Vynohradar', 'Borshchahiv', 'Teremk', 'Lukianiv', 'Lypky', 'Rusaniv', 'Bereznia', 'Nyvky', 'Akademmist', 'Vidradn', 'Khreshchatyk', 'Maidan', 'Lavra', 'Koncha', 'Pushcha', 'Bortnych', 'Irpin', 'Bucha', 'Brovary', 'Vyshhorod' );

$root_dir = dirname( __DIR__ );
$options  = getopt( '', array( 'file:', 'phrases' ) );
$path     = isset( $options['file'] ) ? (string) $options['file'] : $root_dir . '/data/descriptions/descriptions.json';

exit( run_checks( $path, isset( $options['phrases'] ) ) ? 0 : 1 );

/**
 * Runs every check and prints a report. Returns false if any check fails.
 */
function run_checks( string $path, bool $show_phrases ): bool {
	$ok = true;

	$check = function ( bool $passed, string $message ) use ( &$ok ): void {
		echo ( $passed ? 'PASS  ' : 'FAIL  ' ) . $message . "\n";
		$ok = $ok && $passed;
	};

	if ( ! is_file( $path ) ) {
		fail( "Missing $path" );
	}
	$data     = decode_json( (string) file_get_contents( $path ), $path );
	$metadata = $data['metadata'] ?? array();
	$records  = $data['descriptions'] ?? array();

	echo "File\n";
	$check( ! empty( $metadata['generated'] ) && '' !== (string) ( $metadata['generated_note'] ?? '' ), 'metadata marks the texts as generated' );
	$check( isset( $metadata['size_classes'], $metadata['combinations'], $metadata['conditions'], $metadata['features'] ), 'metadata defines size classes, combinations, conditions and features' );
	$check( count( $records ) >= MIN_RECORDS && count( $records ) <= MAX_RECORDS, sprintf( 'between %d and %d records (%d)', MIN_RECORDS, MAX_RECORDS, count( $records ) ) );

	echo "\nRecords\n";
	$errors = array();
	$ids    = array();
	foreach ( $records as $index => $record ) {
		$id = is_array( $record ) && is_string( $record['id'] ?? null ) ? $record['id'] : "#$index";
		foreach ( record_errors( $record, $metadata ) as $error ) {
			$errors[] = "$id: $error";
		}
		$ids[] = $id;
	}
	$check( array() === $errors, 'structure, tags, length and content (' . count( $errors ) . ' problems)' );
	print_list( $errors );

	$duplicate_ids = array_keys( array_filter( array_count_values( $ids ), fn( $count ) => $count > 1 ) );
	$check( array() === $duplicate_ids, 'unique ids' );
	print_list( $duplicate_ids );

	$valid = array_values( array_filter( $records, fn( $record ) => is_array( $record ) && array() === record_errors( $record, $metadata ) ) );

	echo "\nDuplicates\n";
	foreach ( array( 'uk', 'en' ) as $lang ) {
		$texts     = array();
		$openings  = array();
		$shingles  = array();
		$same_text = array();
		foreach ( $valid as $record ) {
			$words = words( $record[ $lang ] );
			$key   = implode( ' ', $words );
			if ( isset( $texts[ $key ] ) ) {
				$same_text[] = $texts[ $key ] . ' = ' . $record['id'];
			}
			$texts[ $key ] = $record['id'];

			$openings[ implode( ' ', array_slice( $words, 0, OPENING_WORDS ) ) ][] = $record['id'];
			$shingles[ $record['id'] ] = shingles( $words );
		}
		$check( array() === $same_text, "$lang: no identical texts" );
		print_list( $same_text );

		$same_opening = array();
		foreach ( $openings as $opening => $opening_ids ) {
			if ( count( $opening_ids ) > 1 ) {
				$same_opening[] = '"' . $opening . '": ' . implode( ', ', $opening_ids );
			}
		}
		$check( array() === $same_opening, sprintf( '%s: no two texts share the first %d words', $lang, OPENING_WORDS ) );
		print_list( $same_opening );

		$similar  = array();
		$max      = 0.0;
		$max_pair = '';
		$keys     = array_keys( $shingles );
		$total    = count( $keys );
		for ( $i = 0; $i < $total; $i++ ) {
			for ( $j = $i + 1; $j < $total; $j++ ) {
				$score = jaccard( $shingles[ $keys[ $i ] ], $shingles[ $keys[ $j ] ] );
				if ( $score > $max ) {
					$max      = $score;
					$max_pair = $keys[ $i ] . ' / ' . $keys[ $j ];
				}
				if ( $score > MAX_SIMILARITY ) {
					$similar[] = sprintf( '%s / %s: %.2f', $keys[ $i ], $keys[ $j ], $score );
				}
			}
		}
		$check( array() === $similar, sprintf( '%s: no pair shares more than %d%% of %d-word phrases (max %.2f, %s)', $lang, MAX_SIMILARITY * 100, SHINGLE_WORDS, $max, $max_pair ) );
		print_list( $similar );
	}

	echo "\nDistribution\n";
	print_distribution( $valid );

	echo "\nCoverage: texts per rooms and size class (* = combination the generator produces)\n";
	foreach ( $metadata['combinations'] ?? array() as $type => $combinations ) {
		$short = coverage_table( $valid, $type, $metadata['size_classes'][ $type ] ?? array(), $combinations );
		$check( array() === $short, sprintf( '%s: every combination has at least %d texts', $type, MIN_COVERAGE ) );
		print_list( $short );
	}

	if ( $show_phrases ) {
		echo "\nMost repeated four-word phrases\n";
		print_phrases( $valid );
	}

	echo "\n" . ( $ok ? 'All checks passed' : 'Some checks failed' ) . "\n";
	return $ok;
}

/**
 * Returns the problems found in one record.
 *
 * @param mixed                $record   Record as decoded from JSON.
 * @param array<string, mixed> $metadata File metadata.
 * @return array<int, string>
 */
function record_errors( mixed $record, array $metadata ): array {
	if ( ! is_array( $record ) ) {
		return array( 'not an object' );
	}
	if ( array_keys( $record ) !== RECORD_KEYS ) {
		return array( 'fields must be exactly ' . implode( ', ', RECORD_KEYS ) . ' in this order' );
	}

	$errors = array();
	$type   = $record['type'];
	if ( ! in_array( $type, array( 'apartment', 'house' ), true ) ) {
		return array( 'type must be apartment or house' );
	}

	$prefix = 'apartment' === $type ? 'apt' : 'house';
	if ( ! is_string( $record['id'] ) || ! preg_match( '/^' . $prefix . '-\d{3}$/', $record['id'] ) ) {
		$errors[] = "id must look like $prefix-001";
	}

	$room_limits = $metadata['room_limits'][ $type ] ?? array( 1, 10 );
	if ( ! is_int_range( $record['rooms'], $room_limits[0], $room_limits[1] ) ) {
		$errors[] = sprintf( 'rooms must be [min, max] within %d-%d', $room_limits[0], $room_limits[1] );
	}

	$size_classes = array_keys( $metadata['size_classes'][ $type ] ?? array() );
	if ( ! is_array( $record['sizes'] ) || array() === $record['sizes'] || array_diff( $record['sizes'], $size_classes ) || ! is_sorted_like( $record['sizes'], $size_classes ) ) {
		$errors[] = 'sizes must be a non-empty ordered subset of ' . implode( ', ', $size_classes );
	}

	if ( ! array_key_exists( (string) $record['condition'], $metadata['conditions'] ?? array() ) ) {
		$errors[] = 'unknown condition ' . to_json( $record['condition'] );
	}

	$features = array_keys( $metadata['features'] ?? array() );
	if ( ! is_array( $record['features'] ) || array_diff( $record['features'], $features ) || ! is_sorted_like( $record['features'], $features ) ) {
		$errors[] = 'features must be an ordered subset of the vocabulary';
	}

	if ( 'apartment' === $type ) {
		if ( null !== $record['floor'] && ! in_array( $record['floor'], FLOORS, true ) ) {
			$errors[] = 'floor must be null, low, high or top';
		}
		if ( null !== $record['storeys'] ) {
			$errors[] = 'storeys must be null for an apartment';
		}
	} else {
		if ( null !== $record['floor'] ) {
			$errors[] = 'floor must be null for a house';
		}
		if ( ! is_int_range( $record['storeys'], 1, 3 ) ) {
			$errors[] = 'storeys must be [min, max] within 1-3';
		}
	}

	foreach ( array( 'uk', 'en' ) as $lang ) {
		if ( ! is_string( $record[ $lang ] ) ) {
			$errors[] = "$lang must be a string";
			continue;
		}
		foreach ( text_errors( $record[ $lang ], $lang ) as $error ) {
			$errors[] = "$lang: $error";
		}
	}

	return $errors;
}

/**
 * Returns the problems found in one text.
 *
 * @return array<int, string>
 */
function text_errors( string $text, string $lang ): array {
	$errors = array();

	$count = count( preg_split( '/\s+/u', trim( $text ) ) );
	if ( $count < MIN_WORDS || $count > MAX_WORDS ) {
		$errors[] = sprintf( '%d words, expected %d-%d', $count, MIN_WORDS, MAX_WORDS );
	}
	if ( $text !== trim( $text ) || preg_match( '/\s{2,}|[\r\n\t]/u', $text ) ) {
		$errors[] = 'extra whitespace or line breaks';
	}

	foreach ( BANNED_PATTERNS as $name => $pattern ) {
		if ( preg_match( $pattern, $text, $match ) ) {
			$errors[] = "$name \"{$match[0]}\"";
		}
	}

	$stems = 'uk' === $lang ? PLACE_STEMS_UK : PLACE_STEMS_EN;
	$flags = 'u';
	foreach ( $stems as $stem ) {
		if ( preg_match( '/(?<!\p{L})' . preg_quote( $stem, '/' ) . '/' . $flags, $text ) ) {
			$errors[] = "place name \"$stem\"";
		}
	}

	if ( 'en' === $lang && preg_match( '/\p{Cyrillic}+/u', $text, $match ) ) {
		$errors[] = "Cyrillic \"{$match[0]}\"";
	}
	if ( 'uk' === $lang ) {
		preg_match_all( '/(?<!\p{L})[A-Za-z]{2,}(?!\p{L})/u', $text, $matches );
		if ( count( $matches[0] ) > MAX_LATIN_IN_UK ) {
			$errors[] = 'Latin words "' . implode( '", "', $matches[0] ) . '"';
		}
		if ( preg_match( '/\p{L}*[ыэъё]\p{L}*/iu', $text, $match ) ) {
			$errors[] = "Russian letters \"{$match[0]}\"";
		}
	}

	return $errors;
}

/* -------------------------------------------------------------------------
 * Report
 * ---------------------------------------------------------------------- */

/**
 * Prints counts by type, condition, floor claim, length and feature.
 *
 * @param array<int, array<string, mixed>> $records Valid records.
 */
function print_distribution( array $records ): void {
	$total = max( 1, count( $records ) );
	$by    = function ( callable $key ) use ( $records ): array {
		$counts = array();
		foreach ( $records as $record ) {
			foreach ( (array) $key( $record ) as $value ) {
				$counts[ $value ] = ( $counts[ $value ] ?? 0 ) + 1;
			}
		}
		return $counts;
	};

	foreach ( $by( fn( $r ) => $r['type'] ) as $type => $count ) {
		printf( "      %-10s %4d  %3d%%\n", $type, $count, round( 100 * $count / $total ) );
	}

	echo "\n      Condition    apartment  house\n";
	$conditions = array();
	foreach ( $records as $record ) {
		$conditions[ $record['condition'] ][ $record['type'] ] = ( $conditions[ $record['condition'] ][ $record['type'] ] ?? 0 ) + 1;
	}
	ksort( $conditions );
	foreach ( $conditions as $condition => $counts ) {
		printf( "      %-12s %9d  %5d\n", $condition, $counts['apartment'] ?? 0, $counts['house'] ?? 0 );
	}

	echo "\n      Words        uk     en\n";
	$buckets = array(
		'60-85'   => array( 60, 85 ),
		'86-120'  => array( 86, 120 ),
		'121-160' => array( 121, 160 ),
	);
	foreach ( $buckets as $label => $range ) {
		$counts = array();
		foreach ( array( 'uk', 'en' ) as $lang ) {
			$counts[ $lang ] = count(
				array_filter(
					$records,
					function ( $record ) use ( $lang, $range ) {
						$count = count( preg_split( '/\s+/u', trim( $record[ $lang ] ) ) );
						return $count >= $range[0] && $count <= $range[1];
					}
				)
			);
		}
		printf( "      %-9s %5d  %5d\n", $label, $counts['uk'], $counts['en'] );
	}

	$floors = $by( fn( $r ) => 'apartment' === $r['type'] ? ( $r['floor'] ?? 'no claim' ) : array() );
	echo "\n      Apartment floor claims: " . implode( ', ', array_map( fn( $k, $v ) => "$k $v", array_keys( $floors ), $floors ) ) . "\n";

	$features = $by( fn( $r ) => $r['features'] );
	arsort( $features );
	echo "\n      Features\n";
	foreach ( $features as $feature => $count ) {
		printf( "      %-20s %4d\n", $feature, $count );
	}
}

/**
 * Prints the rooms by size table for a type and returns the combinations below the minimum.
 *
 * @param array<int, array<string, mixed>> $records      Valid records.
 * @param array<string, mixed>             $size_classes Size classes of the type.
 * @param array<string, array<int, string>> $combinations Size classes the generator uses per room count.
 * @return array<int, string>
 */
function coverage_table( array $records, string $type, array $size_classes, array $combinations ): array {
	$sizes = array_keys( $size_classes );
	$short = array();

	echo "\n      $type\n      rooms " . implode( '', array_map( fn( $s ) => str_pad( $s, 7, ' ', STR_PAD_LEFT ), $sizes ) ) . "\n";
	foreach ( $combinations as $rooms => $required_sizes ) {
		$row = sprintf( '      %-5s ', $rooms );
		foreach ( $sizes as $size ) {
			$count = count(
				array_filter(
					$records,
					fn( $r ) => $r['type'] === $type && $r['rooms'][0] <= (int) $rooms && $r['rooms'][1] >= (int) $rooms && in_array( $size, $r['sizes'], true )
				)
			);
			$required = in_array( $size, $required_sizes, true );
			$row     .= str_pad( $count . ( $required ? '*' : ' ' ), 7, ' ', STR_PAD_LEFT );
			if ( $required && $count < MIN_COVERAGE ) {
				$short[] = "$type, $rooms rooms, size $size: $count texts";
			}
		}
		echo $row . "\n";
	}

	return $short;
}

/**
 * Prints the four-word phrases repeated in the most texts of each language.
 *
 * @param array<int, array<string, mixed>> $records Valid records.
 */
function print_phrases( array $records ): void {
	foreach ( array( 'uk', 'en' ) as $lang ) {
		$counts = array();
		foreach ( $records as $record ) {
			$words = words( $record[ $lang ] );
			$seen  = array();
			for ( $i = 0; $i + 4 <= count( $words ); $i++ ) {
				$seen[ implode( ' ', array_slice( $words, $i, 4 ) ) ] = true;
			}
			foreach ( array_keys( $seen ) as $phrase ) {
				$counts[ $phrase ] = ( $counts[ $phrase ] ?? 0 ) + 1;
			}
		}
		arsort( $counts );
		echo "      $lang\n";
		foreach ( array_slice( $counts, 0, 25, true ) as $phrase => $count ) {
			printf( "      %4d  %s\n", $count, $phrase );
		}
	}
}

/**
 * Prints indented report lines.
 *
 * @param array<int, string> $lines Lines.
 */
function print_list( array $lines ): void {
	foreach ( $lines as $line ) {
		echo "      $line\n";
	}
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

/**
 * Splits a text into lower-case words without punctuation.
 *
 * @return array<int, string>
 */
function words( string $text ): array {
	$text = mb_strtolower( (string) preg_replace( '/[^\p{L}\p{N}\s\'’-]+/u', ' ', $text ) );
	return array_values( array_filter( preg_split( '/\s+/u', $text ), fn( $word ) => '' !== trim( $word, '-' ) ) );
}

/**
 * Builds the set of word shingles of a text.
 *
 * @param array<int, string> $words Words.
 * @return array<string, bool>
 */
function shingles( array $words ): array {
	$set = array();
	for ( $i = 0; $i + SHINGLE_WORDS <= count( $words ); $i++ ) {
		$set[ implode( ' ', array_slice( $words, $i, SHINGLE_WORDS ) ) ] = true;
	}
	return $set;
}

/**
 * Jaccard similarity of two shingle sets.
 *
 * @param array<string, bool> $a First set.
 * @param array<string, bool> $b Second set.
 */
function jaccard( array $a, array $b ): float {
	$common = count( array_intersect_key( $a, $b ) );
	$union  = count( $a ) + count( $b ) - $common;
	return $union > 0 ? $common / $union : 0.0;
}

/**
 * Checks for an [min, max] pair of integers within limits.
 */
function is_int_range( mixed $value, int $low, int $high ): bool {
	return is_array( $value ) && array_is_list( $value ) && 2 === count( $value )
		&& is_int( $value[0] ) && is_int( $value[1] )
		&& $value[0] >= $low && $value[0] <= $value[1] && $value[1] <= $high;
}

/**
 * Checks that values are unique and follow the order of the reference list.
 *
 * @param array<int, mixed>  $values    Values.
 * @param array<int, string> $reference Reference order.
 */
function is_sorted_like( array $values, array $reference ): bool {
	$positions = array_map( fn( $value ) => array_search( $value, $reference, true ), $values );
	$sorted    = $positions;
	sort( $sorted );
	return $positions === $sorted && count( array_unique( $values ) ) === count( $values );
}

/**
 * Encodes a value for an error message.
 */
function to_json( mixed $value ): string {
	return (string) json_encode( $value, JSON_UNESCAPED_UNICODE );
}

/**
 * Decodes JSON or stops with an error.
 *
 * @return array<string, mixed>
 */
function decode_json( string $json, string $source ): array {
	$data = json_decode( $json, true );
	if ( ! is_array( $data ) ) {
		fail( "Invalid JSON in $source: " . json_last_error_msg() );
	}
	return $data;
}

/**
 * Prints an error and exits.
 */
function fail( string $message ): never {
	fwrite( STDERR, "Error: $message\n" );
	exit( 1 );
}
