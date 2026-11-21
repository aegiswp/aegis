<?php
/**
 * Characterization tests for Aegis\Utilities\I18n::load_textdomain().
 *
 * Data (built from a real or Windows-style path) decides theme vs plugin;
 * I18n then calls the matching WordPress loader with a path derived from it.
 */

declare( strict_types=1 );

namespace AegisTests\Framework;

use Aegis\Utilities\Data;
use Aegis\Utilities\I18n;
use PHPUnit\Framework\TestCase;
use WP_Theme;

final class I18nTest extends TestCase {

	private static int $counter = 0;

	protected function setUp(): void {
		Wp::reset();
	}

	/** @return array<string, array{string}> */
	public static function styles(): array {
		return [
			'forward slashes' => [ '/' ],
			'backslashes'     => [ '\\' ],
		];
	}

	private function plugin_data( string $sep, string $domain_path = '/languages' ): Data {
		$name            = 'i18n-plugin' . ( ++self::$counter );
		Wp::$plugin_data = [ 'TextDomain' => $name, 'DomainPath' => $domain_path ];
		$file            = str_replace( '/', $sep, "C:/Sites/aegis/wp-content/plugins/{$name}/{$name}.php" );

		return new Data( $file );
	}

	/** @dataProvider styles */
	public function test_plugin_loads_its_domain_from_the_plugin_relative_path_in_either_path_style( string $sep ): void {
		$data = $this->plugin_data( $sep );

		( new I18n( $data ) )->load_textdomain();

		$this->assertSame(
			[ [ 'fn' => 'load_plugin_textdomain', 'args' => [ $data->slug, false, $data->slug . '/languages' ] ] ],
			Wp::$textdomains
		);
	}

	public function test_plugin_without_a_domain_path_defaults_to_languages(): void {
		$data = $this->plugin_data( '/', '' );

		( new I18n( $data ) )->load_textdomain();

		$this->assertSame( $data->slug . '/languages', Wp::$textdomains[0]['args'][2] );
	}

	/** @dataProvider styles */
	public function test_theme_loads_its_domain_from_the_theme_directory_in_either_path_style( string $sep ): void {
		Wp::$theme = new WP_Theme(
			'C:/Sites/aegis/wp-content/themes/aegis',
			'https://example.test/wp-content/themes/aegis',
			'aegis',
			[ 'DomainPath' => '/languages' ]
		);
		$data = new Data( str_replace( '/', $sep, 'C:/Sites/aegis/wp-content/themes/aegis/functions.php' ) . $sep . 'x' . ( ++self::$counter ) );

		( new I18n( $data ) )->load_textdomain();

		$this->assertSame(
			[ [ 'fn' => 'load_theme_textdomain', 'args' => [ 'aegis', 'C:/Sites/aegis/wp-content/themes/aegis/languages' ] ] ],
			Wp::$textdomains
		);
	}

	public function test_an_unrecognised_path_has_no_slug_and_loads_nothing(): void {
		( new I18n( new Data( '/var/www/html/elsewhere/file.php' ) ) )->load_textdomain();

		$this->assertSame( [], Wp::$textdomains );
	}
}
