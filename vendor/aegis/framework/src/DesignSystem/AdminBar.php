<?php
/**
 * Admin Bar Component
 *
 * Provides support for rendering and managing the WordPress admin bar within the Aegis Framework.
 *
 * Responsibilities:
 * - Registers and manages admin bar styles and assets
 *
 * @package    Aegis\Framework\DesignSystem
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/theme
 */

declare( strict_types=1 );

namespace Aegis\Framework\DesignSystem;

use Aegis\Framework\InlineAssets\Styleable;
use Aegis\Framework\InlineAssets\Styles;
use function is_admin_bar_showing;

class AdminBar implements Styleable {

	/**
	 * Registers service with access to provider.
	 *
	 * @since 1.0.0
	 *
	 * @param Styles $styles Styles service.
	 *
	 * @return void
	 */
	public function styles( Styles $styles ): void {
		$styles->add_file(
			'components/admin-bar.css',
			[],
			is_admin_bar_showing()
		);
	}
}
