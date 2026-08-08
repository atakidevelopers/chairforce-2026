<?php
/**
 * Title: Split — Quote
 * Slug: chairforce/section-split-quote
 * Description: Full-width media-text split with image on the left and a quote/bio block on the right.
 * Categories: chairforce, section
 * Keywords: split, quote, bio, media text, testimonial, about
 */
?>
<!-- wp:group {"tagName":"section","metadata":{"name":"Split — Quote"},"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|section-xl","bottom":"var:preset|spacing|section-xl"}}},"backgroundColor":"background","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--section-xl);padding-bottom:var(--wp--preset--spacing--section-xl)"><!-- wp:media-text {"align":"wide","mediaId":0,"mediaLink":"","linkDestination":"none","mediaType":"image"} -->
    <div class="wp-block-media-text alignwide is-stacked-on-mobile"><figure class="wp-block-media-text__media"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/placeholder-4x3.png' ) ); ?>" alt="placeholder"/></figure><div class="wp-block-media-text__content"><!-- wp:group {"layout":{"type":"constrained"}} -->
            <div class="wp-block-group"><!-- wp:heading -->
                <h2 class="wp-block-heading">Built on Trust &amp; Quality</h2>
                <!-- /wp:heading -->

                <!-- wp:paragraph {"className":"is-style-text-lead","fontSize":"medium"} -->
                <p class="is-style-text-lead has-medium-font-size">Every piece in our collection is selected with care, tested for durability, and backed by our commitment to customer satisfaction. We work directly with manufacturers to ensure quality control and competitive pricing.</p>
                <!-- /wp:paragraph -->

                <!-- wp:paragraph {"className":"is-style-text-lead","fontSize":"medium"} -->
                <p class="is-style-text-lead has-medium-font-size">Our team of furniture specialists brings decades of combined experience, helping thousands of customers find the perfect pieces for their spaces — whether it's a corporate office, restaurant, or family home.</p>
                <!-- /wp:paragraph -->

                <!-- wp:separator -->
                <hr class="wp-block-separator has-alpha-channel-opacity"/>
                <!-- /wp:separator -->

                <!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|xxx-small"}},"layout":{"type":"constrained"}} -->
                <div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600"}},"fontSize":"large"} -->
                    <p class="has-large-font-size" style="font-style:normal;font-weight:600">Sarah Mitchell</p>
                    <!-- /wp:paragraph -->

                    <!-- wp:paragraph {"className":"is-style-text-lead","fontSize":"small"} -->
                    <p class="is-style-text-lead has-small-font-size">Founder &amp; CEO</p>
                    <!-- /wp:paragraph --></div>
                <!-- /wp:group --></div>
            <!-- /wp:group --></div></div>
    <!-- /wp:media-text --></section>
<!-- /wp:group -->
