<?php
/**
 * Title: Split — Features
 * Slug: chairforce/section-split-features
 * Description: Full-width media-text split with image on the right and a 2-column info-box grid on the left.
 * Categories: chairforce, section
 * Keywords: split, features, media text, info box, two column
 */
?>
<!-- wp:group {"tagName":"section","metadata":{"name":"Split — Features"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|0","bottom":"var:preset|spacing|0"}}},"backgroundColor":"background","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--0);padding-bottom:var(--wp--preset--spacing--0)"><!-- wp:media-text {"align":"wide","mediaPosition":"right","mediaId":0,"mediaLink":"","linkDestination":"none","mediaType":"image","imageFill":false} -->
    <div class="wp-block-media-text alignwide has-media-on-the-right is-stacked-on-mobile"><div class="wp-block-media-text__content"><!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|xx-large","bottom":"var:preset|spacing|xx-large"}}},"layout":{"type":"constrained"}} -->
            <div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--xx-large);padding-bottom:var(--wp--preset--spacing--xx-large)"><!-- wp:paragraph {"className":"is-style-text-eyebrow-filled"} -->
                <p class="is-style-text-eyebrow-filled">Commercial Solutions</p>
                <!-- /wp:paragraph -->

                <!-- wp:heading -->
                <h2 class="wp-block-heading">Bulk Orders &amp; Custom Solutions</h2>
                <!-- /wp:heading -->

                <!-- wp:paragraph {"className":"is-style-text-lead"} -->
                <p class="is-style-text-lead">Furnishing a café, restaurant, office, or commercial space? We offer competitive bulk pricing, custom configurations, and dedicated account management.</p>
                <!-- /wp:paragraph -->

                <!-- wp:pattern {"slug":"chairforce/info-box-bar-navy"} /-->

                <!-- wp:buttons -->
                <div class="wp-block-buttons"><!-- wp:button {"chairforceIcon":"arrow-right","chairforceIconPosition":"right"} -->
                    <div class="wp-block-button cf-has-icon cf-icon-right cf-icon-arrow-right"><a class="wp-block-button__link wp-element-button">Change Me</a></div>
                    <!-- /wp:button -->

                    <!-- wp:button {"className":"is-style-light"} -->
                    <div class="wp-block-button is-style-light"><a class="wp-block-button__link wp-element-button">Change Me</a></div>
                    <!-- /wp:button --></div>
                <!-- /wp:buttons --></div>
            <!-- /wp:group --></div><figure class="wp-block-media-text__media"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/placeholder.png' ) ); ?>" alt="placeholder"/></figure></div>
    <!-- /wp:media-text --></section>
<!-- /wp:group -->
