<?php
/**
 * Characterization tests for the icons package and the framework icon classes
 * that call it: set discovery, registry ids, the core manifest reader, version
 * gates and the eager/lazy set rules.
 *
 * Several cases depend on constants (ABSPATH), function-local statics or
 * undefinable stubs, so they run in their own process.
 */

declare( strict_types=1 );

namespace AegisTests\Framework;

use Aegis\Framework\CoreBlocks\IconBlockAttributes;
use Aegis\Framework\Icons\IconMigrationMapper;
use Aegis\Framework\Icons\Library;
use Aegis\Framework\Icons\RestIconsMerge;
use Aegis\Icons\Icon;
use Aegis\Icons\IconRegistryId;
use Aegis\Icons\IconRest;
use Aegis\Icons\IconSets;
use Aegis\Icons\IconSupport;
use AegisTests\Framework\Fixtures\FakeCompanion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use WP_REST_Request;

final class IconsTest extends TestCase {

	private const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M0 0h24v24H0z"/></svg>';

	private string $tmp = '';

	protected function setUp(): void {
		Wp::reset();

		$this->tmp = str_replace( '\\', '/', sys_get_temp_dir() ) . '/aegis-icons-' . uniqid();
		mkdir( $this->tmp, 0777, true );
	}

	protected function tearDown(): void {
		$this->rm( $this->tmp );
	}

	private function rm( string $path ): void {
		if ( is_dir( $path ) ) {
			foreach ( scandir( $path ) ?: [] as $entry ) {
				if ( $entry !== '.' && $entry !== '..' ) {
					$this->rm( $path . '/' . $entry );
				}
			}

			rmdir( $path );
		} elseif ( file_exists( $path ) ) {
			unlink( $path );
		}
	}

	private function put( string $relative, string $contents = self::SVG ): string {
		$path = $this->tmp . '/' . $relative;

		if ( ! is_dir( dirname( $path ) ) ) {
			mkdir( dirname( $path ), 0777, true );
		}

		file_put_contents( $path, $contents );

		return $path;
	}

	/**
	 * Points the WordPress ABSPATH/WPINC constants at a fixture tree holding a core icon manifest.
	 *
	 * @param string[] $slugs Core icon slugs.
	 */
	private function define_manifest( array $slugs ): void {
		$body = '<?php return [ ' . implode( ', ', array_map( static fn( $s ) => "'$s' => [ 'label' => '$s' ]", $slugs ) ) . ' ];';

		$this->put( 'wp/wp-includes/assets/icon-library-manifest.php', $body );

		define( 'ABSPATH', $this->tmp . '/wp/' );
		define( 'WPINC', 'wp-includes' );
	}

	/**
	 * Sets the template/stylesheet dirs to fixture trees under the temp dir.
	 */
	private function use_theme_dirs(): void {
		Wp::$template_dir   = $this->tmp . '/parent';
		Wp::$stylesheet_dir = $this->tmp . '/child';
	}

	// Registry ids.

	/**
	 * @return array<string, array{0: string, 1: ?array}>
	 */
	public static function parse_cases(): array {
		return [
			'core id'                  => [ 'core/plus', [ 'namespace' => 'core', 'set' => 'wordpress', 'name' => 'plus' ] ],
			'set/name'                 => [ 'social/facebook', [ 'namespace' => 'aegis', 'set' => 'social', 'name' => 'facebook' ] ],
			'legacy aegis/set/name'    => [ 'aegis/social/facebook', [ 'namespace' => 'aegis', 'set' => 'social', 'name' => 'facebook' ] ],
			'aegis/x is set aegis'     => [ 'aegis/x', [ 'namespace' => 'aegis', 'set' => 'aegis', 'name' => 'x' ] ],
			'trim and lowercase'       => [ '  Core/Plus ', [ 'namespace' => 'core', 'set' => 'wordpress', 'name' => 'plus' ] ],
			'empty'                    => [ '', null ],
			'whitespace only'          => [ '   ', null ],
			'no slash'                 => [ 'plus', null ],
			'empty name'               => [ 'social/', null ],
			'empty set keeps name'     => [ '/plus', [ 'namespace' => 'aegis', 'set' => '', 'name' => 'plus' ] ],
			'core with extra segment'  => [ 'core/a/b', null ],
			'four segments'            => [ 'aegis/a/b/c', null ],
			'three segments not aegis' => [ 'social/a/b', null ],
		];
	}

	#[DataProvider( 'parse_cases' )]
	public function test_from_registry_id( string $id, ?array $expected ): void {
		$this->assertSame( $expected, Icon::from_registry_id( $id ) );
	}

