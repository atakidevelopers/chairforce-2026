/**
 * Product archive filters — panel UI + archive shell partial reload (PJAX-style).
 *
 * Desktop vertical: in-flow push sidebar (no portal/backdrop).
 * Mobile vertical: fixed drawer portaled to body. Desktop horizontal: dropdown unchanged.
 *
 * @see context/existing-functionality/12A-woodmart-ajax-shop-filtering.md
 */

import { delegateDocument, dispatchContentUpdated } from './shared/delegated-events';

const ROOT_SELECTOR = '.cf-product-filters';
const SHELL_SELECTOR = '.cf-shop-archive-shell';
const LAYOUT_SELECTOR = '.cf-shop-archive-layout';
const SIDEBAR_SELECTOR = '.cf-shop-archive-sidebar';
const BAR_BUTTON_SELECTOR = '.cf-product-filters__bar-button';
const PANEL_SELECTOR = '.cf-filters-panel';
const PANEL_INNER_SELECTOR = '.cf-filters-panel__inner';
const PANEL_CLOSE_SELECTOR = '[data-cf-filters-close]';
const TERM_SELECTOR = '.cf-filter-term, .cf-swatch.cf-filter-term';
const CHIP_SELECTOR = '.cf-active-filters__chip';
const CLEAR_SELECTOR = '.cf-active-filters__clear';
const PRICE_APPLY_SELECTOR = '.cf-filter-price__apply';
const SORT_SELECT_SELECTOR =
	'.wp-block-woocommerce-catalog-sorting select.orderby, .wc-block-catalog-sorting select.orderby';
const OPEN_CLASS = 'is-open';
const LAYOUT_OPEN_CLASS = 'is-filters-open';
const FOCUSED_CARD_CLASS = 'is-focused';
const VERTICAL_OPEN_HTML_CLASS = 'cf-filters-vertical-open';
const SWAPPING_HTML_CLASS = 'cf-filters-swapping';
const ARCHIVE_SHELL_QUERY_ARG = '_cf_archive';
const ARCHIVE_SHELL_QUERY_VALUE = 'shell';
const DESKTOP_BREAKPOINT = '(min-width: 781px)';

let activeFetchController = null;

/**
 * @return {HTMLElement|null}
 */
function getFiltersRoot() {
	return document.querySelector( ROOT_SELECTOR );
}

/**
 * @return {HTMLElement|null}
 */
function getFilterPanel() {
	return document.querySelector( PANEL_SELECTOR );
}

/**
 * @return {boolean}
 */
function isProductArchivePage() {
	return Boolean( getFiltersRoot() );
}

/**
 * @return {MediaQueryList|null}
 */
function getDesktopBreakpoint() {
	if ( typeof window.matchMedia !== 'function' ) {
		return null;
	}

	return window.matchMedia( DESKTOP_BREAKPOINT );
}

/**
 * @param {HTMLElement|null} root
 * @return {HTMLElement|null}
 */
function getArchiveLayout( root ) {
	return root?.closest( LAYOUT_SELECTOR )
		|| document.querySelector( LAYOUT_SELECTOR );
}

/**
 * Desktop + vertical theme option → push sidebar (not mobile drawer).
 *
 * @param {HTMLElement|null} root
 * @return {boolean}
 */
function isDesktopVerticalPushMode( root ) {
	if ( ! root ) {
		return false;
	}

	const mq = getDesktopBreakpoint();

	if ( ! mq?.matches ) {
		return false;
	}

	return root.dataset.panelDesktop !== 'horizontal';
}

/**
 * Mobile vertical drawer still uses body portal + html overlay.
 *
 * @param {HTMLElement|null} root
 * @return {boolean}
 */
