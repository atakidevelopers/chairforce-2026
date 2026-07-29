/**
 * Segmented WooCommerce quantity +/- buttons.
 *
 * @see lib/class-woocommerce-quantity.php
 */

import { delegateDocument } from './shared/delegated-events';

/**
 * Parse a numeric attribute with a fallback.
 *
 * @param {string|undefined} value Attribute value.
 * @param {number}           fallback Fallback when missing/invalid.
 * @return {number}
 */
function parseNumber( value, fallback ) {
	const parsed = Number.parseFloat( value );

	return Number.isFinite( parsed ) ? parsed : fallback;
}

/**
 * Update quantity input value and notify WooCommerce listeners.
 *
 * @param {HTMLInputElement} input Quantity field.
 * @param {number}           nextValue New quantity.
 */
function setQuantityValue( input, nextValue ) {
	input.value = String( nextValue );
	input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
}

/**
 * Step quantity up or down respecting min/max/step.
 *
 * @param {HTMLInputElement} input Quantity field.
 * @param {number}           direction `-1` or `1`.
 */
function stepQuantity( input, direction ) {
	const step = parseNumber( input.step, 1 );
	const min = parseNumber( input.min, step );
	const max = parseNumber( input.max, Number.POSITIVE_INFINITY );
	const current = parseNumber( input.value, min );
	let next = current + direction * step;

	if ( next < min ) {
		next = min;
	}

	if ( Number.isFinite( max ) && max > 0 && next > max ) {
		next = max;
	}

	setQuantityValue( input, next );
}

/**
 * Bind delegated handlers for quantity steppers.
 */
export function initWooCommerceQuantity() {
	delegateDocument( 'click', '.cf-qty-button--minus', ( event, button ) => {
		event.preventDefault();
		const input = button.closest( '.quantity' )?.querySelector( '.qty' );

		if ( input instanceof HTMLInputElement && ! input.readOnly ) {
			stepQuantity( input, -1 );
		}
	} );

	delegateDocument( 'click', '.cf-qty-button--plus', ( event, button ) => {
		event.preventDefault();
		const input = button.closest( '.quantity' )?.querySelector( '.qty' );

		if ( input instanceof HTMLInputElement && ! input.readOnly ) {
			stepQuantity( input, 1 );
		}
	} );
}
