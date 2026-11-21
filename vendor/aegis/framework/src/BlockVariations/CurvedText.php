<?php
/**
 * CurvedText Block Variation
 *
 * Provides support for rendering curved text blocks within the Aegis Framework.
 *
 * Responsibilities:
 * - Handles the logic for displaying and styling curved text block content
 *
 * @package    Aegis\Framework\BlockVariations
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/theme
 */

declare( strict_types=1 );

namespace Aegis\Framework\BlockVariations;

use Aegis\Dom\DOM;
use Aegis\Framework\Interfaces\Renderable;
use WP_Block;

class CurvedText implements Renderable {

	/**
	 * Renders curved text.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $block_content Block HTML.
	 * @param array    $block         Block data.
	 * @param WP_Block $instance      Block instance.
	 *
	 * @hook  render_block_core/paragraph 11
	 *
	 * @return string
	 */
	public function render( string $block_content, array $block, WP_Block $instance ): string {
		$svg_string = $block['attrs']['curvedText']['svgString'] ?? '';

		// Return original content when no SVG is configured.
		if ( ! $svg_string ) {
			return $block_content;
		}

		// Clear paragraph text and prepare for SVG insertion.
		$dom            = DOM::create( $block_content );
		$p              = DOM::get_element( 'p', $dom );

		if ( ! $p ) {
			return $block_content;
		}

		$p->textContent = '';

		$svg_dom     = DOM::create( $svg_string );
		$svg_element = DOM::get_element( 'svg', $svg_dom );

		if ( ! $svg_element ) {
			return $block_content;
		}

		$svg_text_element = DOM::get_element( 'text', $svg_element );

		if ( ! $svg_text_element ) {
			return $block_content;
		}

		// Update the text path content from block attributes.
		$text_path_element = DOM::get_element( '*', $svg_text_element );

		if ( $text_path_element ) {
			$text_path_element->textContent = $block['attrs']['curvedText']['content'] ?? '';
		}

		// Import the updated SVG into the paragraph element.
		$svg_string      = $svg_dom->saveHTML( $svg_element );
		$new_svg_dom     = DOM::create( $svg_string );
		$new_svg_element = DOM::get_element( 'svg', $new_svg_dom );
		$imported        = $dom->importNode( $new_svg_element, true );

		$p->appendChild( $imported );

		return $dom->saveHTML( $p );
	}
}
