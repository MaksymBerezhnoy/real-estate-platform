<?php
/**
 * PHPUnit bootstrap: loads the autoloaders and, unless only the unit suite runs, WordPress.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Tests;

// Repository root: tests -> mb-real-estate -> plugins -> app -> web -> root.
require_once dirname( __DIR__, 5 ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * Checks whether the command line limits the run to the unit suite.
 *
 * @param array<int, string> $arguments Command line arguments.
 * @return bool
 */
function is_unit_suite_only( array $arguments ): bool {
	foreach ( $arguments as $index => $argument ) {
		if ( '--testsuite=unit' === $argument ) {
			return true;
		}

		if ( '--testsuite' === $argument && 'unit' === ( $arguments[ $index + 1 ] ?? '' ) ) {
			return true;
		}
	}

	return false;
}

if ( is_unit_suite_only( $GLOBALS['argv'] ?? array() ) ) {
	return;
}

/**
 * Loads the plugin before regular plugins, the way an activated plugin is loaded.
 *
 * @return void
 */
function load_plugin(): void {
	require dirname( __DIR__ ) . '/mb-real-estate.php';
}

$mbre_tests_library = getenv( 'WP_PHPUNIT__DIR' );

if ( false === $mbre_tests_library ) {
	echo 'The WordPress test library is missing: run "composer install".' . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI output.
	exit( 1 );
}

define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );

require_once $mbre_tests_library . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', __NAMESPACE__ . '\\load_plugin' );

require $mbre_tests_library . '/includes/bootstrap.php';
