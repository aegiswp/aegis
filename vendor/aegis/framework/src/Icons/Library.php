<?php
/**
 * Registers Aegis icon sets with the WordPress Icon library.
 *
 * @package Aegis\Framework\Icons
 * @since   1.0.0
 */

declare( strict_types=1 );

namespace Aegis\Framework\Icons;

use Aegis\Icons\Icon;
use Aegis\Icons\IconSets;
use Aegis\Icons\IconSupport;
use Aegis\Utilities\Str;
use WP_Icon_Collections_Registry;
use WP_Icons_Registry;
use function basename;
use function filemtime;
use function function_exists;
use function get_option;
use function glob;
use function is_array;
use function is_string;
use function preg_match;
use function sprintf;
use function strtolower;
use function update_option;
use function __;

/**
 * Registers custom Aegis icon collections on WP_Icons_Registry.
 */
class Library {

	/**
	 * Registers Aegis icon collections and icons with WordPress.
	 *
	 * @hook init 11
	 */
	public function register(): void {
		if ( ! IconSupport::core_registry() ) {
			return;
		}

		if ( ! function_exists( 'wp_register_icon_collection' ) || ! function_exists( 'wp_register_icon' ) ) {
			return;
		}

		if ( ! IconFeatures::block_enabled() ) {
			return;
		}

		$sets = Icon::get_icon_sets();

		if ( ! is_array( $sets ) ) {
			return;
		}

		$cache       = get_option( self::FILES_OPTION, [] );
		$cache       = is_array( $cache ) ? $cache : [];
		$cache_dirty = false;

		foreach ( $sets as $set => $dir ) {
			$set = strtolower( (string) $set );

			if ( $set === '' || ! is_string( $dir ) || $dir === '' ) {
				continue;
			}

			if ( ! preg_match( '/^[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?$/', $set ) ) {
				continue;
			}

			$files = $this->get_svg_files( $dir, $cache, $cache_dirty );

			if ( $files === [] ) {
				continue;
			}

			$icons = [];

			foreach ( $files as $file ) {
				$name = strtolower( basename( (string) $file, '.svg' ) );

				if ( $name === '' || ! preg_match( '/^[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?$/', $name ) ) {
					continue;
				}

				if ( IconSets::is_wordpress( $set ) && IconSupport::is_core_icon( $name ) ) {
					continue;
				}

				$icons[ $name ] = (string) $file;
			}

			if ( $icons === [] ) {
				continue;
			}

			if ( ! WP_Icon_Collections_Registry::get_instance()->is_registered( $set ) ) {
				wp_register_icon_collection(
					$set,
					[
						'label'       => IconSets::label( $set ),
						'description' => sprintf(
							/* translators: %s: icon set label */
							__( 'Aegis %s icons.', 'aegis' ),
							IconSets::label( $set )
						),
					]
				);
			}

			$icons_registry = WP_Icons_Registry::get_instance();

			foreach ( $icons as $name => $file ) {
				$qualified = $set . '/' . $name;

				if ( $icons_registry->is_registered( $qualified ) ) {
					continue;
				}

				wp_register_icon(
					$qualified,
					[
						'label'     => Str::title_case( $name ),
						'file_path' => $file,
					]
				);
			}
		}

		if ( $cache_dirty ) {
			update_option( self::FILES_OPTION, $cache, false );
		}
	}

	/**
	 * Option caching each icon directory's SVG file names.
	 */
	private const FILES_OPTION = 'aegis_icon_files';

	/**
	 * SVG files in an icon directory.
	 *
	 * Listing thousands of icons on every request is slow on some hosts
	 * (open_basedir checks each match), so names are cached per directory
	 * and re-read when the directory's mtime changes, which adding,
	 * removing or renaming an icon does.
	 *
	 * @param string $dir   Icon directory.
	 * @param array  $cache Cached listings keyed by directory, updated in place.
	 * @param bool   $dirty Set to true when the cache changes.
	 *
	 * @return string[] Absolute file paths.
	 */
	private function get_svg_files( string $dir, array &$cache, bool &$dirty ): array {
		$mtime = @filemtime( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( $mtime === false ) {
			return [];
		}

		$entry = $cache[ $dir ] ?? null;

		if ( ! is_array( $entry ) || ( $entry['mtime'] ?? null ) !== $mtime || ! is_array( $entry['names'] ?? null ) ) {
			$files = glob( $dir . '/*.svg' );
			$names = [];

			foreach ( is_array( $files ) ? $files : [] as $file ) {
				$names[] = basename( (string) $file );
			}

			$entry         = [
				'mtime' => $mtime,
				'names' => $names,
			];
			$cache[ $dir ] = $entry;
			$dirty         = true;
		}

		$files = [];

		foreach ( $entry['names'] as $name ) {
			$files[] = $dir . '/' . $name;
		}

		return $files;
	}
}
