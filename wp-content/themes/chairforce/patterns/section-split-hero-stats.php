<?php
/**
 * Title: Split Hero with Stats
 * Slug: chairforce/section-split-hero-stats
 * Categories: section, chairforce
 * Description: Media-text hero split with animated stat counters and CTA buttons.
 */
?>

<!-- wp:group {"tagName":"section","metadata":{"name":"Split Hero with Stats","patternName":"chairforce/section-split-hero-stats"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|0","bottom":"var:preset|spacing|0"}}},"backgroundColor":"background","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--0);padding-bottom:var(--wp--preset--spacing--0)"><!-- wp:media-text {"align":"wide","mediaPosition":"right","mediaId":0,"mediaLink":"","linkDestination":"none","mediaType":"image","imageFill":false} -->
	<div class="wp-block-media-text alignwide has-media-on-the-right is-stacked-on-mobile"><div class="wp-block-media-text__content"><!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|xx-large","bottom":"var:preset|spacing|xx-large"}}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--xx-large);padding-bottom:var(--wp--preset--spacing--xx-large)"><!-- wp:paragraph {"className":"is-style-text-eyebrow-filled","style":{"elements":{"link":{"color":{"text":"var:preset|color|neutral"}}},"border":{"width":"2px"},"typography":{"fontStyle":"normal","fontWeight":"700"}},"backgroundColor":"white","textColor":"neutral","fontSize":"x-small","borderColor":"outline"} -->
				<p class="is-style-text-eyebrow-filled has-border-color has-outline-border-color has-neutral-color has-white-background-color has-text-color has-background has-link-color has-x-small-font-size" style="border-width:2px;font-style:normal;font-weight:700">Est. 2010</p>
				<!-- /wp:paragraph -->

				<!-- wp:heading {"level":2} -->
				<h2 class="wp-block-heading">Elevating Australian Spaces<br>Since 2010</h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"className":"is-style-text-lead","fontSize":"large"} -->
				<p class="is-style-text-lead has-large-font-size">We believe quality furniture shouldn't mean compromise.<br>From our first Sydney showroom to seven locations nationwide, we've been committed to delivering exceptional commercial and residential furniture that stands the test of time.</p>
				<!-- /wp:paragraph -->

				<!-- wp:columns {"isStackedOnMobile":false} -->
				<div class="wp-block-columns is-not-stacked-on-mobile"><!-- wp:column {"width":"35%"} -->
					<div class="wp-block-column" style="flex-basis:35%"><!-- wp:chairforce/stat-counter {"number":50000,"label":"Happy Customers"} -->
						<div class="wp-block-chairforce-stat-counter"><div class="wp-block-chairforce-stat-counter__number-row"><span class="wp-block-chairforce-stat-counter__number" data-counter-target="50000" data-counter-separator="," data-counter-duration="2000">50,000</span><span class="wp-block-chairforce-stat-counter__suffix">+</span></div><p class="wp-block-chairforce-stat-counter__label">Happy Customers</p></div>
						<!-- /wp:chairforce/stat-counter --></div>
					<!-- /wp:column -->

					<!-- wp:column -->
					<div class="wp-block-column"><!-- wp:chairforce/stat-counter {"number":7,"suffix":"","label":"Showrooms"} -->
						<div class="wp-block-chairforce-stat-counter"><div class="wp-block-chairforce-stat-counter__number-row"><span class="wp-block-chairforce-stat-counter__number" data-counter-target="7" data-counter-separator="," data-counter-duration="2000">7</span></div><p class="wp-block-chairforce-stat-counter__label">Showrooms</p></div>
						<!-- /wp:chairforce/stat-counter --></div>
					<!-- /wp:column -->

					<!-- wp:column {"width":"35%"} -->
					<div class="wp-block-column" style="flex-basis:35%"><!-- wp:chairforce/stat-counter {"number":2500,"label":"Products"} -->
						<div class="wp-block-chairforce-stat-counter"><div class="wp-block-chairforce-stat-counter__number-row"><span class="wp-block-chairforce-stat-counter__number" data-counter-target="2500" data-counter-separator="," data-counter-duration="2000">2,500</span><span class="wp-block-chairforce-stat-counter__suffix">+</span></div><p class="wp-block-chairforce-stat-counter__label">Products</p></div>
						<!-- /wp:chairforce/stat-counter --></div>
					<!-- /wp:column --></div>
				<!-- /wp:columns -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons"><!-- wp:button {"chairforceIcon":"arrow-right","chairforceIconPosition":"right"} -->
					<div class="wp-block-button cf-has-icon cf-icon-right cf-icon-arrow-right"><a class="wp-block-button__link wp-element-button" href="/showrooms/">Visit Our Showrooms</a></div>
					<!-- /wp:button -->

					<!-- wp:button {"className":"is-style-light"} -->
					<div class="wp-block-button is-style-light"><a class="wp-block-button__link wp-element-button" href="/shop/">Browse Collection</a></div>
					<!-- /wp:button --></div>
				<!-- /wp:buttons --></div>
			<!-- /wp:group --></div><figure class="wp-block-media-text__media"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/placeholder.png' ) ); ?>" alt=""/></figure></div>
	<!-- /wp:media-text --></section>
<!-- /wp:group -->
