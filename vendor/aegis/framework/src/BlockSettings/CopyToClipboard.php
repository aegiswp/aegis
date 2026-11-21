<?php
/**
 * Copy To Clipboard Block Setting
 *
 * Provides support for copying code blocks to the clipboard within the Aegis Framework.
 *
 * Responsibilities:
 * - Handles the rendering of code blocks with clipboard functionality
 *
 * @package    Aegis\Framework\BlockSettings
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/theme
 */

declare( strict_types=1 );

namespace Aegis\Framework\BlockSettings;

use Aegis\Framework\InlineAssets\Scriptable;
use Aegis\Framework\InlineAssets\Scripts;
use Aegis\Dom\DOM;
use Aegis\Framework\Interfaces\Renderable;
use WP_Block;
use function esc_html__;
use function esc_textarea;
use function apply_filters;
use function array_filter;
use function array_shift;
use function array_unique;
use function count;
use function ctype_digit;
use function esc_attr;
use function esc_attr__;
use function esc_html;
use function max;
use function min;
use function str_replace;
use function explode;
use function html_entity_decode;
use function implode;
use function is_admin;
use function preg_match;
use function strtolower;
use function trim;
use function wp_strip_all_tags;


/**
 * Handles the "Copy to Clipboard" block setting for the core/code block.
 *
 * This class enhances the code block by adding a "Copy to Clipboard" button,
 * which is then made functional by an associated JavaScript file. It also handles
 * a setting for showing line numbers.
 *
 * @package Aegis\Framework\BlockSettings
 * @since   1.0.0
 */
class CopyToClipboard implements Renderable, Scriptable {

