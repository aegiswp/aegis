<?php
/**
 * Tests for Aegis\Framework\Integrations\Detect.
 *
 * Presence of a class, function or constant cannot be undone inside a PHP
 * process, so every case that declares one runs in its own process.
 */

declare( strict_types=1 );

namespace AegisTests\Framework;

use Aegis\Framework\Integrations\Detect;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class DetectTest extends TestCase {

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_woocommerce_is_false_when_nothing_is_loaded(): void {
		$this->assertFalse( Detect::woocommerce() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_woocommerce_is_true_when_the_class_exists(): void {
		eval( 'class WooCommerce {}' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged

		$this->assertTrue( Detect::woocommerce() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_woocommerce_is_true_when_the_WC_function_exists(): void {
		eval( 'function WC() { return null; }' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged

		$this->assertTrue( Detect::woocommerce() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_woocommerce_is_true_when_the_version_constant_is_defined(): void {
		define( 'WC_VERSION', '9.0.0' );

		$this->assertTrue( Detect::woocommerce() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_woocommerce_is_not_cached_so_a_late_loading_plugin_is_seen(): void {
		$this->assertFalse( Detect::woocommerce(), 'Asked before WooCommerce loads.' );

		define( 'WC_VERSION', '9.0.0' );

		$this->assertTrue( Detect::woocommerce(), 'A false answer must not stick.' );
	}
}
