<?php
/**
 * Service contract.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Contracts;

/**
 * A unit of plugin functionality that attaches itself to WordPress hooks.
 */
interface Service {

	/**
	 * Registers the service's hooks.
	 *
	 * @return void
	 */
	public function register(): void;
}
