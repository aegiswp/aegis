<?php
/**
 * Tests for Location model.
 *
 * @package WPAudit
 */

namespace WPAudit\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use WPAudit\Models\Location;

/**
 * Test Location model functionality.
 */
class LocationTest extends TestCase {
	/**
	 * Test to_array without column.
	 *
	 * @return void
	 */
	public function test_to_array_without_column(): void {
		$location = new Location( 'style.css', 42 );
		$array    = $location->to_array();

		$this->assertSame( 'style.css', $array['file'] );
		$this->assertSame( 42, $array['line'] );
		$this->assertArrayNotHasKey( 'column', $array );
	}

	/**
	 * Test from_array with missing fields uses defaults.
	 *
	 * @return void
	 */
	public function test_from_array_with_defaults(): void {
		$location = Location::from_array( array() );

		$this->assertSame( '', $location->file );
		$this->assertSame( 1, $location->line );
		$this->assertNull( $location->column );
	}
}
