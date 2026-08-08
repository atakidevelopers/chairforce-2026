import Swiper from 'swiper';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';

import {
	CONTENT_UPDATED_EVENT,
	delegateOn,
} from '../../src/js/shared/delegated-events';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const ROOT_SELECTOR = '.wp-block-chairforce-showroom-locator-full';
const CARD_SLOT_SELECTOR = '.cf-showroom-locator-full__card-slot';
const MARKER_SELECTOR = '.cf-showroom-locator-full__marker';
const FEATURED_SELECTOR = '.cf-showroom-locator-full__featured';
const GRID_SELECTOR = '.cf-showroom-locator-full__grid';
const INIT_FLAG = 'cfShowroomLocatorFullInitialized';
const SWIPER_FLAG = 'cfShowroomLocatorFullSwiper';

/**
 * @param {HTMLElement} featured Featured region.
 */
function destroyFeaturedSwiper(featured) {
	const swiperEl = featured.querySelector('.cf-showroom-locator-full__swiper');

	if (swiperEl?.[SWIPER_FLAG]) {
		swiperEl[SWIPER_FLAG].destroy(true, true);
		delete swiperEl[SWIPER_FLAG];
	}

	featured.querySelector('.cf-showroom-locator-full__state-swiper-wrap')?.remove();
}

/**
 * @param {HTMLElement} root Block root.
 * @param {string}      stateSlug State slug.
 */
function updateMarkers(root, stateSlug) {
	root.querySelectorAll(MARKER_SELECTOR).forEach((marker) => {
		const isActive = marker.dataset.showroomState === stateSlug;

		marker.classList.toggle('is-active', isActive);

		if (isActive) {
			marker.setAttribute('aria-current', 'true');
		} else {
			marker.removeAttribute('aria-current');
		}
	});
}

/**
 * @param {HTMLElement} root Block root.
 * @param {string}      activeSlug Active showroom slug.
 */
function updateCardStates(root, activeSlug) {
	root.querySelectorAll(CARD_SLOT_SELECTOR).forEach((slot) => {
		const isActive = slot.dataset.showroomSlug === activeSlug;

		slot.classList.toggle('is-active', isActive);
		slot.dataset.cardContext = isActive ? 'featured' : 'grid';
	});
}

/**
 * @param {HTMLElement} root      Block root.
 * @param {string}      stateSlug State taxonomy slug.
 * @return {HTMLElement[]}
 */
function getStateSlots(root, stateSlug) {
	return Array.from(
		root.querySelectorAll(
			`${CARD_SLOT_SELECTOR}[data-showroom-state="${stateSlug}"]`
		)
	);
}

/**
 * @param {HTMLElement} featured Featured region.
 * @param {HTMLElement[]} slots  Slots to show in swiper.
 */
function mountFeaturedSwiper(featured, slots) {
	destroyFeaturedSwiper(featured);

	const wrap = document.createElement('div');
	wrap.className = 'cf-showroom-locator-full__state-swiper-wrap';

	const swiperEl = document.createElement('div');
	swiperEl.className = 'cf-showroom-locator-full__swiper swiper';

	const slidesWrap = document.createElement('div');
	slidesWrap.className = 'swiper-wrapper';

	slots.forEach((slot) => {
		const slide = document.createElement('div');
		slide.className = 'swiper-slide';
		slide.appendChild(slot);
		slidesWrap.appendChild(slide);
	});

	swiperEl.appendChild(slidesWrap);

	const controls = document.createElement('div');
	controls.className = 'cf-showroom-locator-full__swiper-controls';

	const prevButton = document.createElement('button');
	prevButton.type = 'button';
	prevButton.className =
		'cf-showroom-locator-full__nav cf-showroom-locator-full__nav--prev';
	prevButton.setAttribute('aria-label', 'Previous showroom');

	const pagination = document.createElement('div');
	pagination.className = 'swiper-pagination';

	const nextButton = document.createElement('button');
	nextButton.type = 'button';
	nextButton.className =
		'cf-showroom-locator-full__nav cf-showroom-locator-full__nav--next';
	nextButton.setAttribute('aria-label', 'Next showroom');

	controls.appendChild(prevButton);
	controls.appendChild(pagination);
	controls.appendChild(nextButton);

	wrap.appendChild(swiperEl);
	wrap.appendChild(controls);
	featured.appendChild(wrap);

	swiperEl[SWIPER_FLAG] = new Swiper(swiperEl, {
		modules: [Autoplay, Navigation, Pagination],
		slidesPerView: 1,
		spaceBetween: 16,
		autoplay: {
			delay: 3000,
			disableOnInteraction: false,
			pauseOnMouseEnter: true,
		},
		navigation: {
			prevEl: prevButton,
			nextEl: nextButton,
		},
		pagination: {
			el: pagination,
			clickable: true,
		},
	});
}

