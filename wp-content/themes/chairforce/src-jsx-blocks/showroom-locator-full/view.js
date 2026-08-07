import {
	CONTENT_UPDATED_EVENT,
	delegateOn,
} from '../../src/js/shared/delegated-events';

const ROOT_SELECTOR = '.wp-block-chairforce-showroom-locator-full';
const CARD_SELECTOR = '.cf-showroom-locator-full__card';
const MARKER_SELECTOR = '.cf-showroom-locator-full__marker';
const FEATURED_SELECTOR = '.cf-showroom-locator-full__featured';
const GRID_SELECTOR = '.cf-showroom-locator-full__grid';
const INIT_FLAG = 'cfShowroomLocatorFullInitialized';

/**
 * Update marker active states.
 *
 * @param {HTMLElement} root        Block root element.
 * @param {string}      locationKey Active location key.
 */
function updateMarkers(root, locationKey) {
	root.querySelectorAll(MARKER_SELECTOR).forEach((marker) => {
		const isActive = marker.dataset.showroomLocation === locationKey;

		marker.classList.toggle('is-active', isActive);

		if (isActive) {
			marker.setAttribute('aria-current', 'true');
		} else {
			marker.removeAttribute('aria-current');
		}
	});
}

/**
 * Update card active classes.
 *
 * @param {HTMLElement} root        Block root element.
 * @param {string}      locationKey Active location key.
 */
function updateCardStates(root, locationKey) {
	root.querySelectorAll(CARD_SELECTOR).forEach((card) => {
		const isActive = card.dataset.showroomLocation === locationKey;

		card.classList.toggle('is-active', isActive);
		card.dataset.cardContext = isActive ? 'featured' : 'grid';
	});
}

/**
 * Activate a showroom location within one full locator instance.
 *
 * @param {HTMLElement} root        Block root element.
 * @param {string}      locationKey Machine location key.
 */
function setActiveLocation(root, locationKey) {
	if (!root || !locationKey) {
		return;
	}

	const featured = root.querySelector(FEATURED_SELECTOR);
	const grid = root.querySelector(GRID_SELECTOR);

	if (!featured || !grid) {
		return;
	}

	const currentFeatured = featured.querySelector(CARD_SELECTOR);
	const currentKey = currentFeatured?.dataset.showroomLocation;

	if (currentKey === locationKey) {
		return;
	}

	const nextCard = grid.querySelector(
		`${CARD_SELECTOR}[data-showroom-location="${locationKey}"]`
	);

	if (!nextCard && currentKey !== locationKey) {
		return;
	}

	if (currentFeatured) {
		grid.appendChild(currentFeatured);
	}

	if (nextCard) {
		featured.appendChild(nextCard);
	}

	updateMarkers(root, locationKey);
	updateCardStates(root, locationKey);
	root.dataset.activeLocation = locationKey;
}

/**
 * Resolve the initial location for a locator instance.
 *
 * @param {HTMLElement} root Block root element.
 * @return {string}
 */
function resolveInitialLocation(root) {
	const configuredDefault = root.dataset.defaultLocation;
	const activeFeatured = root
		.querySelector(FEATURED_SELECTOR)
		?.querySelector(CARD_SELECTOR);

	if (
		configuredDefault &&
		root.querySelector(
			`${CARD_SELECTOR}[data-showroom-location="${configuredDefault}"]`
		)
	) {
		return configuredDefault;
	}

	return activeFeatured?.dataset.showroomLocation || '';
}

/**
 * Initialize one showroom locator full instance.
 *
 * @param {HTMLElement} root Block root element.
 */
function initShowroomLocatorFull(root) {
	if (!(root instanceof HTMLElement) || root[INIT_FLAG]) {
		return;
	}

	root[INIT_FLAG] = true;

	const initialLocation = resolveInitialLocation(root);

	delegateOn(root, 'click', MARKER_SELECTOR, (event, marker) => {
		const locationKey = marker.dataset.showroomLocation;

		if (!locationKey) {
			return;
		}

		event.preventDefault();
		setActiveLocation(root, locationKey);
	});

	if (initialLocation) {
		setActiveLocation(root, initialLocation);
	}
}

/**
 * Initialize all uninitialized full locator blocks.
 *
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
