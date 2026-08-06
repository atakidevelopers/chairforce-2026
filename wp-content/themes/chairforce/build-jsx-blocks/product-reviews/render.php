<?php
/**
 * Product reviews section — server render.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id = isset( $block->context['postId'] ) ? absint( $block->context['postId'] ) : 0;

if ( ! $product_id ) {
	$product_id = get_the_ID();
}

if (
	! $product_id
	|| ! function_exists( 'wc_get_product' )
	|| ! wc_get_product( $product_id )
	|| ! chairforce_should_show_product_reviews( $product_id )
	|| ! chairforce_is_product_reviews_section_mode()
) {
	return;
}

$reviews_per_page = isset( $attributes['reviewsPerPage'] ) ? absint( $attributes['reviewsPerPage'] ) : 3;

if ( $reviews_per_page < 1 ) {
	$reviews_per_page = 3;
}

$display_reviews_summary = ! isset( $attributes['displayReviewsSummary'] ) || $attributes['displayReviewsSummary'];
$show_write_button       = ! $display_reviews_summary;

$main_html = chairforce_get_product_reviews_main_column_html(
	$product_id,
	$reviews_per_page,
	$show_write_button
);

if ( '' === trim( $main_html ) ) {
	return;
}

$summary_html = '';

if ( $display_reviews_summary ) {
	$summary_html = chairforce_render_product_reviews_summary_block( $product_id );
}

$columns_markup = chairforce_get_product_reviews_columns_blocks_markup(
	$main_html,
	$summary_html,
	$display_reviews_summary
);

if ( '' === trim( $columns_markup ) || ! function_exists( 'do_blocks' ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'id'    => 'reviews',
		'class' => 'woocommerce-Reviews cf-product-reviews is-layout-constrained has-global-padding',
	]
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo do_blocks( $columns_markup ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block render output. ?>
</div>
