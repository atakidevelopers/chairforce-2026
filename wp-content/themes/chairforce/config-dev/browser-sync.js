const dotenv = require( 'dotenv' );
const browserSync = require( 'browser-sync' ).create();

const conf = dotenv.config( {
	path: 'config-dev/.env',
} ).parsed;

if ( ! conf?.PROXY ) {
	throw new Error(
		'Missing PROXY in config-dev/.env (e.g. PROXY=https://mylocalsite.test)'
	);
}

const proxyTarget = conf.PROXY.includes( '://' )
	? conf.PROXY
	: `https://${ conf.PROXY }`;
const proxyUrl = new URL( proxyTarget );

browserSync.init( {
	proxy: {
		target: proxyUrl.origin,
		ws: true,
		proxyOptions: {
			changeOrigin: true,
			// DDEV uses mkcert; allow the upstream TLS handshake from Node.
			secure: false,
		},
	},
	https: true,
	host: proxyUrl.hostname,
	port: 8000,
	open: false,
	files: 'build',
	ui: {
		port: 8001,
	},
} );
