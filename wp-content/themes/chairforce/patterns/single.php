<?php
/**
 * Title: single
 * Slug: chairforce/single
 * Inserter: no
 */
?>
<!-- wp:template-part {"slug":"header","tagName":"header","className":"site-header"} /-->

<!-- wp:group {"tagName":"main","metadata":{"name":"Main"},"style":{"spacing":{"margin":{"top":"0","bottom":"var:preset|spacing|block-gap"}}}} -->
<main class="wp-block-group" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--block-gap)"><!-- wp:group {"className":"swt-block-post-banner-group","style":{"spacing":{"blockGap":"var:preset|spacing|x-small","margin":{"top":"0","bottom":"var:preset|spacing|x-large"},"padding":{"top":"var:preset|spacing|large","right":"var:preset|spacing|large","bottom":"var:preset|spacing|large","left":"var:preset|spacing|large"}}},"backgroundColor":"foreground","layout":{"type":"constrained"}} -->
<div class="wp-block-group swt-block-post-banner-group has-foreground-background-color has-background" style="margin-top:0;margin-bottom:var(--wp--preset--spacing--x-large);padding-top:var(--wp--preset--spacing--large);padding-right:var(--wp--preset--spacing--large);padding-bottom:var(--wp--preset--spacing--large);padding-left:var(--wp--preset--spacing--large)"><!-- wp:post-title {"textAlign":"center","level":1,"className":"swt-block-post-title","style":{"spacing":{"margin":{"top":"var:preset|spacing|x-small","bottom":"var:preset|spacing|x-small"}}},"textColor":"white"} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|xx-small"}},"textColor":"white","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
<div class="wp-block-group has-white-color has-text-color"><!-- wp:post-author {"showAvatar":false} /-->

<!-- wp:paragraph -->
<p><?php esc_html_e('·', 'chairforce');?></p>
<!-- /wp:paragraph -->

<!-- wp:post-date {"format":"M j, Y","metadata":{"bindings":{"datetime":{"source":"core/post-data","args":{"field":"date"}}}}} /-->

<!-- wp:paragraph -->
<p><?php esc_html_e('·', 'chairforce');?></p>
<!-- /wp:paragraph -->

<!-- wp:post-terms {"term":"category","textAlign":"center","className":"is-style-default"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"swt-block-featured-image","layout":{"type":"constrained"}} -->
<div class="wp-block-group swt-block-featured-image"><!-- wp:post-featured-image {"height":"432px"} /--></div>
<!-- /wp:group -->

<!-- wp:post-content {"lock":{"move":false,"remove":true},"layout":{"type":"constrained"}} /--></main>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|x-large","top":"var:preset|spacing|x-small"},"blockGap":"var:preset|spacing|large"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--x-small);padding-bottom:var(--wp--preset--spacing--x-large)"><!-- wp:post-terms {"term":"post_tag","className":"is-style-swt-post-terms-pill"} /-->

<!-- wp:separator {"className":"is-style-wide","backgroundColor":"outline"} -->
<hr class="wp-block-separator has-text-color has-outline-color has-alpha-channel-opacity has-outline-background-color has-background is-style-wide"/>
<!-- /wp:separator -->

<!-- wp:post-author {"showBio":true,"className":"is-style-swt-post-author-simple","fontSize":"small"} /--></div>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
