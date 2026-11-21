<?php
/**
 * Bootstrap for the framework characterization tests.
 *
 * Loads the Composer autoloader and defines the minimum set of WordPress
 * function stubs the tested code calls. Stubs are recorders or thin
 * re-implementations; their state lives in AegisTests\Framework\Wp.
 */

declare( strict_types=1 );

namespace AegisTests\Framework {

	/**
	 * Shared state for the global WordPress stubs below.
	 */
	final class Wp {

		/** @var array<int, array{tag: string, callback: mixed, priority: mixed, accepted_args: mixed, via: string}> */
		public static array $hooks = [];

		/** @var array<string, mixed> Return value of get_plugin_data(). */
		public static array $plugin_data = [];

		public static int $plugin_data_calls = 0;

		public static ?\WP_Theme $theme = null;

		public static string $template = 'aegis';

		/** @var array<int, array{tag: string, args: array}> */
		public static array $filters_applied = [];

		/** @var array<string, mixed> Value apply_filters() returns for a tag instead of echoing its input. */
		public static array $filter_results = [];

		/** @var array<string, int> Counts did_action() reports. */
		public static array $did_actions = [];

		/** @var array<int, array{fn: string, args: array}> load_*_textdomain() calls. */
		public static array $textdomains = [];

		/** @var string Value get_bloginfo( 'version' ) returns. */
		public static string $wp_version = '7.1';

		public static string $template_dir = '';

		public static string $stylesheet_dir = '';

		/** @var array<string, mixed> Transients written by set_transient(); get_transient() always misses. */
		public static array $transients = [];

		/** @var array<int, array{namespace: string, route: string, args: array}> register_rest_route() calls. */
		public static array $rest_routes = [];

		/** @var array<int, string> current_user_can() checks. */
		public static array $caps_checked = [];

		public static function reset(): void {
			self::$rest_routes       = [];
			self::$caps_checked      = [];
			self::$wp_version        = '7.1';
			self::$template_dir      = '';
			self::$stylesheet_dir    = '';
			self::$transients        = [];
			self::$textdomains       = [];
			self::$hooks             = [];
			self::$plugin_data       = [];
			self::$plugin_data_calls = 0;
			self::$theme             = null;
			self::$template          = 'aegis';
			self::$filters_applied   = [];
			self::$filter_results    = [];
			self::$did_actions       = [];
		}
	}
}

namespace {

	use AegisTests\Framework\Wp;

	require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

	// Fixtures are plain classes (not PSR-4 mapped), so load them explicitly.
	foreach ( glob( __DIR__ . '/Fixtures/*.php' ) ?: [] as $fixture ) {
		require_once $fixture;
	}

	if ( ! class_exists( 'WP_Theme' ) ) {
		/** Minimal stand-in exposing only what Aegis\Utilities\Data reads. */
		class WP_Theme { // phpcs:ignore
			/** @param array<string, string> $headers */
			public function __construct(
				public string $template_dir = '/var/www/wp-content/themes/aegis',
				public string $template_uri = 'https://example.test/wp-content/themes/aegis',
				public string $template = 'aegis',
				public array $headers = []
			) {}

			public function get_template_directory(): string {
				return $this->template_dir;
			}

			public function get_template_directory_uri(): string {
				return $this->template_uri;
			}

			public function get_template(): string {
				return $this->template;
			}

			public function get( string $header ) {
				// Real WP_Theme fills known headers with '' when the theme omits them.
				return $this->headers[ $header ] ?? '';
			}
		}
	}

