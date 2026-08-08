<?php
/**
 * Title: Split — Features & Trust
 * Slug: chairforce/section-split-features-trust
 * Categories: section
 * Description: Two-column section with a feature checklist on the left and a trust/social-proof card with stat counters on the right.
 */
?>

<!-- wp:group {"tagName":"section","metadata":{"name":"Split — Features & Trust","patternName":"chairforce/section-split-features-trust"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|section-xl","bottom":"var:preset|spacing|section-xl"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--section-xl);padding-bottom:var(--wp--preset--spacing--section-xl)"><!-- wp:columns {"align":"wide","isStackedOnTablet":true} -->
	<div class="wp-block-columns alignwide is-stacked-on-tablet"><!-- wp:column -->
		<div class="wp-block-column"><!-- wp:heading -->
			<h2 class="wp-block-heading">Australian Owned &amp; Operated</h2>
			<!-- /wp:heading -->

			<!-- wp:pattern {"slug":"chairforce/info-box-list-green"} /--></div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"stretch","style":{"spacing":{"padding":{"top":"var:preset|spacing|x-large","bottom":"var:preset|spacing|x-large","left":"var:preset|spacing|x-large","right":"var:preset|spacing|x-large"}}},"backgroundColor":"background"} -->
		<div class="wp-block-column is-vertically-aligned-stretch has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--x-large);padding-right:var(--wp--preset--spacing--x-large);padding-bottom:var(--wp--preset--spacing--x-large);padding-left:var(--wp--preset--spacing--x-large)"><!-- wp:heading {"level":3} -->
			<h3 class="wp-block-heading">Trusted by Thousands</h3>
			<!-- /wp:heading -->

			<!-- wp:group {"metadata":{"name":"Stats"},"layout":{"type":"grid","minimumColumnWidth":"10rem"}} -->
			<div class="wp-block-group"><!-- wp:chairforce/stat-counter {"number":15,"suffix":"k+","label":"Happy\u003cbr\u003eCustomers","className":"is-style-card"} -->
				<div class="wp-block-chairforce-stat-counter is-style-card"><div class="wp-block-chairforce-stat-counter__number-row"><span class="wp-block-chairforce-stat-counter__number" data-counter-target="15" data-counter-separator="," data-counter-duration="2000">15</span><span class="wp-block-chairforce-stat-counter__suffix">k+</span></div><p class="wp-block-chairforce-stat-counter__label">Happy<br>Customers</p></div>
				<!-- /wp:chairforce/stat-counter -->

				<!-- wp:chairforce/stat-counter {"number":98,"suffix":"%","label":"Satisfaction Rate","className":"is-style-card"} -->
				<div class="wp-block-chairforce-stat-counter is-style-card"><div class="wp-block-chairforce-stat-counter__number-row"><span class="wp-block-chairforce-stat-counter__number" data-counter-target="98" data-counter-separator="," data-counter-duration="2000">98</span><span class="wp-block-chairforce-stat-counter__suffix">%</span></div><p class="wp-block-chairforce-stat-counter__label">Satisfaction Rate</p></div>
				<!-- /wp:chairforce/stat-counter -->

				<!-- wp:chairforce/stat-counter {"number":50,"suffix":"k+","label":"Items Delivered","className":"is-style-card"} -->
				<div class="wp-block-chairforce-stat-counter is-style-card"><div class="wp-block-chairforce-stat-counter__number-row"><span class="wp-block-chairforce-stat-counter__number" data-counter-target="50" data-counter-separator="," data-counter-duration="2000">50</span><span class="wp-block-chairforce-stat-counter__suffix">k+</span></div><p class="wp-block-chairforce-stat-counter__label">Items Delivered</p></div>
				<!-- /wp:chairforce/stat-counter --></div>
			<!-- /wp:group --></div>
		<!-- /wp:column --></div>
	<!-- /wp:columns --></section>
<!-- /wp:group -->
