const ROOT_SELECTOR = '[data-cf-accordion]';
const ITEM_SELECTOR = '[data-cf-accordion-item]';
const TRIGGER_SELECTOR = '.cf-accordion__trigger';
const PANEL_SELECTOR = '.cf-accordion__panel';
const LOAD_MORE_SELECTOR = '[data-cf-accordion-load-more]';
const LOAD_MORE_HIDDEN_CLASS = 'cf-accordion__item--load-more-hidden';
const LOAD_MORE_REVEAL_CLASS = 'cf-accordion__item--load-more-reveal';
const LOAD_MORE_REVEALED_CLASS = 'is-revealed';
const LOAD_MORE_HIDING_CLASS = 'is-hiding';
const LOAD_MORE_WRAP_SELECTOR = '.cf-accordion__load-more';
const REVEAL_STAGGER_MS = 60;
const OPEN_CLASS = 'is-open';

/**
 * Read accordion transition duration from CSS (falls back to 300ms).
 *
 * @param {HTMLElement} root
 * @return {number}
 */
function getAccordionDurationMs( root ) {
	const duration = window.getComputedStyle( root ).getPropertyValue(
		'--cf-accordion-duration'
	);

	if ( ! duration ) {
		return 300;
	}

	const value = parseFloat( duration );

	if ( Number.isNaN( value ) ) {
		return 300;
	}

	return duration.trim().endsWith( 'ms' ) ? value : value * 1000;
}

/**
 * @param {HTMLElement} root
 */
function revealAllAccordionItems( root ) {
	const hiddenItems = Array.from(
		root.querySelectorAll( `.${ LOAD_MORE_HIDDEN_CLASS }` )
	);
	const loadMoreWrap = root.querySelector( LOAD_MORE_WRAP_SELECTOR );
	const durationMs = getAccordionDurationMs( root );
	const prefersReducedMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;
	const staggerMs = prefersReducedMotion ? 0 : REVEAL_STAGGER_MS;

	if ( loadMoreWrap ) {
		loadMoreWrap.classList.add( LOAD_MORE_HIDING_CLASS );
	}

	hiddenItems.forEach( ( item, index ) => {
		item.classList.remove( LOAD_MORE_HIDDEN_CLASS );
		item.classList.add( LOAD_MORE_REVEAL_CLASS );
		item.style.setProperty(
			'--cf-accordion-reveal-delay',
			`${ index * staggerMs }ms`
		);
	} );

	window.requestAnimationFrame( () => {
		window.requestAnimationFrame( () => {
			hiddenItems.forEach( ( item ) => {
				item.classList.add( LOAD_MORE_REVEALED_CLASS );
			} );
		} );
	} );

	const cleanupDelay =
		durationMs + ( hiddenItems.length - 1 ) * staggerMs + 50;

	window.setTimeout( () => {
		hiddenItems.forEach( ( item ) => {
			item.classList.remove(
				LOAD_MORE_REVEAL_CLASS,
				LOAD_MORE_REVEALED_CLASS
			);
			item.style.removeProperty( '--cf-accordion-reveal-delay' );
		} );

		if ( loadMoreWrap ) {
			loadMoreWrap.hidden = true;
			loadMoreWrap.classList.remove( LOAD_MORE_HIDING_CLASS );
		}
	}, cleanupDelay );
}

/**
 * @param {HTMLElement} item
 * @param {boolean} isOpen
 */
function setItemOpen( item, isOpen ) {
	const trigger = item.querySelector( TRIGGER_SELECTOR );
	const panel = item.querySelector( PANEL_SELECTOR );

	item.classList.toggle( OPEN_CLASS, isOpen );

	if ( trigger ) {
		trigger.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
	}

	if ( panel ) {
		panel.setAttribute( 'aria-hidden', isOpen ? 'false' : 'true' );
	}
}

/**
 * @param {HTMLElement} root
 * @param {HTMLElement} item
 */
function openItem( root, item ) {
	root.querySelectorAll( ITEM_SELECTOR ).forEach( ( section ) => {
		setItemOpen( section, section === item );
	} );
}

/**
 * @param {HTMLElement} root
 */
function initAccordion( root ) {
	if ( root.dataset.cfAccordionInit === 'true' ) {
		return;
	}

	root.dataset.cfAccordionInit = 'true';

	root.addEventListener( 'click', ( event ) => {
		const loadMoreButton = event.target.closest( LOAD_MORE_SELECTOR );

		if ( loadMoreButton && root.contains( loadMoreButton ) ) {
			event.preventDefault();
			revealAllAccordionItems( root );
			return;
		}

		const trigger = event.target.closest( TRIGGER_SELECTOR );

		if ( ! trigger || ! root.contains( trigger ) ) {
			return;
		}

		const item = trigger.closest( ITEM_SELECTOR );

		if ( ! item ) {
			return;
		}

		if ( item.classList.contains( OPEN_CLASS ) ) {
			setItemOpen( item, false );
			return;
		}

		openItem( root, item );
	} );

	root.addEventListener( 'keydown', ( event ) => {
		const trigger = event.target.closest( TRIGGER_SELECTOR );

		if ( ! trigger || ! root.contains( trigger ) ) {
			return;
		}

		if ( 'Enter' !== event.key && ' ' !== event.key ) {
			return;
		}

		event.preventDefault();

		const item = trigger.closest( ITEM_SELECTOR );

		if ( ! item ) {
			return;
		}

		if ( item.classList.contains( OPEN_CLASS ) ) {
			setItemOpen( item, false );
			return;
		}

		openItem( root, item );
	} );
}

/**
 * Initialize true accordion behaviour for all accordion roots.
 */
export function initAccordions() {
	document.querySelectorAll( ROOT_SELECTOR ).forEach( initAccordion );
}

/**
 * @deprecated Use initAccordions().
 */
export function initProductFaqsAccordionAll() {
	initAccordions();
}
