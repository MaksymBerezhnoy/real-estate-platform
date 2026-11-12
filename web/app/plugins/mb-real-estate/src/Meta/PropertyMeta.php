<?php
/**
 * Property meta registration.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Meta;

use MB\RealEstate\Contracts\Service;
use MB\RealEstate\PostTypes\Property;

/**
 * Registers typed property meta and exposes it in the REST API.
 */
final class PropertyMeta implements Service {

	/**
	 * Registers the service's hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Registers every meta key of the property post type.
	 *
	 * @return void
	 */
	public function register_meta(): void {
		foreach ( MetaKey::cases() as $key ) {
			list( $min, $max ) = $key->range();

			register_post_meta(
				Property::POST_TYPE,
				$key->value,
				array(
					'type'              => $key->type(),
					'description'       => $key->description(),
					'single'            => true,
					'default'           => 'integer' === $key->type() ? 0 : 0.0,
					'sanitize_callback' => array( $key, 'sanitize' ),
					'auth_callback'     => array( $this, 'can_edit' ),
					'show_in_rest'      => array(
						'schema' => array(
							'type'    => $key->type(),
							'minimum' => $min,
							'maximum' => $max,
						),
					),
				)
			);
		}
	}

	/**
	 * Checks whether the current user may edit the meta of a property.
	 *
	 * @param bool   $allowed  Whether the user can edit the meta. Unused.
	 * @param string $meta_key Meta key. Unused.
	 * @param int    $post_id  Property ID.
	 * @return bool
	 */
	public function can_edit( bool $allowed, string $meta_key, int $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}
}
