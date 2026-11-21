<?php
/**
 * Buttons Block
 *
 * Provides support for rendering button group blocks within the Aegis Framework.
 *
 * Responsibilities:
 * - Handles the logic for displaying and styling button group block content
 *
 * @package    Aegis\Framework\CoreBlocks
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/theme
 */

declare( strict_types=1 );

namespace Aegis\Framework\CoreBlocks;

use Aegis\Dom\CSS;
use Aegis\Dom\DOM;
use Aegis\Framework\Interfaces\Renderable;
use WP_Block;


/**
 * Handles the rendering of the core/buttons block (the button group container).
 *
 * This class is responsible for applying custom spacing (margin and padding)
 * to the main wrapper element of the `core/buttons` block.
 *
 * @package Aegis\Framework\CoreBlocks
 * @since   1.0.0
 */
class Buttons implements Renderable {

	/**
	 * Renders the buttons block container with custom spacing.
	 *
	 * This method is hooked into the `render_block_core/buttons` filter to apply
	 * margin and padding values from the block's attributes directly to the
	 * block's wrapper `<div>`.
	 *
	 * @since 1.0.0
	 *
	 * @param  string   $block_content The original block content.
	 * @param  array    $block         The full block object, including name and attributes.
	 * @param  WP_Block $instance      The block instance.
	 *
	 * @hook   render_block_core/buttons 10
	 *
	 * @return string The modified block content with custom spacing applied.
	 */
	public function render( string $block_content, array $block, WP_Block $instance ): string {
		$attrs   = $block['attrs'] ?? [];
		$margin  = $attrs['style']['spacing']['margin'] ?? [];
		$padding = $attrs['style']['spacing']['padding'] ?? [];

		// Only modify the block if there are custom margin or padding values.
		if ( $margin || $padding ) {
			$dom = DOM::create( $block_content );
			$div = DOM::get_element( 'div', $dom );

			// If the main wrapper div is found, apply the styles.
			if ( $div ) {
				$styles = CSS::string_to_array( $div->getAttribute( 'style' ) );
				$styles = CSS::add_shorthand_property( $styles, 'margin', $margin );
				$styles = CSS::add_shorthand_property( $styles, 'padding', $padding );

				$div->setAttribute( 'style', CSS::array_to_string( $styles ) );

				$block_content = $dom->saveHTML();
			}
		}

		return $block_content;
	}
}
