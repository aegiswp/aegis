<?php
/**
 * Tests for AuditMetadata model.
 *
 * @package WPAudit
 */

namespace WPAudit\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use WPAudit\Models\AuditMetadata;
use WPAudit\Models\ThemeMetadata;

/**
 * Test AuditMetadata model functionality.
 */
class AuditMetadataTest extends TestCase {
	/**
	 * Test create factory method.
	 *
	 * @return void
	 */
	public function test_create_from_theme_metadata(): void {
		$theme = new ThemeMetadata( 'aegis', '2.0.0', '/path/to/theme', true );
		$meta  = AuditMetadata::create( $theme, 5.75, 100 );

		$this->assertSame( 'aegis', $meta->theme_name );
		$this->assertSame( '2.0.0', $meta->theme_version );
		$this->assertSame( '1.0.0', $meta->audit_version );
		$this->assertSame( 5.75, $meta->duration );
		$this->assertSame( 100, $meta->files_scanned );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $meta->timestamp );
	}
}
