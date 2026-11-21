<?php
/**
 * Renderable Interface
 *
 * Defines the contract for objects that can be rendered as part of the Aegis Framework block system.
 *
 * Responsibilities:
 * - Provides a standard render method for block content
 * - Ensures compatibility with WordPress block and instance structures
 *
 * @package    Aegis\Framework\Interfaces
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/theme
 */

declare( strict_types=1 );

namespace Aegis\Framework\Interfaces;

use WP_Block;

// Declares the Renderable interface for standardizing renderable objects in the framework.
interface Renderable {

	/**
	 * Render the object.
	 *
	 * @since 1.0.0
	 *
	 * @param string   $block_content The block content.
	 * @param array    $block         The full block, including name and attributes.
	 * @param WP_Block $instance      The block instance.
	 *
	 * @return string
	 */
	public function render( string $block_content, array $block, WP_Block $instance ): string;

}
