<?php
/**
 * Emojis Extension Component
 *
 * Provides support for disabling default WordPress emoji scripts and styles in the Aegis Framework.
 *
 * Responsibilities:
 * - Removes emoji scripts and styles from the frontend for standalone theme / free-plugin installs
 * - Defers to Aegis → Performance → Remove Emoji Scripts when Aegis Pro is active
 *
 * @package    Aegis\Framework\DesignSystem
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/theme
 */

declare( strict_types=1 );

namespace Aegis\Framework\DesignSystem;

use Aegis\Framework\Companions;

/**
 * Frontend emoji script/style removal.
 */
class Emojis {

	/**
	 * Removes WordPress emoji detection scripts and styles on the frontend.
	 *
	 * With Aegis Dash active, ownership moves to Aegis → Performance → Remove Emoji Scripts.
	 * Standalone theme and free-plugin installs keep zero-base frontend removal.
	 *
	 * @since 1.0.0
	 *
	 * @hook  init
	 *
	 * @return void
	 */
	public function remove_emoji_script(): void {
		// Aegis Dash owns the full strip (frontend + admin) via the Performance toggle.
		if ( Companions::get()->owns_emoji_removal() ) {
			return;
		}

		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
	}
}
