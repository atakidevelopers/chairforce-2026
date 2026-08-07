import { __ } from '@wordpress/i18n';
import { A11y, Keyboard, Navigation, Scrollbar } from 'swiper/modules';

import { CONTENT_UPDATED_EVENT } from '../../src/js/shared/delegated-events';
import {
	createSwiperCarousel,
	observeSwiperCarousel,
	observeSwiperCarouselResize,
	readSpacingTokenPx,
} from '../../src/js/shared/swiper-carousel';

const ROOT_SELECTOR = '.wp-block-chairforce-testimonials-carousel';
const CAROUSEL_SELECTOR = '.cf-testimonials-carousel';
const VIEWPORT_SELECTOR = '.cf-testimonials-carousel__viewport';
const INIT_FLAG = 'cfTestimonialsCarouselInitialized';

const SPACING_TOKEN = '--wp--preset--spacing--large';
const BREAKPOINT_MEDIUM = 781;
const BREAKPOINT_LARGE = 1024;

/**
 * Mark a carousel as initialized for progressive enhancement.
 *
 * @param {HTMLElement} carouselRoot Carousel root element.
 */
function markCarouselInitialized(carouselRoot) {
	carouselRoot.classList.add('is-carousel-initialized');
}

/**
 * Build Swiper configuration for one testimonials carousel.
 *
 * @param {HTMLElement} blockRoot Block root element.
 * @param {HTMLElement} carouselRoot Carousel root element.
 * @param {HTMLElement} viewportEl Swiper viewport element.
 * @return {import('swiper').SwiperOptions}
 */
function buildSwiperConfig(blockRoot, carouselRoot, viewportEl) {
	const previousButton = carouselRoot.querySelector(
		'.cf-testimonials-carousel__arrow--previous'
	);
	const nextButton = carouselRoot.querySelector(
		'.cf-testimonials-carousel__arrow--next'
	);
	const scrollbarEl = carouselRoot.querySelector(
		'.cf-testimonials-carousel__scrollbar'
	);

	const spaceBetween = readSpacingTokenPx(blockRoot, SPACING_TOKEN);

	return {
		modules: [Navigation, Scrollbar, A11y, Keyboard],
		slidesPerView: 1,
		slidesPerGroup: 1,
		spaceBetween,
		watchOverflow: true,
		grabCursor: true,
		loop: false,
		keyboard: {
			enabled: true,
			onlyInViewport: true,
		},
		a11y: {
			prevSlideMessage: __('Previous testimonial', 'chairforce'),
			nextSlideMessage: __('Next testimonial', 'chairforce'),
			slideLabelMessage: '{{index}} / {{slidesLength}}',
		},
		navigation: {
			prevEl: previousButton,
			nextEl: nextButton,
		},
		scrollbar: {
			el: scrollbarEl,
			draggable: true,
		},
		breakpoints: {
			[BREAKPOINT_MEDIUM]: {
				slidesPerView: 2,
				slidesPerGroup: 1,
				spaceBetween,
			},
			[BREAKPOINT_LARGE]: {
				slidesPerView: 3,
				slidesPerGroup: 1,
				spaceBetween,
			},
		},
		on: {
			init(swiper) {
				markCarouselInitialized(carouselRoot);

				if (swiper.isLocked) {
					carouselRoot.classList.add('is-carousel-locked');
				}
			},
			lock() {
				carouselRoot.classList.add('is-carousel-locked');
			},
			unlock() {
				carouselRoot.classList.remove('is-carousel-locked');
			},
			resize(swiper) {
				const nextSpaceBetween = readSpacingTokenPx(
					blockRoot,
					SPACING_TOKEN
				);

				if (swiper.params.spaceBetween !== nextSpaceBetween) {
					swiper.params.spaceBetween = nextSpaceBetween;

					if (swiper.params.breakpoints) {
						Object.values(swiper.params.breakpoints).forEach(
							(breakpointConfig) => {
								breakpointConfig.spaceBetween =
									nextSpaceBetween;
							}
						);
					}

					swiper.update();
				}
			},
		},
	};
}

/**
 * Initialize one testimonials carousel instance.
 *
 * @param {HTMLElement} blockRoot Block root element.
 */
function initTestimonialsCarousel(blockRoot) {
	if (!(blockRoot instanceof HTMLElement) || blockRoot[INIT_FLAG]) {
		return;
	}

	const carouselRoot = blockRoot.querySelector(CAROUSEL_SELECTOR);
	const viewportEl = blockRoot.querySelector(VIEWPORT_SELECTOR);

	if (!carouselRoot || !viewportEl) {
		return;
	}

	const slideCount = parseInt(viewportEl.dataset.slideCount || '0', 10);

	if (slideCount <= 1) {
		markCarouselInitialized(carouselRoot);
		carouselRoot.classList.add('is-carousel-locked');
		blockRoot[INIT_FLAG] = true;
		return;
	}

	blockRoot[INIT_FLAG] = true;

	const start = () => {
		if (!blockRoot.isConnected || !viewportEl.isConnected) {
			return;
		}

		const config = buildSwiperConfig(blockRoot, carouselRoot, viewportEl);
		const instance = createSwiperCarousel(viewportEl, config);

		if (!instance) {
			return;
		}

		observeSwiperCarouselResize(viewportEl, () => {
			const nextSpaceBetween = readSpacingTokenPx(
				blockRoot,
				SPACING_TOKEN
			);

			if (instance.params.spaceBetween !== nextSpaceBetween) {
				instance.params.spaceBetween = nextSpaceBetween;

				if (instance.params.breakpoints) {
					Object.values(instance.params.breakpoints).forEach(
						(breakpointConfig) => {
							breakpointConfig.spaceBetween = nextSpaceBetween;
						}
					);
				}

				instance.update();
			}
		});
	};

	observeSwiperCarousel(viewportEl, start, {
		rootMargin: '200px 0px',
	});
}

/**
 * Initialize all uninitialized testimonial carousels within a scope.
 *
 * @param {ParentNode} [scope=document]
 */
function initTestimonialsCarousels(scope = document) {
	scope.querySelectorAll(ROOT_SELECTOR).forEach((blockRoot) => {
		if (!blockRoot[INIT_FLAG]) {
			initTestimonialsCarousel(blockRoot);
		}
	});
}

initTestimonialsCarousels();

document.addEventListener(CONTENT_UPDATED_EVENT, (event) => {
	const scope = event?.detail?.root;

	if (scope instanceof ParentNode) {
		initTestimonialsCarousels(scope);
		return;
	}

	initTestimonialsCarousels();
});

document.addEventListener('DOMContentLoaded', () => {
	initTestimonialsCarousels();
});
