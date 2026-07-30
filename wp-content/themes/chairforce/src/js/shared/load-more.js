/**
 * Page-1 Load More — append default WC product cards via REST partial HTML.
 *
 * @see context/plans/3i-load-more-plan.md
 */

import { delegateDocument, dispatchContentUpdated } from './delegated-events';

const BUTTON_SELECTOR = '.cf-load-more__button';

/**
 * Read catalog sort params from the current URL.
 *
 * @return {{ orderby: string|null, order: string|null }}
 */
function getSortParamsFromUrl() {
	const params = new URLSearchParams( window.location.search );

	return {
		orderby: params.get( 'orderby' ),
		order: params.get( 'order' ),
	};
}

/**
 * Build REST URL for the next page batch.
 *
 * @param {HTMLButtonElement} button Load More button.
 * @param {number}            page   Page number to fetch.
 * @return {string|null}
 */
function buildLoadMoreUrl( button, page ) {
	const restBase = window.Chairforce_Public?.loadMoreRestUrl;

	if ( ! restBase ) {
		return null;
	}

	const url = new URL( restBase, window.location.origin );

	url.searchParams.set( 'page', String( page ) );

	const perPage = button.dataset.perPage;

	if ( perPage ) {
		url.searchParams.set( 'per_page', perPage );
	}

	const queryVars = button.dataset.queryVars;

	if ( queryVars ) {
		url.searchParams.set( 'query_vars', queryVars );
	}

	const { orderby, order } = getSortParamsFromUrl();

	if ( orderby ) {
		url.searchParams.set( 'orderby', orderby );
	}

	if ( order ) {
		url.searchParams.set( 'order', order );
	}

	return url.toString();
}

/**
 * Find the product grid list to append into.
 *
 * @param {HTMLButtonElement} button Load More button.
 * @return {HTMLElement|null}
 */
function getProductTemplateList( button ) {
	const collection = button.closest( '.wp-block-woocommerce-product-collection' );

	if ( ! collection ) {
		return null;
	}

	return collection.querySelector( '.wc-block-product-template' );
}

/**
 * @param {HTMLButtonElement} button
 * @param {boolean}           isLoading
 * @param {string}            [labelOverride]
 */
function setButtonLoadingState( button, isLoading, labelOverride ) {
	const defaultLabel = button.textContent?.trim() || '';
	const loadingLabel = button.dataset.loadingText || 'Loading…';

	button.classList.toggle( 'is-loading', isLoading );
	button.disabled = isLoading;
	button.setAttribute( 'aria-busy', isLoading ? 'true' : 'false' );

	if ( isLoading ) {
		if ( ! button.dataset.defaultText ) {
			button.dataset.defaultText = defaultLabel;
		}

		button.textContent = labelOverride || loadingLabel;
		return;
	}

	if ( button.dataset.defaultText ) {
		button.textContent = button.dataset.defaultText;
	}
}

/**
 * Handle Load More click.
 *
 * @param {Event}              event
 * @param {HTMLButtonElement}  button
 */
async function handleLoadMoreClick( event, button ) {
	event.preventDefault();

	if ( button.disabled || button.classList.contains( 'is-loading' ) ) {
		return;
	}

	const nextPage = parseInt( button.dataset.nextPage || '2', 10 );
	const list = getProductTemplateList( button );
	const fetchUrl = buildLoadMoreUrl( button, nextPage );

	if ( ! list || ! fetchUrl || Number.isNaN( nextPage ) ) {
		return;
	}

	setButtonLoadingState( button, true );

	try {
		const headers = {};

		if ( window.Chairforce_Public?.nonce ) {
			headers[ 'X-WP-Nonce' ] = window.Chairforce_Public.nonce;
		}

		const response = await fetch( fetchUrl, {
			method: 'GET',
			credentials: 'same-origin',
			headers,
		} );

		if ( ! response.ok ) {
			throw new Error( `Load More request failed (${ response.status })` );
		}

		const data = await response.json();

		if ( data.html ) {
			list.insertAdjacentHTML( 'beforeend', data.html );
			dispatchContentUpdated( { source: 'load-more', page: nextPage } );
		}

		if ( data.hasMore && data.nextPage ) {
			button.dataset.nextPage = String( data.nextPage );

			if ( data.maxPages ) {
				button.dataset.maxPages = String( data.maxPages );
			}
		} else {
			button.closest( '.cf-load-more' )?.remove();
		}
	} catch ( error ) {
		// eslint-disable-next-line no-console
		console.error( '[chairforce load-more]', error );
	} finally {
		setButtonLoadingState( button, false );
	}
}

/**
 * Register delegated Load More handler (no-op when button absent).
 */
export function initLoadMore() {
	delegateDocument( 'click', BUTTON_SELECTOR, handleLoadMoreClick );
}
