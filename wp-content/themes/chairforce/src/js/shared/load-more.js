/**
 * Page-1 Load More — append default WC product cards via REST partial HTML.
 *
 * @see context/plans/3i-load-more-plan.md
 */

import { delegateDocument, dispatchContentUpdated } from './delegated-events';

const BUTTON_SELECTOR = '.cf-load-more__button';
const LOAD_MORE_SELECTOR = '.cf-load-more';
const RESULTS_COUNT_SELECTOR =
	'.wp-block-woocommerce-product-results-count .woocommerce-result-count, .wc-block-product-results-count .woocommerce-result-count';

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
 * Count rendered product cards in the grid list.
 *
 * @param {HTMLElement} list Product template list.
 * @return {number}
 */
function getLoadedProductCount( list ) {
	return list.querySelectorAll( ':scope > li' ).length;
}

/**
 * @param {number} value
 * @return {string}
 */
function formatNumber( value ) {
	return new Intl.NumberFormat().format( value );
}

/**
 * Build the "Viewing X of Y" status string.
 *
 * @param {number} viewing Visible product count.
 * @param {number} total   Total products in query.
 * @return {string}
 */
function getViewingStatusText( viewing, total ) {
	const template =
		window.Chairforce_Public?.loadMoreViewingStatus || 'Viewing %1$s of %2$s';

	return template
		.replace( '%1$s', formatNumber( viewing ) )
		.replace( '%2$s', formatNumber( total ) );
}

/**
 * Build WooCommerce-style results count text for the archive toolbar.
 *
 * @param {number} viewing Visible product count.
 * @param {number} total   Total products in query.
 * @return {string}
 */
function getResultsCountText( viewing, total ) {
	const i18n = window.Chairforce_Public || {};

	if ( total === 1 ) {
		return i18n.resultsCountSingle || 'Showing the single result';
	}

	if ( viewing >= total ) {
		const template = i18n.resultsCountAll || 'Showing all %1$s results';

		return template.replace( '%1$s', formatNumber( total ) );
	}

	const template =
		i18n.resultsCountRange || 'Showing %1$s–%2$s of %3$s results';

	return template
		.replace( '%1$s', formatNumber( 1 ) )
		.replace( '%2$s', formatNumber( viewing ) )
		.replace( '%3$s', formatNumber( total ) );
}

/**
 * Update the WooCommerce product-results-count block above the grid.
 *
 * @param {number} viewing Visible product count.
 * @param {number} total   Total products in query.
 */
function updateResultsCount( viewing, total ) {
	const resultsCount = document.querySelector( RESULTS_COUNT_SELECTOR );

	if ( ! resultsCount || ! total ) {
		return;
	}

	resultsCount.innerHTML = getResultsCountText( viewing, total );
}

/**
 * Update progress bar and status text inside the Load More component.
 *
 * @param {HTMLElement} container Load More wrapper.
 * @param {number}      viewing   Visible product count.
 * @param {number}      total     Total products in query.
 */
function updateLoadMoreProgress( container, viewing, total ) {
	if ( ! container || ! total ) {
		return;
	}

	const progress = container.querySelector( '.cf-load-more__progress' );
	const progressBar = container.querySelector( '.cf-load-more__progress-bar' );
	const status = container.querySelector( '.cf-load-more__status' );
	const percent = Math.min( 100, Math.round( ( viewing / total ) * 10000 ) / 100 );

	if ( progress ) {
		progress.setAttribute( 'aria-valuenow', String( viewing ) );
		progress.setAttribute( 'aria-valuemax', String( total ) );
		progress.setAttribute(
			'aria-label',
			getViewingStatusText( viewing, total )
		);
	}

	if ( progressBar ) {
		progressBar.style.width = `${ percent }%`;
	}

	if ( status ) {
		status.textContent = getViewingStatusText( viewing, total );
	}

	container.dataset.total = String( total );
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
 * Sync progress UI and archive results count after cards change.
 *
 * @param {HTMLElement}       loadMoreContainer Load More wrapper.
 * @param {HTMLElement}       list              Product template list.
 * @param {number}            [totalOverride]   Optional total from REST.
 */
function syncLoadMoreState( loadMoreContainer, list, totalOverride ) {
	const viewing = getLoadedProductCount( list );
	const total =
		totalOverride ||
		parseInt( loadMoreContainer?.dataset.total || '0', 10 ) ||
		viewing;

	updateLoadMoreProgress( loadMoreContainer, viewing, total );
	updateResultsCount( viewing, total );

	return { viewing, total };
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
	const loadMoreContainer = button.closest( LOAD_MORE_SELECTOR );
	const list = getProductTemplateList( button );
	const fetchUrl = buildLoadMoreUrl( button, nextPage );

	if ( ! list || ! loadMoreContainer || ! fetchUrl || Number.isNaN( nextPage ) ) {
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

		const total = data.total || parseInt( loadMoreContainer.dataset.total || '0', 10 );
		const { viewing } = syncLoadMoreState( loadMoreContainer, list, total );

		if ( data.hasMore && data.nextPage ) {
			button.dataset.nextPage = String( data.nextPage );

			if ( data.maxPages ) {
				button.dataset.maxPages = String( data.maxPages );
			}
		} else {
			updateResultsCount( viewing, total );
			loadMoreContainer.remove();
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
