/**
 * Quick View product gallery — Swiper slider instead of WC thumbnail grid.
 *
 * Transforms injected `.woocommerce-product-gallery` markup into a single-image
 * swiper (Figma quick-view reference). Re-inits when swatch-driven gallery
 * swap replaces the gallery root inside `.cf-quick-view__content`.
 */

import jQuery from 'jquery';

import Swiper from 'swiper';
import { Navigation, Pagination } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

/** @type {WeakMap<Element, Swiper>} */
const swiperInstances = new WeakMap();

/**
 * @param {HTMLImageElement} img
 * @returns {{ src: string, alt: string, srcset: string, sizes: string }}
 */
function getSlideDataFromImage( img ) {
	return {
		src:
			img.dataset.largeImage ||
			img.getAttribute( 'data-large_image' ) ||
			img.dataset.src ||
			img.getAttribute( 'data-src' ) ||
			img.currentSrc ||
			img.src,
		alt: img.alt || '',
		srcset: img.srcset || '',
		sizes: img.sizes || '',
	};
}

/**
 * @param {Element} gallery
 * @returns {Array<{ src: string, alt: string, srcset: string, sizes: string }>}
 */
function collectGallerySlides( gallery ) {
	const slides = [];

	gallery
		.querySelectorAll( '.woocommerce-product-gallery__image' )
		.forEach( ( imageWrap ) => {
			const img = imageWrap.querySelector( 'img' );

			if ( ! img ) {
				return;
			}

			slides.push( getSlideDataFromImage( img ) );
		} );

	return slides;
}

/**
 * @param {Element} gallery
 */
function destroyQuickViewGallery( gallery ) {
	const instance = swiperInstances.get( gallery );

	if ( instance ) {
		instance.destroy( true, true );
		swiperInstances.delete( gallery );
	}

	gallery.classList.remove(
		'cf-quick-view-gallery',
		'cf-quick-view-gallery--ready'
	);
	delete gallery.dataset.cfQuickViewGallerySlides;
}

/**
 * @param {Element} gallery
 * @param {Array<{ src: string, alt: string, srcset: string, sizes: string }>} slides
 */
function renderQuickViewGallery( gallery, slides ) {
	const slideMarkup = slides
		.map( ( slide ) => {
			const srcsetAttr = slide.srcset
				? ` srcset="${ slide.srcset }"`
				: '';
			const sizesAttr = slide.sizes ? ` sizes="${ slide.sizes }"` : '';

			return `
				<div class="swiper-slide cf-quick-view-gallery__slide">
					<img src="${ slide.src }" alt="${ slide.alt }"${ srcsetAttr }${ sizesAttr } loading="lazy" decoding="async" />
				</div>
			`.trim();
		} )
		.join( '' );

	const navMarkup =
		slides.length > 1
			? `
				<button type="button" class="cf-quick-view-gallery__nav cf-quick-view-gallery__nav--prev" aria-label="Previous image"></button>
				<button type="button" class="cf-quick-view-gallery__nav cf-quick-view-gallery__nav--next" aria-label="Next image"></button>
			`.trim()
			: '';

	const paginationMarkup =
		slides.length > 1
			? '<div class="swiper-pagination cf-quick-view-gallery__pagination"></div>'
			: '';

	gallery.innerHTML = `
		<div class="swiper cf-quick-view-gallery__swiper">
			<div class="swiper-wrapper">${ slideMarkup }</div>
			${ navMarkup }
			${ paginationMarkup }
		</div>
	`.trim();

	gallery.classList.add( 'cf-quick-view-gallery', 'cf-quick-view-gallery--ready' );
	gallery.style.opacity = '1';

	const swiperEl = gallery.querySelector( '.cf-quick-view-gallery__swiper' );

	if ( ! swiperEl ) {
		return;
	}

	const swiper = new Swiper( swiperEl, {
		modules: [ Navigation, Pagination ],
		slidesPerView: 1,
		spaceBetween: 0,
		loop: slides.length > 1,
		navigation: {
			prevEl: gallery.querySelector(
				'.cf-quick-view-gallery__nav--prev'
			),
			nextEl: gallery.querySelector(
				'.cf-quick-view-gallery__nav--next'
			),
		},
		pagination: {
			el: gallery.querySelector( '.cf-quick-view-gallery__pagination' ),
			clickable: true,
		},
	} );

	swiperInstances.set( gallery, swiper );
	gallery.dataset.cfQuickViewGallerySlides = String( slides.length );
}

/**
 * @param {Element} gallery
 */
function initQuickViewGalleryElement( gallery ) {
	const slides = collectGallerySlides( gallery );

	if ( ! slides.length ) {
		return;
	}

	const slidesKey = slides.map( ( slide ) => slide.src ).join( '|' );

	if (
		gallery.dataset.cfQuickViewGallerySlidesKey === slidesKey &&
		gallery.classList.contains( 'cf-quick-view-gallery--ready' )
	) {
		return;
	}

	destroyQuickViewGallery( gallery );
	renderQuickViewGallery( gallery, slides );
	gallery.dataset.cfQuickViewGallerySlidesKey = slidesKey;
}

/**
 * @param {ParentNode|null|undefined} root
 */
export function initQuickViewGalleries( root ) {
	if ( ! root ) {
		return;
	}

	root.querySelectorAll( '.woocommerce-product-gallery' ).forEach( ( gallery ) => {
		initQuickViewGalleryElement( gallery );
	} );
}

/**
 * Bind re-init when swatch gallery swap finishes inside the popup.
 */
export function bindQuickViewGalleryListeners() {
	document.addEventListener( 'cf:quick-view-gallery-changed', ( event ) => {
		const target = event.target;

		if ( ! ( target instanceof Element ) ) {
			return;
		}

		const content = target.closest( '.cf-quick-view__content' );

		if ( content ) {
			initQuickViewGalleries( content );
		}
	} );

	// Variations with a single image swap WC's main image without replacing the
	// whole gallery root — refresh the swiper slide from variation.image.
	jQuery( document ).on(
		'found_variation',
		'.cf-quick-view__content .variations_form',
		( event, variation ) => {
		if ( variation?.gallery_images_html || variation?.cf_variation_gallery_html ) {
			return;
		}

			window.setTimeout( () => {
				const form = event.currentTarget;

				if ( ! form || ! variation?.image?.src ) {
					return;
				}

				const gallery = getGalleryForForm( form );

				if ( ! gallery ) {
					return;
				}

				destroyQuickViewGallery( gallery );
				renderQuickViewGallery( gallery, [
					{
						src: String( variation.image.src ),
						alt: String( variation.image.alt || '' ),
						srcset: String( variation.image.srcset || '' ),
						sizes: '',
					},
				] );
				gallery.dataset.cfQuickViewGallerySlidesKey = String(
					variation.image.src
				);
			}, 0 );
		}
	);
}

/**
 * @param {Element} form
 * @returns {Element|null}
 */
function getGalleryForForm( form ) {
	const productRoot = form.closest( '.product' );

	if ( productRoot ) {
		const scoped = productRoot.querySelector(
			'.woocommerce-product-gallery, .cf-quick-view-gallery'
		);

		if ( scoped ) {
			return scoped;
		}
	}

	return null;
}