/**
 * Activate one showroom in the featured region.
 *
 * @param {HTMLElement} root Block root.
 * @param {string}      showroomSlug Showroom slug.
 */
function setActiveShowroom(root, showroomSlug) {
	if (!root || !showroomSlug) {
		return;
	}

	const featured = root.querySelector(FEATURED_SELECTOR);
	const grid = root.querySelector(GRID_SELECTOR);

	if (!featured || !grid) {
		return;
	}

	const targetSlot = root.querySelector(
		`${CARD_SLOT_SELECTOR}[data-showroom-slug="${showroomSlug}"]`
	);

	if (!targetSlot) {
		return;
	}

	const currentFeatured = featured.querySelector(CARD_SLOT_SELECTOR);

	if (currentFeatured === targetSlot) {
		return;
	}

	destroyFeaturedSwiper(featured);

	if (currentFeatured) {
		grid.appendChild(currentFeatured);
	}

	featured.appendChild(targetSlot);

	const stateSlug = targetSlot.dataset.showroomState || '';

	updateMarkers(root, stateSlug);
	updateCardStates(root, showroomSlug);
	root.dataset.activeShowroom = showroomSlug;
	root.dataset.activeState = stateSlug;
}

/**
 * Activate a state marker in the full layout.
 *
 * @param {HTMLElement} root Block root.
 * @param {string}      stateSlug State taxonomy slug.
 */
function setActiveState(root, stateSlug) {
	if (!root || !stateSlug) {
		return;
	}

	const slots = getStateSlots(root, stateSlug);

	if (!slots.length) {
		return;
	}

	if (slots.length === 1) {
		setActiveShowroom(root, slots[0].dataset.showroomSlug || '');
		return;
	}

	const featured = root.querySelector(FEATURED_SELECTOR);
	const grid = root.querySelector(GRID_SELECTOR);

	if (!featured || !grid) {
		return;
	}

	const currentFeatured = featured.querySelector(CARD_SLOT_SELECTOR);

	if (currentFeatured) {
		grid.appendChild(currentFeatured);
	}

	slots.forEach((slot) => {
		if (slot.parentElement !== grid) {
			grid.appendChild(slot);
		}
	});

	mountFeaturedSwiper(featured, slots);

	const firstSlug = slots[0]?.dataset.showroomSlug || '';

	updateMarkers(root, stateSlug);
	updateCardStates(root, firstSlug);
	root.dataset.activeShowroom = firstSlug;
	root.dataset.activeState = stateSlug;
}

/**
 * @param {HTMLElement} root Block root.
 * @return {string}
 */
function resolveInitialShowroom(root) {
	const configuredDefault = root.dataset.defaultShowroom;
	const activeFeatured = root
		.querySelector(FEATURED_SELECTOR)
		?.querySelector(CARD_SLOT_SELECTOR);

	if (
		configuredDefault &&
		root.querySelector(
			`${CARD_SLOT_SELECTOR}[data-showroom-slug="${configuredDefault}"]`
		)
	) {
		return configuredDefault;
	}

	return activeFeatured?.dataset.showroomSlug || '';
}

/**
 * @param {HTMLElement} root Block root.
 */
function initShowroomLocatorFull(root) {
	if (!(root instanceof HTMLElement) || root[INIT_FLAG]) {
		return;
	}

	root[INIT_FLAG] = true;

	const initialShowroom = resolveInitialShowroom(root);

	delegateOn(root, 'click', MARKER_SELECTOR, (event, marker) => {
		const stateSlug = marker.dataset.showroomState;

		if (!stateSlug) {
			return;
		}

		event.preventDefault();
		setActiveState(root, stateSlug);
	});

	if (initialShowroom) {
		setActiveShowroom(root, initialShowroom);
	}
}

/**
 * @param {ParentNode} [scope=document]
 */
function initShowroomLocatorsFull(scope = document) {
	scope.querySelectorAll(ROOT_SELECTOR).forEach((root) => {
		if (!root[INIT_FLAG]) {
			initShowroomLocatorFull(root);
		}
	});
}

initShowroomLocatorsFull();

document.addEventListener(CONTENT_UPDATED_EVENT, () => {
	initShowroomLocatorsFull();
});

document.addEventListener('DOMContentLoaded', () => {
	initShowroomLocatorsFull();
});
