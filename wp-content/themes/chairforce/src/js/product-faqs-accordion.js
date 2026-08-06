const ROOT_SELECTOR = '[data-cf-product-faqs]';
const ITEM_SELECTOR = '[data-cf-product-faq-item]';
const TRIGGER_SELECTOR = '.cf-product-faqs__trigger';
const PANEL_SELECTOR = '.cf-product-faqs__panel';
const OPEN_CLASS = 'is-open';

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
		panel.hidden = ! isOpen;
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
function initProductFaqsAccordion( root ) {
	if ( root.dataset.cfProductFaqsInit === 'true' ) {
		return;
	}

	root.dataset.cfProductFaqsInit = 'true';

	root.addEventListener( 'click', ( event ) => {
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
 * Initialize true accordion behaviour on product FAQ blocks.
 */
export function initProductFaqsAccordionAll() {
	document.querySelectorAll( ROOT_SELECTOR ).forEach( initProductFaqsAccordion );
}
