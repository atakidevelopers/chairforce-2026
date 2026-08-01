<?php
/**
 * YITH Request a Quote — product card quote button fallback.
 *
 * YITH registers `render_block_woocommerce/product-button` on shop/taxonomy
 * template_redirect only. Load More REST and early archive-shell exits skip
 * that path, so appended cards miss Add to Quote. This filter fills the gap
 * when YITH did not append markup (deduped via existing wrapper class).
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether woocommerce/product-button is rendering inside a product loop card.
 *
 * @param array<string, mixed> $block    Parsed block.
 * @param \WP_Block|null       $instance Block instance.
 */
function chairforce_is_product_loop_product_button( array $block, ?\WP_Block $instance ): bool {

	if ( ! empty( $block['attrs']['isDescendentOfQueryLoop'] ) ) {
		return true;
	}

	if ( $instance instanceof \WP_Block && ! empty( $instance->context['query']['isProductCollectionBlock'] ) ) {
		return true;
	}

	return false;
}

/**
 * Resolve product ID for a woocommerce/product-button block render.
 *
 * @param \WP_Block|null $instance Block instance.
 */
function chairforce_get_product_button_block_post_id( ?\WP_Block $instance ): int {

	if ( $instance instanceof \WP_Block && ! empty( $instance->context['postId'] ) ) {
		return absint( $instance->context['postId'] );
	}

	return 0;
}

/**
 * Append YITH Add to Quote when the native WC Blocks filter did not run.
 *
 * @param string         $content  Block HTML.
 * @param array          $block    Parsed block.
 * @param \WP_Block|null $instance Block instance.
 */
function chairforce_append_ywraq_to_product_button_block( string $content, array $block, ?\WP_Block $instance ): string {

	if ( ! chairforce_is_product_loop_product_button( $block, $instance ) ) {
		return $content;
	}

	if ( ! chairforce_is_ywraq_quote_button_enabled( 'woocommerce_blocks' ) ) {
		return $content;
	}

	if ( str_contains( $content, 'yith-ywraq-add-to-quote' ) ) {
		return $content;
	}

	$product_id = chairforce_get_product_button_block_post_id( $instance );

	if ( ! $product_id ) {
		return $content;
	}

	$quote_markup = chairforce_render_ywraq_button_quote_markup( $product_id );

	if ( '' === trim( $quote_markup ) ) {
		return $content;
	}

	return $content . $quote_markup;
}

add_filter( 'render_block_woocommerce/product-button', 'chairforce_append_ywraq_to_product_button_block', 20, 3 );
