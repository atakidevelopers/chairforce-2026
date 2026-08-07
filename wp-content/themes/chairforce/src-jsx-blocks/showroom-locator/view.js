import {
	CONTENT_UPDATED_EVENT,
	delegateOn,
} from '../../src/js/shared/delegated-events';

const ROOT_SELECTOR = '.wp-block-chairforce-showroom-locator';
const FILTER_SELECTOR = '.cf-showroom-locator__filter';
const CARD_SELECTOR = '.cf-showroom-locator__card';
const MARKER_SELECTOR = '.cf-showroom-locator__marker';
const INIT_FLAG = 'cfShowroomLocatorInitialized';

/**
 * Activate a showroom location within one locator instance.
 *
 * @param {HTMLElement} root         Locator root element.
 * @param {string}      locationKey  Machine location key.
 * @param {object}      [options]    Optional behavior flags.
 */
function setActiveLocation(root, locationKey, options = {}) {
	if (!root || !locationKey) {
		return;
	}

	const filters = Array.from(root.querySelectorAll(FILTER_SELECTOR));
	const cards = Array.from(root.querySelectorAll(CARD_SELECTOR));
	const markers = Array.from(root.querySelectorAll(MARKER_SELECTOR));

	const hasLocation = filters.some(
		(filter) => filter.dataset.showroomLocation === locationKey
	);

	if (!hasLocation) {
		return;
	}

	filters.forEach((filter) => {
		const isActive = filter.dataset.showroomLocation === locationKey;

		filter.classList.toggle('is-active', isActive);
		filter.setAttribute('aria-selected', isActive ? 'true' : 'false');
		filter.setAttribute('tabindex', isActive ? '0' : '-1');
	});

	cards.forEach((card) => {
		const isActive = card.dataset.showroomLocation === locationKey;

		card.classList.toggle('is-active', isActive);

		if (isActive) {
			card.removeAttribute('hidden');
		} else {
			card.setAttribute('hidden', '');
		}
	});

	markers.forEach((marker) => {
		const isActive = marker.dataset.showroomLocation === locationKey;

		marker.classList.toggle('is-active', isActive);

		if (isActive) {
			marker.setAttribute('aria-current', 'true');
		} else {
			marker.removeAttribute('aria-current');
		}
	});

	root.dataset.activeLocation = locationKey;

	if (options.focusFilter) {
		const activeFilter = filters.find(
			(filter) => filter.dataset.showroomLocation === locationKey
		);

		activeFilter?.focus({ preventScroll: true });
	}
}

/**
 * Resolve the initial location for a locator instance.
 *
 * @param {HTMLElement} root Locator root element.
 * @return {string}
 */
function resolveInitialLocation(root) {
	const filters = Array.from(root.querySelectorAll(FILTER_SELECTOR));

	if (!filters.length) {
		return '';
	}

	const configuredDefault = root.dataset.defaultLocation;
	const activeFilter = filters.find((filter) =>
		filter.classList.contains('is-active')
	);

	if (
		configuredDefault &&
		filters.some(
			(filter) => filter.dataset.showroomLocation === configuredDefault
		)
	) {
		return configuredDefault;
	}

	if (activeFilter?.dataset.showroomLocation) {
		return activeFilter.dataset.showroomLocation;
	}

	return filters[0].dataset.showroomLocation || '';
}

/**
 * Get enabled filters in DOM order.
 *
 * @param {HTMLElement} root Locator root element.
 * @return {HTMLElement[]}
 */
function getFilters(root) {
	return Array.from(root.querySelectorAll(FILTER_SELECTOR));
}

/**
 * Focus and activate the next or previous filter tab.
 *
 * @param {HTMLElement} root      Locator root element.
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
	const locationKey = nextFilter?.dataset.showroomLocation;

	if (!locationKey) {
		return;
	}

	setActiveLocation(root, locationKey, { focusFilter: true });
}

/**
 * Initialize one showroom locator instance.
 *
 * @param {HTMLElement} root Locator root element.
 */
function initShowroomLocator(root) {
	if (!(root instanceof HTMLElement) || root[INIT_FLAG]) {
		return;
	}

	root[INIT_FLAG] = true;

	const initialLocation = resolveInitialLocation(root);

	delegateOn(root, 'click', FILTER_SELECTOR, (event, filter) => {
		const locationKey = filter.dataset.showroomLocation;

		if (!locationKey) {
			return;
		}

		event.preventDefault();
		setActiveLocation(root, locationKey);
	});

	delegateOn(root, 'click', MARKER_SELECTOR, (event, marker) => {
		const locationKey = marker.dataset.showroomLocation;

		if (!locationKey) {
			return;
		}

		event.preventDefault();
		setActiveLocation(root, locationKey, { focusFilter: true });
	});

	delegateOn(root, 'keydown', FILTER_SELECTOR, (event, filter) => {
		const locationKey = filter.dataset.showroomLocation;

		if (!locationKey) {
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
				const firstFilter = getFilters(root)[0];
				const firstKey = firstFilter?.dataset.showroomLocation;

				if (firstKey) {
					setActiveLocation(root, firstKey, { focusFilter: true });
				}
				break;
			}
			case 'End': {
				event.preventDefault();
				const filters = getFilters(root);
				const lastFilter = filters[filters.length - 1];
				const lastKey = lastFilter?.dataset.showroomLocation;

				if (lastKey) {
					setActiveLocation(root, lastKey, { focusFilter: true });
				}
				break;
			}
			case 'Enter':
			case ' ':
				event.preventDefault();
				setActiveLocation(root, locationKey);
				break;
			default:
				break;
		}
	});

	if (initialLocation) {
		setActiveLocation(root, initialLocation);
	}
}

/**
 * Initialize all uninitialized locator blocks.
 *
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
