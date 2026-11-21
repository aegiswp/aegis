<?php
/**
 * Characterization tests for the pure helpers in Aegis\Utilities\Pattern.
 *
 * register*(), parse_file() and parse_headers() need WordPress (registries,
 * get_file_data, theme directories) and are not covered here.
 */

declare( strict_types=1 );

namespace AegisTests\Framework;

use Aegis\Utilities\Pattern;
use PHPUnit\Framework\TestCase;

final class PatternTest extends TestCase {

	// -- slug_from_headers ----------------------------------------------------

	public function test_slug_from_headers_prefixes_the_first_category(): void {
		$this->assertSame( 'hero-split', Pattern::slug_from_headers( [ 'hero', 'cta' ], 'split' ) );
	}

	public function test_slug_from_headers_falls_back_to_common_without_categories(): void {
		$this->assertSame( 'common-split', Pattern::slug_from_headers( [], 'split' ) );
	}

	public function test_slug_from_headers_does_not_apply_category_slug_rules(): void {
		// A numeric first category is NOT turned into error-N here (only category_slug does that).
		$this->assertSame( '404-not-found', Pattern::slug_from_headers( [ '404' ], 'not-found' ) );
	}

	// -- category_slug --------------------------------------------------------

	/**
	 * @dataProvider category_slugs
	 */
	public function test_category_slug( string $input, string $expected ): void {
		$this->assertSame( $expected, Pattern::category_slug( $input ) );
	}

	/** @return array<string, array{string, string}> */
	public static function category_slugs(): array {
		return [
			'digits get error- prefix'        => [ '404', 'error-404' ],
			'single digit'                    => [ '0', 'error-0' ],
			'word unchanged'                  => [ 'hero', 'hero' ],
			'mixed digits and letters'        => [ '4xx', '4xx' ],
			'negative number is not digits'   => [ '-1', '-1' ],
			'empty string is not digits'      => [ '', '' ],
			'space-padded number'             => [ ' 404', ' 404' ],
		];
	}

	// -- category_label -------------------------------------------------------

	/**
	 * @dataProvider category_labels
	 */
	public function test_category_label( string $input, string $expected ): void {
		$this->assertSame( $expected, Pattern::category_label( $input ) );
	}

	/** @return array<string, array{string, string}> */
	public static function category_labels(): array {
		return [
			'cta is upper-cased'              => [ 'cta', 'CTA' ],
			'faq is upper-cased'              => [ 'faq', 'FAQ' ],
			'single word'                     => [ 'hero', 'Hero' ],
			'hyphens become spaces'           => [ 'call-to-action', 'Call To Action' ],
			'error slug'                      => [ 'error-404', 'Error 404' ],
			'acronym rule is case sensitive'  => [ 'Cta', 'Cta' ],
			'only exact cta/faq match'        => [ 'cta-banner', 'Cta Banner' ],
			'underscores are kept'            => [ 'foo_bar', 'Foo_bar' ],
		];
	}

	// -- referenced_slugs -----------------------------------------------------

	public function test_referenced_slugs_returns_unique_slugs_in_order_of_first_appearance(): void {
		$content = '<!-- wp:group --><!-- wp:pattern {"slug":"hero-split"} /-->'
			. '<!-- wp:pattern {"slug":"cta-banner"} /-->'
			. '<!-- wp:pattern {"slug":"hero-split"} /--><!-- /wp:group -->';

		$this->assertSame( [ 'hero-split', 'cta-banner' ], Pattern::referenced_slugs( $content ) );
	}

	public function test_referenced_slugs_is_empty_without_references(): void {
		$this->assertSame( [], Pattern::referenced_slugs( '<!-- wp:paragraph --><p>x</p><!-- /wp:paragraph -->' ) );
		$this->assertSame( [], Pattern::referenced_slugs( '' ) );
	}

	public function test_referenced_slugs_sees_slugs_with_extra_attributes(): void {
		$this->assertSame(
			[ 'x' ],
			Pattern::referenced_slugs( '<!-- wp:pattern {"slug":"x","preview":true} /-->' )
		);
	}

	public function test_referenced_slugs_only_matches_when_slug_is_the_first_attribute(): void {
		$this->assertSame(
			[],
			Pattern::referenced_slugs( '<!-- wp:pattern {"foo":1,"slug":"x"} /-->' )
		);
	}

	public function test_referenced_slugs_also_matches_a_non_self_closing_comment(): void {
		// Pins an inconsistency: referenced_slugs() only needs the opening, while
		// replace_references() requires the self-closing '/-->'.
		$this->assertSame( [ 'x' ], Pattern::referenced_slugs( '<!-- wp:pattern {"slug":"x"} -->' ) );
	}

	// -- replace_references ---------------------------------------------------

