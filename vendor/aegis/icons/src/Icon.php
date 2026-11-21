<?php
/**
 * Icon Utilities
 *
 * Public facade of the icons package: renders SVG icons from the filesystem and
 * delegates set discovery (IconSets), registry ids (IconRegistryId), the core
 * manifest and version gates (IconSupport), the REST route (IconRest) and SVG
 * sanitising (IconSanitizer) to their own classes.
 *
 * @package    Aegis\Icons
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/theme
 *
 * Public static methods are unchanged; see the package classes for the logic.
 */

// Enforces strict type checking for all code in this file, ensuring type safety for icon utilities.
declare( strict_types=1 );

// Declares the namespace for the icon utilities.
namespace Aegis\Icons;

// Imports classes, interfaces, and functions used by the icon utilities.
use Aegis\Dom\CSS;
use Aegis\Dom\DOM;
use Aegis\Utilities\Str;

use DOMDocument;
use DOMElement;

use WP_REST_Request;

use function apply_filters;
use function explode;
use function esc_html__;
use function file_get_contents;
use function function_exists;
use function implode;
use function in_array;
use function is_numeric;
use function is_string;
use function str_replace;
use function strtolower;
use function trim;
use function uniqid;

/**
 * Icon utility class.
 *
 * @since 1.0.0
 */
class Icon {

	const FILTER = IconSets::FILTER;

	/**
	 * Returns array of all icon sets and their directory path.
	 *
	 * @since 1.0.0
	 *
	 * @return array <string, string>
	 */
	public static function get_icon_sets(): array {
		return IconSets::all();
	}
	/**
	 * Returns SVG string for given icon.
	 *
	 * @since 1.0.0
	 *
	 * @param string          $set   Icon set.
	 * @param string          $name  Icon name.
	 * @param string|int|null $size  Icon size.
	 * @param ?string         $title Icon title.
	 *
	 * @return string
	 */
	public static function get_svg( string $set, string $name, $size = null, ?string $title = '' ): string {
		$set = strtolower( $set );

		static $cache = [];

		$cache_key = implode( '-', [ $set, $name, $size ] );

		if ( isset( $cache[ $cache_key ] ) ) {
			return $cache[ $cache_key ];
		}

		$icon_sets = static::get_icon_sets();

		if ( ! isset( $icon_sets[ $set ] ) ) {
			return '';
		}

		// The name can come from block attributes, so it is a file name, never a path.
		if ( $name === '' || preg_match( '#[\\\\/\0]|\.\.#', $name ) ) {
			return '';
		}

		$dir  = $icon_sets[ $set ];
		$file = $dir . '/' . $name . '.svg';
		$real = realpath( $file );
		$root = realpath( $dir );
		$safe = $real && $root && str_starts_with( $real, $root . DIRECTORY_SEPARATOR );

		if ( ! $safe ) {
			if ( IconSets::is_wordpress( $set ) && function_exists( 'wp_get_icon' ) ) {
				$core = wp_get_icon(
					'core/' . $name,
					[
						'size' => is_numeric( $size ) ? (int) $size : 24,
					]
				);

				if ( is_string( $core ) && $core !== '' ) {
					$cache[ $cache_key ] = $core;

					return $core;
				}
			}

			return '';
		}

		$html = file_get_contents( $real );

		$dom = DOM::create( trim( $html ) );
		$svg = DOM::get_element( 'svg', $dom );

		if ( ! $svg ) {
			return '';
		}

		$unique_id = 'icon-' . uniqid();

		$svg->setAttribute( 'role', 'img' );
		$svg->setAttribute( 'aria-labelledby', $unique_id );
		$svg->setAttribute( 'data-icon', $set . '-' . $name );

		$label     = $title ?: Str::title_case( $name ) . __( ' Icon', 'aegis' );
		$title_tag = DOM::create_element( 'title', $dom );

		$title_tag->appendChild( $dom->createTextNode( $label ) );
		$title_tag->setAttribute( 'id', $unique_id );

		$svg->insertBefore( $title_tag, $svg->firstChild );

		if ( $size ) {
			$has_unit = Str::contains_any( (string) $size, 'px', 'em', 'rem', '%', 'vh', 'vw' );

			if ( $has_unit ) {
				$styles = CSS::string_to_array( $svg->getAttribute( 'style' ) );

				$styles['min-width'] = $size;

				// Payment card marks use a 3:2 frame; a square height crushes them in the editor.
				if ( static::is_payment_card_set( $set ) ) {
					$styles['width']        = $size;
					$styles['height']       = 'auto';
					$styles['aspect-ratio'] = '3 / 2';
				} else {
					$styles['height'] = $size;
				}

				$svg->setAttribute( 'style', CSS::array_to_string( $styles ) );
			} else {
				$svg->setAttribute( 'width', (string) $size );

				if ( static::is_payment_card_set( $set ) ) {
					$svg->setAttribute( 'height', (string) ( (float) $size * 2 / 3 ) );
				} else {
					$svg->setAttribute( 'height', (string) $size );
				}
			}
		}

		static::apply_theme_color( $svg );

		$cache[ $cache_key ] = static::sanitize_svg( $dom->saveHTML() );

		return $cache[ $cache_key ];
	}

