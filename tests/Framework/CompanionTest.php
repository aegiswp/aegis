<?php
/**
 * Tests for the framework's Companion contract: the no-plugin fallbacks, how
 * Companions::get() resolves and caches, and that call sites go through it.
 *
 * Companions keeps static state, so every case that resolves runs in its own process.
 */

declare( strict_types=1 );

namespace AegisTests\Framework;

use Aegis\Framework\Companion;
use Aegis\Framework\Companions;
use Aegis\Framework\Integrations\GravityForms;
use Aegis\Framework\Integrations\WooCommerce\SaleBadge;
use Aegis\Framework\NullCompanion;
use Aegis\Framework\ServiceProvider;
use AegisTests\Framework\Fixtures\FakeCompanion;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class CompanionTest extends TestCase {

	protected function setUp(): void {
		Wp::reset();
	}

	// NullCompanion: each method pins what the framework did with no plugin present.

	public function test_null_integration_active_has_no_opinion(): void {
		$this->assertNull( ( new NullCompanion() )->integration_active( Companion::INTEGRATION_WOOCOMMERCE ) );
	}

	public function test_null_integrations_default_to_enabled(): void {
		$this->assertTrue( ( new NullCompanion() )->is_integration_enabled( 'woocommerce' ) );
	}

	public function test_null_blocks_default_to_enabled(): void {
		$this->assertTrue( ( new NullCompanion() )->is_block_enabled( 'accordion_icon' ) );
	}

	public function test_null_schema_is_never_delegated_to_seo(): void {
		$this->assertFalse( ( new NullCompanion() )->is_schema_delegated_to_seo( 'faq' ) );
	}

	public function test_null_embed_facades_are_on(): void {
		$this->assertTrue( ( new NullCompanion() )->is_embed_facade_enabled() );
	}

	public function test_null_does_not_own_emoji_removal(): void {
		$this->assertFalse( ( new NullCompanion() )->owns_emoji_removal() );
	}

	public function test_null_does_not_own_co_authors_plus(): void {
		$this->assertFalse( ( new NullCompanion() )->owns_co_authors_plus() );
	}

	public function test_null_has_no_visibility_evaluator(): void {
		$this->assertNull( ( new NullCompanion() )->should_hide_for_visibility( [ 'lockdown' => true ] ) );
	}

	public function test_null_conditionals_extras_default_to_on(): void {
		$this->assertTrue( ( new NullCompanion() )->is_conditionals_extra_enabled( 'visibility', 'lockdown' ) );
	}

	public function test_null_conditionals_editor_data_is_empty(): void {
		$null = new NullCompanion();

		$this->assertSame( [], $null->conditionals_editor_settings() );
		$this->assertSame( [], $null->conditionals_timezone_choices() );
	}

	public function test_null_lets_everyone_manage_conditionals(): void {
		$this->assertTrue( ( new NullCompanion() )->current_user_can_manage_conditionals() );
	}

	public function test_null_has_no_wp_fusion_data(): void {
		$this->assertNull( ( new NullCompanion() )->wp_fusion_editor_data() );
	}

	// Companions::get().

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_without_a_filter_answer_the_null_companion_is_returned(): void {
		$this->assertInstanceOf( NullCompanion::class, Companions::get() );
		$this->assertSame( 'aegis_framework_companion', Wp::$filters_applied[0]['tag'] );
		$this->assertNull( Wp::$filters_applied[0]['args'][0] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_a_companion_from_the_filter_is_used_and_cached(): void {
		$fake                                         = new FakeCompanion();
		Wp::$filter_results['aegis_framework_companion'] = $fake;

		$this->assertSame( $fake, Companions::get() );

		// Cached: the filter is not asked again, and a different answer is ignored.
		Wp::$filter_results['aegis_framework_companion'] = new FakeCompanion();

		$this->assertSame( $fake, Companions::get() );
		$this->assertCount( 1, Wp::$filters_applied );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_junk_from_the_filter_is_ignored(): void {
		foreach ( [ 'a string', 42, new \stdClass(), true ] as $junk ) {
			Wp::$filter_results['aegis_framework_companion'] = $junk;

			$this->assertInstanceOf( NullCompanion::class, Companions::get() );
		}
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_the_null_fallback_is_not_cached_before_plugins_loaded(): void {
		$this->assertInstanceOf( NullCompanion::class, Companions::get() );

		// A plugin that loads later still gets to answer.
		$fake                                         = new FakeCompanion();
		Wp::$filter_results['aegis_framework_companion'] = $fake;

		$this->assertSame( $fake, Companions::get() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_the_null_fallback_is_cached_after_plugins_loaded(): void {
		Wp::$did_actions['plugins_loaded'] = 1;

		$first = Companions::get();

		$this->assertInstanceOf( NullCompanion::class, $first );

		Wp::$filter_results['aegis_framework_companion'] = new FakeCompanion();

		$this->assertSame( $first, Companions::get() );
		$this->assertCount( 1, Wp::$filters_applied );
	}

	// Call sites go through the companion.

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_integration_condition_uses_the_companion_answer_true(): void {
		Wp::$filter_results['aegis_framework_companion'] = new FakeCompanion( [ Companion::INTEGRATION_GRAVITY_FORMS => true ] );

		$this->assertTrue( GravityForms::condition() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_integration_condition_uses_the_companion_answer_false_even_when_the_plugin_is_loaded(): void {
		eval( 'class GFForms {}' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
		Wp::$filter_results['aegis_framework_companion'] = new FakeCompanion( [ Companion::INTEGRATION_GRAVITY_FORMS => false ] );

		$this->assertFalse( GravityForms::condition() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_integration_condition_falls_back_to_the_native_check_when_the_companion_has_no_opinion(): void {
		Wp::$filter_results['aegis_framework_companion'] = new FakeCompanion();

		$this->assertFalse( GravityForms::condition() );

		eval( 'class GFForms {}' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged

		$this->assertTrue( GravityForms::condition() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_woocommerce_sub_integration_condition_uses_the_companion(): void {
		Wp::$filter_results['aegis_framework_companion'] = new FakeCompanion( [ Companion::INTEGRATION_WOOCOMMERCE => true ] );

		$this->assertTrue( SaleBadge::condition() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_block_settings_come_from_the_companion(): void {
		Wp::$filter_results['aegis_framework_companion'] = new FakeCompanion( [], false );

		$this->assertFalse( ServiceProvider::is_block_enabled( 'accordion_icon' ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_block_settings_default_to_enabled_without_a_companion(): void {
		$this->assertTrue( ServiceProvider::is_block_enabled( 'accordion_icon' ) );
	}
}
