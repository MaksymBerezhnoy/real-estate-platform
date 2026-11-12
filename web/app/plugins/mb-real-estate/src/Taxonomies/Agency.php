<?php
/**
 * Agency taxonomy.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Taxonomies;

use MB\RealEstate\Contracts\Service;
use MB\RealEstate\PostTypes\Property;

/**
 * Registers the agency taxonomy: the agency that lists a property.
 */
final class Agency implements Service {

	public const TAXONOMY = 'mbre_agency';

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
					'name'          => __( 'Agencies', 'mb-real-estate' ),
					'singular_name' => __( 'Agency', 'mb-real-estate' ),
					'search_items'  => __( 'Search Agencies', 'mb-real-estate' ),
					'all_items'     => __( 'All Agencies', 'mb-real-estate' ),
					'edit_item'     => __( 'Edit Agency', 'mb-real-estate' ),
					'add_new_item'  => __( 'Add New Agency', 'mb-real-estate' ),
					'not_found'     => __( 'No agencies found.', 'mb-real-estate' ),
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'rest_base'         => 'agencies',
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'agency',
					'with_front' => false,
				),
			)
		);
	}
}
