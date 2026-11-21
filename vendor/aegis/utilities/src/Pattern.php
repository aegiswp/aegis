<?php
/**
 * Block pattern registration utilities.
 *
 * @package Aegis\Utilities
 */

declare( strict_types=1 );

namespace Aegis\Utilities;

use function _cleanup_header_comment;
use function explode;
use function get_file_data;
use function get_stylesheet;
use function get_stylesheet_directory;
use function get_template;
use function get_template_directory;
use function in_array;
use function is_readable;
use function ob_get_clean;
use function ob_start;
use function preg_match;
use function preg_quote;
use function register_block_pattern;
use function register_block_pattern_category;
use function str_contains;
use function str_replace;
use function strtoupper;
use function ucwords;
use function wp_get_global_settings;

/**
 * Utility class for registering and parsing WordPress block patterns from files.
 *
 * This class provides methods to read pattern data from a PHP file's header
 * comment and register it as a block pattern, including its categories.
 *
 * @since 1.0.0
 */
class Pattern {

	/**
	 * Parses and registers a block pattern from a PHP file.
	 *
	 * This method reads the pattern's metadata from the file's header comment,
	 * registers its categories, and then registers the block pattern itself.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file The absolute path to the pattern's PHP file.
	 *
	 * @return void
	 */
	public static function register_from_file( string $file ): void {
		// Files register lazily: core includes them only when the content is read.
		self::register( is_readable( $file ) ? self::parse_headers( $file ) : self::parse_file( $file ) );
	}

	/**
	 * Registers an already parsed block pattern and its categories.
	 *
	 * @since 1.0.2
	 *
	 * @param array $pattern Pattern data from parse_file() or parse_headers().
	 *
	 * @return void
	 */
	public static function register( array $pattern ): void {
		if ( ! isset( $pattern['slug'] ) ) {
			return;
		}

		$categories = $pattern['categories'] ?? [];

		foreach ( $categories as $category ) {
			$category = self::category_slug( trim( (string) $category ) );

			register_block_pattern_category(
				$category,
				[
					'label' => self::category_label( $category ),
				]
			);
		}

		register_block_pattern( $pattern['slug'], $pattern );
	}

	/**
	 * Parses a file for block pattern metadata and content.
	 *
	 * This method reads a PHP file's header for pattern details (Title, Slug,
	 * Categories, etc.) and uses the file's output as the pattern content.
	 * It can also parse the metadata from a string containing the file's content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file The file path or a string containing the file's content.
	 *
	 * @return array An array of parsed pattern data or an empty array on failure.
	 */
	public static function parse_file( string $file ): array {
		if ( ! $file ) {
			return [];
		}

		$content         = '';
		$default_headers = self::HEADERS;

		if ( is_readable( $file ) ) {
			$headers = get_file_data( $file, $default_headers );

			ob_start();
			$global_settings = wp_get_global_settings();

			include $file;
			$content = ob_get_clean();

		} else {
			if ( str_contains( $file, 'Title: ' ) ) {
				$content = $file;
				$headers = $default_headers;

				// Use regex from get_file_data().
				foreach ( $headers as $field => $regex ) {
					if ( preg_match( '/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi', $file, $match ) && $match[1] ) {
						$headers[ $field ] = _cleanup_header_comment( $match[1] );
					} else {
						$headers[ $field ] = '';
					}
				}
			}
		}

		$pattern = self::build( $file, $headers ?? [] );

		if ( ! $pattern ) {
			return [];
		}

		$pattern['content'] = Str::replace_first(
			Str::between( '<?php', '?>', $content ),
			'',
			$content
		);

		return $pattern;
	}

	/**
	 * Parses a pattern file's header without executing it.
	 *
	 * The returned pattern carries `filePath` instead of `content`, so the
	 * block patterns registry includes the file only when its content is read.
	 *
	 * @since 1.0.2
	 *
	 * @param string $file The absolute path to the pattern's PHP file.
	 *
	 * @return array An array of parsed pattern data or an empty array on failure.
	 */
	public static function parse_headers( string $file ): array {
		$pattern = self::build( $file, get_file_data( $file, self::HEADERS ) );

		if ( $pattern ) {
			$pattern['filePath'] = $file;
		}

		return $pattern;
	}

	/**
	 * Matches a `wp:pattern` block comment that starts with its slug.
	 *
	 * Group 1 is the slug, group 2 any further attributes after it (such as
	 * `,"preview":true`), group 3 anything between the closing brace and `/-->`.
	 */
	private const REFERENCE_COMMENT = '/<!-- wp:pattern \{"slug":"([^"]+)"([^}]*)\}([^\/]*)\/-->/';

