<?php
/**
 * Product collection card — block render enhancements.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replace WooCommerce product-button block output with theme loop add-to-cart markup.
 *
 * Priority 5 — before YITH appends Add to Quote on `render_block_woocommerce/product-button` (10).
 *
 * @param string         $content  Block HTML.
 * @param array          $block    Parsed block.
 * @param \WP_Block|null $instance Block instance.
 */
function chairforce_render_product_card_product_button( string $content, array $block, ?\WP_Block $instance ): string {

	if ( ! chairforce_boots_product_card_features() ) {
		return $content;
	}

	if ( ! chairforce_is_product_collection_loop_block( $block, $instance ) ) {
		return $content;
	}

	if ( ! function_exists( 'wc_get_product' ) ) {
		return $content;
	}

	$product_id = chairforce_get_product_collection_block_post_id( $instance );

	if ( ! $product_id ) {
		return $content;
	}

	$product = wc_get_product( $product_id );

	if ( ! $product instanceof \WC_Product ) {
		return $content;
	}

	$markup = chairforce_get_product_card_add_to_cart_html(
		$product,
		is_array( $block['attrs'] ?? null ) ? $block['attrs'] : []
	);

	return '' !== trim( $markup ) ? $markup : $content;
}

add_filter( 'render_block_woocommerce/product-button', 'chairforce_render_product_card_product_button', 5, 3 );

/**
 * Append SAVE % label to on-sale product price blocks in collection cards.
 *
 * @param string         $content  Block HTML.
 * @param array          $block    Parsed block.
 * @param \WP_Block|null $instance Block instance.
 */
function chairforce_append_product_card_save_label( string $content, array $block, ?\WP_Block $instance ): string {

	if ( ! chairforce_boots_product_card_features() ) {
		return $content;
	}

	if ( ! chairforce_is_product_collection_loop_block( $block, $instance ) ) {
		return $content;
	}

	if ( ! str_contains( $content, '<del' ) || str_contains( $content, 'cf-product-card__save' ) ) {
		return $content;
	}

	$product_id = chairforce_get_product_collection_block_post_id( $instance );

	if ( ! $product_id ) {
		return $content;
	}

	$save_markup = chairforce_get_product_card_save_label_markup( $product_id );

	if ( '' === $save_markup ) {
		return $content;
	}

	$updated = preg_replace(
		'/(<div class="wc-block-components-product-price[^"]*">.*?)(<\/div>)/s',
		'$1' . $save_markup . '$2',
		$content,
		1
	);

	return is_string( $updated ) ? $updated : $content;
}

add_filter( 'render_block_woocommerce/product-price', 'chairforce_append_product_card_save_label', 20, 3 );

/**
 * Include product cart quantities in WC fragment refresh (mini-cart add/remove, ajax add).
 *
 * @param array<string, string> $fragments Cart fragments.
 * @return array<string, string>
 */
function chairforce_add_product_cart_quantities_fragment( array $fragments ): array {

	if ( ! chairforce_boots_product_card_features() ) {
		return $fragments;
	}

	$markup = chairforce_get_product_cart_quantities_script_markup();

	if ( '' !== $markup ) {
		$fragments['script#cf-product-cart-quantities'] = $markup;
	}

	return $fragments;
}

add_filter( 'woocommerce_add_to_cart_fragments', 'chairforce_add_product_cart_quantities_fragment' );

/**
 * Boot cart-quantity JSON for JS sync on first paint (before any ajax refresh).
 */
function chairforce_print_product_cart_quantities_script(): void {

	if ( ! chairforce_boots_product_card_features() ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON script from trusted cart helper.
	echo chairforce_get_product_cart_quantities_script_markup();
}

add_action( 'wp_footer', 'chairforce_print_product_cart_quantities_script', 20 );

