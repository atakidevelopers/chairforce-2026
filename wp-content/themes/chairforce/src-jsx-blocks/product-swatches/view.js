import { delegateDocument } from '../../src/js/shared/delegated-events';

const SWATCH_SELECTOR = '.cf-swatches-grid .cf-swatch';
const DIVIDER_SELECTOR = '.cf-swatches-grid .cf-swatch-divider';
const PRODUCT_CARD_SELECTOR =
	'.wc-block-product, .product-grid-item, .product-teaser';

/**
 * @returns {boolean}
 */
function isTouchDevice() {
	return (
		'ontouchstart' in window ||
		( navigator.maxTouchPoints && navigator.maxTouchPoints > 0 )
	);
}

/**
 * @param {Element} swatch
 * @returns {Element|null}
 */
function getProductCard( swatch ) {
	return swatch.closest( PRODUCT_CARD_SELECTOR );
}

/**
 * @param {Element} product
 * @returns {HTMLImageElement|null}
 */
function getProductImage( product ) {
	return product.querySelector(
		'.wc-block-components-product-image img, .product-image-link > img, .product-image-link > picture > img'
	);
}

/**
 * @param {HTMLImageElement} image
 */
function cacheOriginalImage( image ) {
	if ( ! image.dataset.cfOriginalSrc ) {
		image.dataset.cfOriginalSrc = image.getAttribute( 'src' ) || '';
	}

	if ( ! image.dataset.cfOriginalSrcset ) {
		image.dataset.cfOriginalSrcset = image.getAttribute( 'srcset' ) || '';
	}

	if ( ! image.dataset.cfOriginalSizes ) {
		image.dataset.cfOriginalSizes = image.getAttribute( 'sizes' ) || '';
	}
}

/**
 * Swap the card thumbnail to the swatch's variation image (swap-and-stay).
 *
 * @param {Element} swatch
 */
function swapProductImage( swatch ) {
	const imageSrc = swatch.dataset.imageSrc;

	if ( ! imageSrc ) {
		return;
	}

	const product = getProductCard( swatch );

	if ( ! product ) {
		return;
	}

	const image = getProductImage( product );

	if ( ! image ) {
		return;
	}

	const imageSrcset = swatch.dataset.imageSrcset || '';
	const imageSizes = swatch.dataset.imageSizes || '';

	cacheOriginalImage( image );

	if ( image.getAttribute( 'src' ) === imageSrc ) {
		return;
	}

	const swatchGroup = swatch.closest( '.cf-swatches-grid' );

	swatchGroup
		?.querySelectorAll( '.cf-swatch--active' )
		.forEach( ( activeSwatch ) => {
			activeSwatch.classList.remove( 'cf-swatch--active' );
		} );

	swatch.classList.add( 'cf-swatch--active' );
	product.classList.add( 'cf-product-swatched' );
	product.classList.add( 'cf-loading-image' );

	const onLoaded = () => {
		product.classList.remove( 'cf-loading-image' );
	};

	if ( image.complete ) {
		onLoaded();
	} else {
		image.addEventListener( 'load', onLoaded, { once: true } );
		image.addEventListener( 'error', onLoaded, { once: true } );
	}

	image.setAttribute( 'src', imageSrc );

	if ( imageSrcset ) {
		image.setAttribute( 'srcset', imageSrcset );
	}

	if ( imageSizes ) {
		image.setAttribute( 'sizes', imageSizes );
	}

	const pictureSource = product.querySelector(
		'.wc-block-components-product-image picture source, .product-image-link picture source'
	);

	if ( pictureSource && imageSrcset ) {
		pictureSource.setAttribute( 'srcset', imageSrcset );

		if ( imageSizes ) {
			pictureSource.setAttribute( 'sizes', imageSizes );
		}
	}
}

/**
 * @param {Element} target Swatch or divider inside a limited group.
 */
function expandSwatches( target ) {
	const group = target.closest( '.cf-swatches-product' );

	if ( ! group || group.classList.contains( 'cf-swatches--all-shown' ) ) {
		return;
	}

	group.classList.add( 'cf-swatches--all-shown' );
	group.querySelectorAll( '.cf-swatch--hidden' ).forEach( ( hiddenSwatch ) => {
		hiddenSwatch.classList.remove( 'cf-swatch--hidden' );
	} );
}

/**
 * @param {Element} swatch
 */
function handleSwatchActivate( swatch ) {
	const group = swatch.closest( '.cf-swatches-product' );

	if (
		group?.classList.contains( 'cf-swatches--limited' ) &&
		! group.classList.contains( 'cf-swatches--all-shown' )
	) {
		expandSwatches( swatch );
	}

	swapProductImage( swatch );
}

function initProductSwatches() {
	delegateDocument( 'click', DIVIDER_SELECTOR, ( event, divider ) => {
		event.preventDefault();
		expandSwatches( divider );
	} );

	delegateDocument( 'click', SWATCH_SELECTOR, ( event, swatch ) => {
		event.preventDefault();
		handleSwatchActivate( swatch );
	} );

	if ( ! isTouchDevice() ) {
		delegateDocument( 'mouseenter', SWATCH_SELECTOR, ( event, swatch ) => {
			if ( swatch.classList.contains( 'cf-swatch--hidden' ) ) {
				return;
			}

			handleSwatchActivate( swatch );
		} );
	}
}

initProductSwatches();
