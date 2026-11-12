<?php
/**
 * Plugin Name:       MB Real Estate
 * Description:       Property catalog: post type, taxonomies, search index, REST search and blocks.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.3
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mb-real-estate
 * Domain Path:       /languages
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate;

use MB\RealEstate\I18n\TextDomain;

defined( 'ABSPATH' ) || exit;

define( 'MBRE_VERSION', '0.1.0' );
define( 'MBRE_PLUGIN_FILE', __FILE__ );

/**
 * Displays an admin notice when Composer dependencies are not installed.
 *
 * @return void
 */
function display_missing_autoloader_notice(): void {
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'MB Real Estate is not running: run "composer install" in the plugin directory.', 'mb-real-estate' )
	);
}

if ( ! is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action( 'admin_notices', __NAMESPACE__ . '\\display_missing_autoloader_notice' );
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Builds the plugin and registers its services.
 *
 * @return void
 */
function boot(): void {
	$plugin = new Plugin(
		new TextDomain( dirname( plugin_basename( MBRE_PLUGIN_FILE ) ) . '/languages' ),
	);

	$plugin->boot();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\boot' );
