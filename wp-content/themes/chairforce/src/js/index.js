import { initProductSearchForms } from './product-search';
import { initSiteHeader } from './site-header';

export {
	CONTENT_UPDATED_EVENT,
	delegateDocument,
	delegateOn,
	dispatchContentUpdated,
} from './shared/delegated-events';

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
