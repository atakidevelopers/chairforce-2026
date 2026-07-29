<?php
/**
 * Product grid swatches block — server render (Mode A / select_options).
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

use Chairforce\Product_Swatches;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
	return;
}

$product = wc_get_product( $product_id );

if ( ! $product ) {
	return;
}

$markup = Product_Swatches::render_grid_swatches( $product );

if ( '' === $markup ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'cf-product-swatches',
	]
);

printf(
	'<div %1$s>%2$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$markup // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Product_Swatches.
);
