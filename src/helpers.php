<?php
/**
 * Theme template helpers used by block patterns.
 *
 * @package Aegis
 * @since   1.0.0
 */

declare( strict_types=1 );

if ( ! function_exists( 'aegis_url' ) ) {
	/**
	 * Returns an absolute site URL for a path.
	 *
	 * @param string $path Site path, e.g. `/contact/`.
	 */
	function aegis_url( string $path = '' ): string {
		return home_url( $path );
	}
}

if ( ! function_exists( 'aegis_wc_page_url' ) ) {
	/**
	 * Returns a WooCommerce page URL, with sensible fallbacks when WooCommerce is inactive.
	 *
	 * @param string $page WooCommerce page key, e.g. `myaccount`.
	 */
	function aegis_wc_page_url( string $page ): string {
		if ( function_exists( 'wc_get_page_permalink' ) ) {
			$permalink = wc_get_page_permalink( $page );

			if ( is_string( $permalink ) && '' !== $permalink ) {
				return $permalink;
			}
		}

		$fallbacks = array(
			'shop'      => '/shop/',
			'cart'      => '/cart/',
			'checkout'  => '/checkout/',
			'myaccount' => '/my-account/',
		);

		return home_url( $fallbacks[ $page ] ?? '/' );
	}
}

if ( ! function_exists( 'aegis_pattern_partial' ) ) {
	/**
	 * Echoes a shared block-markup partial from `src/PatternPartials/`.
	 *
	 * Pattern files use it where several patterns share the same markup. A child
	 * theme overrides a partial by shipping the same path. The partial reads its
	 * inputs from `$args`; the final newline of the partial file is not output.
	 *
	 * @param string               $name   File name without `.php`, lowercase kebab-case.
	 * @param array<string, mixed> $args   Values handed to the partial as `$args`.
	 * @param int                  $indent Tabs to put in front of every non-empty output line.
	 */
	function aegis_pattern_partial( string $name, array $args = array(), int $indent = 0 ): void {
		$file = 1 === preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $name )
			? get_theme_file_path( 'src/PatternPartials/' . $name . '.php' )
			: '';

		if ( '' === $file || ! is_readable( $file ) ) {
			if ( function_exists( '_doing_it_wrong' ) ) {
				_doing_it_wrong( __FUNCTION__, 'Unknown pattern partial.', '1.1.0' );
			}

			return;
		}

		ob_start();
		include $file;
		$output = (string) ob_get_clean();

		if ( str_ends_with( $output, "\n" ) ) {
			$output = substr( $output, 0, -1 );
		}

		if ( $indent > 0 ) {
			$output = (string) preg_replace( '/^(?=.)/m', str_repeat( "\t", $indent ), $output );
		}

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup from the theme's own partials.
	}
}
