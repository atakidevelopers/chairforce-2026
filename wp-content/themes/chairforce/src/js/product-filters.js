/**
 * Product archive filters — panel UI + archive shell partial reload (PJAX-style).
 *
 * @see context/existing-functionality/12A-woodmart-ajax-shop-filtering.md
 */

import { delegateDocument, dispatchContentUpdated } from './shared/delegated-events';

const ROOT_SELECTOR = '.cf-product-filters';
const SHELL_SELECTOR = '.cf-shop-archive-shell';
const BAR_BUTTON_SELECTOR = '.cf-product-filters__bar-button';
const PANEL_SELECTOR = '.cf-filters-panel';
const PANEL_CLOSE_SELECTOR = '[data-cf-filters-close]';
const TERM_SELECTOR = '.cf-filter-term, .cf-swatch.cf-filter-term';
const CHIP_SELECTOR = '.cf-active-filters__chip';
const CLEAR_SELECTOR = '.cf-active-filters__clear';
const PRICE_APPLY_SELECTOR = '.cf-filter-price__apply';
const SORT_SELECT_SELECTOR =
	'.wp-block-woocommerce-catalog-sorting select.orderby, .wc-block-catalog-sorting select.orderby';
const OPEN_CLASS = 'is-open';
const FOCUSED_CARD_CLASS = 'is-focused';
const ARCHIVE_SHELL_QUERY_ARG = '_cf_archive';
const ARCHIVE_SHELL_QUERY_VALUE = 'shell';

let activeFetchController = null;

/**
 * @return {boolean}
 */
function isProductArchivePage() {
	return Boolean( document.querySelector( ROOT_SELECTOR ) );
}

/**
 * @return {MediaQueryList|null}
 */
function getDesktopBreakpoint() {
	if ( typeof window.matchMedia !== 'function' ) {
		return null;
	}

	return window.matchMedia( '(min-width: 768px)' );
}

/**
 * @param {HTMLElement} root
 * @return {'vertical'|'horizontal'}
 */
function getActivePanelOrientation( root ) {
	const desktop = root.dataset.panelDesktop === 'horizontal' ? 'horizontal' : 'vertical';
	const mobile = root.dataset.panelMobile === 'horizontal' ? 'horizontal' : 'vertical';
	const mq = getDesktopBreakpoint();

	return mq?.matches ? desktop : mobile;
}

/**
 * @param {HTMLElement} root
 */
function syncPanelOrientationClasses( root ) {
	const panel = root.querySelector( PANEL_SELECTOR );

	if ( ! panel ) {
		return;
	}

	const orientation = getActivePanelOrientation( root );

	panel.classList.remove(
		'cf-filters-panel--vertical',
		'cf-filters-panel--horizontal'
	);
	panel.classList.add( `cf-filters-panel--${ orientation }` );
}

/**
 * @param {URLSearchParams} params
 */
function stripArchiveShellParam( params ) {
	params.delete( ARCHIVE_SHELL_QUERY_ARG );
}

/**
 * @return {URLSearchParams}
 */
function getCatalogParamsFromUrl() {
	const params = new URLSearchParams( window.location.search );
	stripArchiveShellParam( params );

	return params;
}

/**
 * @param {URLSearchParams} params
 * @return {boolean}
 */
function hasActiveCatalogFilters( params ) {
	for ( const [ key ] of params.entries() ) {
		if (
			key.startsWith( 'filter_' )
			|| key === 'min_price'
			|| key === 'max_price'
		) {
			return true;
		}
	}

	return false;
}

/**
 * @return {string} Archive base path without /page/N/ segment.
 */
function getCatalogBasePath() {
	return window.location.pathname.replace( /\/page\/\d+\/?$/, '' ) || '/';
}

/**
 * Build a catalog URL from params; drops sort params when no filters remain.
 *
 * @param {URLSearchParams} params
 * @return {string}
 */
