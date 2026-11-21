<?php
/**
 * Settings gates for the Aegis icon features.
 *
 * @package Aegis\Framework\Icons
 * @since   1.1.0
 */

declare( strict_types=1 );

namespace Aegis\Framework\Icons;

use Aegis\Framework\ServiceProvider;
use Aegis\Icons\IconSupport;

/**
 * Answers whether the Icon block features are switched on.
 *
 * WordPress version gates live in {@see IconSupport}; this class holds the
 * Aegis → Blocks settings gates, so the toggle names appear once.
 */
final class IconFeatures {

	/**
	 * Whether the Icon block enhancements are enabled.
	 */
	public static function block_enabled(): bool {
		return ServiceProvider::is_block_enabled( 'icon' );
	}

	/**
	 * Whether the Icon block and its REST API endpoint are both enabled.
	 */
	public static function rest_enabled(): bool {
		return self::block_enabled() && ServiceProvider::is_block_enabled( 'icon_rest_api' );
	}

	/**
	 * Whether the Icon block enhancements apply here: supported WordPress version and enabled.
	 */
	public static function active(): bool {
		return IconSupport::available() && self::block_enabled();
	}
}
