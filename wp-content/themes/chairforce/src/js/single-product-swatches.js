/**
 * Single-product variation swatches: proxy clicks into WooCommerce selects and
 * swap the main gallery when a variation exposes cf_variation_gallery_html.
 *
 * Swatch clicks use vanilla delegation (3b convention). found_variation,
 * reset_data, and woocommerce_update_variation_values use a scoped jQuery
 * exception — those events are jQuery-only in WooCommerce core.
 *
 * @see context/plans/3e-single-product-swatches-and-gallery-plan.md
 */

import jQuery from 'jquery';

import { delegateDocument } from './shared/delegated-events';

/** @type {WeakMap<Element, { gallery: Element|null, defaultWrapperHtml: string, galleryReplaced: boolean }>} */
const formState = new WeakMap();

/**
 * @param {Element} form
 */
function getFormState( form ) {
	if ( ! formState.has( form ) ) {
		const gallery = getGalleryForForm( form );
		const wrapper = gallery?.querySelector(
			'.woocommerce-product-gallery__wrapper'
		);

		formState.set( form, {
			gallery: gallery || null,
			defaultWrapperHtml: wrapper?.outerHTML || '',
			galleryReplaced: false,
		} );
	}

	return formState.get( form );
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
 * @param {Element|null|undefined} gallery
 */
function reinitProductGallery( gallery ) {
	if ( ! gallery ) {
		return;
	}

	jQuery( gallery ).wc_product_gallery();
	window.dispatchEvent( new Event( 'resize' ) );
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
 * @param {JQuery.TriggeredEvent} event
 * @param {Record<string, unknown>} variation
 */
function handleFoundVariation( event, variation ) {
	const form = event.currentTarget;

	if ( ! form || ! variation?.cf_variation_gallery_html ) {
		return;
	}

	const state = getFormState( form );
	const wrapper = state.gallery?.querySelector(
		'.woocommerce-product-gallery__wrapper'
	);

	if ( ! wrapper ) {
		return;
	}

	wrapper.outerHTML = String( variation.cf_variation_gallery_html );
	state.galleryReplaced = true;
	reinitProductGallery( state.gallery );
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

	if ( state.galleryReplaced && state.defaultWrapperHtml && state.gallery ) {
		const wrapper = state.gallery.querySelector(
			'.woocommerce-product-gallery__wrapper'
		);

		if ( wrapper ) {
			wrapper.outerHTML = state.defaultWrapperHtml;
		}

		state.galleryReplaced = false;
		reinitProductGallery( state.gallery );
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
 * Cache default gallery markup and bind delegated handlers for every form.
 */
export function initSingleProductSwatches() {
	const forms = document.querySelectorAll( '.variations_form' );

	if ( ! forms.length ) {
		return;
	}

	forms.forEach( ( form ) => {
		getFormState( form );
	} );

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
