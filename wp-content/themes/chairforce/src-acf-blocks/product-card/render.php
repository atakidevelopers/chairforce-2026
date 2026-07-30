<?php
/**
 * Product Card block — server render.
 *
 * Renders one product card via WooCommerce's classic loop hooks
 * (see `Chairforce\Product_Card::render()`), inside `woocommerce/product-template`.
 * Deliberately has no editable inner structure — extend the card by hooking
 * `woocommerce_before_shop_loop_item`, `woocommerce_before_shop_loop_item_title`,
 * `woocommerce_shop_loop_item_title`, `woocommerce_after_shop_loop_item_title`,
 * or `woocommerce_after_shop_loop_item`.
 *
 * @var array    $block      Block data (ACF).
 * @var string   $content    Block content.
 * @var bool     $is_preview True when rendering in the block editor.
 * @var int      $post_id    Current post ID in the editor.
 * @var WP_Block $wp_block   Block instance (when available).
 */

use Chairforce\Product_Card;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_backend = isset( $is_preview ) && $is_preview;

$product_id = 0;

if ( isset( $wp_block ) && $wp_block instanceof WP_Block && ! empty( $wp_block->context['postId'] ) ) {
	$product_id = (int) $wp_block->context['postId'];
} elseif ( is_array( $block ) && ! empty( $block['context']['postId'] ) ) {
	$product_id = (int) $block['context']['postId'];
} elseif ( ! empty( $context['postId'] ) ) {
	$product_id = (int) $context['postId'];
}

if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
	if ( $is_backend ) {
		$wrapper_attributes = get_block_wrapper_attributes(
			[
				'class' => 'cf-product-card-editor',
			],
			$block ?? null
		);

		printf(
			'<div %1$s><p>%2$s</p><p class="cf-product-card-editor__hint">%3$s</p></div>',
			$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html__( 'Product Card', 'chairforce' ),
			esc_html__(
				'Locked structure — rendered via classic WooCommerce loop hooks (woocommerce_before_shop_loop_item, etc.). Not editable here; extend via add_action() in PHP.',
				'chairforce'
			)
		);
	}

	return;
}

$product = wc_get_product( $product_id );

if ( ! $product ) {
	return;
}

$markup = Product_Card::render( $product );

if ( '' === $markup ) {
	return;
}

$classes = array_merge(
	[ 'cf-product-card' ],
	Product_Card::get_wrapper_classes( $product )
);

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => implode( ' ', array_unique( $classes ) ),
	],
	$block ?? null
);

printf(
	'<div %1$s>%2$s</div>',
	$wrapper_attributes, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	$markup // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- classic WC hook output, same trust boundary as content-product.php.
);
