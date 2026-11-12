<?php
/**
 * Tests for the plugin container.
 *
 * @package MB\RealEstate
 */

declare( strict_types=1 );

namespace MB\RealEstate\Tests\Unit;

use MB\RealEstate\Contracts\Service;
use MB\RealEstate\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * Plugin container tests.
 */
final class PluginTest extends TestCase {

	/**
	 * Names of the services in the order they were registered.
	 *
	 * @var list<string>
	 */
	private array $registered = array();

	/**
	 * Boot registers every service once, in constructor order.
	 *
	 * @return void
	 */
	public function test_boot_registers_each_service_once_in_order(): void {
		$plugin = new Plugin(
			$this->make_service( 'first' ),
			$this->make_service( 'second' ),
			$this->make_service( 'third' ),
		);

		$plugin->boot();

		$this->assertSame( array( 'third', 'second', 'first' ), $this->registered );
	}

	/**
	 * Building the container has no side effects until boot.
	 *
	 * @return void
	 */
	public function test_constructor_does_not_register_services(): void {
		$plugin = new Plugin( $this->make_service( 'first' ) );

		$this->assertInstanceOf( Plugin::class, $plugin );
		$this->assertSame( array(), $this->registered );
	}

	/**
	 * Creates a service that records its registration.
	 *
	 * @param string $name Service name.
	 * @return Service
	 */
	private function make_service( string $name ): Service {
		$on_register = function () use ( $name ): void {
			$this->registered[] = $name;
		};

		return new class( $on_register ) implements Service {

			/**
			 * Constructor.
			 *
			 * @param \Closure $on_register Called on registration.
			 */
			public function __construct( private readonly \Closure $on_register ) {
			}

			/**
			 * Records the registration.
			 *
			 * @return void
			 */
			public function register(): void {
				( $this->on_register )();
			}
		};
	}
}
