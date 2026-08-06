/**
 * Reveal the review form when Write a Review is clicked.
 */
export function initProductReviewsFormToggle() {
	const section = document.getElementById( 'reviews' );

	if ( ! section ) {
		return;
	}

	const formWrapper = section.querySelector( '.cf-product-reviews__form-wrapper' );
	const writeReviewButtons = document.querySelectorAll(
		'[data-cf-show-review-form]'
	);

	if ( ! formWrapper || ! writeReviewButtons.length ) {
		return;
	}

	const showForm = () => {
		formWrapper.classList.remove( 'cf-product-reviews__form-wrapper--hidden' );

		const focusTarget = formWrapper.querySelector( '#rating, #comment' );

		if ( focusTarget instanceof HTMLElement ) {
			focusTarget.focus( { preventScroll: true } );
		}
	};

	writeReviewButtons.forEach( ( button ) => {
		button.addEventListener( 'click', ( event ) => {
			event.preventDefault();

			if ( ! section.contains( button ) ) {
				section.scrollIntoView( { behavior: 'smooth', block: 'start' } );
			}

			showForm();
		} );
	} );
}
