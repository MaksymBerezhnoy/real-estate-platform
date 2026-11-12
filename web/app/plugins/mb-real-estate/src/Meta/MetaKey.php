<?php
/**
 * Property meta keys.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Meta;

/**
 * Meta keys of a property. The values are stored in the database: do not rename.
 */
enum MetaKey: string {

	case Price         = 'mbre_price';
	case PreviousPrice = 'mbre_previous_price';
	case Area          = 'mbre_area';
	case Rooms         = 'mbre_rooms';
	case Floor         = 'mbre_floor';
	case FloorsTotal   = 'mbre_floors_total';
	case Latitude      = 'mbre_latitude';
	case Longitude     = 'mbre_longitude';

	/**
	 * Returns the meta type as understood by register_post_meta().
	 *
	 * @return string
	 */
	public function type(): string {
		return match ( $this ) {
			self::Area, self::Latitude, self::Longitude => 'number',
			default => 'integer',
		};
	}

	/**
	 * Returns a human-readable description for the REST schema.
	 *
	 * @return string
	 */
	public function description(): string {
		return match ( $this ) {
			self::Price         => __( 'Asking price in US dollars.', 'mb-real-estate' ),
			self::PreviousPrice => __( 'Price before the last reduction in US dollars, 0 if there was none.', 'mb-real-estate' ),
			self::Area          => __( 'Total area in square meters.', 'mb-real-estate' ),
			self::Rooms         => __( 'Number of rooms.', 'mb-real-estate' ),
			self::Floor         => __( 'Floor of the apartment, 0 for a house.', 'mb-real-estate' ),
			self::FloorsTotal   => __( 'Number of floors in the building.', 'mb-real-estate' ),
			self::Latitude      => __( 'Latitude in decimal degrees.', 'mb-real-estate' ),
			self::Longitude     => __( 'Longitude in decimal degrees.', 'mb-real-estate' ),
		};
	}

	/**
	 * Returns the inclusive range of allowed values.
	 *
	 * @return array{0: int|float, 1: int|float}
	 */
	public function range(): array {
		return match ( $this ) {
			self::Price, self::PreviousPrice => array( 0, 1_000_000_000 ),
			self::Area                       => array( 0.0, 100_000.0 ),
			self::Rooms                      => array( 0, 100 ),
			self::Floor, self::FloorsTotal   => array( 0, 200 ),
			self::Latitude                   => array( -90.0, 90.0 ),
			self::Longitude                  => array( -180.0, 180.0 ),
		};
	}

	/**
	 * Casts a raw value to the meta type and clamps it to the allowed range.
	 *
	 * @param mixed $value Raw value.
	 * @return int|float
	 */
	public function sanitize( mixed $value ): int|float {
		list( $min, $max ) = $this->range();

		$number  = is_numeric( $value ) ? (float) $value : 0.0;
		$clamped = max( $min, min( $max, $number ) );

		return 'integer' === $this->type() ? (int) round( $clamped ) : (float) $clamped;
	}
}
