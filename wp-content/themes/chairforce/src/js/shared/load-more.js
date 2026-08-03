/**
 * Page-1 Load More — fetch full catalog pages and append product cards from SSR HTML.
 *
 * @see context/plans/3i-load-more-plan.md
 */

import { delegateDocument, dispatchContentUpdated } from './delegated-events';

const BUTTON_SELECTOR = '.cf-load-more__button';
const LOAD_MORE_SELECTOR = '.cf-load-more';
const PRODUCT_LIST_SELECTOR = '.wc-block-product-template';
const ARCHIVE_SHELL_QUERY_ARG = '_cf_archive';
const RESULTS_COUNT_SELECTOR =
	'.wp-block-woocommerce-product-results-count .woocommerce-result-count, .wc-block-product-results-count .woocommerce-result-count';

/**
 * Split WooCommerce hyphenated orderby values (e.g. price-desc → price + DESC).
 *
 * @param {string|null} orderby Raw orderby query param.
 * @param {string|null} order   Raw order query param.
 * @return {{ orderby: string|null, order: string|null }}
 */
function parseCatalogOrderby( orderby, order ) {
	if ( ! orderby ) {
		return {
			orderby: null,
			order: order || null,
		};
	}

	const parts = orderby.split( '-' );

	if (
		parts.length >= 2 &&
		/^(asc|desc)$/i.test( parts[ parts.length - 1 ] )
	) {
		const direction = parts.pop().toUpperCase();

		return {
			orderby: parts.join( '-' ) || null,
			order: order || direction,
		};
	}

	return {
		orderby,
		order: order || null,
	};
}

/**
 * Read catalog sort params from the current URL.
 *
 * @return {{ orderby: string|null, order: string|null }}
 */
function getSortParamsFromUrl() {
	const params = new URLSearchParams( window.location.search );

	return parseCatalogOrderby(
		params.get( 'orderby' ),
		params.get( 'order' )
	);
}

/**
 * Fallback sort params from the Load More button's exported query vars.
 *
 * @param {HTMLButtonElement} button Load More button.
 * @return {{ orderby: string|null, order: string|null }}
 */
function getSortParamsFromButton( button ) {
	const raw = button?.dataset?.queryVars;

	if ( raw ) {
		try {
			const vars = JSON.parse( raw );

			const fromVars = parseCatalogOrderby(
				typeof vars.orderby === 'string' ? vars.orderby : null,
				typeof vars.order === 'string' ? vars.order : null
			);

			if ( fromVars.orderby ) {
				return fromVars;
			}
		} catch {
			// Fall through to URL params.
		}
	}

	return getSortParamsFromUrl();
}

/**
 * @param {URLSearchParams} params
 */
function stripInternalQueryParams( params ) {
	params.delete( ARCHIVE_SHELL_QUERY_ARG );
}

/**
 * @return {URLSearchParams}
 */
function getCatalogParamsFromUrl() {
	const params = new URLSearchParams( window.location.search );

	stripInternalQueryParams( params );

	return params;
}

/**
 * @return {string} Archive base path without /page/N/ segment.
 */
function getCatalogBasePath() {
	return window.location.pathname.replace( /\/page\/\d+\/?$/, '' ) || '/';
}

/**
 * Build a full catalog page URL for Load More (same query as the current archive view).
 *
 * @param {number}            page   Target catalog page.
 * @param {HTMLButtonElement} button Load More button.
 * @return {string}
 */
function buildCatalogPageUrl( page, button ) {
	const params = getCatalogParamsFromUrl();

	if ( ! params.has( 'orderby' ) ) {
		const { orderby, order } = getSortParamsFromButton( button );

		if ( orderby ) {
			params.set( 'orderby', orderby );
		}

		if ( order ) {
			params.set( 'order', order );
		}
	}

	const basePath = getCatalogBasePath().replace( /\/$/, '' ) || '';
	const pagePath = page > 1 ? `${ basePath }/page/${ page }/` : `${ basePath }/`;
	const query = params.toString();

	return query ? `${ pagePath }?${ query }` : pagePath;
}

/**
 * Extract product `<li>` nodes and optional total from a full catalog page.
 *
 * @param {string} html Full document HTML.
 * @return {{ items: HTMLLIElement[], total: number }}
 */
function parseProductItemsFromPageHtml( html ) {
	const doc = new DOMParser().parseFromString( html, 'text/html' );
	const list = doc.querySelector( PRODUCT_LIST_SELECTOR );
	const items = list
		? Array.from( list.querySelectorAll( ':scope > li' ) )
		: [];

	let total = parseInt(
		doc.querySelector( `${ LOAD_MORE_SELECTOR }` )?.dataset?.total || '0',
		10
	);

	if ( ! total ) {
		const resultsCount = doc.querySelector( RESULTS_COUNT_SELECTOR );
		const match = resultsCount?.textContent?.match( /of\s+([\d,.]+)\s+results/i );

		if ( match ) {
			total = parseInt( match[ 1 ].replace( /[,.]/g, '' ), 10 );
		}
	}

	return { items, total };
}

/**
 * Append SSR product cards from a fetched catalog page.
 *
 * @param {HTMLElement}        list  Product template list.
 * @param {HTMLLIElement[]}    items Parsed list items from a catalog page.
 */
function appendProductItems( list, items ) {
	const fragment = document.createDocumentFragment();

	items.forEach( ( item ) => {
		fragment.appendChild( document.importNode( item, true ) );
	} );

	list.appendChild( fragment );
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

	return collection.querySelector( PRODUCT_LIST_SELECTOR );
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
 * @param {HTMLElement} loadMoreContainer Load More wrapper.
 * @param {HTMLElement} list              Product template list.
 * @param {number}      [totalOverride]   Optional total from fetched page.
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
	const fetchUrl = buildCatalogPageUrl( nextPage, button );

	if ( ! list || ! loadMoreContainer || ! fetchUrl || Number.isNaN( nextPage ) ) {
		return;
	}

	setButtonLoadingState( button, true );

	try {
		const response = await fetch( fetchUrl, {
			method: 'GET',
			credentials: 'same-origin',
		} );

		if ( ! response.ok ) {
			throw new Error( `Load More request failed (${ response.status })` );
		}

		const html = await response.text();
		const { items, total: fetchedTotal } = parseProductItemsFromPageHtml( html );

		if ( items.length ) {
			appendProductItems( list, items );
			dispatchContentUpdated( { source: 'load-more', page: nextPage } );
		}

		const total =
			fetchedTotal ||
			parseInt( loadMoreContainer.dataset.total || '0', 10 );
		const { viewing } = syncLoadMoreState( loadMoreContainer, list, total );

		if ( viewing < total && items.length > 0 ) {
			button.dataset.nextPage = String( nextPage + 1 );
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
