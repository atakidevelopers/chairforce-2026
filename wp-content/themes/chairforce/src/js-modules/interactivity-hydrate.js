/**
 * Hydrate WordPress Interactivity API roots injected after initial page load.
 *
 * Load More, filter shell swap, and Quick View inject SSR markup with
 * `data-wp-interactive` directives that core only processes on first paint.
 */

import { privateApis, splitTask, store } from '@wordpress/interactivity';

const CONSENT =
	'I acknowledge that using private APIs means my theme or plugin will inevitably break in the next version of WordPress.';

const PRODUCTS_STORE_LOCK =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

const { getRegionRootFragment, toVdom, render, parseServerData, populateServerData } =
	privateApis( CONSENT );

const HYDRATED_ATTR = 'data-cf-wp-iapi-hydrated';

const PRODUCT_BUTTON_SELECTOR =
	'[data-wp-interactive="woocommerce/product-button"]';

/**
 * @param {ParentNode} root
 * @param {string}     [selector]
 * @return {Element[]}
 */
function collectInteractiveRoots( root, selector ) {
	/** @type {Element[]} */
	const nodes = [];

	const matchesSelector = ( node ) => {
		if ( ! ( node instanceof Element ) ) {
			return false;
		}

		if ( node.hasAttribute( HYDRATED_ATTR ) ) {
			return false;
		}

		if ( ! node.hasAttribute( 'data-wp-interactive' ) ) {
			return false;
		}

		if ( ! selector ) {
			return true;
		}

		return node.matches( selector );
	};

	if ( matchesSelector( root ) ) {
		nodes.push( root );
	}

	if ( root instanceof Element || root instanceof DocumentFragment ) {
		root.querySelectorAll( '[data-wp-interactive]' ).forEach( ( node ) => {
			if ( matchesSelector( node ) ) {
				nodes.push( node );
			}
		} );
	}

	return nodes.filter( ( node, index, list ) => list.indexOf( node ) === index );
}

/**
 * Merge interactivity config/state from a fetched full-page HTML document.
 *
 * Required for Load More / filter refresh: appended cards ship product-button
 * markup but the `woocommerce/products` store data lives in the page JSON blob.
 *
 * @param {Document} doc Parsed catalog page.
 */
export function mergeInteractivityDataFromDocument( doc ) {
	if ( ! doc ) {
		return;
	}

	populateServerData( parseServerData( doc ) );
}

/**
 * Ensure Store API product records exist for add-to-cart actions.
 *
 * @param {number[]} productIds
 */
export async function ensureProductsLoaded( productIds ) {
	const ids = [ ...new Set( productIds.filter( ( id ) => Number.isFinite( id ) && id > 0 ) ) ];

	if ( ! ids.length ) {
		return;
	}

	const { state } = store(
		'woocommerce/products',
		{},
		{ lock: PRODUCTS_STORE_LOCK }
	);

	const missing = ids.filter( ( id ) => ! state.products?.[ id ] );

	if ( ! missing.length ) {
		return;
	}

	await Promise.all(
		missing.map( async ( id ) => {
			const response = await fetch(
				`${ window.location.origin }/wp-json/wc/store/v1/products/${ id }`,
				{ credentials: 'same-origin' }
			);

			if ( ! response.ok ) {
				return;
			}

			const product = await response.json();

			if ( product?.id ) {
				state.products[ product.id ] = product;
			}
		} )
	);
}

/**
 * @param {HTMLLIElement[]} items
 * @return {number[]}
 */
export function getProductIdsFromListItems( items ) {
	return items
		.map( ( item ) => {
			const classMatch = [ ...item.classList ].find( ( className ) =>
				className.startsWith( 'post-' )
			);

			if ( ! classMatch ) {
				return 0;
			}

			return parseInt( classMatch.replace( 'post-', '' ), 10 );
		} )
		.filter( ( id ) => Number.isFinite( id ) && id > 0 );
}

/**
 * Hydrate interactive islands inside a container (Load More, filter swap, QV).
 *
 * @param {ParentNode|ParentNode[]} root     DOM subtree or list of subtrees.
 * @param {{ selector?: string }}   [options] Optional interactive root filter.
 */
export async function hydrateInteractiveRoots( root = document, options = {} ) {
	const scopes = Array.isArray( root ) ? root : [ root ];
	const selector = options.selector || '';

	for ( const scope of scopes ) {
		const nodes = collectInteractiveRoots( scope, selector || undefined );

		for ( const node of nodes ) {
			if ( ! node.parentElement ) {
				continue;
			}

			await splitTask();

			const fragment = getRegionRootFragment( node );
			const vdom = toVdom( node );

			await splitTask();
			render( vdom, fragment );
			node.setAttribute( HYDRATED_ATTR, 'true' );
		}
	}
}

export { PRODUCT_BUTTON_SELECTOR };