	/**
	 * Renders the code block with a "Copy to Clipboard" button.
	 *
	 * This method is hooked into the `render_block_core/code` filter. It injects
	 * the necessary HTML for the copy button, including a hidden textarea that
	 * holds the raw code content for easy copying.
	 *
	 * @since 1.0.0
	 *
	 * @param  string   $block_content The original block content.
	 * @param  array    $block         The full block object.
	 * @param  WP_Block $instance      The block instance.
	 *
	 * @hook   render_block_core/code
	 *
	 * @return string The modified block content with the copy button HTML.
	 */
	public function render( string $block_content, array $block, WP_Block $instance ): string {
		// This functionality is for the front-end only.
		if ( is_admin() ) {
			return $block_content;
		}

		// Site-wide defaults come from Aegis Dash (Blocks → Code); a block's own setting always wins.
		$attrs        = $block['attrs'] ?? [];
		$copy         = (bool) ( $attrs['copyToClipboard'] ?? $this->site_default( 'copy_button', true ) );
		$line_numbers = (bool) ( $attrs['showLineNumbers'] ?? $this->site_default( 'line_numbers', false ) );
		$wrap         = (bool) $this->site_default( 'wrap_lines', false );
		$download     = (bool) $this->site_default( 'download', false );
		$collapse     = (bool) $this->site_default( 'collapse', false );
		$file_on      = (bool) $this->site_default( 'filename', false );
		$lines_on     = (bool) $this->site_default( 'highlight_lines', false );
		$position_on  = (bool) $this->site_default( 'copy_position', false );
		$language     = ( $this->site_default( 'language_label', false ) || $download ) ? $this->language_of( $block, $block_content ) : '';
		$class_name   = (string) ( $attrs['className'] ?? '' );

		// The code as text. A first-line comment can name the file ("// file: functions.php") and
		// mark lines to highlight ("// highlight: 3,7-9"); both are removed from what is shown.
		$text     = trim( html_entity_decode( wp_strip_all_tags( $block['innerHTML'] ) ) );
		$lines    = explode( "\n", str_replace( "\r\n", "\n", $text ) );
		$filename = '';
		$marked   = [];
		$stripped = false;

		if ( $file_on && $lines !== [] && preg_match( '/^(?:\/\/|#|\/\*|<!--)\s*file(?:name)?\s*:\s*(.+?)\s*(?:\*\/|-->)?\s*$/i', $lines[0], $matches ) ) {
			$filename = trim( $matches[1] );
			array_shift( $lines );
			$stripped = true;
		}

		if ( $lines_on && $lines !== [] && preg_match( '/^(?:\/\/|#|\/\*|<!--)\s*(?:highlight|lines)\s*:\s*([0-9,\s-]+?)\s*(?:\*\/|-->)?\s*$/i', $lines[0], $matches ) ) {
			$marked   = $this->line_set( $matches[1] );
			$stripped = true;
			array_shift( $lines );
		}

		$content     = implode( "\n", $lines );
		$limit       = max( 3, (int) apply_filters( 'aegis_code_block_collapse_lines', 20 ) );
		$collapsible = $collapse && count( $lines ) > $limit;

		if ( ! $copy && ! $line_numbers && ! $wrap && ! $download && ! $collapsible && ! $stripped && $language === '' && $filename === '' ) {
			return $block_content;
		}

		$label  = esc_html__( 'Copy to clipboard', 'aegis' );
		$copied = esc_html__( 'Copied!', 'aegis' );
		$svg    = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" stroke="currentcolor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="24" height="24" fill="none">
  <path d="M12.9975 10.7499L11.7475 10.7499C10.6429 10.7499 9.74747 11.6453 9.74747 12.7499L9.74747 21.2499C9.74747 22.3544 10.6429 23.2499 11.7475 23.2499L20.2475 23.2499C21.352 23.2499 22.2475 22.3544 22.2475 21.2499L22.2475 12.7499C22.2475 11.6453 21.352 10.7499 20.2475 10.7499L18.9975 10.7499Z"></path>
  <path d="M17.9975 12.2499L13.9975 12.2499C13.4452 12.2499 12.9975 11.8022 12.9975 11.2499L12.9975 9.74988C12.9975 9.19759 13.4452 8.74988 13.9975 8.74988L17.9975 8.74988C18.5498 8.74988 18.9975 9.19759 18.9975 9.74988L18.9975 11.2499C18.9975 11.8022 18.5498 12.2499 17.9975 12.2499Z"></path>
  <path d="M13.7475 16.2499L18.2475 16.2499"></path>
  <path d="M13.7475 19.2499L18.2475 19.2499"></path>
</svg>
SVG;
		// The decoded code is text again, so escape it before it goes back into markup.
		$escaped                = esc_textarea( $content );
		$copy_to_clipboard_html = $copy ? "<div class='copy-to-clipboard'><span >$copied</span><button class='click-to-copy-button' title='$label'>$svg</button><textarea>$escaped</textarea></div>" : '';

		$dom     = DOM::create( $block_content );
		$element = DOM::get_element( 'pre', $dom ) ?? DOM::get_element( 'code', $dom );

		if ( ! $element ) {
			return $block_content;
		}

		$classes = explode( ' ', $element->getAttribute( 'class' ) );

		if ( $line_numbers ) {
			$classes[] = 'show-line-numbers';
		}

		if ( $wrap ) {
			$classes[] = 'wrap-lines';
		}

		if ( $language !== '' && $this->site_default( 'language_label', false ) ) {
			$classes[] = 'has-language-label';
			$element->setAttribute( 'data-language', $language );
		}

		if ( $position_on && preg_match( '/\bcopy-(left|bottom)\b/', $class_name ) ) {
			$classes[] = 'has-copy-position';
		}

		// Show the code without the directive lines, and wrap highlighted lines.
		$code_el = DOM::get_element( 'code', $dom );

		if ( $code_el && ( $stripped || $marked !== [] ) ) {
			while ( $code_el->firstChild ) {
				$code_el->removeChild( $code_el->firstChild );
			}

			if ( $marked === [] ) {
				$code_el->appendChild( $dom->createTextNode( $content ) );
			} else {
				foreach ( $lines as $index => $line ) {
					$span = $dom->createElement( 'span' );
					$span->setAttribute( 'class', 'code-line' . ( isset( $marked[ $index + 1 ] ) ? ' is-highlighted' : '' ) );
					$span->appendChild( $dom->createTextNode( $line ) );
					$code_el->appendChild( $span );
				}
			}
		}

		if ( $filename !== '' ) {
			$classes[] = 'has-filename';
			$bar       = $dom->createElement( 'div' );
			$bar->setAttribute( 'class', 'code-filename' );
			$bar->appendChild( $dom->createTextNode( $filename ) );
			$element->insertBefore( $bar, $element->firstChild );
		}

		if ( $download ) {
			$name = $filename !== '' ? $filename : 'code.' . ( $language !== '' ? $language : 'txt' );
			$name = trim( (string) preg_replace( '/[^A-Za-z0-9._-]+/', '-', $name ), '-' );
			$tool = DOM::create(
				"<div class='code-download'><button type='button' class='code-download-button' data-filename='" . esc_attr( $name ) . "' title='" . esc_attr__( 'Download', 'aegis' ) . "' aria-label='" . esc_attr__( 'Download code', 'aegis' ) . "'><svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32' stroke='currentcolor' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round' width='24' height='24' fill='none'><path d='M16 9v10M12 16l4 4 4-4M10 23h12'></path></svg></button><textarea>$escaped</textarea></div>"
			);
			$div = DOM::get_element( 'div', $tool );

			if ( $div ) {
				$element->insertBefore( $element->ownerDocument->importNode( $div, true ), $element->firstChild );
			}
		}

		// Inject the copy button HTML into the code block element.
		if ( $copy ) {
			$copy_dom = DOM::create( $copy_to_clipboard_html );
			$div      = DOM::get_element( 'div', $copy_dom );
			$imported = $element->ownerDocument->importNode( $div, true );
			$element->insertBefore( $imported, $element->firstChild );
		}

		$toggle = '';

		if ( $collapsible ) {
			$classes[] = 'is-collapsible';
			$classes[] = 'is-collapsed';
			$style     = trim( $element->getAttribute( 'style' ), '; ' );
			$element->setAttribute( 'style', ( $style !== '' ? $style . ';' : '' ) . '--aegis-code-lines:' . $limit );
			$toggle = '<button type="button" class="code-toggle" aria-expanded="false" data-more="' . esc_attr__( 'Show more', 'aegis' ) . '" data-less="' . esc_attr__( 'Show less', 'aegis' ) . '">' . esc_html__( 'Show more', 'aegis' ) . '</button>';
		}

		$element->setAttribute( 'class', trim( implode( ' ', array_unique( array_filter( $classes ) ) ) ) );

		return $dom->saveHTML() . $toggle;
	}