	public function test_replace_references_passes_slug_flag_and_full_match_to_the_callback(): void {
		$calls = [];

		$out = Pattern::replace_references(
			'before <!-- wp:pattern {"slug":"hero-split"} /--> after',
			static function ( string $slug, bool $has_extra, string $match ) use ( &$calls ): string {
				$calls[] = [ $slug, $has_extra, $match ];
				return '[' . $slug . ']';
			}
		);

		$this->assertSame( 'before [hero-split] after', $out );
		$this->assertSame( [ [ 'hero-split', false, '<!-- wp:pattern {"slug":"hero-split"} /-->' ] ], $calls );
	}

	public function test_replace_references_flags_extra_attributes(): void {
		$calls = [];

		$out = Pattern::replace_references(
			'<!-- wp:pattern {"slug":"x","preview":true} /-->',
			static function ( string $slug, bool $has_extra, string $match ) use ( &$calls ): string {
				$calls[] = [ $slug, $has_extra, $match ];
				return 'REPLACED';
			}
		);

		$this->assertSame( 'REPLACED', $out );
		$this->assertSame( [ [ 'x', true, '<!-- wp:pattern {"slug":"x","preview":true} /-->' ] ], $calls );
	}

	public function test_replace_references_replaces_every_reference_independently(): void {
		$out = Pattern::replace_references(
			'<!-- wp:pattern {"slug":"a"} /--><p>mid</p><!-- wp:pattern {"slug":"b","k":1} /-->',
			static fn( string $slug, bool $extra ): string => $slug . ( $extra ? '+' : '' )
		);

		$this->assertSame( 'a<p>mid</p>b+', $out );
	}

	public function test_replace_references_accepts_no_space_before_the_closer(): void {
		$out = Pattern::replace_references(
			'<!-- wp:pattern {"slug":"a"}/-->',
			static fn( string $slug ): string => '<' . $slug . '>'
		);

		$this->assertSame( '<a>', $out );
	}

	public function test_replace_references_handles_a_nested_object_attribute_as_extra(): void {
		$calls = [];

		$out = Pattern::replace_references(
			'<!-- wp:pattern {"slug":"a","meta":{"k":1}} /-->',
			static function ( string $slug, bool $extra ) use ( &$calls ): string {
				$calls[] = [ $slug, $extra ];
				return 'R';
			}
		);

		$this->assertSame( 'R', $out );
		$this->assertSame( [ [ 'a', true ] ], $calls );
	}

	public function test_replace_references_leaves_non_matching_markup_alone_and_skips_the_callback(): void {
		$content = '<!-- wp:pattern {"foo":1,"slug":"x"} /-->'     // Slug not first.
			. '<!-- wp:paragraph /-->'
			. '<!-- wp:pattern -->';                               // No attributes.
		$called  = false;

		$out = Pattern::replace_references(
			$content,
			static function () use ( &$called ): string {
				$called = true;
				return 'X';
			}
		);

		$this->assertSame( $content, $out );
		$this->assertFalse( $called );
	}

	public function test_replace_references_swallows_markup_after_a_non_self_closing_reference(): void {
		// Pins a known bug: the trailing group is [^/]*, so a non-self-closing
		// reference keeps matching up to the NEXT '/-->' as long as nothing in
		// between contains a '/', so an unrelated self-closing block is replaced too.
		$matches = [];

		$out = Pattern::replace_references(
			'<!-- wp:pattern {"slug":"y"} --><!-- wp:separator /-->',
			static function ( string $slug, bool $extra, string $match ) use ( &$matches ): string {
				$matches[] = $match;
				return 'X';
			}
		);

		$this->assertSame( 'X', $out );
		$this->assertSame( [ '<!-- wp:pattern {"slug":"y"} --><!-- wp:separator /-->' ], $matches );
	}

	public function test_replace_references_callback_can_remove_a_reference(): void {
		$out = Pattern::replace_references(
			'a<!-- wp:pattern {"slug":"gone"} /-->b',
			static fn(): string => ''
		);

		$this->assertSame( 'ab', $out );
	}

	public function test_replace_references_returns_original_content_when_the_regex_engine_fails(): void {
		$old_limit = ini_get( 'pcre.backtrack_limit' );
		$old_jit   = ini_get( 'pcre.jit' );
		// Force a PREG_BACKTRACK_LIMIT_ERROR so preg_replace_callback() returns null.
		ini_set( 'pcre.jit', '0' );
		ini_set( 'pcre.backtrack_limit', '1' );

		try {
			$content = '<!-- wp:pattern {"slug":"a","pad":"' . str_repeat( 'x<', 500 ) . '"} /-->';
			$out     = Pattern::replace_references( $content, static fn(): string => 'REPLACED' );
		} finally {
			ini_set( 'pcre.backtrack_limit', (string) $old_limit );
			ini_set( 'pcre.jit', (string) $old_jit );
		}

		$this->assertSame( $content, $out );
	}
}
