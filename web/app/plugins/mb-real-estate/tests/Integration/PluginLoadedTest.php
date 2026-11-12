<?php
/**
 * Tests that the plugin is loaded in the WordPress test environment.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Tests\Integration;

use WP_UnitTestCase;

/**
 * Plugin loading tests.
 */
final class PluginLoadedTest extends WP_UnitTestCase {

	/**
	 * The version constant matches the Version header of the main file.
	 *
	 * @return void
	 */
	public function test_version_constant_matches_plugin_header(): void {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$plugin_data = get_plugin_data( MBRE_PLUGIN_FILE, false, false );

		$this->assertSame( $plugin_data['Version'], MBRE_VERSION );
	}

	/**
	 * The main file constant points to the plugin inside the plugins directory.
	 *
	 * @return void
	 */
	public function test_plugin_file_resolves_to_plugin_basename(): void {
		$this->assertSame( 'mb-real-estate/mb-real-estate.php', plugin_basename( MBRE_PLUGIN_FILE ) );
	}

	/**
	 * The plugin boots on plugins_loaded, which has already fired.
	 *
	 * @return void
	 */
	public function test_plugin_boots_on_plugins_loaded(): void {
		$this->assertSame( 10, has_action( 'plugins_loaded', 'MB\\RealEstate\\boot' ) );
		$this->assertSame( 1, did_action( 'plugins_loaded' ) );
	}
}
