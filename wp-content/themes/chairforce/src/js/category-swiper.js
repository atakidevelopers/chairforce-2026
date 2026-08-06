/**
 * Shared category swiper — Swiper 11 init for term-card carousels.
 */

import Swiper from 'swiper';
import { Navigation, Scrollbar } from 'swiper/modules';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/scrollbar';

import { CONTENT_UPDATED_EVENT } from './shared/delegated-events';

const ROOT_SELECTOR = '[data-cf-category-swiper]';
const SWIPER_SELECTOR = '.cf-category-swiper__swiper';
const NAV_WRAP_SELECTOR = '.cf-category-swiper__nav';
const PREV_SELECTOR = '.cf-category-swiper__arrow--prev';
const NEXT_SELECTOR = '.cf-category-swiper__arrow--next';
const SCROLLBAR_SELECTOR = '.cf-category-swiper__scrollbar';
const DESKTOP_ARROWS_CLASS = 'cf-category-swiper__nav--desktop';
const MOBILE_ARROWS_CLASS = 'cf-category-swiper__nav--mobile';
const NAV_MEDIA = window.matchMedia( '(min-width: 781px)' );

/** @type {WeakMap<HTMLElement, Swiper>} */
const swiperInstances = new WeakMap();

/**
 * @param {HTMLElement} root
 * @param {string} name
 * @param {boolean} fallback
 * @return {boolean}
 */
function readBoolData( root, name, fallback ) {
	const value = root.dataset[ name ];

	if ( undefined === value ) {
		return fallback;
	}

	return 'true' === value;
}

/**
 * @param {HTMLElement} root
 */
function syncArrowVisibilityClasses( root ) {
	const nav = root.querySelector( NAV_WRAP_SELECTOR );

	if ( ! nav ) {
		return;
	}

	const showDesktop = readBoolData( root, 'showArrowsDesktop', true );
	const showMobile = readBoolData( root, 'showArrowsMobile', false );
	const isDesktop = NAV_MEDIA.matches;

	nav.hidden = ! ( ( isDesktop && showDesktop ) || ( ! isDesktop && showMobile ) );
	nav.classList.toggle( DESKTOP_ARROWS_CLASS, showDesktop );
	nav.classList.toggle( MOBILE_ARROWS_CLASS, showMobile );
}

/**
 * @param {HTMLElement} root
 */
function destroyCategorySwiper( root ) {
	const instance = swiperInstances.get( root );

	if ( instance ) {
		instance.destroy( true, true );
		swiperInstances.delete( root );
	}

	delete root.dataset.cfCategorySwiperInit;
}

/**
 * @param {HTMLElement} root
 */
function initCategorySwiper( root ) {
	if ( root.dataset.cfCategorySwiperInit === 'true' ) {
		return;
	}

	const swiperEl = root.querySelector( SWIPER_SELECTOR );

	if ( ! swiperEl ) {
		return;
	}

	syncArrowVisibilityClasses( root );

	const showProgressBar = readBoolData( root, 'showProgressBar', true );
	const showArrowsDesktop = readBoolData( root, 'showArrowsDesktop', true );
	const showArrowsMobile = readBoolData( root, 'showArrowsMobile', false );
	const nav = root.querySelector( NAV_WRAP_SELECTOR );
	const prevEl = nav?.querySelector( PREV_SELECTOR ) ?? null;
	const nextEl = nav?.querySelector( NEXT_SELECTOR ) ?? null;
	const scrollbarEl = root.querySelector( SCROLLBAR_SELECTOR );

	/** @type {import('swiper').SwiperOptions} */
	const options = {
		modules: [ Navigation, Scrollbar ],
		slidesPerView: 'auto',
		spaceBetween: 12,
		watchOverflow: true,
	};

	if ( showProgressBar && scrollbarEl ) {
		options.scrollbar = {
			el: scrollbarEl,
			dragSize: 80,
			hide: false,
			draggable: true,
		};
	}

	if ( nav && ( showArrowsDesktop || showArrowsMobile ) ) {
		options.navigation = {
			prevEl,
			nextEl,
			disabledClass: 'cf-category-swiper__arrow--disabled',
		};
	}

	const swiper = new Swiper( swiperEl, options );

	swiperInstances.set( root, swiper );
	root.dataset.cfCategorySwiperInit = 'true';
}

/**
 * @param {ParentNode|null|undefined} scope
 */
export function initCategorySwipers( scope ) {
	const context = scope ?? document;

	context.querySelectorAll( ROOT_SELECTOR ).forEach( ( root ) => {
		if ( root instanceof HTMLElement ) {
			initCategorySwiper( root );
		}
	} );
}

/**
 * Re-init swipers inside replaced DOM (e.g. archive shell reload).
 *
 * @param {ParentNode|null|undefined} scope
 */
export function refreshCategorySwipers( scope ) {
	const context = scope ?? document;

	context.querySelectorAll( ROOT_SELECTOR ).forEach( ( root ) => {
		if ( root instanceof HTMLElement ) {
			destroyCategorySwiper( root );
			initCategorySwiper( root );
		}
	} );
}

/**
 * Bind resize + content-updated listeners.
 */
export function bindCategorySwiperListeners() {
	let resizeTimer = 0;

	window.addEventListener( 'resize', () => {
		window.clearTimeout( resizeTimer );
		resizeTimer = window.setTimeout( () => {
			document.querySelectorAll( ROOT_SELECTOR ).forEach( ( root ) => {
				if ( root instanceof HTMLElement ) {
					syncArrowVisibilityClasses( root );
				}
			} );
		}, 150 );
	} );

	document.addEventListener( CONTENT_UPDATED_EVENT, ( event ) => {
		const detail = event.detail ?? {};
		const scope =
			detail?.scope instanceof ParentNode ? detail.scope : document;

		refreshCategorySwipers( scope );
	} );
}