	/**
	 * Makes hardcoded black fills/strokes follow the current text color.
	 */
	private static function apply_theme_color( DOMElement $svg ): void {
		$fill = strtolower( trim( $svg->getAttribute( 'fill' ) ) );

		if ( $fill === '' || static::is_literal_ink( $fill ) ) {
			$svg->setAttribute( 'fill', 'currentColor' );
		}

		$stroke = strtolower( trim( $svg->getAttribute( 'stroke' ) ) );

		if ( static::is_literal_ink( $stroke ) ) {
			$svg->setAttribute( 'stroke', 'currentColor' );
		}

		foreach ( $svg->getElementsByTagName( '*' ) as $el ) {
			if ( ! $el instanceof DOMElement ) {
				continue;
			}

			$child_fill = strtolower( trim( $el->getAttribute( 'fill' ) ) );

			if ( static::is_literal_ink( $child_fill ) ) {
				$el->setAttribute( 'fill', 'currentColor' );
			}

			$child_stroke = strtolower( trim( $el->getAttribute( 'stroke' ) ) );

			if ( static::is_literal_ink( $child_stroke ) ) {
				$el->setAttribute( 'stroke', 'currentColor' );
			}
		}
	}

	/**
	 * Whether an icon set uses Visa-style 3:2 payment card frames.
	 */
	private static function is_payment_card_set( string $set ): bool {
		return str_starts_with( $set, 'payment' );
	}

	/**
	 * Whether a paint value is a hardcoded dark ink color.
	 */
	private static function is_literal_ink( string $value ): bool {
		$value = strtolower( str_replace( ' ', '', $value ) );

		return in_array(
			$value,
			[ '#000', '#000000', 'black', '#111', '#111111', '#1e1e1e', '#0a0a0a', 'rgb(0,0,0)' ],
			true
		);
	}

	/**
	 * Registers icon REST endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param string $namespace Route namespace.
	 * @param string $route     Route path.
	 *
	 * @return void
	 */
	public static function register_rest_route( string $namespace = IconRest::REST_NAMESPACE, string $route = IconRest::ROUTE ): void {
		IconRest::register( $namespace, $route );
	}
	/**
	 * Returns placeholder icon dom element.
	 *
	 * @since 1.0.0
	 *
	 * @param DOMDocument $dom DOM document.
	 *
	 * @return DOMElement
	 */
	public static function get_placeholder( DOMDocument $dom ): DOMElement {
		$svg_title = esc_html__( 'Image placeholder', 'aegis' );
		$svg_icon  = <<<HTML
<svg xmlns="http://www.w3.org/2000/svg" role="img" viewBox="0 0 64 64" width="32" height="32">
	<title>$svg_title</title>
	<circle cx="52" cy="18" r="7"/>
	<path d="M47 32.1 39 41 23 20.9 0 55.1h64z"/>
</svg>
HTML;

		/**
		 * Filters the SVG icon for the placeholder image.
		 *
		 * @since 1.0.0
		 *
		 * @param string $svg_icon  SVG icon.
		 * @param string $svg_title SVG title.
		 */
		$svg_icon    = apply_filters( 'aegis_placeholder_svg', $svg_icon, $svg_title );
		$svg_icon    = static::sanitize_svg( $svg_icon );
		$svg_dom     = DOM::create( $svg_icon );
		$svg_element = DOM::get_element( 'svg', $svg_dom );

		if ( ! $svg_element ) {
			return DOM::create_element( 'span', $dom );
		}

		$svg_classes   = explode( ' ', $svg_element->getAttribute( 'class' ) );
		$svg_classes[] = 'wp-block-image__placeholder-icon';

		$svg_element->setAttribute( 'class', implode( ' ', $svg_classes ) );
		$svg_element->setAttribute( 'fill', 'currentColor' );

		$imported = $dom->importNode( $svg_element, true );

		return DOM::node_to_element( $imported );
	}

	/**
	 * Normalizes icon set slugs (legacy "WordPress" → wordpress).
	 */
	public static function normalize_set_slug( string $set ): string {
		return IconSets::normalize( $set );
	}

	/**
	 * Icon names in a set (no SVG bodies).
	 *
	 * @return list<string>
	 */
	public static function get_set_icon_names( string $set ): array {
		return IconSets::names( $set );
	}

	/**
	 * Full SVG map for one icon set.
	 *
	 * @return array<string, string> name => svg
	 */
	public static function get_set_icons( string $set ): array {
		return IconRest::set_icons( $set );
	}

	/**
	 * Returns icon data for rest endpoint.
	 *
	 * Default (no params): catalog — small sets with SVGs, large packs as empty
	 * stubs so the button/tab picker can list them and load via ?set=.
	 * ?set=slug — one set with full SVGs.
	 * ?sets=1 — set slug list only.
	 * ?full=1 — every set with SVGs (legacy / WP 7.0 merge; avoid in editor).
	 *
	 * @since 1.0.0
	 *
	 * @param ?WP_REST_Request $request Request object.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_icon_data( ?WP_REST_Request $request ) {
		return IconRest::data( $request );
	}

	/**
	 * Builds a registry-style icon identifier for the Aegis namespace.
	 *
	 * @since 1.0.0
	 *
	 * @param string $set  Icon set slug.
	 * @param string $name Icon file slug.
	 *
	 * @return string e.g. social/facebook or core/plus
	 */
	public static function to_registry_id( string $set, string $name ): string {
		return IconRegistryId::build( $set, $name );
	}

	/**
	 * Parses a registry icon identifier.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id Icon id (`core/{name}`, `{set}/{name}`, or legacy `aegis/{set}/{name}`).
	 *
	 * @return array{namespace: string, set: string, name: string}|null
	 */
	public static function from_registry_id( string $id ): ?array {
		return IconRegistryId::parse( $id );
	}

	/**
	 * Core Icon library slugs shipped by WordPress.
	 *
	 * @return array<string, true>
	 */
	public static function get_core_library_slugs(): array {
		return IconSupport::core_slugs();
	}

	/**
	 * Sanitizes SVG string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $svg SVG string.
	 *
	 * @return string
	 */
	public static function sanitize_svg( string $svg ): string {
		return IconSanitizer::sanitize( $svg );
	}
}
