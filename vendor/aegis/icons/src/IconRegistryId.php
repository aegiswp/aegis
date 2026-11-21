<?php
/**
 * Icon registry identifiers: `core/{name}` and `{set}/{name}`.
 *
 * @package Aegis\Icons
 * @since   1.1.0
 */

declare( strict_types=1 );

namespace Aegis\Icons;

use function explode;
use function strtolower;
use function trim;

/**
 * Builds and parses registry-style icon ids.
 */
final class IconRegistryId {

	/**
	 * Builds a registry id from a set and an icon name.
	 *
	 * The WordPress set maps to `core/{name}` for names in the core library.
	 *
	 * @param string $set  Icon set slug.
	 * @param string $name Icon file slug.
	 *
	 * @return string e.g. social/facebook or core/plus
	 */
	public static function build( string $set, string $name ): string {
		$set  = strtolower( $set );
		$name = strtolower( $name );

		if ( IconSets::is_wordpress( $set ) && isset( IconSupport::core_slugs()[ $name ] ) ) {
			return 'core/' . $name;
		}

		return $set . '/' . $name;
	}

	/**
	 * Parses a registry id.
	 *
	 * @param string $id Icon id (`core/{name}`, `{set}/{name}`, or legacy `aegis/{set}/{name}`).
	 *
	 * @return array{namespace: string, set: string, name: string}|null
	 */
	public static function parse( string $id ): ?array {
		$id = strtolower( trim( $id ) );

		if ( '' === $id ) {
			return null;
		}

		$parts = explode( '/', $id );

		if ( ( $parts[1] ?? '' ) === '' ) {
			return null;
		}

		if ( 'core' === $parts[0] && isset( $parts[1] ) && ! isset( $parts[2] ) ) {
			return array(
				'namespace' => 'core',
				'set'       => IconSets::WORDPRESS,
				'name'      => $parts[1],
			);
		}

		if ( 'aegis' === $parts[0] && isset( $parts[1], $parts[2] ) && ! isset( $parts[3] ) ) {
			return array(
				'namespace' => 'aegis',
				'set'       => $parts[1],
				'name'      => $parts[2],
			);
		}

		if ( isset( $parts[0], $parts[1] ) && ! isset( $parts[2] ) ) {
			return array(
				'namespace' => 'aegis',
				'set'       => $parts[0],
				'name'      => $parts[1],
			);
		}

		return null;
	}

	/**
	 * Rewrites any id to its current form.
	 *
	 * An id that does not parse becomes `wordpress/` (the WordPress set, no name).
	 *
	 * @param string $id Icon id in any accepted form.
	 */
	public static function canonical( string $id ): string {
		$parsed = self::parse( $id );

		if ( null === $parsed ) {
			return self::build( IconSets::WORDPRESS, '' );
		}

		return self::build( $parsed['set'], $parsed['name'] );
	}
}