function buildCatalogUrl( params ) {
	const next = new URLSearchParams( params );
	stripArchiveShellParam( next );

	if ( ! hasActiveCatalogFilters( next ) ) {
		next.delete( 'orderby' );
		next.delete( 'order' );
	}

	const query = next.toString();

	return query
		? `${ getCatalogBasePath() }?${ query }`
		: getCatalogBasePath();
}

/**
 * Split WooCommerce hyphenated orderby values.
 *
 * @param {string|null} orderby
 * @param {string|null} order
 * @return {{ orderby: string|null, order: string|null }}
 */
function parseCatalogOrderby( orderby, order ) {
	if ( ! orderby ) {
		return { orderby: null, order: order || null };
	}

	const parts = orderby.split( '-' );

	if (
		parts.length >= 2
		&& /^(asc|desc)$/i.test( parts[ parts.length - 1 ] )
	) {
		const direction = parts.pop().toUpperCase();

		return {
			orderby: parts.join( '-' ) || null,
			order: order || direction,
		};
	}

	return { orderby, order: order || null };
}

/**
 * @param {string} catalogUrl Public catalog URL (no fragment param).
 * @return {string}
 */
function buildArchiveShellFetchUrl( catalogUrl ) {
	const url = new URL( catalogUrl, window.location.origin );

	stripArchiveShellParam( url.searchParams );
	url.searchParams.set( ARCHIVE_SHELL_QUERY_ARG, ARCHIVE_SHELL_QUERY_VALUE );

	return url.toString();
}

/**
 * @param {string} html Fragment HTML.
 * @return {HTMLElement|null}
 */
function parseArchiveShellFromHtml( html ) {
	const doc = new DOMParser().parseFromString( html, 'text/html' );

	return doc.querySelector( SHELL_SELECTOR );
}

/**
 * @param {HTMLElement|null} root
 * @return {{ wasOpen: boolean, activeCard: string|null }}
 */
function getPanelRestoreState( root ) {
	if ( ! root ) {
		return { wasOpen: false, activeCard: null };
	}

	const panel = root.querySelector( PANEL_SELECTOR );
	const wasOpen = Boolean( panel?.classList.contains( OPEN_CLASS ) );
	const activeCard =
		root.querySelector( `${ BAR_BUTTON_SELECTOR }.is-active` )?.getAttribute( 'data-filter-card' )
		|| root.querySelector( `.cf-filter-section.${ FOCUSED_CARD_CLASS }` )?.getAttribute( 'data-filter-card' )
		|| null;

	return { wasOpen, activeCard };
}

/**
 * Replace the archive shell from a server-rendered HTML fragment.
 *
 * @param {string} html
 * @param {{ push?: boolean, replaceUrl?: string }} [options]
 */
function applyArchiveShellHtml( html, options = {} ) {
	const currentShell = document.querySelector( SHELL_SELECTOR );
	const root = document.querySelector( ROOT_SELECTOR );
	const newShell = parseArchiveShellFromHtml( html );
	const { wasOpen, activeCard } = getPanelRestoreState( root );

	if ( ! currentShell || ! newShell ) {
		return;
	}

	if ( wasOpen ) {
		document.documentElement.classList.add( 'cf-filters-open' );
	}

	currentShell.outerHTML = newShell.outerHTML;

	const nextRoot = document.querySelector( ROOT_SELECTOR );

	if ( nextRoot ) {
		syncPanelOrientationClasses( nextRoot );

		if ( wasOpen ) {
			setPanelOpen( nextRoot, true );

			if ( activeCard ) {
				focusFilterCard( nextRoot, activeCard );
			}
		}
	}

	if ( options.push && options.replaceUrl ) {
		window.history.pushState( { cfFilters: true }, '', options.replaceUrl );
	}

	dispatchContentUpdated( { source: 'filters' } );
}

/**
 * @param {HTMLElement} root
 * @param {boolean} isOpen
 */
