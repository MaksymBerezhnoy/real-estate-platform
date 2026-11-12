<?php
/**
 * WordPress test environment configuration.
 *
 * Read by the test library bootstrap and by its installer, which runs in a separate PHP
 * process. The site's .env is not used: the tests have their own database.
 *
 * @package MB\RealEstate
 */

// Repository root: tests -> mb-real-estate -> plugins -> app -> web -> root.
$mbre_root = dirname( __DIR__, 5 );

define( 'ABSPATH', $mbre_root . '/web/wp/' );
define( 'WP_CONTENT_DIR', $mbre_root . '/web/app' );

// Defaults match the DDEV database container; CI overrides them with environment variables.
define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ? getenv( 'WP_TESTS_DB_NAME' ) : 'db_tests' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ? getenv( 'WP_TESTS_DB_USER' ) : 'db' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) ? getenv( 'WP_TESTS_DB_PASSWORD' ) : 'db' );
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ? getenv( 'WP_TESTS_DB_HOST' ) : 'db' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
define( 'WP_DEBUG', true );

// The test library expects the table prefix in this global; every run reinstalls these tables.
$table_prefix = 'wptests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- The test library reads it.
