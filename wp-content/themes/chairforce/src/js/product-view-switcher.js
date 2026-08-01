/**
 * Product archive grid/list view toggle — localStorage + CSS class on main column.
 *
 * @see context/plans/3k-grid-list-view-toggle-plan.md
 */

import {
	CONTENT_UPDATED_EVENT,
	delegateDocument,
} from './shared/delegated-events';

const STORAGE_KEY = 'cf_products_view';
const SWITCHER_SELECTOR = '.cf-product-view-switcher';
const MAIN_SELECTOR = '.cf-shop-archive-main';
const SHELL_SELECTOR = '.cf-shop-archive-shell';
const BUTTON_SELECTOR = '[data-cf-products-view]';
const LIST_CLASS = 'cf-products-view-list';
const VALID_VIEWS = new Set( [ 'grid', 'list' ] );

/**
 * @return {'grid'|'list'}
 */
function getStoredView() {
	try {
		const stored = window.localStorage.getItem( STORAGE_KEY );

		if ( stored && VALID_VIEWS.has( stored ) ) {
			return stored;
		}
	} catch {
		// Private browsing or blocked storage — fall back to grid.
	}

	return 'grid';
}

/**
 * @param {'grid'|'list'} view
 */
function persistView( view ) {
	try {
		window.localStorage.setItem( STORAGE_KEY, view );
	} catch {
		// Ignore write failures.
	}
}

/**
 * Archive main columns that belong to a switcher on the page.
 *
 * @return {HTMLElement[]}
 */
function getArchiveMains() {
	const mains = new Set();

	document.querySelectorAll( SWITCHER_SELECTOR ).forEach( ( switcher ) => {
		const shell = switcher.closest( SHELL_SELECTOR );
		const main = shell?.querySelector( MAIN_SELECTOR );

		if ( main instanceof HTMLElement ) {
			mains.add( main );
		}
	} );

	if ( mains.size > 0 ) {
		return Array.from( mains );
	}

	return Array.from( document.querySelectorAll( MAIN_SELECTOR ) ).filter(
		( node ) => node instanceof HTMLElement
	);
}

/**
 * @param {'grid'|'list'} view
 */
function applyView( view ) {
	const isList = 'list' === view;

	getArchiveMains().forEach( ( main ) => {
		main.classList.toggle( LIST_CLASS, isList );
	} );

	document.querySelectorAll( BUTTON_SELECTOR ).forEach( ( button ) => {
		if ( ! ( button instanceof HTMLButtonElement ) ) {
			return;
		}

		const buttonView = button.dataset.cfProductsView;

		if ( ! buttonView || ! VALID_VIEWS.has( buttonView ) ) {
			return;
		}

		button.setAttribute( 'aria-pressed', buttonView === view ? 'true' : 'false' );
		button.classList.toggle(
			'cf-product-view-switcher__button--active',
			buttonView === view
		);
	} );
}

/**
 * @param {HTMLButtonElement} button
 */
function handleViewClick( button ) {
	const view = button.dataset.cfProductsView;

	if ( ! view || ! VALID_VIEWS.has( view ) ) {
		return;
	}

	persistView( view );
	applyView( view );
}

export function initProductViewSwitcher() {
	if ( ! document.querySelector( SWITCHER_SELECTOR ) ) {
		return;
	}

	applyView( getStoredView() );

	delegateDocument( 'click', BUTTON_SELECTOR, ( event, button ) => {
		if ( ! ( button instanceof HTMLButtonElement ) ) {
			return;
		}

		event.preventDefault();
		handleViewClick( button );
	} );

	document.addEventListener( CONTENT_UPDATED_EVENT, () => {
		applyView( getStoredView() );
	} );
}
