import { initProductSearchForms } from './product-search';
import { initSiteHeader } from './site-header';

document.addEventListener( 'DOMContentLoaded', () => {
	initSiteHeader();
	initProductSearchForms();
} );

/* Grid Guide overlay */
const toggle = document.querySelector( '#grid-overlay-toggle' );
if ( toggle ) {
	toggle.addEventListener( 'click', () => {
		document.querySelectorAll( '.grid-overlay' ).forEach( ( overlay ) => {
			const isHidden =
				overlay.style.display === 'none' ||
				getComputedStyle( overlay ).display === 'none';
			overlay.style.display = isHidden ? 'block' : 'none';
		} );
	} );
}
