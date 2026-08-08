/**
 * Stat Counter — viewport animation.
 *
 * Finds every counter number element on the page, then triggers a
 * count-up animation the first time each one enters the viewport.
 *
 * Respects prefers-reduced-motion: in that case the final value is
 * shown instantly without animating.
 */
( function () {
	const SELECTOR = '.wp-block-chairforce-stat-counter__number[data-counter-target]';
	const counters  = document.querySelectorAll( SELECTOR );

	if ( ! counters.length ) return;

	const prefersReducedMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;

	// ── Easing ────────────────────────────────────────────────────────────────
	function easeOutQuart( t ) {
		return 1 - Math.pow( 1 - t, 4 );
	}

	// ── Number formatter ──────────────────────────────────────────────────────
	function fmt( n, sep ) {
		const rounded = Math.round( n );
		if ( ! sep ) return String( rounded );
		return rounded.toLocaleString( 'en-US' );
	}

	// ── Single counter animation ──────────────────────────────────────────────
	function animateCounter( el ) {
		const target   = parseFloat( el.dataset.counterTarget )  || 0;
		const duration = parseInt( el.dataset.counterDuration )  || 2000;
		const sep      = el.dataset.counterSeparator             ?? ',';

		if ( prefersReducedMotion || duration === 0 ) {
			el.textContent = fmt( target, sep );
			return;
		}

		const start = performance.now();

		function step( now ) {
			const elapsed  = now - start;
			const progress = Math.min( elapsed / duration, 1 );
			el.textContent = fmt( easeOutQuart( progress ) * target, sep );

			if ( progress < 1 ) {
				requestAnimationFrame( step );
			} else {
				el.textContent = fmt( target, sep );
			}
		}

		requestAnimationFrame( step );
	}

	// ── IntersectionObserver — fire once per element ──────────────────────────
	const observer = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					animateCounter( entry.target );
					observer.unobserve( entry.target );
				}
			} );
		},
		{ threshold: 0.3 }
	);

	counters.forEach( ( el ) => observer.observe( el ) );
} )();
