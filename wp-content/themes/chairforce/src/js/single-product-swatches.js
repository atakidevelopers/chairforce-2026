/**
 * Single-product variation swatches: proxy clicks into WooCommerce selects and
 * swap the main gallery when a variation exposes cf_variation_gallery_html.
 *
 * Swatch clicks use vanilla delegation (3b convention). found_variation,
 * reset_data, and woocommerce_update_variation_values use a scoped jQuery
 * exception — those events are jQuery-only in WooCommerce core.
 *
 * @see context/plans/3e-single-product-swatches-and-gallery-plan.md
 * @see context/plans/3h-quick-view-plan.md
 */

import jQuery from 'jquery';

import { delegateDocument } from './shared/delegated-events';

/** @type {WeakMap<Element, { defaultGalleryHtml: string, galleryReplaced: boolean }>} */
const formState = new WeakMap();

let swatchListenersBound = false;
let singleProductSwatchesInitialized = false;

/**
 * @param {Element} form
 */
function getFormState( form ) {
	if ( ! formState.has( form ) ) {
		const gallery = getGalleryForForm( form );

		formState.set( form, {
			defaultGalleryHtml: gallery?.outerHTML || '',
			galleryReplaced: false,
		} );
	}

	return formState.get( form );
}

/**
 * @param {Element} form
 * @returns {boolean}
 */
function isQuickViewForm( form ) {
	return Boolean( form.closest( '.cf-quick-view__content' ) );
}

/**
 * Notify quick-view gallery module that gallery markup changed.
 *
 * @param {Element} form
 */
function dispatchQuickViewGalleryChanged( form ) {
	if ( ! isQuickViewForm( form ) ) {
		return;
	}

	form.dispatchEvent(
		new CustomEvent( 'cf:quick-view-gallery-changed', { bubbles: true } )
	);
}

/**
 * @param {Element} form
 * @returns {Element|null}
 */
function getGalleryForForm( form ) {
	const productRoot =
		form.closest( '.product' ) ||
		form.closest( '.wp-block-woocommerce-add-to-cart-form' )?.parentElement;

	if ( productRoot ) {
		const scoped = productRoot.querySelector(
			'.woocommerce-product-gallery'
		);
		if ( scoped ) {
			return scoped;
		}
	}

	return document.querySelector( '.woocommerce-product-gallery' );
}

/**
 * @param {Element} form
 * @param {string} galleryHtml
 * @param {Record<string, unknown>|false} variation
 * @returns {boolean}
 */
function replaceProductGallery( form, galleryHtml, variation ) {
	const $form = jQuery( form );

	if (
		typeof $form.wc_variations_gallery_replace === 'function' &&
		galleryHtml
	) {
		const replaced = Boolean(
			$form.wc_variations_gallery_replace( galleryHtml, variation )
		);

		if ( replaced ) {
			dispatchQuickViewGalleryChanged( form );
		}

		return replaced;
	}

	const gallery = getGalleryForForm( form );

	if ( ! gallery || ! galleryHtml ) {
		return false;
	}

	const template = document.createElement( 'template' );
	template.innerHTML = galleryHtml.trim();
	const newGallery = template.content.querySelector(
		'.woocommerce-product-gallery'
	);

	if ( ! newGallery ) {
		return false;
	}

	gallery.replaceWith( newGallery );

	if ( ! isQuickViewForm( form ) ) {
		jQuery( newGallery ).wc_product_gallery();
	}

	window.dispatchEvent( new Event( 'resize' ) );

	if ( variation && variation.image_id ) {
		$form.attr( 'current-image', String( variation.image_id ) );
		$form.attr( 'data-product_gallery_active', 'yes' );
	} else {
		$form.removeAttr( 'data-product_gallery_active' );
		$form.attr( 'current-image', '' );
	}

	dispatchQuickViewGalleryChanged( form );

	return true;
}

/**
 * @param {Element} form
 */
function syncActiveSwatches( form ) {
	form.querySelectorAll( '.cf-swatches-single' ).forEach( ( group ) => {
		const selectId = group.dataset.id;

		if ( ! selectId ) {
			return;
		}

		const select = form.querySelector(
			`select#${ CSS.escape( selectId ) }`
		);

		if ( ! select ) {
			return;
		}

		const selectedValue = select.value;

		group.querySelectorAll( '.cf-swatch' ).forEach( ( swatch ) => {
			swatch.classList.toggle(
				'cf-swatch--active',
				swatch.dataset.value === selectedValue
			);
		} );
	} );
}

/**
 * @param {Element} form
 */
function syncSwatchDisabledState( form ) {
	form.querySelectorAll( '.cf-swatches-single' ).forEach( ( group ) => {
		const selectId = group.dataset.id;

		if ( ! selectId ) {
			return;
		}

		const select = form.querySelector(
			`select#${ CSS.escape( selectId ) }`
		);

		if ( ! select ) {
			return;
		}

		group.querySelectorAll( '.cf-swatch' ).forEach( ( swatch ) => {
			const value = swatch.dataset.value || '';
			const option = Array.from( select.options ).find(
				( opt ) => opt.value === value
			);
			const isEnabled =
				option &&
				! option.disabled &&
				! option.classList.contains( 'disabled' );

			swatch.classList.toggle( 'cf-swatch--out-of-stock', ! isEnabled );
			swatch.disabled = ! isEnabled;
		} );
	} );
}