	/**
	 * Matches the opening of a `wp:pattern` block comment; group 1 is the slug.
	 */
	private const REFERENCE_SLUG = '/<!-- wp:pattern \{"slug":"([^"]+)"/';

	/**
	 * Registered slug for a pattern: `{first category}-{header slug}`.
	 *
	 * @param string[] $categories Categories from the pattern header, in order.
	 * @param string   $slug       The header `Slug` value.
	 *
	 * @return string
	 */
	public static function slug_from_headers( array $categories, string $slug ): string {
		return ( $categories[0] ?? 'common' ) . '-' . $slug;
	}

	/**
	 * Category slug to register: numeric names (error pages such as 404) get an `error-` prefix.
	 *
	 * @param string $category Category or directory name.
	 *
	 * @return string
	 */
	public static function category_slug( string $category ): string {
		return ctype_digit( $category ) ? 'error-' . $category : $category;
	}

	/**
	 * Display label for a pattern category slug.
	 *
	 * @param string $category Category slug.
	 *
	 * @return string
	 */
	public static function category_label( string $category ): string {
		if ( in_array( $category, array( 'cta', 'faq' ), true ) ) {
			return strtoupper( $category );
		}

		return ucwords( str_replace( '-', ' ', $category ) );
	}

	/**
	 * Unique pattern slugs referenced by `wp:pattern` blocks in markup, in order of appearance.
	 *
	 * @param string $content Block markup.
	 *
	 * @return string[]
	 */
	public static function referenced_slugs( string $content ): array {
		if ( ! preg_match_all( self::REFERENCE_SLUG, $content, $matches ) ) {
			return [];
		}

		return array_values( array_unique( $matches[1] ) );
	}

	/**
	 * Replaces each self-closing `wp:pattern` block that starts with a slug.
	 *
	 * The callback gets the slug, whether the block has attributes besides the
	 * slug, and the full match; it returns the replacement markup.
	 *
	 * @param string   $content  Block markup.
	 * @param callable $callback function ( string $slug, bool $has_extra_attrs, string $match ): string.
	 *
	 * @return string The new markup, or the original when the replace failed.
	 */
	public static function replace_references( string $content, callable $callback ): string {
		$replaced = preg_replace_callback(
			self::REFERENCE_COMMENT,
			static fn( array $m ): string => $callback( $m[1], '' !== $m[2], $m[0] ),
			$content
		);

		return is_string( $replaced ) ? $replaced : $content;
	}

	/**
	 * Header fields read from pattern files.
	 *
	 * @var array<string, string>
	 */
	private const HEADERS = [
		'categories'  => 'Categories',
		'title'       => 'Title',
		'slug'        => 'Slug',
		'block_types' => 'Block Types',
		'inserter'    => 'Inserter',
		'ID'          => 'ID',
		'theme'       => 'Theme',
	];

	/**
	 * Builds pattern properties, minus content, from parsed header fields.
	 *
	 * @param string $file    The pattern file path, used to work out the theme.
	 * @param array  $headers Parsed header fields.
	 *
	 * @return array
	 */
	private static function build( string $file, array $headers ): array {
		if ( ! isset( $headers['title'], $headers['slug'], $headers['categories'] ) ) {
			return [];
		}

		$categories = array_map( 'trim', explode( ',', $headers['categories'] ) );
		$categories = array_values( array_filter( $categories, static fn( string $category ): bool => $category !== '' ) );

		$theme = $headers['theme'] ?? null;

		if ( ! $theme ) {
			$stylesheet_dir = get_stylesheet_directory();
			$template_dir   = get_template_directory();

			if ( $stylesheet_dir === $template_dir ) {
				$theme = get_template();
			} else {
				if ( str_contains( $file, $stylesheet_dir ) ) {
					$theme = get_stylesheet();
				} else {
					if ( str_contains( $file, $template_dir ) ) {
						$theme = get_template();
					}
				}
			}
		}

		$slug = self::slug_from_headers( $categories, (string) $headers['slug'] );

		$pattern = [
			'slug'        => $slug,
			'title'       => $headers['title'],
			'categories'  => [ ...$categories ],
			'description' => $headers['description'] ?? '',
			'blockTypes'  => explode( ',', $headers['block_types'] ?? [] ),
			'ID'          => $headers['ID'] ?? null,
			'theme'       => $theme,
		];

		// Hidden category patterns are for template composition, not the inserter.
		if ( ( $headers['inserter'] ?? null ) === 'false' || in_array( 'hidden', $categories, true ) ) {
			$pattern['inserter'] = false;
		}

		return $pattern;
	}

}
