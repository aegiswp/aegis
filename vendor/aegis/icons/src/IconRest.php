<?php
/**
 * The icon REST route used by the editor's button/tab icon picker.
 *
 * @package Aegis\Icons
 * @since   1.1.0
 */

declare( strict_types=1 );

namespace Aegis\Icons;

use WP_REST_Request;
use WP_REST_Server;

use function add_action;
use function array_keys;
use function basename;
use function current_user_can;
use function get_transient;
use function glob;
use function is_array;
use function is_string;
use function register_rest_route;
use function set_transient;

use const DAY_IN_SECONDS;

/**
 * Registers `aegis/v1/icons` and builds its responses.
 */
final class IconRest {

	/**
	 * Route namespace.
	 */
	public const REST_NAMESPACE = 'aegis/v1';

	/**
	 * Route path inside the namespace.
	 */
	public const ROUTE = '/icons/';

	/**
	 * Full route as the editor requests it (no trailing slash).
	 */
	public const PATH = '/' . self::REST_NAMESPACE . '/icons';

	/**
	 * Registers the icon REST endpoint.
	 *
	 * @param string $namespace Route namespace.
	 * @param string $route     Route path.
	 */
	public static function register( string $namespace = self::REST_NAMESPACE, string $route = self::ROUTE ): void {
		static $registered = array();

		if ( isset( $registered[ $namespace ] ) ) {
			return;
		}

		$registered[ $namespace ] = $route;

		$args = array(
			'permission_callback' => static fn() => current_user_can( 'edit_posts' ),
			'callback'            => static fn( WP_REST_Request $request ): array => self::data( $request ),
			'methods'             => WP_REST_Server::READABLE,
			'args'                => array(
				'sets' => array(
					'required'          => false,
					'type'              => 'string',
					'description'       => 'When set, return only icon set slugs.',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'set'  => array(
					'required'          => false,
					'type'              => 'string',
					'description'       => 'Load one set with full SVG markup (lazy picker).',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'full' => array(
					'required'    => false,
					'type'        => 'boolean',
					'description' => 'Load every set with SVGs (heavy; avoid in the editor).',
					'default'     => false,
				),
			),
		);

		add_action(
			'rest_api_init',
			static fn() => register_rest_route( $namespace, $route, $args )
		);
	}

	/**
	 * Returns icon data for the REST endpoint.
	 *
	 * Default (no params): catalog, with small sets as SVGs and large packs as empty
	 * stubs so the button/tab picker can list them and load via ?set=.
	 * ?set=slug returns one set with full SVGs.
	 * ?sets=1 returns the set slug list only.
	 * ?full=1 returns every set with SVGs (legacy / WP 7.0 merge; avoid in editor).
	 *
	 * @param ?WP_REST_Request $request Request object.
	 *
	 * @return array<int|string, mixed>
	 */
	public static function data( ?WP_REST_Request $request ) {
		$icon_sets = IconSets::all();

		if ( ! isset( $icon_sets[ IconSets::WORDPRESS ] ) ) {
			$icon_sets[ IconSets::WORDPRESS ] = '';
		}

		if ( $request && $request->get_param( 'sets' ) ) {
			return array_keys( $icon_sets );
		}

		if ( $request && $request->get_param( 'set' ) ) {
			return self::set_icons( (string) $request->get_param( 'set' ) );
		}

		$force_full = $request && $request->get_param( 'full' );

		$icon_data = array();

		foreach ( array_keys( $icon_sets ) as $icon_set ) {
			$set = IconSets::normalize( (string) $icon_set );

			if ( $force_full || IconSets::is_eager( $set, $icon_sets ) ) {
				$icon_data[ $set ] = self::set_icons( $set );
			} else {
				// Stub: set appears in Select Icon Set; SVGs load via ?set=.
				$icon_data[ $set ] = array();
			}
		}

		return $icon_data;
	}

	/**
	 * Full SVG map for one icon set.
	 *
	 * @param string $set Set slug.
	 *
	 * @return array<string, string> name => svg
	 */
	public static function set_icons( string $set ): array {
		$set = IconSets::normalize( $set );

		$cache_key = 'aegis_icon_set_v1_' . $set;
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$icon_sets = IconSets::all();
		$icons     = array();

		if ( isset( $icon_sets[ $set ] ) && is_string( $icon_sets[ $set ] ) ) {
			$files = glob( $icon_sets[ $set ] . '/*.svg' );

			if ( is_array( $files ) ) {
				foreach ( $files as $file ) {
					$name           = basename( (string) $file, '.svg' );
					$icons[ $name ] = Icon::get_svg( $set, $name, 24, null );
				}
			}
		}

		if ( IconSets::is_wordpress( $set ) ) {
			foreach ( array_keys( IconSupport::core_slugs() ) as $core_slug ) {
				if ( ! isset( $icons[ $core_slug ] ) ) {
					$icons[ $core_slug ] = Icon::get_svg( IconSets::WORDPRESS, (string) $core_slug, 24, null );
				}
			}
		}

		// Only cache real sets, so a made-up ?set= value cannot add database rows.
		if ( isset( $icon_sets[ $set ] ) || IconSets::is_wordpress( $set ) ) {
			set_transient( $cache_key, $icons, DAY_IN_SECONDS );
		}

		return $icons;
	}
}
