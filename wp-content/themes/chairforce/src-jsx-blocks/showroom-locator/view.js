import Swiper from 'swiper';
import { Autoplay, Navigation, Pagination } from 'swiper/modules';

import {
	CONTENT_UPDATED_EVENT,
	delegateOn,
} from '../../src/js/shared/delegated-events';

import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

const ROOT_SELECTOR = '.wp-block-chairforce-showroom-locator';
const FILTER_SELECTOR = '.cf-showroom-locator__filter';
const SINGLE_PANEL_SELECTOR = '.cf-showroom-locator__panel--single';
const STATE_PANEL_SELECTOR = '.cf-showroom-locator__panel--state';
const MARKER_SELECTOR = '.cf-showroom-locator__marker';
const INIT_FLAG = 'cfShowroomLocatorInitialized';
const SWIPER_FLAG = 'cfShowroomLocatorSwiper';

/**
 * @param {HTMLElement} root Locator root.
 */
function destroySwipers(root) {
	root.querySelectorAll(STATE_PANEL_SELECTOR).forEach((panel) => {
		const swiperEl = panel.querySelector('.cf-showroom-locator__swiper');

		if (swiperEl?.[SWIPER_FLAG]) {
			swiperEl[SWIPER_FLAG].destroy(true, true);
			delete swiperEl[SWIPER_FLAG];
		}
	});
}

/**
 * @param {HTMLElement} root Locator root.
 */
function hideAllPanels(root) {
	root.querySelectorAll(`${SINGLE_PANEL_SELECTOR}, ${STATE_PANEL_SELECTOR}`).forEach(
		(panel) => {
			panel.classList.remove('is-active');
			panel.setAttribute('hidden', '');
		}
	);
}

/**
 * @param {HTMLElement} root        Locator root.
 * @param {string}      showroomSlug Active showroom slug.
 */
function updateTabs(root, showroomSlug, stateSlug, { highlightState = false } = {}) {
	root.querySelectorAll(FILTER_SELECTOR).forEach((filter) => {
		const slug = filter.dataset.showroomSlug;
		const filterState = filter.dataset.showroomState;
		const isActive = highlightState
			? filterState === stateSlug
			: slug === showroomSlug;

		filter.classList.toggle('is-active', isActive);
		filter.setAttribute('aria-selected', isActive ? 'true' : 'false');
		filter.setAttribute('tabindex', isActive ? '0' : '-1');
	});
}

/**
 * @param {HTMLElement} root      Locator root.
 * @param {string}      stateSlug Active state slug.
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
 * @param {HTMLElement} root Locator root.
 * @param {string}      slug Showroom slug.
 * @return {string}
 */
function getShowroomState(root, slug) {
	const filter = root.querySelector(
		`${FILTER_SELECTOR}[data-showroom-slug="${slug}"]`
	);

	return filter?.dataset.showroomState || '';
}

/**
 * @param {HTMLElement} root      Locator root.
 * @param {string}      stateSlug State taxonomy slug.
 * @return {string[]}
 */
function getShowroomSlugsForState(root, stateSlug) {
	return Array.from(
		root.querySelectorAll(
			`${FILTER_SELECTOR}[data-showroom-state="${stateSlug}"]`
		)
	)
		.map((filter) => filter.dataset.showroomSlug || '')
		.filter(Boolean);
}

/**
 * @param {HTMLElement} statePanel State swiper panel.
 * @return {import('swiper').default|null}
 */
function initStateSwiper(statePanel) {
	const swiperEl = statePanel.querySelector('.cf-showroom-locator__swiper');

	if (!swiperEl) {
		return null;
	}

	if (swiperEl[SWIPER_FLAG]) {
		swiperEl[SWIPER_FLAG].update();
		return swiperEl[SWIPER_FLAG];
	}

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
			prevEl: statePanel.querySelector('.cf-showroom-locator__nav--prev'),
			nextEl: statePanel.querySelector('.cf-showroom-locator__nav--next'),
		},
		pagination: {
			el: statePanel.querySelector(
				'.cf-showroom-locator__swiper-controls .swiper-pagination'
			),
			clickable: true,
		},
	});

	return swiperEl[SWIPER_FLAG];
}

/**
 * Activate one showroom tab — single card view.
 *
 * @param {HTMLElement} root         Locator root.
 * @param {string}      showroomSlug Showroom post slug.
 * @param {object}      [options]    Optional behavior flags.
 */
function setActiveShowroom(root, showroomSlug, options = {}) {
	if (!root || !showroomSlug) {
		return;
	}

	const panel = root.querySelector(
		`${SINGLE_PANEL_SELECTOR}[data-showroom-slug="${showroomSlug}"]`
	);

	if (!panel) {
		return;
	}

	const stateSlug = getShowroomState(root, showroomSlug);

	destroySwipers(root);
	hideAllPanels(root);

	panel.classList.add('is-active');
	panel.removeAttribute('hidden');

	updateTabs(root, showroomSlug, stateSlug);
	updateMarkers(root, stateSlug);

	root.dataset.viewMode = 'single';
	root.dataset.activeShowroom = showroomSlug;
	root.dataset.activeState = stateSlug;

	if (options.focusFilter) {
		root
			.querySelector(`${FILTER_SELECTOR}[data-showroom-slug="${showroomSlug}"]`)
			?.focus({ preventScroll: true });
	}
}

/**
 * Activate a state marker — one card or swiper for multiple showrooms.
 *
 * @param {HTMLElement} root      Locator root.
 * @param {string}      stateSlug State taxonomy slug.
 * @param {object}      [options] Optional behavior flags.
 */
