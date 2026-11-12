<?php
/**
 * Property post type.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\PostTypes;

use MB\RealEstate\Contracts\Service;

/**
 * Registers the property post type.
 */
final class Property implements Service {

	public const POST_TYPE = 'mbre_property';

	/**
	 * Registers the service's hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}

	/**
	 * Registers the post type.
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'               => __( 'Properties', 'mb-real-estate' ),
					'singular_name'      => __( 'Property', 'mb-real-estate' ),
					'add_new_item'       => __( 'Add New Property', 'mb-real-estate' ),
					'edit_item'          => __( 'Edit Property', 'mb-real-estate' ),
					'new_item'           => __( 'New Property', 'mb-real-estate' ),
					'view_item'          => __( 'View Property', 'mb-real-estate' ),
					'search_items'       => __( 'Search Properties', 'mb-real-estate' ),
					'not_found'          => __( 'No properties found.', 'mb-real-estate' ),
					'not_found_in_trash' => __( 'No properties found in Trash.', 'mb-real-estate' ),
					'all_items'          => __( 'All Properties', 'mb-real-estate' ),
				),
				'public'       => true,
				'show_in_rest' => true,
				'rest_base'    => 'properties',
				'has_archive'  => 'properties',
				'rewrite'      => array(
					'slug'       => 'property',
					'with_front' => false,
				),
				'menu_icon'    => 'dashicons-admin-home',
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
			)
		);
	}
}
