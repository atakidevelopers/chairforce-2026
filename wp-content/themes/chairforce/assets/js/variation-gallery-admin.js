( function ( $ ) {
	'use strict';

	function updateHiddenInput( $wrapper ) {
		const $galleryImages = $wrapper.find( '.cf-variation-gallery-images' );
		const $imageGalleryIds = $wrapper.find( '.cf-variation-gallery-ids' );
		const attachmentIds = [];

		$galleryImages.find( 'li.image' ).each( function () {
			const attachmentId = $( this ).attr( 'data-attachment_id' );

			if ( attachmentId ) {
				attachmentIds.push( attachmentId );
			}
		} );

		$imageGalleryIds.val( attachmentIds.join( ',' ) );
	}

	function markVariationNeedsUpdate( $wrapper ) {
		$wrapper
			.parents( '.woocommerce_variation' )
			.eq( 0 )
			.addClass( 'variation-needs-update' );
		$( '#variable_product_options' ).find( 'input' ).eq( 0 ).trigger( 'change' );
	}

	function initVariationGallery( $wrapper ) {
		if ( $wrapper.data( 'cfVariationGalleryInit' ) ) {
			return;
		}

		$wrapper.data( 'cfVariationGalleryInit', true );

		const $galleryImages = $wrapper.find( '.cf-variation-gallery-images' );
		const $imageGalleryIds = $wrapper.find( '.cf-variation-gallery-ids' );
		let galleryFrame;

		$wrapper.on( 'click', '.cf-add-variation-gallery-image', function ( event ) {
			event.preventDefault();

			if ( galleryFrame ) {
				galleryFrame.open();
				return;
			}

			galleryFrame = wp.media( {
				title: $wrapper.data( 'frame-title' ) || 'Add images',
				button: {
					text: $wrapper.data( 'frame-button' ) || 'Add to gallery',
				},
				states: [
					new wp.media.controller.Library( {
						filterable: 'all',
						multiple: true,
					} ),
				],
			} );

			galleryFrame.on( 'select', function () {
				const selection = galleryFrame.state().get( 'selection' );
				const existingIds = $imageGalleryIds.val()
					? $imageGalleryIds.val().split( ',' )
					: [];

				selection.each( function ( attachment ) {
					const data = attachment.toJSON();

					if ( ! data.id || existingIds.indexOf( String( data.id ) ) !== -1 ) {
						return;
					}

					const attachmentImage =
						data.sizes && data.sizes.thumbnail
							? data.sizes.thumbnail.url
							: data.url;

					$galleryImages.append(
						'<li class="image" data-attachment_id="' +
							data.id +
							'"><img src="' +
							attachmentImage +
							'" alt=""><a href="#" class="delete cf-remove-variation-gallery-image" aria-label="Remove image">&times;</a></li>'
					);

					existingIds.push( String( data.id ) );
				} );

				$imageGalleryIds.val( existingIds.join( ',' ) );
				markVariationNeedsUpdate( $wrapper );
			} );

			galleryFrame.open();
		} );

		$wrapper.on( 'click', '.cf-remove-variation-gallery-image', function ( event ) {
			event.preventDefault();
			$( this ).closest( 'li.image' ).remove();
			updateHiddenInput( $wrapper );
			markVariationNeedsUpdate( $wrapper );
		} );

		if ( typeof $galleryImages.sortable !== 'undefined' ) {
			$galleryImages.sortable( {
				items: 'li.image',
				cursor: 'move',
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				opacity: 0.65,
				placeholder: 'wc-metabox-sortable-placeholder',
				update: function () {
					updateHiddenInput( $wrapper );
					markVariationNeedsUpdate( $wrapper );
				},
			} );
		}
	}

	$( function () {
		const $productData = $( '#woocommerce-product-data' );

		if ( ! $productData.length ) {
			return;
		}

		$productData.on( 'woocommerce_variations_loaded', function () {
			$( '.cf-variation-gallery-wrapper' ).each( function () {
				initVariationGallery( $( this ) );
			} );
		} );
	} );
}( jQuery ) );
