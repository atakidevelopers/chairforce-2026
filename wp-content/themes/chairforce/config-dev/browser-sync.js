const dotenv = require( 'dotenv' );
const browserSync = require( 'browser-sync' ).create();

const conf = dotenv.config( {
	path: 'config-dev/.env',
} ).parsed;

browserSync.init( {
	proxy: conf.PROXY,
	files: 'build',
	port: 8000,
	ui: {
		port: 8001,
	},
} );
