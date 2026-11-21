<?php
/**
 * Tag Cloud Block
 *
 * Provides support for rendering tag cloud blocks within the Aegis Framework.
 *
 * Responsibilities:
 * - Handles the logic for displaying and styling tag cloud block content
 *
 * @package    Aegis\Framework\CoreBlocks
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/theme
 */

declare( strict_types=1 );

namespace Aegis\Framework\CoreBlocks;

use Aegis\Dom\DOM;
use Aegis\Framework\Interfaces\Renderable;
use WP_Block;
use function esc_attr;
use function implode;

class TagCloud implements Renderable {

	/**
	 * Modifies front end HTML output of block.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $block_content Block HTML.
	 * @param array    $block         Block data.
	 * @param WP_Block $instance      Block instance.
	 *
	 * @hook  render_block_core/tag-cloud
	 *
	 * @return string
	 */
	public function render( string $block_content, array $block, WP_Block $instance ): string {
		// Read font size bounds from block attributes.
		$smallest  = esc_attr( $block['attrs']['smallestFontSize'] ?? '1em' );
		$largest   = esc_attr( $block['attrs']['largestFontSize'] ?? '1em' );
		$dom       = DOM::create( $block_content );
		$paragraph = DOM::get_element( 'p', $dom );

		// No tags yet (a new site): the block renders without a paragraph.
		if ( ! $paragraph ) {
			return $block_content;
		}

		// Enforce a minimum font size using the largest configured value.
		$paragraph->setAttribute(
			'style',
			implode(
				';',
				[
					'font-size:max(' . $smallest . ',' . $largest . ')',
					$paragraph->getAttribute( 'style' ),
				]
			)
		);

		return $dom->saveHTML();
	}
}