	public function test_to_registry_id_without_a_manifest_never_returns_core(): void {
		$this->assertSame( 'wordpress/plus', Icon::to_registry_id( 'wordpress', 'plus' ) );
		$this->assertSame( 'social/facebook', Icon::to_registry_id( 'Social', 'Facebook' ) );
		$this->assertSame( 'wordpress/', Icon::to_registry_id( 'wordpress', '' ) );
		$this->assertSame( [], Icon::get_core_library_slugs() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_to_registry_id_uses_core_for_manifest_slugs_in_the_wordpress_set_only(): void {
		$this->define_manifest( [ 'plus', 'star' ] );

		$this->assertSame( 'core/plus', Icon::to_registry_id( 'wordpress', 'plus' ) );
		$this->assertSame( 'core/star', Icon::to_registry_id( 'WordPress', 'STAR' ) );
		$this->assertSame( 'wordpress/nope', Icon::to_registry_id( 'wordpress', 'nope' ) );
		$this->assertSame( 'social/plus', Icon::to_registry_id( 'social', 'plus' ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_core_manifest_reader(): void {
		$this->define_manifest( [ 'plus', 'star' ] );

		$this->assertSame( [ 'plus' => true, 'star' => true ], Icon::get_core_library_slugs() );
		$this->assertSame(
			[ 'coreManifestSlugs' => [ 'plus', 'star' ] ],
			IconMigrationMapper::get_rules_for_editor()
		);
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_core_manifest_reader_ignores_a_manifest_that_is_not_an_array(): void {
		$this->put( 'wp/wp-includes/assets/icon-library-manifest.php', '<?php return "nope";' );
		define( 'ABSPATH', $this->tmp . '/wp/' );
		define( 'WPINC', 'wp-includes' );

		$this->assertSame( [], Icon::get_core_library_slugs() );
		$this->assertSame( [ 'coreManifestSlugs' => [] ], IconMigrationMapper::get_rules_for_editor() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_core_manifest_reader_without_a_manifest_file(): void {
		define( 'ABSPATH', $this->tmp . '/missing/' );
		define( 'WPINC', 'wp-includes' );

		$this->assertSame( [], Icon::get_core_library_slugs() );
		$this->assertSame( [ 'coreManifestSlugs' => [] ], IconMigrationMapper::get_rules_for_editor() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_migration_mapper_rewrites_wordpress_ids_by_the_manifest(): void {
		$this->define_manifest( [ 'plus' ] );

		$content = '<!-- wp:icon {"icon":"wordpress/plus"} /--><!-- wp:icon {"icon": "wordpress/nope"} /--><!-- wp:icon {"icon":"wordpress/"} /-->';

		$this->assertSame(
			'<!-- wp:icon {"icon":"core/plus"} /--><!-- wp:icon {"icon":"wordpress/nope"} /--><!-- wp:icon {"icon":"wordpress/"} /-->',
			IconMigrationMapper::normalize_icon_ids_in_content( $content )
		);
		$this->assertSame(
			'<!-- wp:icon {"icon":"core/plus"} /-->',
			IconMigrationMapper::migrate_block_comments( '<!-- wp:icon {"icon":"aegis/wordpress/plus"} /-->' )
		);
	}

	public function test_migration_mapper_resolves_legacy_image_attributes(): void {
		$this->assertSame(
			[ 'icon' => 'social/facebook' ],
			IconMigrationMapper::map_image_icon_to_core_icon( [ 'iconSet' => 'Social', 'iconName' => 'Facebook' ] )
		);
		// No set means the wordpress set; no name means no icon.
		$this->assertSame(
			[ 'icon' => 'wordpress/plus' ],
			IconMigrationMapper::map_image_icon_to_core_icon( [ 'iconName' => 'plus' ] )
		);
		$this->assertSame( [], IconMigrationMapper::map_image_icon_to_core_icon( [ 'iconSet' => 'social' ] ) );
		$this->assertSame(
			'<!-- wp:icon {"icon":"social/x"} /-->',
			IconMigrationMapper::migrate_block_comments( '<!-- wp:icon {"icon":"aegis/social/x"} /-->' )
		);
	}

	public function test_heading_icon_map_canonicalises_icon_ids_and_maps_unparseable_ids_to_the_wordpress_set(): void {
		$this->put(
			'patterns/a.php',
			'<!-- wp:icon {"icon":"foo"} /--><h3>One</h3>'
			. '<!-- wp:icon {"icon":"aegis/social/x"} /--><h3>Two</h3>'
			. '<!-- wp:icon {"icon":"core/plus","style":{"dimensions":{"width":"2em"}}} /--><h3>Three</h3>'
			. '<!-- wp:icon {"icon":"wordpress/star"} /--><h3>Four</h3>'
			. '<!-- wp:icon {"icon":"a/b/c/d"} /--><h3>Five</h3>'
		);

		$this->assertSame(
			[
				'one'   => [ 'icon' => 'wordpress/' ],
				'two'   => [ 'icon' => 'social/x' ],
				'three' => [ 'icon' => 'wordpress/plus', 'width' => '2em' ],
				'four'  => [ 'icon' => 'wordpress/star' ],
				'five'  => [ 'icon' => 'wordpress/' ],
			],
			IconMigrationMapper::heading_icon_map_from_patterns( $this->tmp . '/patterns' )
		);
	}

	// Set discovery.

	public function test_normalize_set_slug(): void {
		$this->assertSame( 'wordpress', Icon::normalize_set_slug( '' ) );
		$this->assertSame( 'wordpress', Icon::normalize_set_slug( '   ' ) );
		$this->assertSame( 'wordpress', Icon::normalize_set_slug( ' WordPress ' ) );
		$this->assertSame( 'hand-drawn', Icon::normalize_set_slug( 'Hand-Drawn' ) );
	}

	public function test_icon_sets_merge_bundled_parent_and_child_dirs_with_the_child_winning(): void {
		$this->use_theme_dirs();
		$this->put( 'parent/assets/icons/alpha/a.svg' );
		$this->put( 'parent/assets/icons/wordpress/own.svg' );
		$this->put( 'child/assets/icons/alpha/b.svg' );
		$this->put( 'child/assets/icons/zeta/z.svg' );

		$sets = Icon::get_icon_sets();

		// Bundled sets first (glob order), then parent-only, then child-only, in discovery order.
		$this->assertSame( [ 'payment', 'social', 'wordpress', 'alpha', 'zeta' ], array_keys( $sets ) );
		$this->assertSame( $this->tmp . '/parent/assets/icons/wordpress', $sets['wordpress'] );
		$this->assertSame( $this->tmp . '/child/assets/icons/alpha', $sets['alpha'] );
		$this->assertSame( $this->tmp . '/child/assets/icons/zeta', $sets['zeta'] );
		$this->assertStringEndsWith( '/icons/public/icons/social', str_replace( '\\', '/', $sets['social'] ) );
		$this->assertSame( 'aegis_icon_sets', Icon::FILTER );
		$this->assertSame( 'aegis_icon_sets', Wp::$filters_applied[0]['tag'] );
	}

	public function test_icon_sets_are_passed_through_the_filter(): void {
		$this->use_theme_dirs();
		Wp::$filter_results['aegis_icon_sets'] = [ 'extra' => '/somewhere' ];

		$this->assertSame( [ 'extra' => '/somewhere' ], Icon::get_icon_sets() );
		$this->assertSame( [ 'payment', 'social', 'wordpress' ], array_keys( Wp::$filters_applied[0]['args'][0] ) );
	}

	public function test_set_icon_names_are_sorted_and_unique(): void {
		$this->use_theme_dirs();
		$this->put( 'child/assets/icons/alpha/b.svg' );
		$this->put( 'child/assets/icons/alpha/a.svg' );
		$this->put( 'child/assets/icons/alpha/readme.txt', 'x' );

		$this->assertSame( [ 'a', 'b' ], Icon::get_set_icon_names( 'Alpha' ) );
		$this->assertSame( [], Icon::get_set_icon_names( 'nope' ) );
		$this->assertContains( 'add-card', Icon::get_set_icon_names( '' ), 'An empty set means wordpress.' );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_wordpress_set_icon_names_include_core_manifest_slugs(): void {
		$this->define_manifest( [ 'plus', 'add-card' ] );
		$names = Icon::get_set_icon_names( 'wordpress' );

		$this->assertContains( 'plus', $names );
		$this->assertSame( 1, count( array_keys( $names, 'add-card', true ) ), 'Duplicates are removed.' );
		$this->assertSame( $names, array_values( array_unique( $names ) ) );
	}

	// Icon data: eager and lazy sets.

	public function test_icon_data_catalog_loads_small_sets_and_stubs_large_and_known_pack_sets(): void {
		$this->use_theme_dirs();

		for ( $i = 1; $i <= 81; $i++ ) {
			$this->put( 'child/assets/icons/big/i' . $i . '.svg' );
		}

		for ( $i = 1; $i <= 80; $i++ ) {
			$this->put( 'child/assets/icons/edge/i' . $i . '.svg' );
		}

		$this->put( 'child/assets/icons/small/a.svg' );
		$this->put( 'child/assets/icons/feather/a.svg' ); // Known large pack: never eager.
		$this->put( 'child/assets/icons/brand/a.svg' );  // Always eager.
		$this->put( 'child/assets/icons/empty/readme.txt', 'x' ); // No SVGs: counts as small.

		$data = Icon::get_icon_data( null );

		$this->assertSame( [], $data['big'], '81 icons is lazy.' );
		$this->assertCount( 80, $data['edge'], '80 icons is still eager.' );
		$this->assertSame( [], $data['feather'] );
		$this->assertSame( [ 'a' ], array_keys( $data['small'] ) );
		$this->assertSame( [ 'a' ], array_keys( $data['brand'] ) );
		$this->assertSame( [], $data['empty'] );
		$this->assertNotSame( [], $data['wordpress'] );
		$this->assertNotSame( [], $data['social'] );
		$this->assertNotSame( [], $data['payment'] );
		$this->assertStringContainsString( 'data-icon="small-a"', $data['small']['a'] );
	}

	public function test_icon_data_always_lists_the_wordpress_set(): void {
		Wp::$filter_results['aegis_icon_sets'] = [];

		$this->assertSame( [ 'wordpress' ], array_keys( Icon::get_icon_data( null ) ) );
		$this->assertSame( [], Icon::get_icon_data( null )['wordpress'] );
	}

	public function test_icon_data_modes(): void {
		$this->use_theme_dirs();
		$this->put( 'child/assets/icons/big/a.svg' );

		$sets = new WP_REST_Request( 'GET', '/aegis/v1/icons' );
		$sets->set_param( 'sets', '1' );
		$this->assertSame( [ 'payment', 'social', 'wordpress', 'big' ], Icon::get_icon_data( $sets ) );

		$one = new WP_REST_Request( 'GET', '/aegis/v1/icons' );
		$one->set_param( 'set', 'Big' );
		$this->assertSame( [ 'a' ], array_keys( Icon::get_icon_data( $one ) ) );
		$this->assertArrayHasKey( 'aegis_icon_set_v1_big', Wp::$transients );

		$full = new WP_REST_Request( 'GET', '/aegis/v1/icons' );
		$full->set_param( 'full', true );
		$this->assertNotSame( [], Icon::get_icon_data( $full )['big'] );

		$unknown = new WP_REST_Request( 'GET', '/aegis/v1/icons' );
		$unknown->set_param( 'set', 'nothing' );
		Wp::$transients = [];
		$this->assertSame( [], Icon::get_icon_data( $unknown ) );
		$this->assertSame( [], Wp::$transients, 'Unknown sets are not cached.' );
	}

	public function test_wordpress_set_is_cached_even_without_a_directory(): void {
		Wp::$filter_results['aegis_icon_sets'] = [];

		$this->assertSame( [], Icon::get_set_icons( 'wordpress' ) );
		$this->assertArrayHasKey( 'aegis_icon_set_v1_wordpress', Wp::$transients );
	}

	// Rendering.

	public function test_get_svg_renders_a_file_icon_and_rejects_bad_names(): void {
		$this->use_theme_dirs();
		$this->put( 'child/assets/icons/alpha/a-b.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#000"><path d="M0 0h24v24H0z" stroke="black"/></svg>' );

		$svg = Icon::get_svg( 'Alpha', 'a-b', '2em' );

		$this->assertStringContainsString( 'data-icon="alpha-a-b"', $svg );
		$this->assertStringContainsString( 'role="img"', $svg );
		$this->assertStringContainsString( 'A B Icon', $svg );
		$this->assertStringContainsString( 'fill="currentColor"', $svg );
		$this->assertStringContainsString( 'stroke="currentColor"', $svg );
		$this->assertMatchesRegularExpression( '/min-width:\s*2em/', $svg );
		$this->assertMatchesRegularExpression( '/aria-labelledby="icon-[0-9a-f]{13}"/', $svg );

		$this->assertSame( '', Icon::get_svg( 'alpha', '../a-b' ) );
		$this->assertSame( '', Icon::get_svg( 'alpha', '' ) );
		$this->assertSame( '', Icon::get_svg( 'alpha', 'missing' ) );
		$this->assertSame( '', Icon::get_svg( 'nosuchset', 'a-b' ) );
	}

	public function test_payment_sets_keep_a_three_by_two_frame(): void {
		$this->use_theme_dirs();
		$this->put( 'child/assets/icons/payment-extra/card.svg' );

		$unitless = Icon::get_svg( 'payment-extra', 'card', 30 );
		$this->assertStringContainsString( 'width="30"', $unitless );
		$this->assertStringContainsString( 'height="20"', $unitless );

		$unit = Icon::get_svg( 'payment-extra', 'card', '3em' );
		$this->assertMatchesRegularExpression( '/height:\s*auto/', $unit );
		$this->assertMatchesRegularExpression( '/aspect-ratio:\s*3 \/ 2/', $unit );
	}

	public function test_sanitize_svg(): void {
		$this->assertSame( '', Icon::sanitize_svg( 'not svg' ) );
		$this->assertSame( '', Icon::sanitize_svg( '' ) );

		$clean = Icon::sanitize_svg( "<svg xmlns=\"http://www.w3.org/2000/svg\" viewbox=\"0 0 1 1\">\n\t<!-- c --><path d=\"M0 0h1v1z\"/>\n\t<script>alert(1)</script></svg>" );
		$this->assertStringContainsString( 'viewBox="0 0 1 1"', $clean );
		$this->assertStringNotContainsString( 'script', $clean );
		$this->assertStringNotContainsString( '<!--', $clean );
		$this->assertStringNotContainsString( "\n", $clean );

		// JSON hex escapes whose backslash was stripped are decoded first.
		$decoded = Icon::sanitize_svg( 'u003csvg xmlns=u0022http://www.w3.org/2000/svg" viewBox=u00220 0 1 1u0022u003eu003cpath d=u0022M0 0h1v1zu0022/u003eu003c/svgu003e' );
		$this->assertStringContainsString( '<svg', $decoded );
	}

	// REST route.

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_register_rest_route_registers_once_per_namespace_with_the_defaults(): void {
		Icon::register_rest_route();
		Icon::register_rest_route();
		Icon::register_rest_route( 'other/v1', '/x/' );

		$rest = array_values( array_filter( Wp::$hooks, static fn( $h ) => $h['tag'] === 'rest_api_init' ) );
		$this->assertCount( 2, $rest );

		( $rest[0]['callback'] )();
		( $rest[1]['callback'] )();

		$this->assertSame( 'aegis/v1', Wp::$rest_routes[0]['namespace'] );
		$this->assertSame( '/icons/', Wp::$rest_routes[0]['route'] );
		$this->assertSame( 'other/v1', Wp::$rest_routes[1]['namespace'] );
		$this->assertSame( '/x/', Wp::$rest_routes[1]['route'] );

		$args = Wp::$rest_routes[0]['args'];
		$this->assertSame( 'GET', $args['methods'] );
		$this->assertSame( [ 'sets', 'set', 'full' ], array_keys( $args['args'] ) );
		$this->assertSame( 'sanitize_text_field', $args['args']['set']['sanitize_callback'] );
		$this->assertFalse( $args['args']['full']['default'] );
		$this->assertTrue( ( $args['permission_callback'] )() );
		$this->assertSame( [ 'edit_posts' ], Wp::$caps_checked );

		$request = new WP_REST_Request( 'GET', '/aegis/v1/icons' );
		$request->set_param( 'sets', '1' );
		$this->assertSame( [ 'payment', 'social', 'wordpress' ], ( $args['callback'] )( $request ) );
	}

	// Version gates.

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function block_version_cases(): array {
		return [
			'6.9'        => [ '6.9', false ],
			'7.0-alpha'  => [ '7.0-alpha1', false ],
			'7.0'        => [ '7.0', true ],
			'7.0.1'      => [ '7.0.1', true ],
			'7.1-beta'   => [ '7.1-beta1', true ],
			'7.1'        => [ '7.1', true ],
		];
	}

	#[DataProvider( 'block_version_cases' )]
	public function test_icon_block_attributes_register_from_wordpress_7_0( string $version, bool $registers ): void {
		Wp::$wp_version = $version;

		new IconBlockAttributes();

		$tags = array_column( Wp::$hooks, 'tag' );
		$this->assertSame( $registers, in_array( 'register_block_type_args', $tags, true ) );
	}

	public function test_icon_block_attributes_extend_only_core_icon(): void {
		$attributes = new IconBlockAttributes();

		$this->assertSame( [ 'x' => 1 ], $attributes->add_attributes( [ 'x' => 1 ], 'core/image' ) );
		$this->assertSame(
			[ 'gradient', 'url', 'linkTarget', 'rel', 'animation', 'iconSvgString' ],
			array_keys( $attributes->add_attributes( [], 'core/icon' )['attributes'] )
		);
	}

	private function response( array $data ): object {
		return new class( $data ) {
			public function __construct( public array $data ) {}

			public function get_data(): array {
				return $this->data;
			}

			public function set_data( array $data ): void {
				$this->data = $data;
			}
		};
	}

	private function merge( string $version, string $route = '/wp/v2/icons', string $method = 'GET', string $search = 'facebook' ): array {
		Wp::$wp_version = $version;

		$request = new WP_REST_Request( $method, $route );
		$request->set_param( 'search', $search );
		$response = $this->response( [ 'existing' ] );

		( new RestIconsMerge() )->merge_aegis_icons( $response, null, $request );

		return $response->data;
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_merge_appends_aegis_icons_on_7_0(): void {
		$data = $this->merge( '7.0' );

		$this->assertSame( 'existing', $data[0] );
		$this->assertSame( [ 'name' => 'social/facebook', 'label' => 'facebook' ], array_diff_key( $data[1], [ 'content' => 1 ] ) );
		$this->assertStringContainsString( '<svg', $data[1]['content'] );
		$this->assertCount( 2, $data, 'Only icons matching the search are appended.' );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_merge_does_nothing_below_7_0_or_on_other_routes(): void {
		$this->assertSame( [ 'existing' ], $this->merge( '6.9' ) );
		$this->assertSame( [ 'existing' ], $this->merge( '7.0', '/wp/v2/other' ) );
		$this->assertSame( [ 'existing' ], $this->merge( '7.0', '/wp/v2/icons', 'POST' ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_merge_is_skipped_on_7_1_only_when_the_core_register_function_exists(): void {
		// 7.1 without wp_register_icon(): still merges.
		$this->assertCount( 2, $this->merge( '7.1' ) );

		require_once __DIR__ . '/Fixtures/Icons/RegistryStubs.php';

		$this->assertSame( [ 'existing' ], $this->merge( '7.1' ) );
		$this->assertSame( [ 'existing' ], $this->merge( '7.2' ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_merge_needs_the_icon_and_rest_api_blocks_enabled(): void {
		Wp::$filter_results['aegis_framework_companion'] = new FakeCompanion( [], false );

		$this->assertSame( [ 'existing' ], $this->merge( '7.0' ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_rest_route_registration_follows_the_icon_block_toggle(): void {
		Wp::$filter_results['aegis_framework_companion'] = new FakeCompanion( [], false );
		( new RestIconsMerge() )->register_rest_route();

		$this->assertSame( [], array_column( Wp::$hooks, 'tag' ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_rest_route_registration_when_enabled(): void {
		( new RestIconsMerge() )->register_rest_route();

		$this->assertSame( [ 'rest_api_init' ], array_column( Wp::$hooks, 'tag' ) );
	}

	private function first_payment(): string {
		$files = glob( dirname( __DIR__, 2 ) . '/vendor/aegis/icons/public/icons/payment/*.svg' );

		return basename( $files[0], '.svg' );
	}

	// Library registration (WordPress 7.1 collections).

	/**
	 * Runs Library::register() against recorder stubs and returns what it registered.
	 *
	 * @return array{collections: array, icons: array, writes: array}
	 */
	private function register_library( string $version ): array {
		require_once __DIR__ . '/Fixtures/Icons/RegistryStubs.php';

		Wp::$wp_version = $version;
		$this->use_theme_dirs();
		$this->put( 'child/assets/icons/remixicon/home-line.svg' );
		$this->put( 'child/assets/icons/unknown-pack/a.svg' );
		$this->put( 'child/assets/icons/Bad_Slug/a.svg' );
		$this->put( 'child/assets/icons/empty-set/readme.txt', 'x' );
		$this->put( 'child/assets/icons/wordpress/add-card.svg' ); // A core slug, skipped in the wordpress set (the child dir replaces the bundled one).
		$this->put( 'child/assets/icons/wordpress/kept.svg' );
		$this->put( 'child/assets/icons/remixicon/Bad Name.svg' ); // Fails the name pattern.

		( new Library() )->register();

		return [
			'collections' => \IconRegistryRecorder::$collections,
			'icons'       => \IconRegistryRecorder::$icons,
			'writes'      => \IconRegistryRecorder::$option_writes,
		];
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_library_registers_collections_with_labels_and_skips_core_icons(): void {
		$this->define_manifest( [ 'add-card' ] );
		$registered = $this->register_library( '7.1' );

		$this->assertSame(
			[ 'payment', 'social', 'wordpress', 'bad_slug', 'remixicon', 'unknown-pack' ],
			array_keys( $registered['collections'] )
		);
		$this->assertEquals(
			[
				'wordpress'    => [ 'Aegis', 'Aegis Aegis icons.' ],
				'social'       => [ 'Social', 'Aegis Social icons.' ],
				'payment'      => [ 'Payment', 'Aegis Payment icons.' ],
				'remixicon'    => [ 'Remix Icon', 'Aegis Remix Icon icons.' ],
				'unknown-pack' => [ 'Unknown Pack', 'Aegis Unknown Pack icons.' ],
				'bad_slug'     => [ 'Bad Slug', 'Aegis Bad Slug icons.' ],
			],
			array_map(
				static fn( array $c ): array => [ $c['label'], $c['description'] ],
				array_intersect_key( $registered['collections'], array_flip( [ 'wordpress', 'social', 'payment', 'remixicon', 'unknown-pack', 'bad_slug' ] ) )
			)
		);

		$icons = $registered['icons'];

		$this->assertArrayHasKey( 'remixicon/home-line', $icons );
		$this->assertSame( 'Home Line', $icons['remixicon/home-line']['label'] );
		$this->assertArrayHasKey( 'payment/' . $this->first_payment(), $icons );
		$this->assertArrayNotHasKey( 'wordpress/add-card', $icons, 'Core slugs are skipped in the wordpress set.' );
		$this->assertArrayHasKey( 'wordpress/kept', $icons );
		$this->assertArrayNotHasKey( 'empty-set/readme', $icons );
		$this->assertArrayNotHasKey( 'remixicon/bad name', $icons );
		$this->assertSame( [ 'aegis_icon_files' ], array_values( array_unique( $registered['writes'] ) ) );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_library_registers_the_wordpress_set_whole_without_a_manifest(): void {
		$registered = $this->register_library( '7.1' );

		$this->assertArrayHasKey( 'wordpress/add-card', $registered['icons'] );
		$this->assertArrayHasKey( 'wordpress/kept', $registered['icons'] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_library_does_nothing_below_7_1(): void {
		$registered = $this->register_library( '7.0' );

		$this->assertSame( [], $registered['collections'] );
		$this->assertSame( [], $registered['icons'] );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_library_does_nothing_when_the_icon_block_is_off(): void {
		Wp::$filter_results['aegis_framework_companion'] = new FakeCompanion( [], false );
		$registered                                       = $this->register_library( '7.1' );

		$this->assertSame( [], $registered['collections'] );
	}

	// The consolidated owners.

	public function test_icon_support_reads_a_manifest_file(): void {
		$this->put( 'm/ok.php', "<?php return [ 'plus' => [], 'star' => [] ];" );
		$this->put( 'm/bad.php', '<?php return 5;' );

		$this->assertSame( [ 'plus' => true, 'star' => true ], IconSupport::read_manifest( $this->tmp . '/m/ok.php' ) );
		$this->assertSame( [], IconSupport::read_manifest( $this->tmp . '/m/bad.php' ) );
		$this->assertSame( [], IconSupport::read_manifest( $this->tmp . '/m/missing.php' ) );
	}

	/**
	 * @return array<string, array{0: string, 1: bool, 2: bool}>
	 */
	public static function support_versions(): array {
		return [
			'6.9'       => [ '6.9', false, false ],
			'7.0'       => [ '7.0', true, false ],
			'7.0.9'     => [ '7.0.9', true, false ],
			'7.1-alpha' => [ '7.1-alpha1', true, false ],
			'7.1'       => [ '7.1', true, true ],
			'8.0'       => [ '8.0', true, true ],
		];
	}

	#[DataProvider( 'support_versions' )]
	public function test_icon_support_version_gates( string $version, bool $available, bool $registry ): void {
		Wp::$wp_version = $version;

		$this->assertSame( $available, IconSupport::available() );
		$this->assertSame( $registry, IconSupport::core_registry() );
	}

	public function test_icon_sets_discover_is_pure_over_three_folders(): void {
		$this->put( 'b/one/a.svg' );
		$this->put( 'p/one/a.svg' );
		$this->put( 'p/two/a.svg' );
		$this->put( 'c/two/a.svg' );

		$this->assertSame(
			[
				'one' => $this->tmp . '/p/one',
				'two' => $this->tmp . '/c/two',
			],
			IconSets::discover( $this->tmp . '/b/', $this->tmp . '/p/', $this->tmp . '/c/' )
		);
		$this->assertSame( [], IconSets::discover( $this->tmp . '/x/', $this->tmp . '/y/', $this->tmp . '/z/' ) );
	}

	public function test_icon_sets_rules(): void {
		$this->assertTrue( IconSets::is_wordpress( 'wordpress' ) );
		$this->assertFalse( IconSets::is_wordpress( 'WordPress' ) );
		$this->assertSame( 'Remix Icon', IconSets::label( 'remixicon' ) );
		$this->assertSame( 'Aegis', IconSets::label( 'wordpress' ) );
		$this->assertSame( 'Some Set', IconSets::label( 'some-set' ) );

		foreach ( [ 'wordpress', 'social', 'payment', 'brand' ] as $set ) {
			$this->assertTrue( IconSets::is_eager( $set, [] ), $set );
		}

		foreach ( [ 'feather', 'hand-drawn', 'heroicons', 'phosphor-duotone', 'remixicon', 'lucide', 'tabler' ] as $set ) {
			$this->assertFalse( IconSets::is_eager( $set, [ $set => $this->tmp ] ), $set );
		}

		$this->assertFalse( IconSets::is_eager( 'unlisted', [] ), 'A set with no directory is lazy.' );
	}

	public function test_registry_id_canonical_form(): void {
		$this->assertSame( 'social/x', IconRegistryId::canonical( 'aegis/social/x' ) );
		$this->assertSame( 'social/x', IconRegistryId::canonical( ' Social/X ' ) );
		$this->assertSame( 'wordpress/plus', IconRegistryId::canonical( 'core/plus' ) );
		$this->assertSame( 'wordpress/', IconRegistryId::canonical( 'nonsense' ) );
		$this->assertSame( 'wordpress/', IconRegistryId::canonical( '' ) );
	}

	public function test_rest_route_name_is_owned_by_the_icons_package(): void {
		$this->assertSame( '/aegis/v1/icons', IconRest::PATH );

		$defaults = array_map(
			static fn( \ReflectionParameter $p ) => $p->getDefaultValue(),
			( new \ReflectionMethod( Icon::class, 'register_rest_route' ) )->getParameters()
		);

		$this->assertSame( [ 'aegis/v1', '/icons/' ], $defaults );
		$this->assertSame( [ IconRest::REST_NAMESPACE, IconRest::ROUTE ], $defaults );
	}

	/**
	 * Each piece of icon knowledge has one owner; the rest call it.
	 *
	 * @return array<string, array{0: string, 1: string[]}> Needle => the files allowed to contain it. The route is built from IconRest constants, so no file holds the literal.
	 */
	public static function single_owner_cases(): array {
		return [
			'core manifest path'  => [ 'icon-library-manifest', [ 'IconSupport.php' ] ],
			'rest route literal'  => [ "'/aegis/v1/icons'", [] ],
			'7.0 version gate'    => [ "'7.0'", [ 'IconSupport.php' ] ],
			'7.1 version gate'    => [ "'7.1'", [ 'IconSupport.php' ] ],
			'icon filter literal' => [ "'aegis_icon_sets'", [ 'IconSets.php' ] ],
			'eager set list'      => [ "'payment', 'brand'", [ 'IconSets.php' ] ],
			'icon toggle'         => [ "is_block_enabled( 'icon' )", [ 'IconFeatures.php' ] ],
			'icon rest toggle'    => [ "is_block_enabled( 'icon_rest_api' )", [ 'IconFeatures.php', 'EditorAssets.php' ] ],
		];
	}

	#[DataProvider( 'single_owner_cases' )]
	public function test_each_piece_of_icon_knowledge_has_one_owner( string $needle, array $owners ): void {
		$root  = dirname( __DIR__, 2 ) . '/vendor/aegis';
		$files = [
			...glob( $root . '/icons/src/*.php' ),
			...glob( $root . '/framework/src/Icons/*.php' ),
			$root . '/framework/src/CoreBlocks/Icon.php',
			$root . '/framework/src/CoreBlocks/IconBlockAttributes.php',
			$root . '/framework/src/DesignSystem/EditorAssets.php',
		];

		$found = [];

		foreach ( $files as $file ) {
			$source = file_get_contents( $file );

			// Docblock and line comments do not count as logic.
			$source = preg_replace( '#/\*.*?\*/#s', '', $source );
			$source = preg_replace( '#^\s*//.*$#m', '', $source );

			if ( str_contains( $source, $needle ) ) {
				$found[] = basename( $file );
			}
		}

		$this->assertSame( $owners, $found, $needle );
	}
}