	/**
	 * A site-wide Code block default. Aegis Dash supplies these from Blocks → Code.
	 *
	 * @param string $key      copy_button, line_numbers, wrap_lines, or language_label.
	 * @param bool   $fallback Value when no plugin answers.
	 */
	private function site_default( string $key, bool $fallback ): bool {
		/**
		 * Filters a site-wide Code block default.
		 *
		 * @param bool   $value Default value.
		 * @param string $key   Setting key.
		 */
		return (bool) apply_filters( 'aegis_code_block_default', $fallback, $key );
	}

	/**
	 * Line numbers named by a list such as "3,7-9", as a set.
	 *
	 * @param string $list Comma-separated numbers and ranges.
	 *
	 * @return array<int, true>
	 */
	private function line_set( string $list ): array {
		$set = [];

		foreach ( explode( ',', $list ) as $part ) {
			$part = trim( $part );

			if ( preg_match( '/^(\d{1,4})\s*-\s*(\d{1,4})$/', $part, $m ) ) {
				$from = min( (int) $m[1], (int) $m[2] );
				$to   = min( max( (int) $m[1], (int) $m[2] ), $from + 500 );

				for ( $n = $from; $n <= $to; $n++ ) {
					$set[ $n ] = true;
				}
			} elseif ( ctype_digit( $part ) ) {
				$set[ (int) $part ] = true;
			}
		}

		return $set;
	}

	/**
	 * The language named by a `language-xxx` class on the block, its `<pre>`, or its `<code>`.
	 *
	 * @param array<string, mixed> $block         Block.
	 * @param string               $block_content Rendered block.
	 */
	private function language_of( array $block, string $block_content ): string {
		$haystack = (string) ( $block['attrs']['className'] ?? '' ) . ' ' . $block_content;

		if ( preg_match( '/\blanguage-([a-z0-9+#-]{1,20})\b/i', $haystack, $matches ) ) {
			return strtolower( $matches[1] );
		}

		return '';
	}

	/**
	 * Conditionally enqueues the JavaScript for the copy-to-clipboard functionality.
	 *
	 * This script is only loaded if a block with the `copy-to-clipboard` class
	 * exists on the page.
	 *
	 * @since 1.0.0
	 *
	 * @param Scripts $scripts The Scripts service instance.
	 */
	public function scripts( Scripts $scripts ): void {
		$scripts->add_file( 'copy-to-clipboard.js', [ 'copy-to-clipboard' ] );
		$scripts->add_file( 'code-block-extras.js', [ 'code-download', 'code-toggle' ] );
	}
}
