/**
 * Quick View popup — shell, fetch, open/close.
 *
 * @see context/plans/3h-quick-view-plan.md
 */

import jQuery from 'jquery';

import { delegateDocument } from './shared/delegated-events';
import { primeVariationForms } from './single-product-swatches';

/** @type {HTMLElement|null} */
let shellRoot = null;

/** @type {HTMLElement|null} */
let lastTrigger = null;

/** @type {boolean} */
let isClosing = false;

const TRANSITION_MS = 400;

/**
 * @returns {Record<string, string|undefined>}
 */
function getPublicConfig() {
	return window.Chairforce_Public || {};
}

/**
 * @returns {'modal'|'drawer'}
 */
function getDisplayMode() {
	const mode = getPublicConfig().quickViewDisplay;

	return mode === 'modal' ? 'modal' : 'drawer';
}

/**
 * Build the popup shell once on first open.
 */
function ensureShell() {
	if ( shellRoot ) {
		return;
	}

	const display = getDisplayMode();

	shellRoot = document.createElement( 'div' );
	shellRoot.id = 'cf-quick-view';
	shellRoot.className = `cf-quick-view cf-quick-view--${ display }`;
	shellRoot.hidden = true;
	shellRoot.inert = true;
	shellRoot.innerHTML = `
		<div class="cf-quick-view__backdrop" data-cf-quick-view-close></div>
		<div
			class="cf-quick-view__panel"
			role="dialog"
			aria-modal="true"
			aria-labelledby="cf-quick-view-title"
			tabindex="-1"
		>
			<button type="button" class="cf-quick-view__close" data-cf-quick-view-close aria-label="Close quick view">
				<span aria-hidden="true">&times;</span>
			</button>
			<div class="cf-quick-view__content"></div>
		</div>
	`.trim();

	document.body.appendChild( shellRoot );

	shellRoot.addEventListener( 'click', handleShellClick );
	document.addEventListener( 'keydown', handleDocumentKeydown );
	shellRoot.addEventListener( 'transitionend', handleTransitionEnd );
}

/**
 * @returns {HTMLElement|null}
 */
function getContentEl() {
	return shellRoot?.querySelector( '.cf-quick-view__content' ) || null;
}

/**
 * @returns {HTMLElement|null}
 */
function getPanelEl() {
	return shellRoot?.querySelector( '.cf-quick-view__panel' ) || null;
}

/**
 * @param {boolean} loading
 */
function setLoading( loading ) {
	if ( ! shellRoot ) {
		return;
	}

	shellRoot.classList.toggle( 'cf-quick-view--loading', loading );

	const content = getContentEl();

	if ( loading && content ) {
		content.innerHTML =
			'<div class="cf-quick-view__loader" role="status"><span class="screen-reader-text">Loading product…</span></div>';
	}
}

/**
 * @param {string} html
 */
function injectContent( html ) {
	const content = getContentEl();

	if ( ! content ) {
		return;
	}

	content.innerHTML = html;
	initQuickViewProductScripts( content );
}

/**
 * Initialize WooCommerce variation form + gallery inside injected markup.
 *
 * @param {HTMLElement} root
 */
function initQuickViewProductScripts( root ) {
	const $root = jQuery( root );

	$root.find( '.variations_form' ).each( function initVariationForm() {
		const $form = jQuery( this );

		if ( $form.data( 'cf_variation_form_initialized' ) ) {
			return;
		}

		$form.data( 'cf_variation_form_initialized', true );

		if ( typeof $form.wc_variation_form === 'function' ) {
			$form.wc_variation_form();
		}
	} );

	$root.find( '.woocommerce-product-gallery' ).each( function initGallery() {
		const $gallery = jQuery( this );

		if ( $gallery.data( 'cf_gallery_initialized' ) ) {
			return;
		}

		$gallery.data( 'cf_gallery_initialized', true );

		if ( typeof $gallery.wc_product_gallery === 'function' ) {
			$gallery.wc_product_gallery(
				typeof wc_single_product_params !== 'undefined'
					? wc_single_product_params
					: undefined
			);
		}
	} );
}

/**
 * @param {Event} event
 * @param {HTMLElement} trigger
 */
async function handleTriggerClick( event, trigger ) {
	event.preventDefault();

	const productId = trigger.dataset.productId;

	if ( ! productId ) {
		return;
	}

	const restBase = getPublicConfig().quickViewRestUrl;

	if ( ! restBase ) {
		return;
	}

	lastTrigger = trigger;
	ensureShell();

	if ( ! shellRoot ) {
		return;
	}

	openShell();
	setLoading( true );

	try {
		const response = await fetch(
			`${ String( restBase ).replace( /\/$/, '' ) }/${ productId }`,
			{
				headers: {
					'X-WP-Nonce': getPublicConfig().nonce || '',
				},
			}
		);

		const data = await response.json();

		if ( ! response.ok || ! data?.html ) {
			throw new Error( 'Quick view response empty' );
		}

		injectContent( data.html );

		const content = getContentEl();

		if ( content ) {
			primeVariationForms( content );
		}

		setLoading( false );

		const panel = getPanelEl();
		panel?.focus();
	} catch ( error ) {
		setLoading( false );
		closeShell( true );
	}
}

/**
 * @param {boolean} [immediate=false]
 */
function closeShell( immediate = false ) {
	if ( ! shellRoot?.classList.contains( 'is-open' ) ) {
		if ( immediate ) {
			finishClose();
		}
		return;
	}

	isClosing = true;
	shellRoot.classList.remove( 'is-open' );
	document.documentElement.classList.remove( 'cf-quick-view-open' );

	if ( immediate ) {
		finishClose();
		return;
	}

	window.setTimeout( () => {
		if ( isClosing ) {
			finishClose();
		}
	}, TRANSITION_MS + 50 );
}

function finishClose() {
	if ( ! shellRoot ) {
		return;
	}

	isClosing = false;

	const content = getContentEl();

	if ( content ) {
		content.innerHTML = '';
	}

	shellRoot.hidden = true;
	shellRoot.inert = true;
	shellRoot.classList.remove( 'cf-quick-view--loading' );

	if ( lastTrigger ) {
		lastTrigger.focus();
	}
}

function openShell() {
	if ( ! shellRoot ) {
		return;
	}

	isClosing = false;

	shellRoot.hidden = false;
	shellRoot.inert = false;
	document.documentElement.classList.add( 'cf-quick-view-open' );

	window.requestAnimationFrame( () => {
		shellRoot?.classList.add( 'is-open' );
	} );
}

/**
 * @param {Event} event
 */
function handleShellClick( event ) {
	const target = event.target;

	if (
		target instanceof HTMLElement &&
		target.closest( '[data-cf-quick-view-close]' )
	) {
		event.preventDefault();
		closeShell();
	}
}

/**
 * @param {KeyboardEvent} event
 */
function handleDocumentKeydown( event ) {
	if ( event.key === 'Escape' && shellRoot?.classList.contains( 'is-open' ) ) {
		event.preventDefault();
		closeShell();
	}
}

/**
 * @param {TransitionEvent} event
 */
function handleTransitionEnd( event ) {
	if (
		! shellRoot ||
		shellRoot.classList.contains( 'is-open' ) ||
		! isClosing ||
		event.target !== getPanelEl()
	) {
		return;
	}

	finishClose();
}

/**
 * Bind delegated quick-view triggers and prepare for lazy shell creation.
 */
export function initQuickView() {
	delegateDocument( 'click', '.cf-quick-view-trigger', handleTriggerClick );
}
