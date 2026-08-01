import { initProductSearchForms } from './product-search';
import { initProductFilters } from './product-filters';
import { initProductViewSwitcher } from './product-view-switcher';
import { initQuickView } from './quick-view';
import { initLoadMore } from './shared/load-more';
import { initLoadMoreAddToCart } from './shared/load-more-add-to-cart';
import { initProductGridSwatches } from './shared/product-grid-swatches';
import { initWishlist } from './shared/wishlist';
import { initSingleProductSwatches } from './single-product-swatches';
import { initSiteHeader } from './site-header';
import { initWooCommerceQuantity } from './woocommerce-quantity';

export {
	CONTENT_UPDATED_EVENT,
	delegateDocument,
	delegateOn,
	dispatchContentUpdated,
} from './shared/delegated-events';

document.addEventListener( 'DOMContentLoaded', () => {
	initSiteHeader();
	initProductSearchForms();
	initProductGridSwatches();
	initSingleProductSwatches();
	initQuickView();
	initProductFilters();
	initProductViewSwitcher();
	initLoadMore();
	initLoadMoreAddToCart();
	initWishlist();
	initWooCommerceQuantity();
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
