<?php
/**
 * Quick View trigger button — server render.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : 0;

if ( ! $product_id ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => 'cf-quick-view-button',
	]
);

printf(
	'<div %1$s><button type="button" class="cf-quick-view-trigger" data-product-id="%2$d" aria-label="%3$s"><span class="screen-reader-text">%3$s</span></button></div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$product_id,
	esc_attr__( 'Quick view', 'chairforce' )
);
