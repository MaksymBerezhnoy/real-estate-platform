<?php
/**
 * Tests for the property meta keys.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Tests\Unit\Meta;

use MB\RealEstate\Meta\MetaKey;
use PHPUnit\Framework\TestCase;

/**
 * Meta key tests.
 */
final class MetaKeyTest extends TestCase {

	/**
	 * Every key carries the plugin prefix and is unique.
	 *
	 * @return void
	 */
	public function test_keys_are_prefixed_and_unique(): void {
		$values = array_map( static fn ( MetaKey $key ): string => $key->value, MetaKey::cases() );

		foreach ( $values as $value ) {
			$this->assertStringStartsWith( 'mbre_', $value );
		}

		$this->assertSame( $values, array_unique( $values ) );
	}

	/**
	 * The sanitizer casts to the meta type and clamps to the allowed range.
	 *
	 * @dataProvider data_sanitize
	 *
	 * @param MetaKey   $key      Meta key.
	 * @param mixed     $raw      Raw value.
	 * @param int|float $expected Sanitized value.
	 * @return void
	 */
	public function test_sanitize_casts_and_clamps( MetaKey $key, mixed $raw, int|float $expected ): void {
		$this->assertSame( $expected, $key->sanitize( $raw ) );
	}

	/**
	 * Data provider for the sanitizer.
	 *
	 * @return array<string, array{0: MetaKey, 1: mixed, 2: int|float}>
	 */
	public static function data_sanitize(): array {
		return array(
			'integer stays an integer'                => array( MetaKey::Price, 118000, 118000 ),
			'numeric string becomes an integer'       => array( MetaKey::Rooms, '3', 3 ),
			'fraction is rounded for an integer'      => array( MetaKey::Price, 118000.6, 118001 ),
			'text becomes zero'                       => array( MetaKey::Price, 'abc', 0 ),
			'null becomes zero'                       => array( MetaKey::Floor, null, 0 ),
			'negative price is raised to zero'        => array( MetaKey::Price, -5, 0 ),
			'negative rooms are raised to zero'       => array( MetaKey::Rooms, '-3', 0 ),
			'too many floors are capped'              => array( MetaKey::FloorsTotal, 5000, 200 ),
			'area keeps its fraction'                 => array( MetaKey::Area, '62.5', 62.5 ),
			'whole area is still a float'             => array( MetaKey::Area, 62, 62.0 ),
			'text area becomes zero'                  => array( MetaKey::Area, 'abc', 0.0 ),
			'latitude above the pole is capped'       => array( MetaKey::Latitude, 500, 90.0 ),
			'latitude below the pole is capped'       => array( MetaKey::Latitude, -500, -90.0 ),
			'longitude is capped at the antimeridian' => array( MetaKey::Longitude, -500, -180.0 ),
			'valid coordinate is unchanged'           => array( MetaKey::Longitude, 30.5155, 30.5155 ),
		);
	}

	/**
	 * Every key has a range whose lower bound is not above the upper bound and matches its type.
	 *
	 * @return void
	 */
	public function test_range_matches_type(): void {
		foreach ( MetaKey::cases() as $key ) {
			list( $min, $max ) = $key->range();

			$this->assertLessThanOrEqual( $max, $min, $key->value );

			if ( 'integer' === $key->type() ) {
				$this->assertIsInt( $min, $key->value );
				$this->assertIsInt( $max, $key->value );
			} else {
				$this->assertSame( 'number', $key->type(), $key->value );
				$this->assertIsFloat( $min, $key->value );
				$this->assertIsFloat( $max, $key->value );
			}
		}
	}
}
