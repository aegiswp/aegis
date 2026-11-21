<?php
/**
 * Unit tests for Fix model.
 *
 * @package WPAudit
 */

namespace WPAudit\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use WPAudit\Models\Fix;
use WPAudit\Enums\FixConfidence;

/**
 * Test Fix model functionality.
 */
class FixTest extends TestCase {
	/**
	 * Test meets_threshold with SAFE fix.
	 */
	public function test_meets_threshold_safe(): void {
		$fix = new Fix(
			'replace',
			FixConfidence::SAFE,
			'Test fix',
			function () {
				return true;
			}
		);

		$this->assertTrue( $fix->meets_threshold( FixConfidence::SAFE ) );
		$this->assertTrue( $fix->meets_threshold( FixConfidence::MODERATE ) );
		$this->assertTrue( $fix->meets_threshold( FixConfidence::RISKY ) );
	}

	/**
	 * Test meets_threshold with MODERATE fix.
	 */
	public function test_meets_threshold_moderate(): void {
		$fix = new Fix(
			'replace',
			FixConfidence::MODERATE,
			'Test fix',
			function () {
				return true;
			}
		);

		$this->assertFalse( $fix->meets_threshold( FixConfidence::SAFE ) );
		$this->assertTrue( $fix->meets_threshold( FixConfidence::MODERATE ) );
		$this->assertTrue( $fix->meets_threshold( FixConfidence::RISKY ) );
	}

	/**
	 * Test meets_threshold with RISKY fix.
	 */
	public function test_meets_threshold_risky(): void {
		$fix = new Fix(
			'refactor',
			FixConfidence::RISKY,
			'Test fix',
			function () {
				return true;
			}
		);

		$this->assertFalse( $fix->meets_threshold( FixConfidence::SAFE ) );
		$this->assertFalse( $fix->meets_threshold( FixConfidence::MODERATE ) );
		$this->assertTrue( $fix->meets_threshold( FixConfidence::RISKY ) );
	}
}
