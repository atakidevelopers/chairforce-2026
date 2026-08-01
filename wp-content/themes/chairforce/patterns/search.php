<?php
/**
 * Title: search
 * Slug: chairforce/search
 * Inserter: no
 */
?>
<!-- wp:template-part {"slug":"header","tagName":"header","className":"site-header"} /-->

<!-- wp:group {"tagName":"main","align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|large","padding":{"top":"var:preset|spacing|x-large","bottom":"var:preset|spacing|x-large"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"foreground","layout":{"type":"constrained"}} -->
<main class="wp-block-group alignwide has-foreground-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--x-large);padding-bottom:var(--wp--preset--spacing--x-large)"><!-- wp:query-title {"type":"search","textAlign":"center","align":"wide","textColor":"white"} /--></main>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|x-large","bottom":"var:preset|spacing|x-large"},"blockGap":"var:preset|spacing|large"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--x-large);padding-bottom:var(--wp--preset--spacing--x-large)"><!-- wp:query {"queryId":0,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-query alignwide"><!-- wp:query-no-results {"align":"wide"} -->
<!-- wp:paragraph {"align":"center","placeholder":"Add text or blocks that will display when a query returns no results.","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size"><?php esc_html_e('Sorry, but nothing matched your search terms. Try another search?', 'chairforce');?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"layout":{"type":"constrained","contentSize":"480px"}} -->
<div class="wp-block-group"><!-- wp:search {"label":"<?php esc_attr_e('Search', 'chairforce');?>","showLabel":false,"placeholder":"<?php esc_attr_e('Search...', 'chairforce');?>","widthUnit":"%","buttonText":"<?php esc_attr_e('Search', 'chairforce');?>","buttonPosition":"button-inside","buttonUseIcon":true,"align":"center","className":"is-style-swt-search-minimal"} /--></div>
<!-- /wp:group -->
<!-- /wp:query-no-results -->

<!-- wp:post-template {"align":"wide","layout":{"type":"grid","columnCount":1}} -->
<!-- wp:chairforce/query-post-card-blokki {"name":"chairforce/query-post-card-blokki","data":[],"mode":"preview"} /-->
<!-- /wp:post-template -->

<!-- wp:query-pagination {"paginationArrow":"arrow","layout":{"type":"flex","justifyContent":"center"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer"} /-->
