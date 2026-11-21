<?php
/**
 * Characterization tests for Aegis\Utilities\Data.
 *
 * Data decides "plugin or theme" with str_contains( $file, 'content/plugins' )
 * and 'content/themes' after normalising backslashes to '/', so Windows
 * __FILE__ paths work on every host. Data::from() caches per file in a static, so every test uses a
 * unique path.
 */

declare( strict_types=1 );

namespace AegisTests\Framework;

use Aegis\Utilities\Data;
use PHPUnit\Framework\TestCase;
use WP_Theme;

final class DataTest extends TestCase {

	private static int $counter = 0;

	protected function setUp(): void {
		Wp::reset();
	}

	private function plugin_file( ?string $name = null ): string {
		$name ??= 'plugin' . ( ++self::$counter );

		return "/var/www/wp-content/plugins/{$name}/{$name}.php";
	}

	public function test_plugin_path_populates_properties_from_plugin_headers(): void {
		Wp::$plugin_data = [
			'Name'        => 'My Plugin',
			'TextDomain'  => 'my-plugin',
			'Description' => 'Does things.',
			'Author'      => '<a href="https://example.test">Alex</a>',
			'AuthorURI'   => 'https://example.test',
			'Version'     => '1.2.3',
			'PluginURI'   => 'https://example.test/plugin',
			'DomainPath'  => '/languages',
			'RequiresWP'  => '7.0',
			'RequiresPHP' => '8.1',
			'UpdateURI'   => 'https://example.test/update',
		];
		$file = $this->plugin_file( 'my-plugin' );

		$data = new Data( $file );

		$this->assertSame( $file, $data->file );
		$this->assertSame( '/var/www/wp-content/plugins/my-plugin/', $data->dir );
		$this->assertSame( 'https://example.test/wp-content/plugins/my-plugin/', $data->url );
		$this->assertSame( 'my-plugin/my-plugin.php', $data->basename );
		$this->assertSame( 'My Plugin', $data->name );
		$this->assertSame( 'my-plugin', $data->slug );
		$this->assertSame( 'Does things.', $data->description );
		$this->assertSame( 'Alex', $data->author, 'Author markup is stripped.' );
		$this->assertSame( 'https://example.test', $data->author_uri );
		$this->assertSame( '1.2.3', $data->version );
		$this->assertSame( 'https://example.test/plugin', $data->uri );
		$this->assertSame( '/languages', $data->domain_path );
		$this->assertSame( '7.0', $data->min_wp );
		$this->assertSame( '8.1', $data->min_php );
		$this->assertSame( 'https://example.test/update', $data->update_uri );
	}

	public function test_missing_plugin_headers_become_empty_strings(): void {
		Wp::$plugin_data = [ 'Name' => 'Only Name' ];

		$data = new Data( $this->plugin_file() );

		$this->assertSame( 'Only Name', $data->name );
		$this->assertSame( '', $data->slug );
		$this->assertSame( '', $data->author );
		$this->assertSame( '', $data->version );
		$this->assertSame( '', $data->update_uri );
	}

	public function test_theme_path_reads_the_active_template_theme_not_the_given_file(): void {
		Wp::$template = 'aegis';
		Wp::$theme    = new WP_Theme(
			'/var/www/wp-content/themes/aegis',
			'https://example.test/wp-content/themes/aegis',
			'aegis',
			[
				'Name'        => 'Aegis',
				'Description' => 'Block theme.',
				'Author'      => 'Atmostfear',
				'AuthorURI'   => 'https://example.test',
				'Version'     => '2.0.0',
				'RequiresPHP' => '8.1',
				'RequiresWP'  => '7.0',
				'ThemeURI'    => 'https://example.test/theme',
				'DomainPath'  => '/languages',
				'UpdateURI'   => 'https://example.test/update',
			]
		);

		// A child theme's file path still yields the parent (template) theme's data.
		$data = new Data( '/var/www/wp-content/themes/aegis-child/functions.php' );

		$this->assertSame( '/var/www/wp-content/themes/aegis/', $data->dir );
		$this->assertSame( 'https://example.test/wp-content/themes/aegis/', $data->url );
		$this->assertSame( 'aegis', $data->slug );
		$this->assertSame( 'Aegis', $data->name );
		$this->assertSame( 'Block theme.', $data->description );
		$this->assertSame( 'Atmostfear', $data->author );
		$this->assertSame( '2.0.0', $data->version );
		$this->assertSame( 'https://example.test/theme', $data->uri );
		$this->assertSame( '8.1', $data->min_php );
		$this->assertSame( '7.0', $data->min_wp );
		$this->assertSame( '/languages', $data->domain_path );
		$this->assertSame( 'https://example.test/update', $data->update_uri );
		$this->assertSame( 0, Wp::$plugin_data_calls, 'Themes never read plugin headers.' );
	}

