/**
 * Shared Swiper lifecycle helpers for Chairforce carousels.
 *
 * @package Chairforce
 */

import Swiper from 'swiper';

/** @type {WeakMap<HTMLElement, import('swiper').default>} */
const instances = new WeakMap();

/** @type {WeakMap<HTMLElement, IntersectionObserver>} */
const observers = new WeakMap();

/** @type {WeakMap<HTMLElement, ResizeObserver>} */
const resizeObservers = new WeakMap();

/**
 * Return an existing Swiper instance for a viewport element.
 *
 * @param {HTMLElement} viewportEl Swiper root element.
 * @return {import('swiper').default|null}
 */
export function getSwiperInstance(viewportEl) {
	return instances.get(viewportEl) || null;
}

/**
 * Destroy a Swiper instance safely.
 *
 * @param {HTMLElement} viewportEl Swiper root element.
 */
export function destroySwiperCarousel(viewportEl) {
	const observer = observers.get(viewportEl);

	if (observer) {
		observer.disconnect();
		observers.delete(viewportEl);
	}

	const resizeObserver = resizeObservers.get(viewportEl);

	if (resizeObserver) {
		resizeObserver.disconnect();
		resizeObservers.delete(viewportEl);
	}

	const instance = instances.get(viewportEl);

	if (!instance) {
		return;
	}

	instance.destroy(true, true);
	instances.delete(viewportEl);
}

/**
 * Create or return an existing Swiper instance.
 *
 * @param {HTMLElement} viewportEl Swiper root element.
 * @param {import('swiper').SwiperOptions} config Swiper configuration.
 * @return {import('swiper').default|null}
 */
export function createSwiperCarousel(viewportEl, config = {}) {
	if (!(viewportEl instanceof HTMLElement) || !viewportEl.isConnected) {
		return null;
	}

	const existing = instances.get(viewportEl);

	if (existing) {
		return existing;
	}

	try {
		const instance = new Swiper(viewportEl, config);
		instances.set(viewportEl, instance);
		return instance;
	} catch (error) {
		if (typeof console !== 'undefined' && console.warn) {
			console.warn('Chairforce carousel failed to initialize.', error);
		}

		return null;
	}
}

/**
 * Observe a carousel viewport and initialize when near the viewport.
 *
 * @param {HTMLElement} viewportEl Swiper root element.
 * @param {Function} initCallback Callback invoked once when initialization should occur.
 * @param {IntersectionObserverInit} [observerOptions] Observer options.
 */
export function observeSwiperCarousel(
	viewportEl,
	initCallback,
	observerOptions = {}
) {
	if (!(viewportEl instanceof HTMLElement) || !viewportEl.isConnected) {
		return;
	}

	if (instances.get(viewportEl)) {
		return;
	}

	const existingObserver = observers.get(viewportEl);

	if (existingObserver) {
		return;
	}

	const runInit = () => {
		if (!viewportEl.isConnected || instances.get(viewportEl)) {
			return;
		}

		initCallback(viewportEl);
	};

	if (
		typeof window === 'undefined' ||
		typeof window.IntersectionObserver === 'undefined'
	) {
		runInit();
		return;
	}

	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) {
				return;
			}

			observer.disconnect();
			observers.delete(viewportEl);
			runInit();
		});
	}, observerOptions);

	observer.observe(viewportEl);
	observers.set(viewportEl, observer);
}

/**
 * Watch a carousel viewport and run a callback when its size changes.
 *
 * @param {HTMLElement} viewportEl Swiper root element.
 * @param {Function} callback Callback invoked on resize.
 */
export function observeSwiperCarouselResize(viewportEl, callback) {
	if (
		!(viewportEl instanceof HTMLElement) ||
		typeof callback !== 'function'
	) {
		return;
	}

	const existing = resizeObservers.get(viewportEl);

	if (existing) {
		return;
	}

	if (
		typeof window === 'undefined' ||
		typeof window.ResizeObserver === 'undefined'
	) {
		callback();
		return;
	}

	const resizeObserver = new ResizeObserver(() => {
		callback();
	});

	resizeObserver.observe(viewportEl);
	resizeObservers.set(viewportEl, resizeObserver);
}

/**
 * Read a theme spacing custom property as pixels.
 *
 * @param {HTMLElement} root Element used for computed-style resolution.
 * @param {string} token CSS custom property name.
 * @return {number}
 */
export function readSpacingTokenPx(
	root,
	token = '--wp--preset--spacing--large'
) {
	if (!(root instanceof HTMLElement) || typeof window === 'undefined') {
		return 24;
	}

	const doc = root.ownerDocument;
	const probe = doc.createElement('div');

	probe.style.position = 'absolute';
	probe.style.visibility = 'hidden';
	probe.style.pointerEvents = 'none';
	probe.style.width = '0';
	probe.style.height = '0';
	probe.style.paddingLeft = `var(${token})`;

	root.appendChild(probe);

	const value = parseFloat(window.getComputedStyle(probe).paddingLeft);

	probe.remove();

	return Number.isFinite(value) && value > 0 ? value : 24;
}

export { Swiper };
