/**
 * Header product search — debounced AJAX dropdown.
 */
function initProductSearch( form ) {
	const input = form.querySelector( '.site-header__search-input' );
	if ( ! input || form.dataset.searchInit ) {
		return;
	}

	form.dataset.searchInit = 'true';

	const listId = `${ input.id }-results`;
	let list = document.getElementById( listId );

	if ( ! list ) {
		list = document.createElement( 'ul' );
		list.id = listId;
		list.className = 'site-header__search-results';
		list.setAttribute( 'role', 'listbox' );
		list.hidden = true;
		form.appendChild( list );
	}

	input.setAttribute( 'aria-controls', listId );
	input.setAttribute( 'aria-expanded', 'false' );
	input.setAttribute( 'autocomplete', 'off' );

	let debounceTimer = null;
	let activeIndex = -1;

	const restBase =
		window.Chairforce_Public?.rest_url ||
		`${ window.location.origin }/wp-json/chairforce/v1/product-search`;

	const closeResults = () => {
		list.hidden = true;
		list.innerHTML = '';
		input.setAttribute( 'aria-expanded', 'false' );
		activeIndex = -1;
	};

	const renderResults = ( items ) => {
		list.innerHTML = '';

		if ( ! items.length ) {
			closeResults();
			return;
		}

		items.forEach( ( item, index ) => {
			const li = document.createElement( 'li' );
			li.setAttribute( 'role', 'option' );
			li.id = `${ listId }-item-${ index }`;

			const link = document.createElement( 'a' );
			link.className = 'site-header__search-result';
			link.href = item.url;

			if ( item.thumbnail ) {
				const img = document.createElement( 'img' );
				img.src = item.thumbnail;
				img.alt = '';
				img.loading = 'lazy';
				img.className = 'site-header__search-result-thumb';
				link.appendChild( img );
			}

			const textWrap = document.createElement( 'span' );
			textWrap.className = 'site-header__search-result-text';

			const title = document.createElement( 'span' );
			title.className = 'site-header__search-result-title';
			title.textContent = item.title;
			textWrap.appendChild( title );

			if ( item.price ) {
				const price = document.createElement( 'span' );
				price.className = 'site-header__search-result-price';
				price.innerHTML = item.price;
				textWrap.appendChild( price );
			}

			link.appendChild( textWrap );
			li.appendChild( link );
			list.appendChild( li );
		} );

		list.hidden = false;
		input.setAttribute( 'aria-expanded', 'true' );
	};

	const fetchResults = async ( term ) => {
		const url = new URL( restBase, window.location.origin );
		url.searchParams.set( 's', term );

		const response = await fetch( url.toString(), {
			headers: {
				'X-WP-Nonce': window.Chairforce_Public?.nonce || '',
			},
		} );

		if ( ! response.ok ) {
			closeResults();
			return;
		}

		const data = await response.json();
		renderResults( data.items || [] );
	};

	input.addEventListener( 'input', () => {
		const term = input.value.trim();
		clearTimeout( debounceTimer );

		if ( term.length < 2 ) {
			closeResults();
			return;
		}

		debounceTimer = setTimeout( () => {
			fetchResults( term );
		}, 300 );
	} );

	input.addEventListener( 'keydown', ( event ) => {
		const options = list.querySelectorAll( '[role="option"] a' );

		if ( event.key === 'Escape' ) {
			closeResults();
			return;
		}

		if ( ! options.length || list.hidden ) {
			return;
		}

		if ( event.key === 'ArrowDown' ) {
			event.preventDefault();
			activeIndex = Math.min( activeIndex + 1, options.length - 1 );
			options[ activeIndex ].focus();
		}

		if ( event.key === 'ArrowUp' ) {
			event.preventDefault();
			activeIndex = Math.max( activeIndex - 1, 0 );
			options[ activeIndex ].focus();
		}
	} );

	document.addEventListener( 'click', ( event ) => {
		if ( ! form.contains( event.target ) ) {
			closeResults();
		}
	} );
}

export function initProductSearchForms() {
	document.querySelectorAll( '.site-header__search' ).forEach( initProductSearch );
}
