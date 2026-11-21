<?php
/**
 * Recorder stubs for the WordPress 7.1 icon registry. Not auto-loaded: tests that
 * need them require this file inside a separate process, because the functions and
 * classes cannot be undefined.
 */

declare( strict_types=1 );

namespace {

	final class IconRegistryRecorder {

		/** @var array<string, array> */
		public static array $collections = [];

		/** @var array<string, array> */
		public static array $icons = [];

		/** @var array<string, mixed> */
		public static array $options = [];

		/** @var array<int, string> */
		public static array $option_writes = [];
	}

	class WP_Icon_Collections_Registry { // phpcs:ignore
		public static function get_instance(): self {
			return new self();
		}

		public function is_registered( string $slug ): bool {
			return isset( IconRegistryRecorder::$collections[ $slug ] );
		}
	}

	class WP_Icons_Registry { // phpcs:ignore
		public static function get_instance(): self {
			return new self();
		}

		public function is_registered( string $name ): bool {
			return isset( IconRegistryRecorder::$icons[ $name ] );
		}
	}

	function wp_register_icon_collection( $slug, $args ) { // phpcs:ignore
		IconRegistryRecorder::$collections[ $slug ] = $args;
		return true;
	}

	function wp_register_icon( $name, $args ) { // phpcs:ignore
		IconRegistryRecorder::$icons[ $name ] = $args;
		return true;
	}

	function get_option( $key, $default = false ) { // phpcs:ignore
		return IconRegistryRecorder::$options[ $key ] ?? $default;
	}

	function update_option( $key, $value, $autoload = null ) { // phpcs:ignore
		IconRegistryRecorder::$options[ $key ] = $value;
		IconRegistryRecorder::$option_writes[] = $key;
		return true;
	}
}
