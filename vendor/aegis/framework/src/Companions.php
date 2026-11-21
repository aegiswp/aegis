<?php
/**
 * Resolves the companion plugin's answers.
 *
 * @package Aegis\Framework
 */

declare( strict_types=1 );

namespace Aegis\Framework;

use function apply_filters;
use function did_action;

/**
 * Resolves the active {@see Companion}.
 */
final class Companions {

	/**
	 * The cached companion, or null until resolved.
	 *
	 * @var Companion|null
	 */
	private static ?Companion $resolved = null;

	/**
	 * The shared fallback companion.
	 *
	 * @var NullCompanion|null
	 */
	private static ?NullCompanion $null = null;

	/**
	 * The companion for this request.
	 *
	 * Caching rule: plugins load before the theme and add their filter when their file loads, but
	 * cannot name framework classes then, so the framework resolves lazily on first use. A Companion
	 * returned by the filter is cached at once; the NullCompanion fallback is cached only after
	 * `plugins_loaded`, because before that a plugin that has not loaded yet could still register one.
	 *
	 * @filter aegis_framework_companion Return a Companion, or the input unchanged.
	 */
	public static function get(): Companion {
		if ( null !== self::$resolved ) {
			return self::$resolved;
		}

		$companion = apply_filters( 'aegis_framework_companion', null );

		if ( $companion instanceof Companion ) {
			self::$resolved = $companion;

			return $companion;
		}

		self::$null ??= new NullCompanion();

		if ( did_action( 'plugins_loaded' ) ) {
			self::$resolved = self::$null;
		}

		return self::$null;
	}
}
