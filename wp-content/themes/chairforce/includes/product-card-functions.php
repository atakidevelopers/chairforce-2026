<?php
/**
 * Shared product card markup helpers.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render WooCommerce product-button block markup for a product ID.
 *
 * Uses the native Interactivity API block so simple products show "X in cart"
 * and ajax add-to-cart stays in sync with the blocks mini-cart.
 *
 * @param int                  $product_id Product post ID.
 * @param array<string, mixed> $attrs      Optional product-button block attrs.
 * @return string
 */
function chairforce_render_product_button_block( int $product_id, array $attrs = [] ): string {

	if ( $product_id <= 0 || ! class_exists( 'WP_Block' ) ) {
		return '';
	}

	$defaults = [
		'textAlign'               => 'center',
		'width'                   => 100,
		'fontSize'                => 'small',
		'isDescendentOfQueryLoop' => true,
	];

	$block = new \WP_Block(
		[
			'blockName' => 'woocommerce/product-button',
			'attrs'     => array_merge( $defaults, $attrs ),
		],
		'',
		[
			'postId'   => $product_id,
			'postType' => 'product',
		]
	);

	$markup = $block->render();

	return is_string( $markup ) ? $markup : '';
}