/**
 * @param {Event} event
 * @param {Element} swatch
 */
function handleSwatchClick( event, swatch ) {
	event.preventDefault();

	const group = swatch.closest( '.cf-swatches-single' );
	const form = swatch.closest( '.variations_form' );

	if ( ! group || ! form || swatch.disabled ) {
		return;
	}

	const selectId = group.dataset.id;

	if ( ! selectId ) {
		return;
	}

	const select = form.querySelector( `select#${ CSS.escape( selectId ) }` );

	if ( ! select ) {
		return;
	}

	select.value = swatch.dataset.value || '';
	select.dispatchEvent( new Event( 'change', { bubbles: true } ) );

	group.querySelectorAll( '.cf-swatch--active' ).forEach( ( active ) => {
		active.classList.remove( 'cf-swatch--active' );
	} );
	swatch.classList.add( 'cf-swatch--active' );
}

/**
 * Runs after WooCommerce's onFoundVariation (document bubble) so the full
 * gallery root is replaced via wc_variations_gallery_replace().
 *
 * @param {JQuery.TriggeredEvent} event
 * @param {Record<string, unknown>} variation
 */
function handleFoundVariation( event, variation ) {
	const form = event.currentTarget;

	if ( ! form || ! variation?.cf_variation_gallery_html ) {
		return;
	}

	const state = getFormState( form );
	const replaced = replaceProductGallery(
		form,
		String( variation.cf_variation_gallery_html ),
		variation
	);

	state.galleryReplaced = replaced;
	syncActiveSwatches( form );
	syncSwatchDisabledState( form );
}

/**
 * @param {JQuery.TriggeredEvent} event
 */
function handleResetData( event ) {
	const form = event.currentTarget;

	if ( ! form ) {
		return;
	}

	const state = getFormState( form );

	if ( state.galleryReplaced && state.defaultGalleryHtml ) {
		replaceProductGallery( form, state.defaultGalleryHtml, false );
		state.galleryReplaced = false;
	}

	form.querySelectorAll( '.cf-swatch--active' ).forEach( ( swatch ) => {
		swatch.classList.remove( 'cf-swatch--active' );
	} );

	syncSwatchDisabledState( form );
}

/**
 * @param {JQuery.TriggeredEvent} event
 */
function handleVariationValuesUpdated( event ) {
	const form = event.currentTarget;

	if ( ! form ) {
		return;
	}

	syncActiveSwatches( form );
	syncSwatchDisabledState( form );
}

/**
 * @param {JQuery.TriggeredEvent} event
 */
function handleResetVariationsClick( event ) {
	const link = event.currentTarget;
	const form = link.closest( '.variations_form' );

	if ( ! form ) {
		return;
	}

	form.querySelectorAll( '.cf-swatch--active' ).forEach( ( swatch ) => {
		swatch.classList.remove( 'cf-swatch--active' );
	} );
}

/**
 * Bind delegated swatch + jQuery variation handlers once (Load-More-safe).
 */
export function bindSingleProductSwatchListeners() {
	if ( swatchListenersBound ) {
		return;
	}

	swatchListenersBound = true;

	delegateDocument(
		'click',
		'.cf-swatches-single .cf-swatch',
		handleSwatchClick
	);

	jQuery( document ).on(
		'found_variation',
		'.variations_form',
		handleFoundVariation
	);
	jQuery( document ).on( 'reset_data', '.variations_form', handleResetData );
	jQuery( document ).on(
		'woocommerce_update_variation_values',
		'.variations_form',
		handleVariationValuesUpdated
	);
	jQuery( document ).on(
		'click',
		'.variations_form .reset_variations',
		handleResetVariationsClick
	);
}

/**
 * Cache default gallery markup for variation forms within `root`.
 * Safe to call again after AJAX/quick-view injects new forms.
 *
 * @param {ParentNode} [root]
 */
export function primeVariationForms( root = document ) {
	const forms = root.querySelectorAll( '.variations_form' );

	if ( ! forms.length ) {
		return;
	}

	forms.forEach( ( form ) => {
		getFormState( form );
	} );

	// WC fires the first availability pass during its own init — defer so we
	// register listeners first, then sync once its option classes are settled.
	jQuery( function () {
		window.setTimeout( () => {
			forms.forEach( ( form ) => {
				syncSwatchDisabledState( form );
			} );
		}, 0 );
	} );
}

/**
 * One-time init for page load — binds listeners and primes existing forms.
 */
export function initSingleProductSwatches() {
	if ( singleProductSwatchesInitialized ) {
		return;
	}

	singleProductSwatchesInitialized = true;
	bindSingleProductSwatchListeners();
	primeVariationForms( document );
}
