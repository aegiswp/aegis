<?php
/**
 * Code Block
 *
 * Provides support for rendering code blocks within the Aegis Framework.
 *
 * Responsibilities:
 * - Handles the logic for displaying and styling code block content
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
 * Handles the rendering of the core/code block.
 *
 * This class is responsible for applying custom margin values from the block's
 * spacing settings to the `<pre>` element within the code block.
 *
 * @package Aegis\Framework\CoreBlocks
 * @since   1.0.0
 */
class Code implements Renderable {

	/**
	 * Renders the code block with custom margins.
	 *
	 * This method is hooked into the `render_block_core/code` filter to apply
	 * margin values from the block's attributes as an inline style on the `<pre>` tag.
	 *
	 * @since 1.0.0
	 *
	 * @param  string   $block_content The original block content.
	 * @param  array    $block         The full block object.
	 * @param  WP_Block $instance      The block instance.
	 *
	 * @hook   render_block_core/code 12
	 *
	 * @return string The modified block content with custom margins applied.
	 */
	public function render( string $block_content, array $block, WP_Block $instance ): string {
		$attrs  = $block['attrs'] ?? [];
		$margin = $attrs['style']['spacing']['margin'] ?? null;

		// Only modify the block if there are custom margin values.
		if ( $margin ) {
			$dom = DOM::create( $block_content );
			$pre = DOM::get_element( 'pre', $dom );

			// If the <pre> element is found, apply the margin styles.
			if ( $pre ) {
				$pre_styles = CSS::string_to_array( $pre->getAttribute( 'style' ) );
				$pre_styles = CSS::add_shorthand_property( $pre_styles, 'margin', $margin );

				$pre->setAttribute( 'style', CSS::array_to_string( $pre_styles ) );

				$block_content = $dom->saveHTML();
			}
		}

		return $block_content;
	}
}