function setPanelOpen( root, isOpen ) {
	const panel = root.querySelector( PANEL_SELECTOR );

	if ( ! panel ) {
		return;
	}

	panel.classList.toggle( OPEN_CLASS, isOpen );
	panel.hidden = ! isOpen;
	panel.inert = ! isOpen;
	panel.setAttribute( 'aria-hidden', isOpen ? 'false' : 'true' );

	const backdrop = panel.querySelector( '.cf-filters-panel__backdrop' );

	if ( backdrop ) {
		backdrop.hidden = ! isOpen;
		backdrop.setAttribute( 'aria-hidden', isOpen ? 'false' : 'true' );
	}

	root.querySelectorAll( BAR_BUTTON_SELECTOR ).forEach( ( button ) => {
		button.setAttribute(
			'aria-expanded',
			isOpen && button.classList.contains( 'is-active' ) ? 'true' : 'false'
		);
	} );

	document.documentElement.classList.toggle( 'cf-filters-open', isOpen );

	if ( isOpen ) {
		panel.querySelector( '.cf-filters-panel__inner' )?.focus();
	}
}

/**
 * @param {HTMLElement} root
 * @param {string} cardSlug
 */
function focusFilterCard( root, cardSlug ) {
	const panel = root.querySelector( PANEL_SELECTOR );

	if ( ! panel ) {
		return;
	}

	panel.querySelectorAll( '.cf-filter-section' ).forEach( ( section ) => {
		section.classList.toggle(
			FOCUSED_CARD_CLASS,
			section.getAttribute( 'data-filter-card' ) === cardSlug
		);
	} );

	const target = panel.querySelector(
		`[data-filter-card="${ CSS.escape( cardSlug ) }"]`
	);

	target?.scrollIntoView( { block: 'nearest', behavior: 'smooth' } );
}

/**
 * @param {HTMLElement} root
 * @param {string} cardSlug
 */
function openPanelForCard( root, cardSlug ) {
	root.querySelectorAll( BAR_BUTTON_SELECTOR ).forEach( ( button ) => {
		button.classList.toggle(
			'is-active',
			button.getAttribute( 'data-filter-card' ) === cardSlug
		);
		button.setAttribute(
			'aria-expanded',
			button.getAttribute( 'data-filter-card' ) === cardSlug ? 'true' : 'false'
		);
	} );

	setPanelOpen( root, true );
	focusFilterCard( root, cardSlug );
}

/**
 * Fetch archive shell HTML for current URL params.
 *
 * @param {{ push?: boolean, replaceUrl?: string }} [options]
 */
async function refreshCatalogFromUrl( options = {} ) {
	const root = document.querySelector( ROOT_SELECTOR );
	let catalogUrl = options.replaceUrl || buildCatalogUrl( getCatalogParamsFromUrl() );

	if ( ! root || ! document.querySelector( SHELL_SELECTOR ) ) {
		return;
	}

	const fetchUrl = buildArchiveShellFetchUrl( catalogUrl );

	if ( activeFetchController ) {
		activeFetchController.abort();
	}

	activeFetchController = new AbortController();
	root.classList.add( 'is-loading' );

	try {
		const headers = {
			'X-Requested-With': 'XMLHttpRequest',
		};

		if ( window.Chairforce_Public?.nonce ) {
			headers[ 'X-WP-Nonce' ] = window.Chairforce_Public.nonce;
		}

		const response = await fetch( fetchUrl, {
			method: 'GET',
			credentials: 'same-origin',
			headers,
			signal: activeFetchController.signal,
		} );

		if ( ! response.ok ) {
			throw new Error( `Filter refresh failed (${ response.status })` );
		}

		const html = await response.text();

		applyArchiveShellHtml( html, {
			push: options.push,
			replaceUrl: catalogUrl,
		} );
	} catch ( error ) {
		if ( error?.name !== 'AbortError' ) {
			// eslint-disable-next-line no-console
			console.error( '[chairforce product-filters]', error );
		}
	} finally {
		document.querySelector( ROOT_SELECTOR )?.classList.remove( 'is-loading' );
		activeFetchController = null;
	}
}

