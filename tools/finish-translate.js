/**
 * Normalize POT file references after `wp i18n make-pot`.
 * Rewrites `build/I18nScan/<file>` back to the vendor path listed in
 * translate-vendor-files.js.
 *
 * @package
 */

const fs = require( 'fs' );
const path = require( 'path' );
const vendorFiles = require( './translate-vendor-files' );

const potFile = path.join( path.resolve( __dirname, '..' ), 'languages', 'aegis.pot' );

if ( ! fs.existsSync( potFile ) ) {
	console.error( `POT file not found: ${ potFile }` );
	process.exit( 1 );
}

let content = fs.readFileSync( potFile, 'utf8' );
for ( const file of vendorFiles ) {
	content = content.replaceAll(
		`#: build/I18nScan/${ path.basename( file.path ) }`,
		`#: ${ file.path }`
	);
}
fs.writeFileSync( potFile, content );
