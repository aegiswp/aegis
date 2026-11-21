<?php
/**
 * Icon set discovery and the rules that depend on a set's identity.
 *
 * @package Aegis\Icons
 * @since   1.1.0
 */

declare( strict_types=1 );

namespace Aegis\Icons;

use Aegis\Utilities\Str;

use function apply_filters;
use function array_unique;
use function array_values;
use function basename;
use function count;
use function dirname;
use function get_stylesheet_directory;
use function get_template_directory;
use function glob;
use function in_array;
use function is_array;
use function is_dir;
use function is_string;
use function sort;
use function strtolower;
use function trim;
use function __;

use const GLOB_ONLYDIR;

/**
 * Where icon sets live, which set is the default, and how sets are listed.
 */
final class IconSets {

	/**
	 * Filter for the icon sets.
	 */
	public const FILTER = 'aegis_icon_sets';

	/**
	 * The default set: used for an empty set slug and as the home of core library icons.
	 */
	public const WORDPRESS = 'wordpress';

	/**
	 * Max SVG count loaded eagerly for the default catalog (button/tab picker).
	 * Larger packs load on demand via ?set=.
	 */
	public const EAGER_LIMIT = 80;

	/**
	 * Sets that always ship full SVGs in the default catalog.
	 */
	private const ALWAYS_EAGER = array( self::WORDPRESS, 'social', 'payment', 'brand' );

	/**
	 * Known large packs, stubbed without globbing (glob is slow on WASM).
	 */
	private const LARGE_PACKS = array( 'feather', 'hand-drawn', 'heroicons', 'phosphor-duotone', 'remixicon', 'lucide', 'tabler' );

	/**
	 * Directory holding the icon sets bundled with this package.
	 */
	public static function bundled_dir(): string {
		return dirname( __DIR__ ) . '/public/icons/';
	}

	/**
	 * Returns all icon sets and their directory path, after the filter.
	 *
	 * @return array<string, string> Set name => set path.
	 */
	public static function all(): array {
		$icon_sets = self::discover(
			self::bundled_dir(),
			get_template_directory() . '/assets/icons/',
			get_stylesheet_directory() . '/assets/icons/'
		);

		/**
		 * Filters the icon sets.
		 *
		 * @since 1.0.0
		 *
		 * @param array $icon_sets <string, string> Set name, set path.
		 */
		return apply_filters( self::FILTER, $icon_sets );
	}

	/**
	 * Finds the set directories under the bundled, parent and child icon folders.
	 *
	 * A set found in more than one folder resolves to the most specific one
	 * (child over parent over bundled). Keys keep discovery order.
	 *
	 * @param string $bundled_dir Bundled icons folder, with trailing slash.
	 * @param string $parent_dir  Parent theme icons folder, with trailing slash.
	 * @param string $child_dir   Child theme icons folder, with trailing slash.
	 *
	 * @return array<string, string> Set name => set path.
	 */
	public static function discover( string $bundled_dir, string $parent_dir, string $child_dir ): array {
		$dirs = array(
			...glob( $bundled_dir . '*', GLOB_ONLYDIR ),
			...glob( $parent_dir . '*', GLOB_ONLYDIR ),
			...glob( $child_dir . '*', GLOB_ONLYDIR ),
		);

		$icon_sets = array();

		foreach ( $dirs as $dir ) {
			$slug = basename( $dir );

			$bundled = $bundled_dir . $slug;
			$parent  = $parent_dir . $slug;
			$child   = $child_dir . $slug;

			if ( is_dir( $bundled ) ) {
				$icon_sets[ $slug ] = $bundled;
			}

			if ( is_dir( $parent ) ) {
				$icon_sets[ $slug ] = $parent;
			}

			if ( is_dir( $child ) ) {
				$icon_sets[ $slug ] = $child;
			}
		}

		return $icon_sets;
	}

	/**
	 * Whether a slug is the default set.
	 *
	 * @param string $set Set slug.
	 */
	public static function is_wordpress( string $set ): bool {
		return self::WORDPRESS === $set;
	}

	/**
	 * Normalizes an icon set slug (legacy "WordPress" becomes WordPress, empty becomes WordPress).
	 *
	 * @param string $set Set slug.
	 */
	public static function normalize( string $set ): string {
		$set = strtolower( trim( $set ) );

		return '' === $set ? self::WORDPRESS : $set;
	}

	/**
	 * Whether a set should ship full SVGs in the default catalog response.
	 *
	 * @param string               $set  Set slug.
	 * @param array<string,string> $dirs Icon set directories.
	 */
	public static function is_eager( string $set, array $dirs ): bool {
		if ( in_array( $set, self::ALWAYS_EAGER, true ) ) {
			return true;
		}

		if ( in_array( $set, self::LARGE_PACKS, true ) ) {
			return false;
		}

		$dir = $dirs[ $set ] ?? '';

		if ( '' === $dir || ! is_dir( $dir ) ) {
			return false;
		}

		$files = glob( $dir . '/*.svg' );

		return is_array( $files ) && count( $files ) <= self::EAGER_LIMIT;
	}

	/**
	 * Icon names in a set (no SVG bodies).
	 *
	 * @param string $set Set slug.
	 *
	 * @return list<string>
	 */
	public static function names( string $set ): array {
		$set       = self::normalize( $set );
		$icon_sets = self::all();
		$names     = array();

		if ( isset( $icon_sets[ $set ] ) && is_string( $icon_sets[ $set ] ) ) {
			$files = glob( $icon_sets[ $set ] . '/*.svg' );

			if ( is_array( $files ) ) {
				foreach ( $files as $file ) {
					$names[] = basename( (string) $file, '.svg' );
				}
			}
		}

		if ( self::is_wordpress( $set ) ) {
			foreach ( array_keys( IconSupport::core_slugs() ) as $core_slug ) {
				$names[] = (string) $core_slug;
			}
		}

		$names = array_values( array_unique( $names ) );
		sort( $names );

		return $names;
	}

	/**
	 * Human-readable label of a set.
	 *
	 * @param string $set Set slug.
	 */
	public static function label( string $set ): string {
		$labels = array(
			self::WORDPRESS    => __( 'Aegis', 'aegis' ),
			'social'           => __( 'Social', 'aegis' ),
			'payment'          => __( 'Payment', 'aegis' ),
			'remixicon'        => __( 'Remix Icon', 'aegis' ),
			'plugin'           => __( 'Plugins', 'aegis' ),
			'hand-drawn'       => __( 'Hand Drawn', 'aegis' ),
			'phosphor-duotone' => __( 'Phosphor Duotone', 'aegis' ),
			'heroicons'        => __( 'Heroicons', 'aegis' ),
			'feather'          => __( 'Feather', 'aegis' ),
			'brand'            => __( 'Brand', 'aegis' ),
		);

		return $labels[ $set ] ?? Str::title_case( $set );
	}
}
