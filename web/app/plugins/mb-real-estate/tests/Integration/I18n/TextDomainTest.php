<?php
/**
 * Tests for the text domain loader.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Tests\Integration\I18n;

use MB\RealEstate\I18n\TextDomain;
use WP_UnitTestCase;

/**
 * Text domain loader tests.
 */
final class TextDomainTest extends WP_UnitTestCase {

	/**
	 * Restores the plugin's translations after each test.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		unload_textdomain( TextDomain::DOMAIN );
		( new TextDomain( 'mb-real-estate/languages' ) )->load();

		parent::tear_down();
	}

	/**
	 * Registering the service hooks the loader on init.
	 *
	 * @return void
	 */
	public function test_register_hooks_load_on_init(): void {
		$text_domain = new TextDomain( 'mb-real-estate/languages' );

		$text_domain->register();

		$this->assertSame( 10, has_action( 'init', array( $text_domain, 'load' ) ) );
	}

	/**
	 * The booted plugin has its text domain loader on init.
	 *
	 * @return void
	 */
	public function test_booted_plugin_hooks_text_domain_on_init(): void {
		$this->assertContains( TextDomain::class, $this->get_init_callback_classes() );
	}

	/**
	 * Loaded translations come from the given directory, relative to the plugins directory.
	 *
	 * @return void
	 */
	public function test_load_translates_strings_from_languages_path(): void {
		$text_domain = new TextDomain( plugin_basename( __DIR__ . '/fixtures/languages' ) );
		add_filter( 'determine_locale', static fn (): string => 'uk' );

		$text_domain->load();

		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralDomain -- The domain is the plugin's own constant.
		$this->assertSame( 'Нерухомість', __( 'Property', TextDomain::DOMAIN ) );
	}

	/**
	 * Returns the classes of object callbacks hooked on init.
	 *
	 * @return list<string>
	 */
	private function get_init_callback_classes(): array {
		global $wp_filter;

		$classes = array();

		foreach ( $wp_filter['init']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && is_object( $callback['function'][0] ) ) {
					$classes[] = get_class( $callback['function'][0] );
				}
			}
		}

		return $classes;
	}
}
