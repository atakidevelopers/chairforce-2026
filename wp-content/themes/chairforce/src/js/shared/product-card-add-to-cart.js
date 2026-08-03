/**
 * Sync product-card add-to-cart labels with WooCommerce cart fragments.
 *
 * Simple ajax buttons show "X in cart" when the product is already in the cart,
 * matching WC Blocks product-button behaviour.
 */

import { CONTENT_UPDATED_EVENT } from './delegated-events';

const QUANTITIES_SELECTOR = '#cf-product-cart-quantities';
const BUTTON_SELECTOR =
	'.cf-add-to-cart .ajax_add_to_cart[data-product_id]';

/**
 * @return {Record<string, number>}
 */
function getQuantitiesFromDocument() {
	const node = document.querySelector( QUANTITIES_SELECTOR );

	if ( ! node?.textContent ) {
		return {};
	}

	try {
		const parsed = JSON.parse( node.textContent );

		return parsed && typeof parsed === 'object' ? parsed : {};
	} catch {
		return {};
	}
}

/**
 * @param {number} quantity
 * @return {string}
 */
function getInCartLabel( quantity ) {
	const template =
		window.Chairforce_Public?.inCartLabel || '%d in cart';

	return template.replace( '%d', String( quantity ) );
}

/**
 * @param {HTMLAnchorElement} button
 * @return {string}
 */
function getDefaultLabel( button ) {
	return button.dataset.cfDefaultLabel || button.textContent.trim();
}

/**
 * @param {HTMLAnchorElement} button
 * @param {number}          quantity
 */
function updateButtonLabel( button, quantity ) {
	const defaultLabel = getDefaultLabel( button );
	const wrapper = button.closest( '.cf-add-to-cart' );

	if ( quantity > 0 ) {
		button.textContent = getInCartLabel( quantity );
		button.classList.add( 'cf-in-cart', 'added' );
	} else {
		button.textContent = defaultLabel;
		button.classList.remove( 'cf-in-cart', 'added' );
	}

	wrapper?.querySelector( ':scope > .added_to_cart' )?.remove();
}

/**
 * Apply cart quantities to all product-card ajax add-to-cart buttons.
 *
 * @param {ParentNode} [root]
 */
function syncProductCardAddToCartLabels( root = document ) {
	const quantities = getQuantitiesFromDocument();

	root.querySelectorAll( BUTTON_SELECTOR ).forEach( ( button ) => {
		if ( ! ( button instanceof HTMLAnchorElement ) ) {
			return;
		}

		const productId = parseInt( button.dataset.product_id || '', 10 );
		const quantity = Number.isFinite( productId )
			? quantities[ productId ] || 0
			: 0;

		updateButtonLabel( button, quantity );
	} );
}

/**
 * Wire WC cart fragment events to product-card button labels.
 */
export function initProductCardAddToCart() {
	const sync = () => syncProductCardAddToCartLabels();

	if ( typeof jQuery !== 'undefined' ) {
		jQuery( document.body ).on(
			'added_to_cart removed_from_cart wc_fragments_loaded wc_fragments_refreshed',
			sync
		);
	}

	document.addEventListener( CONTENT_UPDATED_EVENT, ( event ) => {
		const root = event.detail?.root;

		if ( root instanceof Element || root instanceof DocumentFragment ) {
			syncProductCardAddToCartLabels( root );
			return;
		}

		sync();
	} );

	sync();
}