	if ( ! function_exists( 'add_filter' ) ) {
		function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) { // phpcs:ignore
			Wp::$hooks[] = compact( 'tag', 'callback', 'priority', 'accepted_args' ) + [ 'via' => 'add_filter' ];
			return true;
		}
	}

	if ( ! function_exists( 'add_action' ) ) {
		function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) { // phpcs:ignore
			Wp::$hooks[] = compact( 'tag', 'callback', 'priority', 'accepted_args' ) + [ 'via' => 'add_action' ];
			return true;
		}
	}

	if ( ! function_exists( 'apply_filters' ) ) {
		function apply_filters( $tag, $value, ...$args ) { // phpcs:ignore
			Wp::$filters_applied[] = [ 'tag' => $tag, 'args' => [ $value, ...$args ] ];
			return array_key_exists( $tag, Wp::$filter_results ) ? Wp::$filter_results[ $tag ] : $value;
		}
	}

	if ( ! function_exists( 'did_action' ) ) {
		function did_action( $tag ) { // phpcs:ignore
			return Wp::$did_actions[ $tag ] ?? 0;
		}
	}

	if ( ! function_exists( 'untrailingslashit' ) ) {
		function untrailingslashit( $value ) { // phpcs:ignore
			return rtrim( $value, '/\\' ); // Same as WordPress.
		}
	}

	if ( ! function_exists( 'trailingslashit' ) ) {
		function trailingslashit( $value ) { // phpcs:ignore
			return untrailingslashit( $value ) . '/'; // Same as WordPress.
		}
	}

	if ( ! function_exists( 'content_url' ) ) {
		function content_url( $path = '' ) { // phpcs:ignore
			return 'https://example.test/wp-content' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
		}
	}

	if ( ! function_exists( 'get_plugin_data' ) ) {
		function get_plugin_data( $file ) { // phpcs:ignore
			++Wp::$plugin_data_calls;
			return Wp::$plugin_data;
		}
	}

	if ( ! function_exists( 'plugin_dir_url' ) ) {
		function plugin_dir_url( $file ) { // phpcs:ignore
			return 'https://example.test/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
		}
	}

	if ( ! function_exists( 'plugin_basename' ) ) {
		function plugin_basename( $file ) { // phpcs:ignore
			return basename( dirname( $file ) ) . '/' . basename( $file );
		}
	}

	if ( ! function_exists( 'get_template' ) ) {
		function get_template() { // phpcs:ignore
			return Wp::$template;
		}
	}

	if ( ! function_exists( 'wp_get_theme' ) ) {
		function wp_get_theme( $stylesheet = '' ) { // phpcs:ignore
			return Wp::$theme ?? new WP_Theme();
		}
	}

	if ( ! function_exists( 'load_theme_textdomain' ) ) {
		function load_theme_textdomain( $domain, $path = false ) { // phpcs:ignore
			Wp::$textdomains[] = [ 'fn' => 'load_theme_textdomain', 'args' => [ $domain, $path ] ];
			return true;
		}
	}

	if ( ! function_exists( 'load_plugin_textdomain' ) ) {
		function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) { // phpcs:ignore
			Wp::$textdomains[] = [ 'fn' => 'load_plugin_textdomain', 'args' => [ $domain, $deprecated, $plugin_rel_path ] ];
			return true;
		}
	}

	// Stubs for the icons package tests.

	if ( ! function_exists( 'get_bloginfo' ) ) {
		function get_bloginfo( $show = '' ) { // phpcs:ignore
			return $show === 'version' ? Wp::$wp_version : '';
		}
	}

	if ( ! function_exists( 'get_template_directory' ) ) {
		function get_template_directory() { // phpcs:ignore
			return Wp::$template_dir;
		}
	}

	if ( ! function_exists( 'get_stylesheet_directory' ) ) {
		function get_stylesheet_directory() { // phpcs:ignore
			return Wp::$stylesheet_dir;
		}
	}

	if ( ! function_exists( 'get_transient' ) ) {
		function get_transient( $key ) { // phpcs:ignore
			return false;
		}
	}

	if ( ! function_exists( 'set_transient' ) ) {
		function set_transient( $key, $value, $expiration = 0 ) { // phpcs:ignore
			Wp::$transients[ $key ] = $value;
			return true;
		}
	}

	if ( ! defined( 'DAY_IN_SECONDS' ) ) {
		define( 'DAY_IN_SECONDS', 86400 );
	}

	if ( ! function_exists( '__' ) ) {
		function __( $text, $domain = 'default' ) { // phpcs:ignore
			return $text;
		}
	}

	if ( ! function_exists( 'esc_html__' ) ) {
		function esc_html__( $text, $domain = 'default' ) { // phpcs:ignore
			return $text;
		}
	}

	if ( ! function_exists( 'esc_html' ) ) {
		function esc_html( $text ) { // phpcs:ignore
			return htmlspecialchars( (string) $text, ENT_QUOTES );
		}
	}

	if ( ! function_exists( 'capital_P_dangit' ) ) {
		function capital_P_dangit( $text ) { // phpcs:ignore
			return $text;
		}
	}

	if ( ! function_exists( 'wp_json_encode' ) ) {
		function wp_json_encode( $value, $flags = 0, $depth = 512 ) { // phpcs:ignore
			return json_encode( $value, $flags, $depth );
		}
	}

	if ( ! function_exists( 'register_rest_route' ) ) {
		function register_rest_route( $namespace, $route, $args = [] ) { // phpcs:ignore
			Wp::$rest_routes[] = compact( 'namespace', 'route', 'args' );
			return true;
		}
	}

	if ( ! function_exists( 'current_user_can' ) ) {
		function current_user_can( $capability ) { // phpcs:ignore
			Wp::$caps_checked[] = $capability;
			return true;
		}
	}

	if ( ! class_exists( 'WP_REST_Server' ) ) {
		class WP_REST_Server { // phpcs:ignore
			public const READABLE = 'GET';
		}
	}

	if ( ! class_exists( 'WP_REST_Request' ) ) {
		/** Minimal stand-in: method, route and params only. */
		class WP_REST_Request { // phpcs:ignore
			/** @var array<string, mixed> */
			private array $params = [];

			public function __construct( private string $method = 'GET', private string $route = '' ) {}

			public function get_method() {
				return $this->method;
			}

			public function get_route() {
				return $this->route;
			}

			public function set_param( $key, $value ) {
				$this->params[ $key ] = $value;
			}

			public function get_param( $key ) {
				return $this->params[ $key ] ?? null;
			}
		}
	}
}
