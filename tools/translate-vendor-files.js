/**
 * Vendored files that `wp i18n make-pot` cannot scan in place (`vendor/` is
 * excluded). prepare-translate.js copies each one to `build/I18nScan/<name>`;
 * finish-translate.js maps the POT references back to `path`.
 *
 * @package
 */

const base = 'vendor/aegis/framework/';

module.exports = [
	{ path: base + 'src/Icons/Library.php', label: 'Icon library PHP' },
	{ path: 'vendor/aegis/icons/src/IconSets.php', label: 'Icon set labels PHP' },
	{ path: base + 'public/js/icon-block-editor.js', label: 'Icon block editor script' },
	{ path: base + 'public/js/marquee-editor.js', label: 'Marquee editor script' },
	{ path: base + 'public/js/newsletter-editor.js', label: 'Newsletter editor script' },
	{ path: base + 'public/js/svg-editor.js', label: 'SVG editor script' },
	{ path: base + 'src/BlockVariations/Svg.php', label: 'SVG variation PHP' },
	{ path: base + 'src/BlockVariations/Newsletter.php', label: 'Newsletter variation PHP' },
	{ path: base + 'src/BlockVariations/AccordionList.php', label: 'Accordion variation PHP' },
	{ path: base + 'src/CoreBlocks/Navigation.php', label: 'Navigation submenu PHP' },
	{ path: base + 'public/js/visibility-toggles.js', label: 'Visibility editor script' },
	{ path: base + 'public/js/query-enhancements-editor.js', label: 'Query Loop editor script' },
	{ path: base + 'src/BlockSettings/QueryEnhancements.php', label: 'Query Loop enhancements PHP' },
	{ path: base + 'src/BlockSettings/QueryNoResults.php', label: 'Query Loop no-results PHP' },
];
