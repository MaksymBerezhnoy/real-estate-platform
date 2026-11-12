<?php
/**
 * Property type taxonomy.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Taxonomies;

use MB\RealEstate\Contracts\Service;
use MB\RealEstate\PostTypes\Property;

/**
 * Registers the property type taxonomy: apartment, house.
 */
final class PropertyType implements Service {

	public const TAXONOMY = 'mbre_property_type';

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
					'name'          => __( 'Property Types', 'mb-real-estate' ),
					'singular_name' => __( 'Property Type', 'mb-real-estate' ),
					'search_items'  => __( 'Search Property Types', 'mb-real-estate' ),
					'all_items'     => __( 'All Property Types', 'mb-real-estate' ),
					'edit_item'     => __( 'Edit Property Type', 'mb-real-estate' ),
					'add_new_item'  => __( 'Add New Property Type', 'mb-real-estate' ),
					'not_found'     => __( 'No property types found.', 'mb-real-estate' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'rest_base'         => 'property-types',
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'property-type',
					'with_front' => false,
				),
			)
		);
	}
}
