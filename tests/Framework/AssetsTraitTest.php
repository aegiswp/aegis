<?php
/**
 * Characterization tests for Aegis\Framework\InlineAssets\AssetsTrait.
 *
 * get_assets() decides which registered strings, callbacks and files are
 * emitted for a template. The two `condition` flags are used inconsistently
 * (strings: OR; files: AND-ish gate), which is pinned below.
 */

declare( strict_types=1 );

namespace AegisTests\Framework;

use AegisTests\Framework\Fixtures\FakeScripts;
use AegisTests\Framework\Fixtures\FakeStyles;
use PHPUnit\Framework\TestCase;

final class AssetsTraitTest extends TestCase {

	private string $base;

	private string $root;

	private string $assets_dir;

	protected function setUp(): void {
		Wp::reset();
		$this->base       = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'aegis-fw-tests-' . uniqid();
		$this->root       = $this->base . DIRECTORY_SEPARATOR . 'Theme-Dir';
		$this->assets_dir = $this->root . '/vendor/aegis/framework/public/js';
		mkdir( $this->assets_dir, 0777, true );
		file_put_contents( $this->assets_dir . '/a.js', "var a = 1;\nvar b = 2;\n" );
	}

	protected function tearDown(): void {
		$this->remove_tree( $this->base );
	}

	private function remove_tree( string $path ): void {
		if ( is_file( $path ) ) {
			unlink( $path );
			return;
		}

		foreach ( glob( $path . '/*' ) ?: [] as $child ) {
			$this->remove_tree( $child );
		}

		if ( is_dir( $path ) ) {
			rmdir( $path );
		}
	}

	private function scripts(): FakeScripts {
		return new FakeScripts( $this->root . DIRECTORY_SEPARATOR . 'main.php' );
	}

	// -- Constructor ----------------------------------------------------------

	public function test_constructor_derives_dir_url_type_and_handle_from_the_main_file(): void {
		$scripts = $this->scripts();

		// Path normalises backslashes (Windows temp dirs) to '/'.
		$this->assertSame( \Aegis\Utilities\Path::normalize( $this->root ) . '/vendor/aegis/framework/public/js/', $scripts->dir );
		$this->assertSame(
			'https://example.test/wp-content/' . basename( $this->base ) . '/Theme-Dir/vendor/aegis/framework/public/js/',
			$scripts->url
		);
		$this->assertSame( 'theme-dir', $scripts->handle, 'Handle is the lower-cased project folder name.' );
	}

	public function test_class_name_containing_style_switches_the_asset_type_to_css(): void {
		$styles = new FakeStyles( $this->root . DIRECTORY_SEPARATOR . 'main.php' );

		$this->assertStringEndsWith( '/public/css/', $styles->dir );
		$this->assertStringEndsWith( '/public/css/', $styles->url );
	}

	// -- Strings --------------------------------------------------------------

	/**
	 * @dataProvider string_matrix
	 *
	 * @param string[] $needles
	 */
	public function test_string_inclusion_matrix( array $needles, bool $condition, bool $load_all, string $html, bool $included ): void {
		$scripts = $this->scripts()->add_string( 'S', $needles, $condition );

		$this->assertSame( $included ? [ 'S' ] : [], array_values( $scripts->assets( $html, $load_all ) ) );
	}

	/** @return array<string, array{string[], bool, bool, string, bool}> */
	public static function string_matrix(): array {
		return [
			'no needles: always'                           => [ [], true, false, '', true ],
			'no needles, condition false: still always'    => [ [], false, false, '', true ],
			'needle missing but condition true: included'  => [ [ 'x' ], true, false, 'nope', true ], // Odd: condition ORs with the match.
			'needle missing, condition false: excluded'    => [ [ 'x' ], false, false, 'nope', false ],
			'needle present, condition false: included'    => [ [ 'x' ], false, false, 'has x', true ],
			'load_all overrides a missing needle'          => [ [ 'x' ], false, true, 'nope', true ],
			'any one of several needles is enough'         => [ [ 'x', 'y' ], false, false, 'has y', true ],
		];
	}

	// -- Files ----------------------------------------------------------------

	/**
	 * @dataProvider file_matrix
	 *
	 * @param string[] $needles
	 */
	public function test_file_inclusion_matrix( array $needles, bool $condition, bool $load_all, string $html, bool $included ): void {
		$scripts = $this->scripts()->add_file( 'a.js', $needles, $condition );

		$this->assertSame( $included, isset( $scripts->assets( $html, $load_all )['a.js'] ) );
	}

