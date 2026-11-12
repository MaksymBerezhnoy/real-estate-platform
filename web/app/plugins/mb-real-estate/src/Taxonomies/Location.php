<?php
/**
 * Location taxonomy.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Taxonomies;

use MB\RealEstate\Contracts\Service;
use MB\RealEstate\PostTypes\Property;

/**
 * Registers the hierarchical location taxonomy: city, then district.
 */
final class Location implements Service {

	public const TAXONOMY = 'mbre_location';

	/**
	 * Registers the service's hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Registers the taxonomy.
	 *
	 * @return void
	 */
	public function register_taxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			Property::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Locations', 'mb-real-estate' ),
					'singular_name' => __( 'Location', 'mb-real-estate' ),
					'search_items'  => __( 'Search Locations', 'mb-real-estate' ),
					'all_items'     => __( 'All Locations', 'mb-real-estate' ),
					'parent_item'   => __( 'Parent Location', 'mb-real-estate' ),
					'edit_item'     => __( 'Edit Location', 'mb-real-estate' ),
					'add_new_item'  => __( 'Add New Location', 'mb-real-estate' ),
					'not_found'     => __( 'No locations found.', 'mb-real-estate' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'rest_base'         => 'locations',
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'         => 'location',
					'with_front'   => false,
					'hierarchical' => true,
				),
			)
		);
	}
}
