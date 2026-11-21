<?php
/**
 * SVG sanitising for icon markup.
 *
 * @package Aegis\Icons
 * @since   1.1.0
 */

declare( strict_types=1 );

namespace Aegis\Icons;

use enshrined\svgSanitize\Sanitizer;

use function chr;
use function class_exists;
use function hexdec;
use function is_null;
use function preg_match;
use function preg_replace;
use function preg_replace_callback;
use function str_contains;
use function str_replace;
use function trim;

/**
 * Cleans SVG strings and fixes up escaped markup.
 */
final class IconSanitizer {

	/**
	 * Sanitizes an SVG string.
	 *
	 * Without the sanitizer library nothing is output (fail closed).
	 *
	 * @param string $svg SVG string.
	 */
	public static function sanitize( string $svg ): string {
		$svg = self::decode( $svg );

		if ( ! class_exists( 'enshrined\svgSanitize\Sanitizer' ) ) {
			return '';
		}

		static $sanitizer = null;

		if ( is_null( $sanitizer ) ) {
			$sanitizer = new Sanitizer();

			$sanitizer->minify( true );
			$sanitizer->removeXMLTag( true );
		}

		$sanitized = $sanitizer->sanitize( $svg );
		$svg       = $sanitized ? $sanitized : '';

		// Remove comments.
		$svg = preg_replace( '/<!--(.|\s)*?-->/', '', $svg );

		// Remove new lines.
		$svg = preg_replace( '/\s+/', ' ', $svg );

		// Remove tabs.
		$svg = preg_replace( '/\t+/', '', $svg );

		// Remove spaces between tags.
		$svg = preg_replace( '/>\s+</', '><', $svg );

		// Correct viewBox.
		$svg = str_replace( 'viewbox=', 'viewBox=', $svg );

		return trim( $svg );
	}

	/**
	 * Normalizes SVG markup that was JSON-hex-escaped without backslashes.
	 *
	 * Some editors persist `svgString` as `u003csvg…u0022…` instead of `<svg…"…`.
	 * Decodes those sequences before sanitizing.
	 *
	 * @param string $svg Raw SVG attribute value.
	 */
	private static function decode( string $svg ): string {
		$svg = trim( $svg );

		if ( '' === $svg || str_contains( $svg, '<svg' ) || str_contains( $svg, '<SVG' ) ) {
			return $svg;
		}

		// Bare JSON hex escapes (backslash stripped): u003c → <, u0022 → ".
		if ( preg_match( '/u00[0-9a-fA-F]{2}/', $svg ) ) {
			$svg = (string) preg_replace_callback(
				'/u00([0-9a-fA-F]{2})/',
				static fn( array $matches ): string => chr( (int) hexdec( $matches[1] ) ),
				$svg
			);
		}

		return $svg;
	}
}
