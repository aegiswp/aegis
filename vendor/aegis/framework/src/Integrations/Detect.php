<?php
/**
 * Detects third-party plugins without relying on the Aegis companion plugin.
 *
 * Integration classes ask the companion plugin first (when it is loaded) and
 * fall back to these checks otherwise.
 *
 * @package Aegis\Framework
 */

declare( strict_types=1 );

namespace Aegis\Framework\Integrations;

use function class_exists;
use function defined;
use function function_exists;

/**
 * Plugin presence checks.
 *
 * Deliberately not cached: plugins load in sequence, so a `false` answer given
 * early (before WooCommerce loads) must not stick for the rest of the request.
 */
final class Detect {

	/**
	 * Whether WooCommerce is loaded.
	 *
	 * @return bool
	 */
	public static function woocommerce(): bool {
		return class_exists( 'WooCommerce' )
			|| function_exists( 'WC' )
			|| defined( 'WC_VERSION' );
	}
}
