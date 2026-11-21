/**
 * Prepare vendored PHP/JS for `wp i18n make-pot`.
 *
 * WP-CLI scans JavaScript but not TypeScript. Theme block sources under
 * `src/Blocks` compile in place.
 *
 * `vendor/` is excluded from make-pot, so Icon library PHP/JS, Marquee
 * editor JS, Newsletter PHP/JS, Accordion PHP, SVG editor JS/PHP,
 * Visibility editor JS, Navigation PHP, and Query Loop PHP/JS are copied into
 * `build/I18nScan` for the scan. finish-translate.js rewrites POT
 * references back to the real vendor paths.
 * Bundled `public/js/editor.js` is not copied (minified leftover inspector
 * strings are unreachable; live SVG inspector strings come from `svg-editor.js`).
 *
 * Companion plugin strings (Map, Modal, Blocks admin) live in
 * `wp-content/plugins/aegis-dash/languages/`. Do not copy them here.
 *
 * @package
 */

const fs = require( 'fs' );
const path = require( 'path' );
const vendorFiles = require( './translate-vendor-files' );

const themeRoot = path.resolve( __dirname, '..' );
const i18nScanDir = path.join( themeRoot, 'build', 'I18nScan' );

const scanCopies = vendorFiles.map( ( file ) => ( {
	from: path.join( themeRoot, file.path ),
	to: path.join( i18nScanDir, path.basename( file.path ) ),
	label: file.label,
} ) );

if ( ! fs.existsSync( path.join( themeRoot, 'src', 'Blocks' ) ) ) {
	console.warn(
		'Warning: src/Blocks not found. Run `npm run build` before translate so block editor strings are included.'
	);
}

if ( fs.existsSync( i18nScanDir ) ) {
	fs.rmSync( i18nScanDir, { recursive: true, force: true } );
}

fs.mkdirSync( i18nScanDir, { recursive: true } );

for ( const item of scanCopies ) {
	if ( fs.existsSync( item.from ) ) {
		fs.copyFileSync( item.from, item.to );
		console.log( `Copied ${ item.label } for translation scan.` );
	} else {
		console.warn( `Warning: ${ item.label } not found at ${ item.from }.` );
	}
}
