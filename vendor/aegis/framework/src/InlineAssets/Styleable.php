<?php
/**
 * Styleable Interface
 *
 * Defines the contract for classes that support style registration in the Aegis Framework.
 *
 * Responsibilities:
 * - Ensures implementing classes provide a method for registering styles
 *
 * @package    Aegis\Framework\InlineAssets
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/theme
 */

declare( strict_types=1 );

namespace Aegis\Framework\InlineAssets;

// Declares the Styleable interface for style registration in the design system.

interface Styleable {

	/**
	 * Register styles.
	 *
	 * @since 1.0.0
	 *
	 * @param Styles $styles Inlinable service.
	 *
	 * @return void
	 */
	public function styles( Styles $styles ): void;

}