/**
 * Toggle one layered-nav term in URL params.
 *
 * @param {string} filterName
 * @param {string} termSlug
 * @param {URLSearchParams} params
 */
function toggleFilterTermInParams( filterName, termSlug, params ) {
	const current = ( params.get( filterName ) || '' )
		.split( ',' )
		.map( ( slug ) => slug.trim() )
		.filter( Boolean );

	const index = current.indexOf( termSlug );

	if ( index >= 0 ) {
		current.splice( index, 1 );
	} else {
		current.push( termSlug );
	}

	if ( current.length ) {
		params.set( filterName, current.join( ',' ) );
	} else {
		params.delete( filterName );
	}
}

/**
 * @param {Event} event
 * @param {HTMLElement} button
 */
function handleBarButtonClick( event, button ) {
	event.preventDefault();

	const root = button.closest( ROOT_SELECTOR );

	if ( ! root ) {
		return;
	}

	const cardSlug = button.getAttribute( 'data-filter-card' ) || '';

	if ( ! cardSlug ) {
		return;
	}

	const panel = root.querySelector( PANEL_SELECTOR );
	const isOpen = panel?.classList.contains( OPEN_CLASS );
	const isSame = button.classList.contains( 'is-active' );

	if ( isOpen && isSame ) {
		setPanelOpen( root, false );
		return;
	}

	openPanelForCard( root, cardSlug );
}

/**
 * @param {Event} event
 * @param {HTMLElement} button
 */
async function handleFilterTermClick( event, button ) {
	event.preventDefault();

	const filterName = button.getAttribute( 'data-filter-name' );
	const termSlug = button.getAttribute( 'data-term-slug' );

	if ( ! filterName || ! termSlug ) {
		return;
	}

	const params = getCatalogParamsFromUrl();
	toggleFilterTermInParams( filterName, termSlug, params );

	await refreshCatalogFromUrl( {
		push: true,
		replaceUrl: buildCatalogUrl( params ),
	} );
}

/**
 * @param {Event} event
 * @param {HTMLElement} button
 */
async function handleChipClick( event, button ) {
	event.preventDefault();

	const filterName = button.getAttribute( 'data-filter-name' );
	const termSlug = button.getAttribute( 'data-term-slug' );
	const params = getCatalogParamsFromUrl();

	if ( 'price' === filterName ) {
		params.delete( 'min_price' );
		params.delete( 'max_price' );
	} else if ( filterName && termSlug ) {
		toggleFilterTermInParams( filterName, termSlug, params );
	}

	await refreshCatalogFromUrl( {
		push: true,
		replaceUrl: buildCatalogUrl( params ),
	} );
}

/**
 * @param {Event} event
 * @param {HTMLElement} button
 */
async function handleClearClick( event, button ) {
	event.preventDefault();

	const clearUrl =
		button.getAttribute( 'data-clear-url' )
		|| button.closest( ROOT_SELECTOR )?.dataset.clearUrl
		|| window.location.pathname;

	await refreshCatalogFromUrl( {
		push: true,
		replaceUrl: clearUrl.split( '?' )[ 0 ],
	} );
}

/**
 * @param {Event} event
 * @param {HTMLElement} button
 */
async function handlePriceApplyClick( event, button ) {
	event.preventDefault();

	const priceWrap = button.closest( '.cf-filter-price' );

	if ( ! priceWrap ) {
		return;
	}

	const minInput = priceWrap.querySelector( '.cf-filter-price__input--min' );
	const maxInput = priceWrap.querySelector( '.cf-filter-price__input--max' );
	const params = getCatalogParamsFromUrl();
	const minValue = minInput?.value ?? '';
	const maxValue = maxInput?.value ?? '';

	if ( minValue ) {
		params.set( 'min_price', String( minValue ) );
	} else {
		params.delete( 'min_price' );
	}

	if ( maxValue ) {
		params.set( 'max_price', String( maxValue ) );
	} else {
		params.delete( 'max_price' );
	}

	await refreshCatalogFromUrl( {
		push: true,
		replaceUrl: buildCatalogUrl( params ),
	} );
}

