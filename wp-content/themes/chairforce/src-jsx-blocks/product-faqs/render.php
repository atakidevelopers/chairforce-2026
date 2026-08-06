<?php
/**
 * Product FAQs — resolves FAQ post IDs, renders via shared accordion component.
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

if ( ! $product_id || ! function_exists( 'wc_get_product' ) || ! wc_get_product( $product_id ) ) {
	return;
}

$initial_visible_count = isset( $attributes['initialVisibleCount'] )
	? absint( $attributes['initialVisibleCount'] )
	: 5;
$include_faq_schema     = ! isset( $attributes['includeFaqSchema'] ) || (bool) $attributes['includeFaqSchema'];

$markup = chairforce_get_product_faqs_html(
	$product_id,
	[
		'initial_visible_count' => $initial_visible_count,
	]
);

if ( '' === trim( $markup ) ) {
	return;
}

if ( $include_faq_schema ) {
	chairforce_queue_faqpage_schema( $product_id );
}

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'cf-accordion',
	]
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-cf-accordion>
	<?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
</div>
