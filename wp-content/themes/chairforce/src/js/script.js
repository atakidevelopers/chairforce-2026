document.addEventListener( 'DOMContentLoaded', function () {
	console.log( 'Chairforce Theme JS loaded' );

	/* Grid Guide overlay */
	var toggle = document.querySelector( '#grid-overlay-toggle' );
	if ( toggle ) {
		toggle.addEventListener( 'click', function () {
			document.querySelectorAll( '.grid-overlay' ).forEach( function (overlay) {
				var isHidden = overlay.style.display === 'none' || getComputedStyle( overlay ).display === 'none';
				overlay.style.display = isHidden ? 'block' : 'none';
			} );
		} );
	}

	/* Smooth scrolling */

	// $(".wp-site-blocks a[href^='#']").on("click", function (e) {
	//   e.preventDefault();
	//   var $link = $(this).attr("href");
	//
	//   $("html, body").animate(
	//     {
	//       scrollTop: $($link).offset().top - 75,
	//     },
	//     350,
	//   );
	// });
} );
