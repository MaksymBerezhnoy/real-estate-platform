<?php
/**
 * Tests for the property meta registration.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Tests\Integration\Meta;

use MB\RealEstate\Meta\MetaKey;
use MB\RealEstate\Meta\PropertyMeta;
use MB\RealEstate\PostTypes\Property;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Property meta registration tests.
 */
final class PropertyMetaTest extends WP_UnitTestCase {

	/**
	 * Registers the meta again: the test library unregisters all meta keys after every test.
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		( new PropertyMeta() )->register_meta();
	}

	/**
	 * Every key is registered for the property post type.
	 *
	 * @return void
	 */
	public function test_every_key_is_registered_for_properties(): void {
		foreach ( MetaKey::cases() as $key ) {
			$this->assertTrue( registered_meta_key_exists( 'post', $key->value, Property::POST_TYPE ), $key->value );
		}
	}

	/**
	 * A new property reads typed defaults instead of empty strings.
	 *
	 * @return void
	 */
	public function test_new_property_reads_typed_defaults(): void {
		$property_id = $this->create_property();

		$this->assertSame( 0, get_post_meta( $property_id, MetaKey::Price->value, true ) );
		$this->assertSame( 0.0, get_post_meta( $property_id, MetaKey::Area->value, true ) );
	}

	/**
	 * Values written outside the REST API are sanitized on save.
	 *
	 * @return void
	 */
	public function test_direct_update_is_sanitized(): void {
		$property_id = $this->create_property();

		update_post_meta( $property_id, MetaKey::Latitude->value, '500' );
		update_post_meta( $property_id, MetaKey::Rooms->value, '-3' );

		$this->assertEquals( 90.0, get_post_meta( $property_id, MetaKey::Latitude->value, true ) );
		$this->assertEquals( 0, get_post_meta( $property_id, MetaKey::Rooms->value, true ) );
	}

	/**
	 * An editor saves meta through the REST API and reads it back with its types.
	 *
	 * @return void
	 */
	public function test_rest_round_trip_keeps_types(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$property_id = $this->create_property();

		$response = $this->update_meta_via_rest(
			$property_id,
			array(
				MetaKey::Price->value    => 118000,
				MetaKey::Area->value     => 62.5,
				MetaKey::Latitude->value => 50.4657,
			)
		);

		$this->assertSame( 200, $response->get_status() );

		$meta = $response->get_data()['meta'];

		$this->assertSame( 118000, $meta[ MetaKey::Price->value ] );
		$this->assertSame( 62.5, $meta[ MetaKey::Area->value ] );
		$this->assertSame( 50.4657, $meta[ MetaKey::Latitude->value ] );
	}

	/**
	 * The REST API rejects a value outside the allowed range instead of clamping it.
	 *
	 * @return void
	 */
	public function test_rest_rejects_out_of_range_value(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		$property_id = $this->create_property();

		$response = $this->update_meta_via_rest( $property_id, array( MetaKey::Latitude->value => 500 ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertEquals( 0.0, get_post_meta( $property_id, MetaKey::Latitude->value, true ) );
	}

	/**
	 * A user who cannot edit the property cannot change its meta.
	 *
	 * @return void
	 */
	public function test_rest_denies_user_without_edit_rights(): void {
		$property_id = $this->create_property();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$response = $this->update_meta_via_rest( $property_id, array( MetaKey::Price->value => 1 ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 0, get_post_meta( $property_id, MetaKey::Price->value, true ) );
	}

	/**
	 * Creates a published property.
	 *
	 * @return int
	 */
	private function create_property(): int {
		return self::factory()->post->create(
			array(
				'post_type'   => Property::POST_TYPE,
				'post_status' => 'publish',
			)
		);
	}

	/**
	 * Sends a meta update for a property through the REST API.
	 *
	 * @param int                  $property_id Property ID.
	 * @param array<string, mixed> $meta        Meta values by key.
	 * @return \WP_REST_Response
	 */
	private function update_meta_via_rest( int $property_id, array $meta ): \WP_REST_Response {
		$request = new WP_REST_Request( 'POST', '/wp/v2/properties/' . $property_id );
		$request->set_body_params( array( 'meta' => $meta ) );

		return rest_get_server()->dispatch( $request );
	}
}
