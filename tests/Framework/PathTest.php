<?php
/**
 * Characterization tests for Aegis\Utilities\Path.
 *
 * Path normalises backslashes to '/' first, so a Windows __FILE__ path and the
 * forward-slash paths WordPress returns behave identically on every host. All
 * outputs use forward slashes. Style-sensitive tests run for both styles.
 */

declare( strict_types=1 );

namespace AegisTests\Framework;

use Aegis\Utilities\Path;
use PHPUnit\Framework\TestCase;

final class PathTest extends TestCase {

	private const THEMES_URL = 'https://example.test/wp-content/themes/aegis/';

	/** Convert a POSIX-style fixture path to the host's native style. */
	private function native( string $posix ): string {
		return str_replace( '/', DIRECTORY_SEPARATOR, $posix );
	}

	/** @return array<string, array{string}> */
	public static function styles(): array {
		return [
			'forward slashes' => [ '/' ],
			'backslashes'     => [ '\\' ],
		];
	}

	private function style( string $posix, string $sep ): string {
		return str_replace( '/', $sep, $posix );
	}

	public function test_get_segment_takes_the_last_n_segments(): void {
		$path = $this->native( 'www/wp-content/themes/aegis/vendor/aegis/framework' );

		$this->assertSame( 'vendor/aegis/framework', Path::get_segment( $path, -3 ) );
		$this->assertSame( 'framework', Path::get_segment( $path, -1 ) );
	}

	public function test_get_segment_takes_the_first_n_segments_for_a_positive_count(): void {
		$path = $this->native( 'www/wp-content/themes/aegis' );

		$this->assertSame( 'www/wp-content', Path::get_segment( $path, 2 ) );
	}

	public function test_get_segment_wrap_adds_leading_and_trailing_slash(): void {
		$path = $this->native( 'www/wp-content/themes/aegis' );

		$this->assertSame( '/themes/aegis/', Path::get_segment( $path, -2, true ) );
	}

	public function test_get_segment_always_joins_with_forward_slashes(): void {
		$path = $this->native( 'a/b/c' );

		$this->assertSame( 'a/b/c', Path::get_segment( $path, 3 ) );
	}

	public function test_get_segment_ignores_leading_and_trailing_native_separators(): void {
		$path = $this->native( '/a/b/c/' );

		$this->assertSame( 'b/c', Path::get_segment( $path, -2 ) );
	}

	public function test_get_segment_with_more_segments_than_exist_returns_all(): void {
		$this->assertSame( 'a/b', Path::get_segment( $this->native( 'a/b' ), -5 ) );
	}

	public function test_get_segment_with_zero_returns_everything_after_the_first_split(): void {
		// array_slice( $x, -0 ) is the whole array: 0 is not "no segments".
		$this->assertSame( 'a/b/c', Path::get_segment( $this->native( 'a/b/c' ), 0 ) );
	}

	public function test_get_package_dir_appends_the_last_three_package_segments_to_the_project(): void {
		$project = $this->native( 'www/wp-content/themes/aegis' );
		$package = $this->native( 'www/wp-content/themes/aegis/vendor/aegis/framework' );

		$this->assertSame( 'www/wp-content/themes/aegis/vendor/aegis/framework/', Path::get_package_dir( $project, $package ) );
	}

	public function test_get_package_dir_normalises_a_trailing_slash_on_the_project(): void {
		$project = $this->native( 'www/wp-content/themes/aegis/' );
		$package = $this->native( 'x/vendor/aegis/framework' );

		$this->assertSame( 'www/wp-content/themes/aegis/vendor/aegis/framework/', Path::get_package_dir( $project, $package ) );
	}

	public function test_get_package_url_is_project_url_plus_package_segments(): void {
		$project = $this->native( 'www/wp-content/themes/aegis/' );
		$package = $this->native( 'www/wp-content/themes/aegis/vendor/aegis/framework' );

		$this->assertSame( self::THEMES_URL . 'vendor/aegis/framework/', Path::get_package_url( $project, $package ) );
	}

	public function test_get_project_url_uses_the_last_two_segments_under_content_url(): void {
		$this->assertSame( self::THEMES_URL, Path::get_project_url( $this->native( 'www/wp-content/themes/aegis/' ) ) );
		$this->assertSame( self::THEMES_URL, Path::get_project_url( $this->native( 'www/wp-content/themes/aegis' ) ) );
	}

	public function test_get_project_dir_is_three_levels_above_with_trailing_slash(): void {
		$package = $this->native( 'www/wp-content/themes/aegis/vendor/aegis/framework' );

		$this->assertSame( 'www/wp-content/themes/aegis/', Path::get_project_dir( $package ) );
	}

	// -- Both path styles, on every host -------------------------------------

	/** @dataProvider styles */
	public function test_get_segment_splits_either_path_style( string $sep ): void {
		$path = $this->style( 'www/wp-content/themes/aegis/vendor/aegis/framework', $sep );

		$this->assertSame( 'vendor/aegis/framework', Path::get_segment( $path, -3 ) );
		$this->assertSame( '/vendor/aegis/framework/', Path::get_segment( $path, -3, true ) );
		$this->assertSame( 'www/wp-content', Path::get_segment( $path, 2 ) );
	}

	/** @dataProvider styles */
	public function test_get_segment_ignores_leading_and_trailing_separators_in_either_style( string $sep ): void {
		$this->assertSame( 'b/c', Path::get_segment( $this->style( '/a/b/c/', $sep ), -2 ) );
	}

	/** @dataProvider styles */
	public function test_get_package_dir_handles_either_path_style( string $sep ): void {
		$project = $this->style( 'www/wp-content/themes/aegis', $sep );
		$package = $this->style( 'www/wp-content/themes/aegis/vendor/aegis/framework', $sep );

		$this->assertSame( 'www/wp-content/themes/aegis/vendor/aegis/framework/', Path::get_package_dir( $project, $package ) );
		$this->assertSame(
			'www/wp-content/themes/aegis/vendor/aegis/framework/',
			Path::get_package_dir( $project . $sep, $package ),
			'A trailing separator on the project is dropped.'
		);
	}

	/** @dataProvider styles */
	public function test_get_package_and_project_urls_handle_either_path_style( string $sep ): void {
		$project = $this->style( 'www/wp-content/themes/aegis', $sep );
		$package = $this->style( 'www/wp-content/themes/aegis/vendor/aegis/framework', $sep );

		$this->assertSame( self::THEMES_URL, Path::get_project_url( $project ) );
		$this->assertSame( self::THEMES_URL . 'vendor/aegis/framework/', Path::get_package_url( $project, $package ) );
	}

	/** @dataProvider styles */
	public function test_get_project_dir_handles_either_path_style( string $sep ): void {
		$package = $this->style( 'www/wp-content/themes/aegis/vendor/aegis/framework', $sep );

		$this->assertSame( 'www/wp-content/themes/aegis/', Path::get_project_dir( $package ) );
	}

	public function test_get_project_dir_keeps_a_windows_drive_prefix(): void {
		$this->assertSame(
			'C:/Sites/aegis/wp-content/themes/aegis/',
			Path::get_project_dir( 'C:\\Sites\\aegis\\wp-content\\themes\\aegis\\vendor\\aegis\\framework' )
		);
	}

	public function test_normalize_converts_backslashes_and_leaves_forward_slash_paths_alone(): void {
		$this->assertSame( 'C:/a/b/c.php', Path::normalize( 'C:\\a\\b\\c.php' ) );
		$this->assertSame( '/a/b/c.php', Path::normalize( '/a/b/c.php' ) );
	}
}
