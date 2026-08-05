<?php
/**
 * Product feature icons — server render.
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

$html = chairforce_get_product_features_html( $product_id );

if ( '' === trim( $html ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'cf-product-features',
	]
);

printf(
	'<div %1$s>%2$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper.
);