/**
 * @param {Event} event
 * @param {HTMLElement} target
 */
function handlePanelCloseClick( event, target ) {
	event.preventDefault();

	const root = target.closest( ROOT_SELECTOR );

	if ( root ) {
		setPanelOpen( root, false );
	}
}

/**
 * @param {Event} event
 */
function handleDocumentClick( event ) {
	const panel = document.querySelector( `${ PANEL_SELECTOR }.${ OPEN_CLASS }` );

	if ( ! panel ) {
		return;
	}

	const target = event.target;

	if ( ! ( target instanceof Element ) ) {
		return;
	}

	if (
		target.closest( PANEL_SELECTOR )
		|| target.closest( BAR_BUTTON_SELECTOR )
	) {
		return;
	}

	const root = panel.closest( ROOT_SELECTOR );

	if ( root ) {
		setPanelOpen( root, false );
	}
}

/**
 * @param {Event} event
 */
function handleEscapeKey( event ) {
	if ( event.key !== 'Escape' ) {
		return;
	}

	const panel = document.querySelector( `${ PANEL_SELECTOR }.${ OPEN_CLASS }` );
	const root = panel?.closest( ROOT_SELECTOR );

	if ( root && panel ) {
		setPanelOpen( root, false );
	}
}

/**
 * @param {Event} event
 */
async function handleSortChange( event ) {
	const select = event.target;

	if ( ! ( select instanceof HTMLSelectElement ) ) {
		return;
	}

	if ( ! select.closest( '.wp-block-woocommerce-catalog-sorting, .wc-block-catalog-sorting' ) ) {
		return;
	}

	const params = getCatalogParamsFromUrl();
	const parsed = parseCatalogOrderby( select.value, params.get( 'order' ) );

	if ( parsed.orderby ) {
		params.set( 'orderby', parsed.orderby );
	} else {
		params.delete( 'orderby' );
	}

	if ( parsed.order ) {
		params.set( 'order', parsed.order );
	} else {
		params.delete( 'order' );
	}

	await refreshCatalogFromUrl( {
		push: true,
		replaceUrl: buildCatalogUrl( params ),
	} );
}

/**
 * @param {Event} event
 */
function handlePopState() {
	if ( ! isProductArchivePage() ) {
		return;
	}

	refreshCatalogFromUrl();
}

/**
 * Register product archive filter interactions.
 */
export function initProductFilters() {
	if ( ! isProductArchivePage() ) {
		return;
	}

	const root = document.querySelector( ROOT_SELECTOR );

	if ( root ) {
		syncPanelOrientationClasses( root );

		const mq = getDesktopBreakpoint();
		mq?.addEventListener( 'change', () => syncPanelOrientationClasses( root ) );
	}

	delegateDocument( 'click', BAR_BUTTON_SELECTOR, handleBarButtonClick );
	delegateDocument( 'click', TERM_SELECTOR, handleFilterTermClick );
	delegateDocument( 'click', CHIP_SELECTOR, handleChipClick );
	delegateDocument( 'click', CLEAR_SELECTOR, handleClearClick );
	delegateDocument( 'click', PRICE_APPLY_SELECTOR, handlePriceApplyClick );
	delegateDocument( 'click', PANEL_CLOSE_SELECTOR, handlePanelCloseClick );

	document.addEventListener( 'click', handleDocumentClick );
	document.addEventListener( 'keydown', handleEscapeKey );
	document.addEventListener( 'change', handleSortChange );
	delegateDocument(
		'submit',
		'.wp-block-woocommerce-catalog-sorting form, .wc-block-catalog-sorting form',
		( event ) => {
			if ( ! isProductArchivePage() ) {
				return;
			}

			event.preventDefault();
		}
	);
	window.addEventListener( 'popstate', handlePopState );
}