function setActiveState(root, stateSlug, options = {}) {
	if (!root || !stateSlug) {
		return;
	}

	const slugs = getShowroomSlugsForState(root, stateSlug);

	if (!slugs.length) {
		return;
	}

	if (slugs.length === 1) {
		setActiveShowroom(root, slugs[0], options);
		return;
	}

	const statePanel = root.querySelector(
		`${STATE_PANEL_SELECTOR}[data-showroom-state="${stateSlug}"]`
	);

	if (!statePanel) {
		setActiveShowroom(root, slugs[0], options);
		return;
	}

	destroySwipers(root);
	hideAllPanels(root);

	statePanel.classList.add('is-active');
	statePanel.removeAttribute('hidden');

	initStateSwiper(statePanel);

	updateTabs(root, '', stateSlug, { highlightState: true });
	updateMarkers(root, stateSlug);

	root.dataset.viewMode = 'state';
	root.dataset.activeState = stateSlug;
	root.dataset.activeShowroom = slugs[0];

	if (options.focusFilter) {
		root
			.querySelector(`${FILTER_SELECTOR}[data-showroom-slug="${slugs[0]}"]`)
			?.focus({ preventScroll: true });
	}
}

/**
 * @param {HTMLElement} root Locator root.
 * @return {string}
 */
function resolveInitialShowroom(root) {
	const filters = Array.from(root.querySelectorAll(FILTER_SELECTOR));

	if (!filters.length) {
		return '';
	}

	const configuredDefault = root.dataset.defaultShowroom;
	const activeFilter = filters.find((filter) =>
		filter.classList.contains('is-active')
	);

	if (
		configuredDefault &&
		filters.some((filter) => filter.dataset.showroomSlug === configuredDefault)
	) {
		return configuredDefault;
	}

	if (activeFilter?.dataset.showroomSlug) {
		return activeFilter.dataset.showroomSlug;
	}

	return filters[0].dataset.showroomSlug || '';
}

/**
 * @param {HTMLElement} root Locator root.
 * @return {HTMLElement[]}
 */
function getFilters(root) {
	return Array.from(root.querySelectorAll(FILTER_SELECTOR));
}

/**
 * @param {HTMLElement} root      Locator root.
 * @param {number}      direction 1 for next, -1 for previous.
 */
function activateAdjacentFilter(root, direction) {
	const filters = getFilters(root);

	if (filters.length < 2) {
		return;
	}

	const currentIndex = filters.findIndex(
		(filter) => filter.getAttribute('aria-selected') === 'true'
	);
	const startIndex = currentIndex >= 0 ? currentIndex : 0;
	const nextIndex =
		(startIndex + direction + filters.length) % filters.length;
	const nextFilter = filters[nextIndex];
	const showroomSlug = nextFilter?.dataset.showroomSlug;

	if (!showroomSlug) {
		return;
	}

	setActiveShowroom(root, showroomSlug, { focusFilter: true });
}

/**
 * @param {HTMLElement} root Locator root.
 */
function initShowroomLocator(root) {
	if (!(root instanceof HTMLElement) || root[INIT_FLAG]) {
		return;
	}

	root[INIT_FLAG] = true;

	const initialShowroom = resolveInitialShowroom(root);

	delegateOn(root, 'click', FILTER_SELECTOR, (event, filter) => {
		const showroomSlug = filter.dataset.showroomSlug;

		if (!showroomSlug) {
			return;
		}

		event.preventDefault();
		setActiveShowroom(root, showroomSlug);
	});

	delegateOn(root, 'click', MARKER_SELECTOR, (event, marker) => {
		const stateSlug = marker.dataset.showroomState;

		if (!stateSlug) {
			return;
		}

		event.preventDefault();
		setActiveState(root, stateSlug, { focusFilter: true });
	});

	delegateOn(root, 'keydown', FILTER_SELECTOR, (event, filter) => {
		const showroomSlug = filter.dataset.showroomSlug;

		if (!showroomSlug) {
			return;
		}

		switch (event.key) {
			case 'ArrowRight':
				event.preventDefault();
				activateAdjacentFilter(root, 1);
				break;
			case 'ArrowLeft':
				event.preventDefault();
				activateAdjacentFilter(root, -1);
				break;
			case 'Home': {
				event.preventDefault();
				const firstSlug = getFilters(root)[0]?.dataset.showroomSlug;

				if (firstSlug) {
					setActiveShowroom(root, firstSlug, { focusFilter: true });
				}
				break;
			}
			case 'End': {
				event.preventDefault();
				const filters = getFilters(root);
				const lastSlug = filters[filters.length - 1]?.dataset.showroomSlug;

				if (lastSlug) {
					setActiveShowroom(root, lastSlug, { focusFilter: true });
				}
				break;
			}
			case 'Enter':
			case ' ':
				event.preventDefault();
				setActiveShowroom(root, showroomSlug);
				break;
			default:
				break;
		}
	});

	if (initialShowroom) {
		setActiveShowroom(root, initialShowroom);
	}
}

/**
 * @param {ParentNode} [scope=document]
 */
function initShowroomLocators(scope = document) {
	scope.querySelectorAll(ROOT_SELECTOR).forEach((root) => {
		if (!root[INIT_FLAG]) {
			initShowroomLocator(root);
		}
	});
}

initShowroomLocators();

document.addEventListener(CONTENT_UPDATED_EVENT, () => {
	initShowroomLocators();
});

document.addEventListener('DOMContentLoaded', () => {
	initShowroomLocators();
});
