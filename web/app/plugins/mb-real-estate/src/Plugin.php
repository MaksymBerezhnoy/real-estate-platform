<?php
/**
 * Plugin container.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate;

use MB\RealEstate\Contracts\Service;

/**
 * Holds the plugin's services and registers them.
 */
final class Plugin {

	/**
	 * Services in registration order.
	 *
	 * @var list<Service>
	 */
	private readonly array $services;

	/**
	 * Constructor.
	 *
	 * @param Service ...$services Services to register on boot.
	 */
	public function __construct( Service ...$services ) {
		$this->services = array_values( $services );
	}

	/**
	 * Registers every service.
	 *
	 * @return void
	 */
	public function boot(): void {
		foreach ( $this->services as $service ) {
			$service->register();
		}
	}
}
