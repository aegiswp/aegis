<?php
/**
 * Which WordPress icon features this install supports, and the core icon manifest.
 *
 * The one place that knows the WordPress versions the Icon features need and
 * where core lists its own icon library. Callers keep their own function checks
 * (for example `wp_register_icon`); this class answers the version question only.
 *
 * @package Aegis\Icons
 * @since   1.1.0
 */

declare( strict_types=1 );

namespace Aegis\Icons;

use function array_keys;
use function defined;
use function get_bloginfo;
use function is_array;
use function is_readable;
use function strtolower;
use function version_compare;

/**
 * WordPress version gates and the core icon library slugs.
 */
final class IconSupport {

	/**
	 * First WordPress version with the core/icon block.
	 */
	public const BLOCK_VERSION = '7.0';

	/**
	 * First WordPress version with the icon collection registry.
	 */
	public const REGISTRY_VERSION = '7.1';

	/**
	 * Cached core icon slugs.
	 *
	 * @var array<string, true>|null
	 */
	private static ?array $core_slugs = null;

	/**
	 * Whether this WordPress version has the core/icon block (7.0+).
	 */
	public static function available(): bool {
		return ! version_compare( get_bloginfo( 'version' ), self::BLOCK_VERSION, '<' );
	}

	/**
	 * Whether this WordPress version has the icon collection registry (7.1+).
	 *
	 * Version only: callers that need `wp_register_icon()` still check it.
	 */
	public static function core_registry(): bool {
		return ! version_compare( get_bloginfo( 'version' ), self::REGISTRY_VERSION, '<' );
	}

	/**
	 * Core Icon library slugs shipped by WordPress.
	 *
	 * @return array<string, true>
	 */
	public static function core_slugs(): array {
		if ( null !== self::$core_slugs ) {
			return self::$core_slugs;
		}

		self::$core_slugs = array();

		if ( ! defined( 'ABSPATH' ) || ! defined( 'WPINC' ) ) {
			return self::$core_slugs;
		}

		self::$core_slugs = self::read_manifest( ABSPATH . WPINC . '/assets/icon-library-manifest.php' );

		return self::$core_slugs;
	}

	/**
	 * Whether a slug is in the core icon library.
	 *
	 * @param string $slug Icon slug.
	 */
	public static function is_core_icon( string $slug ): bool {
		return isset( self::core_slugs()[ strtolower( $slug ) ] );
	}

	/**
	 * Reads the slugs from a core icon library manifest file.
	 *
	 * @param string $manifest Absolute path to `icon-library-manifest.php`.
	 *
	 * @return array<string, true> Empty when the file is missing or does not return an array.
	 */
	public static function read_manifest( string $manifest ): array {
		$slugs = array();

		if ( ! is_readable( $manifest ) ) {
			return $slugs;
		}

		$collection = include $manifest;

		if ( is_array( $collection ) ) {
			foreach ( array_keys( $collection ) as $slug ) {
				$slugs[ (string) $slug ] = true;
			}
		}

		return $slugs;
	}
}
