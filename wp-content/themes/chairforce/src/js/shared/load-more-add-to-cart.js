/**
 * Load More cards skip WC Interactivity hydration — bridge Add to Cart via Store API.
 *
 * Page-1 product buttons are wired by WooCommerce's Interactivity API at load time.
 * Appended `<li>` HTML keeps the directives but never gets hydrated, so clicks no-op.
 */

import { delegateDocument } from './delegated-events';

const BUTTON_SELECTOR =
	'[data-cf-load-more-card] .ajax_add_to_cart.wc-block-components-product-button__button';

/**
 * @returns {Record<string, unknown>}
 */
function getPublicConfig() {
	return window.Chairforce_Public || {};
}

/**
 * Resolve the Store API nonce (WC middleware cache or theme localize).
 *
 * @return {string}
 */
function getStoreApiNonce() {
	try {
		const stored = window.localStorage?.getItem( 'storeApiNonce' );

		if ( stored ) {
			const parsed = JSON.parse( stored );

			if ( parsed?.nonce ) {
				return parsed.nonce;
			}
		}
	} catch {
		// Fall through to localized nonce.
	}

	const config = getPublicConfig();

	return typeof config.storeApiNonce === 'string' ? config.storeApiNonce : '';
}

/**
 * Persist nonce returned by Store API responses when WC middleware is unavailable.
 *
 * @param {Response} response Fetch response.
 */
function persistStoreApiNonce( response ) {
	const nonce = response.headers.get( 'Nonce' );
	const timestamp = response.headers.get( 'Nonce-Timestamp' );

	if ( ! nonce ) {
		return;
	}

	if ( window.wp?.apiFetch?.setNonce ) {
		window.wp.apiFetch.setNonce( {
			Nonce: nonce,
			'Nonce-Timestamp': timestamp,
		} );
		return;
	}

	try {
		window.localStorage?.setItem(
			'storeApiNonce',
			JSON.stringify( {
				nonce,
				timestamp: timestamp ? parseFloat( timestamp ) : Date.now() / 1000,
			} )
		);
	} catch {
		// Nonce persistence is best-effort.
	}
}

/**
 * Notify WC Blocks cart consumers (mini-cart, etc.) to refresh.
 */
function dispatchCartAdded() {
	document.body.dispatchEvent(
		new CustomEvent( 'wc-blocks_added_to_cart', {
			bubbles: true,
			cancelable: true,
			detail: { preserveCartData: true },
		} )
	);
}

/**
 * @param {HTMLElement} button Add to cart control.
 * @return {number}
 */
function getProductId( button ) {
	const fromButton = parseInt(
		button.dataset.product_id || button.getAttribute( 'data-product_id' ) || '',
		10
	);

	if ( Number.isFinite( fromButton ) && fromButton > 0 ) {
		return fromButton;
	}

	const card = button.closest( '[data-cf-load-more-card]' );
	const fromCard = parseInt( card?.dataset?.cfProductId || '', 10 );

	return Number.isFinite( fromCard ) && fromCard > 0 ? fromCard : 0;
}

/**
 * @param {Event}              event
 * @param {HTMLButtonElement}  button
 */
async function handleAddToCartClick( event, button ) {
	event.preventDefault();
	event.stopPropagation();

	if ( button.disabled || button.classList.contains( 'loading' ) ) {
		return;
	}

	const productId = getProductId( button );
	const nonce = getStoreApiNonce();
	const storeApiUrl =
		typeof getPublicConfig().storeApiUrl === 'string'
			? getPublicConfig().storeApiUrl
			: '/wp-json/wc/store/v1/cart/add-item';

	if ( ! productId || ! nonce ) {
		return;
	}

	button.disabled = true;
	button.classList.add( 'loading' );
	button.setAttribute( 'aria-busy', 'true' );

	try {
		const response = await fetch( storeApiUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				Nonce: nonce,
			},
			body: JSON.stringify( {
				id: productId,
				quantity: 1,
			} ),
		} );

		persistStoreApiNonce( response );

		if ( ! response.ok ) {
			const errorBody = await response.json().catch( () => null );
			throw new Error( errorBody?.message || 'Add to cart failed' );
		}

		dispatchCartAdded();

		const wcSettings = window.wcSettings || window.wc?.wcSettings;

		if ( wcSettings?.productsSettings?.cartRedirectAfterAdd && wcSettings?.STORE_PAGES?.cart ) {
			window.location.href = wcSettings.STORE_PAGES.cart;
		}
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( '[chairforce load-more add-to-cart]', error );
	} finally {
		button.disabled = false;
		button.classList.remove( 'loading' );
		button.setAttribute( 'aria-busy', 'false' );
	}
}

/**
 * Register delegated Add to Cart handler for Load More cards.
 */
export function initLoadMoreAddToCart() {
	delegateDocument( 'click', BUTTON_SELECTOR, handleAddToCartClick );
}
