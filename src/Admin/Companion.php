<?php
/**
 * Companion plugin identity
 *
 * Single owner of the Aegis Dash plugin's basename, minimum version and
 * installed / active / version-met checks, shared by the onboarding notice and
 * the GitHub updater.
 *
 * @package Aegis
 * @since   1.0.1
 */

declare( strict_types=1 );

namespace Aegis\Admin;

/**
 * Describes the Aegis Dash companion plugin and its install state.
 */
final class Companion {

	/**
	 * Plugin basename relative to wp-content/plugins.
	 *
	 * @var string
	 */
	public const PLUGIN_FILE = 'aegis-dash/aegis-dash.php';

	/**
	 * Plugin directory slug (folder name inside the release zip).
	 *
	 * @var string
	 */
	public const PLUGIN_SLUG = 'aegis-dash';

	/**
	 * First plugin version that provides the Slider, Toggle, Countdown and Related Posts blocks.
	 *
	 * @var string
	 */
	public const BLOCKS_MIN_VERSION = '1.1.0';

	/**
	 * Whether the plugin is present in wp-content/plugins.
	 *
	 * @return bool
	 */
	public static function is_installed(): bool {
		return is_readable( WP_PLUGIN_DIR . '/' . self::PLUGIN_FILE );
	}

	/**
	 * Whether the plugin is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( self::PLUGIN_FILE );
	}

	/**
	 * Whether the plugin is installed, active and at least the given version.
	 *
	 * Reads the plugin header directly, so it works without the plugin loaded.
	 *
	 * @param string $required Minimum plugin version.
	 *
	 * @return bool
	 */
	public static function requirement_met( string $required ): bool {
		// A loaded plugin reports its own version, whatever its folder is called.
		if ( defined( 'Aegis\Plugin\VERSION' ) ) {
			return version_compare( (string) constant( 'Aegis\Plugin\VERSION' ), $required, '>=' );
		}

		if ( ! self::is_installed() || ! self::is_active() ) {
			return false;
		}

		$data      = get_file_data( WP_PLUGIN_DIR . '/' . self::PLUGIN_FILE, array( 'Version' => 'Version' ) );
		$installed = (string) ( $data['Version'] ?? '' );

		return '' !== $installed && version_compare( $installed, $required, '>=' );
	}
}
