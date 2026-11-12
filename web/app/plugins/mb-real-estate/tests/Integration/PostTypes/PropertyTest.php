<?php
/**
 * Tests for the property post type and its taxonomies.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Tests\Integration\PostTypes;

use MB\RealEstate\PostTypes\Property;
use MB\RealEstate\Taxonomies\Agency;
use MB\RealEstate\Taxonomies\Location;
use MB\RealEstate\Taxonomies\PropertyType;
use WP_UnitTestCase;

/**
 * Property post type tests.
 */
final class PropertyTest extends WP_UnitTestCase {

	/**
	 * The post type is public and exposed in the REST API under its own base.
	 *
	 * @return void
	 */
	public function test_post_type_is_public_and_in_rest(): void {
		$post_type = get_post_type_object( Property::POST_TYPE );

		$this->assertNotNull( $post_type );
		$this->assertTrue( $post_type->public );
		$this->assertTrue( $post_type->show_in_rest );
		$this->assertSame( 'properties', $post_type->rest_base );
		$this->assertSame( 'properties', $post_type->has_archive );
	}

	/**
	 * Meta is only saved through the REST API when the post type supports custom fields.
	 *
	 * @return void
	 */
	public function test_post_type_supports_custom_fields(): void {
		$this->assertTrue( post_type_supports( Property::POST_TYPE, 'custom-fields' ) );
	}

	/**
	 * The three taxonomies are attached to the post type and exposed in the REST API.
	 *
	 * @return void
	 */
	public function test_taxonomies_are_attached_and_in_rest(): void {
		$expected = array( PropertyType::TAXONOMY, Location::TAXONOMY, Agency::TAXONOMY );
		$attached = get_object_taxonomies( Property::POST_TYPE );

		foreach ( $expected as $name ) {
			$this->assertContains( $name, $attached );

			$taxonomy = get_taxonomy( $name );

			$this->assertNotFalse( $taxonomy );
			$this->assertTrue( $taxonomy->show_in_rest, $name );
		}
	}

	/**
	 * A district can be nested under a city.
	 *
	 * @return void
	 */
	public function test_location_is_hierarchical(): void {
		$city     = self::factory()->term->create( array( 'taxonomy' => Location::TAXONOMY ) );
		$district = self::factory()->term->create(
			array(
				'taxonomy' => Location::TAXONOMY,
				'parent'   => $city,
			)
		);

		$this->assertSame( $city, get_term( $district, Location::TAXONOMY )->parent );
	}
}