function shouldPortalVerticalPanel( root ) {
	if ( ! root || getActivePanelOrientation( root ) !== 'vertical' ) {
		return false;
	}

	return ! isDesktopVerticalPushMode( root );
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
	const panel = getFilterPanel();

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
 * Move the panel back into the filters sidebar (before shell swap destroys portaled copy).
 *
 * @param {HTMLElement|null} root
 */
function embedFilterPanelInRoot( root ) {
	const panel = getFilterPanel();

	if ( ! panel || ! root ) {
		return;
	}

	const sidebar = root.querySelector( SIDEBAR_SELECTOR ) || root;

	if ( sidebar.contains( panel ) ) {
		return;
	}

	sidebar.appendChild( panel );
}

/**
 * Mobile vertical drawer: portal to body. Others: keep panel in sidebar/root.
 *
 * @param {HTMLElement|null} root
 */
function syncFilterPanelPortal( root ) {
	const panel = getFilterPanel();

	if ( ! panel || ! root ) {
		return;
	}

	syncPanelOrientationClasses( root );

	if ( shouldPortalVerticalPanel( root ) ) {
		if ( panel.parentElement !== document.body ) {
			document.body.appendChild( panel );
		}

		return;
	}

	embedFilterPanelInRoot( root );
}

/**
 * @param {HTMLElement|null} root
 * @param {boolean} isOpen
 */
function syncLayoutOpenState( root, isOpen ) {
	const layout = getArchiveLayout( root );

	if ( ! layout || ! isDesktopVerticalPushMode( root ) ) {
		return;
	}

	layout.classList.toggle( LAYOUT_OPEN_CLASS, isOpen );
}

/**
 * @param {HTMLElement|null} root
 * @param {boolean} isOpen
 */
function syncPanelAccessibility( root, isOpen ) {
	const panel = getFilterPanel();
	const inner = panel?.querySelector( PANEL_INNER_SELECTOR );

	if ( ! inner ) {
		return;
	}

	if ( isDesktopVerticalPushMode( root ) ) {
		inner.setAttribute( 'role', 'region' );
		inner.removeAttribute( 'aria-modal' );
		panel?.removeAttribute( 'aria-modal' );
		return;
	}

	inner.setAttribute( 'role', 'dialog' );
	inner.setAttribute( 'aria-modal', isOpen ? 'true' : 'false' );
}

/**
 * @param {boolean} isOpen
 */
function syncVerticalOverlayHtmlClass( isOpen ) {
	const root = getFiltersRoot();

	document.documentElement.classList.toggle(
		VERTICAL_OPEN_HTML_CLASS,
		isOpen && shouldPortalVerticalPanel( root )
	);
}

/**
 * @param {boolean} swapping
 */
function setFiltersSwapping( swapping ) {
	const root = getFiltersRoot();

	document.documentElement.classList.toggle(
		SWAPPING_HTML_CLASS,
		swapping && shouldPortalVerticalPanel( root )
	);
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
 * Build a catalog URL from params.
 *
 * @param {URLSearchParams} params
 * @param {{ stripSort?: boolean }} [options] Pass stripSort when clearing filters entirely.
 * @return {string}
 */
function buildCatalogUrl( params, options = {} ) {
	const next = new URLSearchParams( params );
	stripArchiveShellParam( next );

	if ( options.stripSort ) {
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

	const panel = getFilterPanel();
	const wasOpen = Boolean( panel?.classList.contains( OPEN_CLASS ) );
	const activeCard =
		root.querySelector( `${ BAR_BUTTON_SELECTOR }.is-active` )?.getAttribute( 'data-filter-card' )
		|| panel?.querySelector( `.cf-filter-section.${ FOCUSED_CARD_CLASS }` )?.getAttribute( 'data-filter-card' )
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
	const root = getFiltersRoot();
	const newShell = parseArchiveShellFromHtml( html );
	const { wasOpen, activeCard } = getPanelRestoreState( root );

	if ( ! currentShell || ! newShell ) {
		setFiltersSwapping( false );
		return;
	}

	if ( wasOpen && shouldPortalVerticalPanel( root ) ) {
		document.documentElement.classList.add( 'cf-filters-open' );
		setFiltersSwapping( true );
	}

	if ( shouldPortalVerticalPanel( root ) ) {
		embedFilterPanelInRoot( root );
	}

	currentShell.outerHTML = newShell.outerHTML;

	const nextRoot = getFiltersRoot();

	if ( nextRoot ) {
		syncFilterPanelPortal( nextRoot );

		if ( wasOpen ) {
			setPanelOpen( nextRoot, true );

			if ( activeCard ) {
				focusFilterCard( nextRoot, activeCard );
			}
		}
	}

	setFiltersSwapping( false );

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
	const panel = getFilterPanel();

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

	syncLayoutOpenState( root, isOpen );
	syncPanelAccessibility( root, isOpen );

	const lockDocumentScroll = isOpen && ! isDesktopVerticalPushMode( root );
	document.documentElement.classList.toggle( 'cf-filters-open', lockDocumentScroll );
	syncVerticalOverlayHtmlClass( isOpen );

	if ( isOpen ) {
		panel.querySelector( PANEL_INNER_SELECTOR )?.focus();
	}
}

/**
 * @param {HTMLElement} root
 * @param {string} cardSlug
 */
function focusFilterCard( root, cardSlug ) {
	const panel = getFilterPanel();

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

	syncFilterPanelPortal( root );
	setPanelOpen( root, true );
	focusFilterCard( root, cardSlug );
}

/**
 * Fetch archive shell HTML for current URL params.
 *
 * @param {{ push?: boolean, replaceUrl?: string }} [options]
 */
async function refreshCatalogFromUrl( options = {} ) {
	const root = getFiltersRoot();
	let catalogUrl = options.replaceUrl || buildCatalogUrl( getCatalogParamsFromUrl() );

	if ( ! root || ! document.querySelector( SHELL_SELECTOR ) ) {
		return;
	}

	const fetchUrl = buildArchiveShellFetchUrl( catalogUrl );
	const panelWasOpen = Boolean( getFilterPanel()?.classList.contains( OPEN_CLASS ) );

	if ( activeFetchController ) {
		activeFetchController.abort();
	}

	activeFetchController = new AbortController();
	root.classList.add( 'is-loading' );

	if ( panelWasOpen && shouldPortalVerticalPanel( root ) ) {
		setFiltersSwapping( true );
	}

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

		setFiltersSwapping( false );
	} finally {
		getFiltersRoot()?.classList.remove( 'is-loading' );
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

	const panel = getFilterPanel();
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

	const root = getFiltersRoot();

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

	const root = getFiltersRoot();

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
	const root = getFiltersRoot();

	if ( root && panel ) {
		setPanelOpen( root, false );
	}
}

/**
 * @param {Event} event
 * @param {HTMLElement} select
 */
async function handleSortChange( event, select ) {
	if ( ! isProductArchivePage() ) {
		return;
	}

	if ( ! ( select instanceof HTMLSelectElement ) ) {
		return;
	}

	event.preventDefault();
	event.stopPropagation();

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

	const root = getFiltersRoot();

	if ( root ) {
		syncFilterPanelPortal( root );
		syncPanelAccessibility( root, false );

		const mq = getDesktopBreakpoint();
		mq?.addEventListener( 'change', () => {
			syncFilterPanelPortal( root );

			const panel = getFilterPanel();

			if ( panel?.classList.contains( OPEN_CLASS ) ) {
				syncLayoutOpenState( root, isDesktopVerticalPushMode( root ) );
				syncPanelAccessibility( root, true );
				syncVerticalOverlayHtmlClass( true );
				document.documentElement.classList.toggle(
					'cf-filters-open',
					! isDesktopVerticalPushMode( root )
				);
			}
		} );
	}

	delegateDocument( 'click', BAR_BUTTON_SELECTOR, handleBarButtonClick );
	delegateDocument( 'click', TERM_SELECTOR, handleFilterTermClick );
	delegateDocument( 'click', CHIP_SELECTOR, handleChipClick );
	delegateDocument( 'click', CLEAR_SELECTOR, handleClearClick );
	delegateDocument( 'click', PRICE_APPLY_SELECTOR, handlePriceApplyClick );
	delegateDocument( 'click', PANEL_CLOSE_SELECTOR, handlePanelCloseClick );

	document.addEventListener( 'click', handleDocumentClick );
	document.addEventListener( 'keydown', handleEscapeKey );
	delegateDocument( 'change', SORT_SELECT_SELECTOR, handleSortChange, true );
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