	/** @return array<string, array{string[], bool, bool, string, bool}> */
	public static function file_matrix(): array {
		return [
			'no needles: always'                               => [ [], true, false, '', true ],
			'needle present: included'                         => [ [ 'x' ], true, false, 'has x', true ],
			// Pins an inconsistency with add_string(): here condition=true does NOT force inclusion.
			'needle missing, condition true: excluded'         => [ [ 'x' ], true, false, 'nope', false ],
			'load_all overrides a missing needle'              => [ [ 'x' ], true, true, 'nope', true ],
			'condition false, needle present: included'        => [ [ 'x' ], false, false, 'has x', true ],
			// Pins an oddity: a false condition gate ignores load_all.
			'condition false, load_all, needle missing: skip'  => [ [ 'x' ], false, true, 'nope', false ],
			'condition false, no needles, load_all: skipped'   => [ [], false, true, '', false ],
			'condition false, no needles: skipped'             => [ [], false, false, '', false ],
		];
	}

	public function test_file_contents_are_read_verbatim_and_keyed_by_file_name(): void {
		$assets = $this->scripts()->add_file( 'a.js' )->assets( '', false );

		$this->assertSame( [ 'a.js' => "var a = 1;\nvar b = 2;\n" ], $assets );
	}

	public function test_missing_files_are_silently_skipped(): void {
		$assets = $this->scripts()->add_file( 'nope.js' )->assets( '', true );

		$this->assertSame( [], $assets );
	}

	public function test_re_adding_the_same_file_overwrites_its_rule(): void {
		$scripts = $this->scripts()->add_file( 'a.js', [ 'x' ], true )->add_file( 'a.js', [], true );

		$this->assertArrayHasKey( 'a.js', $scripts->assets( 'nothing', false ) );
	}

	// -- Callbacks ------------------------------------------------------------

	public function test_closure_callbacks_always_run_with_html_load_all_and_the_instance(): void {
		$seen    = [];
		$scripts = $this->scripts()->add_callback(
			static function ( string $html, bool $load_all, object $instance ) use ( &$seen ): string {
				$seen = [ $html, $load_all, $instance ];
				return 'from-closure';
			}
		);

		$assets = $scripts->assets( '<p>x</p>', true );

		$this->assertSame( [ 'from-closure' ], array_values( $assets ) );
		$this->assertSame( [ '<p>x</p>', true, $scripts ], $seen );
	}

	public function test_array_callbacks_are_keyed_by_class_and_method_so_repeats_collapse(): void {
		$scripts = $this->scripts();
		$scripts->add_callback( [ $scripts, 'callback_method' ] )->add_callback( [ $scripts, 'callback_method' ] );

		$assets = $scripts->assets( 'H', false );

		$this->assertSame( [ FakeScripts::class . '\\callback_method' => 'method:H' ], $assets );
	}

	public function test_two_closures_are_both_kept(): void {
		$scripts = $this->scripts()
			->add_callback( static fn(): string => 'one' )
			->add_callback( static fn(): string => 'two' );

		$this->assertSame( [ 'one', 'two' ], array_values( $scripts->assets( '', false ) ) );
	}

	// -- get_inline_assets ----------------------------------------------------

	public function test_inline_assets_concatenate_strings_then_callbacks_then_files_in_that_order(): void {
		$scripts = $this->scripts()
			->add_file( 'a.js' )
			->add_callback( static fn(): string => 'cb;' )
			->add_string( 'str;' );

		$this->assertSame( "str;cb;var a = 1;\nvar b = 2;", $scripts->inline( '', false ) );
	}

	public function test_inline_assets_only_trim_the_edges_of_each_asset(): void {
		// Pins an oddity in Str::remove_line_breaks(): interior line breaks survive.
		$scripts = $this->scripts()->add_string( "  a\nb  " );

		$this->assertSame( "a\nb", $scripts->inline( '', false ) );
	}

	public function test_inline_assets_treat_null_html_as_empty_and_apply_the_handle_filter(): void {
		$scripts = $this->scripts()->add_string( 'x', [ 'needle' ], false );

		$result = $scripts->inline( null, false );

		$this->assertSame( '', $result, 'No match against empty html and condition false.' );
		$this->assertCount( 1, Wp::$filters_applied );
		$this->assertSame( 'theme-dir_inline_js', Wp::$filters_applied[0]['tag'] );
		$this->assertSame( [ '', null, false, $scripts ], Wp::$filters_applied[0]['args'] );
	}
}
