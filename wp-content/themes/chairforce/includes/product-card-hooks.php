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
 * Append SAVE % label to on-sale product price blocks in collection cards.
 *
 * @param string         $content  Block HTML.
 * @param array          $block    Parsed block.
 * @param \WP_Block|null $instance Block instance.
 */
function chairforce_append_product_card_save_label( string $content, array $block, ?\WP_Block $instance ): string {

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
