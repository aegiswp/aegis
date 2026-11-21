<?php
/**
 * Unit tests for Finding model.
 *
 * @package WPAudit
 */

namespace WPAudit\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use WPAudit\Models\Finding;
use WPAudit\Models\Location;
use WPAudit\Models\Fix;
use WPAudit\Enums\Severity;
use WPAudit\Enums\FixConfidence;

/**
 * Test Finding model functionality.
 */
class FindingTest extends TestCase {
	/**
	 * Test has_fix returns false when no fix provided.
	 */
	public function test_has_fix_without_fix(): void {
		$location = new Location( 'test.php', 10 );
		$finding  = new Finding(
			'test-001',
			'performance',
			'perf-001',
			Severity::HIGH,
			'Test issue',
			$location,
			'Fix it'
		);

		$this->assertFalse( $finding->has_fix() );
	}

	/**
	 * Test has_fix returns true when fix provided.
	 */
	public function test_has_fix_with_fix(): void {
		$location = new Location( 'test.php', 10 );
		$fix      = new Fix(
			'replace',
			FixConfidence::SAFE,
			'Add attribute',
			function () {
				return true;
			}
		);

		$finding = new Finding(
			'test-001',
			'performance',
			'perf-001',
			Severity::HIGH,
			'Test issue',
			$location,
			'Fix it',
			$fix
		);

		$this->assertTrue( $finding->has_fix() );
		$this->assertEquals( $fix, $finding->suggested_fix );
	}

	/**
	 * Test to_array includes fix when present.
	 */
	public function test_to_array_with_fix(): void {
		$location = new Location( 'test.php', 10 );
		$fix      = new Fix(
			'replace',
			FixConfidence::SAFE,
			'Add attribute',
			function () {
				return true;
			}
		);

		$finding = new Finding(
			'test-001',
			'performance',
			'perf-001',
			Severity::HIGH,
			'Test issue',
			$location,
			'Fix it',
			$fix
		);

		$array = $finding->to_array();

		$this->assertArrayHasKey( 'suggestedFix', $array );
		$this->assertIsArray( $array['suggestedFix'] );
		$this->assertEquals( 'replace', $array['suggestedFix']['type'] );
		$this->assertEquals( FixConfidence::SAFE, $array['suggestedFix']['confidence'] );
	}

	/**
	 * Test from_array with missing fields uses defaults.
	 */
	public function test_from_array_with_defaults(): void {
		$data = array();

		$finding = Finding::from_array( $data );

		$this->assertEquals( '', $finding->id );
		$this->assertEquals( '', $finding->category );
		$this->assertEquals( '', $finding->rule );
		$this->assertEquals( Severity::LOW, $finding->severity );
		$this->assertEquals( '', $finding->message );
		$this->assertEquals( '', $finding->recommendation );
		$this->assertEmpty( $finding->metadata );
	}
}
