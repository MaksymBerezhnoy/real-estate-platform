<?php
/**
 * Text domain loader.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\I18n;

use MB\RealEstate\Contracts\Service;

/**
 * Loads the plugin's translations.
 */
final class TextDomain implements Service {

	public const DOMAIN = 'mb-real-estate';

	/**
	 * Constructor.
	 *
	 * @param string $languages_path Languages directory, relative to the plugins directory.
	 */
	public function __construct( private readonly string $languages_path ) {
	}

	/**
	 * Registers the service's hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'load' ) );
	}

	/**
	 * Loads the translation files.
	 *
	 * @return void
	 */
	public function load(): void {
		load_plugin_textdomain( self::DOMAIN, false, $this->languages_path );
	}
}