	public function test_theme_file_and_basename_use_single_forward_slashes(): void {
		Wp::$theme = new WP_Theme();

		$data = new Data( '/var/www/wp-content/themes/aegis/functions.php' );

		$this->assertSame( '/var/www/wp-content/themes/aegis/aegis.php', $data->file );
		$this->assertSame( 'aegis/aegis.php', $data->basename );
	}

	public function test_plugin_check_wins_when_path_matches_both(): void {
		Wp::$plugin_data = [ 'Name' => 'Plugin Won' ];
		Wp::$theme       = new WP_Theme( '/t', 'https://example.test/t', 't', [ 'Name' => 'Theme Won' ] );

		$data = new Data( '/var/www/wp-content/plugins/content/themes/x.php' );

		$this->assertSame( 'Plugin Won', $data->name );
	}

	public function test_unrecognised_path_leaves_every_property_empty(): void {
		$data = new Data( '/var/www/html/elsewhere/file.php' );

		$this->assertSame( '', $data->file );
		$this->assertSame( '', $data->dir );
		$this->assertSame( '', $data->url );
		$this->assertSame( '', $data->name );
		$this->assertSame( '', $data->slug );
		$this->assertSame( 0, Wp::$plugin_data_calls );
	}

	public function test_windows_backslash_plugin_path_is_recognised_like_a_forward_slash_path(): void {
		Wp::$plugin_data = [ 'Name' => 'Win Plugin', 'TextDomain' => 'win-plugin' ];

		$data = new Data( 'C:\\Sites\\aegis\\wp-content\\plugins\\win-plugin\\win-plugin.php' );

		$this->assertSame( 'Win Plugin', $data->name );
		$this->assertSame( 'win-plugin', $data->slug );
		$this->assertSame( 'C:/Sites/aegis/wp-content/plugins/win-plugin/win-plugin.php', $data->file );
		$this->assertSame( 'C:/Sites/aegis/wp-content/plugins/win-plugin/', $data->dir );
		$this->assertSame( 'win-plugin/win-plugin.php', $data->basename );
		$this->assertSame( 1, Wp::$plugin_data_calls );
	}

	public function test_forward_slash_windows_drive_plugin_path_is_recognised(): void {
		Wp::$plugin_data = [ 'Name' => 'Drive Plugin' ];

		$data = new Data( 'C:/Sites/aegis/wp-content/plugins/drive-plugin/drive-plugin.php' );

		$this->assertSame( 'Drive Plugin', $data->name );
		$this->assertSame( 'C:/Sites/aegis/wp-content/plugins/drive-plugin/', $data->dir );
	}

	public function test_windows_backslash_theme_path_is_recognised_like_a_forward_slash_path(): void {
		Wp::$theme = new WP_Theme( '/t', 'https://example.test/t', 't', [ 'Name' => 'Win Theme' ] );

		$data = new Data( 'C:\\Sites\\aegis\\wp-content\\themes\\aegis\\functions.php' );

		$this->assertSame( 'Win Theme', $data->name );
		$this->assertSame( 't', $data->slug );
		$this->assertSame( '/t/', $data->dir );
	}

	public function test_from_returns_the_same_instance_for_the_same_file_without_rereading(): void {
		Wp::$plugin_data = [ 'Name' => 'Cached' ];
		$file            = $this->plugin_file();

		$first = Data::from( $file );
		Wp::$plugin_data = [ 'Name' => 'Changed' ];
		$second          = Data::from( $file );

		$this->assertSame( $first, $second );
		$this->assertSame( 'Cached', $second->name );
		$this->assertSame( 1, Wp::$plugin_data_calls );
	}

	public function test_from_returns_distinct_instances_for_distinct_files(): void {
		Wp::$plugin_data = [ 'Name' => 'X' ];

		$this->assertNotSame( Data::from( $this->plugin_file() ), Data::from( $this->plugin_file() ) );
	}
}
