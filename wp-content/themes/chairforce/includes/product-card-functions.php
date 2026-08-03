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
 * Build wrapper classes for a product collection product-button block.
 *
 * @param array<string, mixed> $block_attrs Parsed block attributes.
 * @return string
 */
function chairforce_get_product_card_button_wrapper_classes( array $block_attrs = [] ): string {

	$classes = [
		'cf-add-to-cart',
		'wp-block-button',
		'wc-block-components-product-button',
		'wp-block-woocommerce-product-button',
	];

	if ( ! empty( $block_attrs['fontSize'] ) ) {
		$classes[] = 'has-' . sanitize_html_class( (string) $block_attrs['fontSize'] ) . '-font-size';
	}

	if ( ! empty( $block_attrs['width'] ) ) {
		$classes[] = 'has-custom-width';
		$classes[] = 'wp-block-button__width-' . absint( $block_attrs['width'] );
	}

	if ( ! empty( $block_attrs['textAlign'] ) ) {
		$classes[] = 'align-' . sanitize_html_class( (string) $block_attrs['textAlign'] );
	}

	return implode( ' ', array_unique( $classes ) );
}

/**
 * Align classic loop add-to-cart link classes with block product-button markup.
 *
 * @param string $link Loop add-to-cart anchor HTML.
 * @return string
 */
function chairforce_enhance_product_card_add_to_cart_link( string $link ): string {

	if ( '' === trim( $link ) ) {
		return '';
	}

	if ( str_contains( $link, 'wc-block-components-product-button__button' ) ) {
		return $link;
	}

	$updated = preg_replace(
		'/class="([^"]*)"/',
		'class="wp-block-button__link wp-element-button wc-block-components-product-button__button $1"',
		$link,
		1
	);

	return is_string( $updated ) ? $updated : $link;
}

/**
 * Add to cart button markup for product collection cards.
 *
 * Uses WooCommerce loop add-to-cart so simple, variable, and grouped products
 * behave like the classic shop loop (ajax add-to-cart, Select options links, etc.).
 *
 * @param \WC_Product          $product     Product object.
 * @param array<string, mixed> $block_attrs Optional product-button block attrs.
 * @return string
 */
function chairforce_get_product_card_add_to_cart_html( \WC_Product $product, array $block_attrs = [] ): string {

	global $product;

	$previous_product = ( isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof \WC_Product )
		? $GLOBALS['product']
		: null;

	$GLOBALS['product'] = $product;

	ob_start();
	woocommerce_template_loop_add_to_cart();
	$link = (string) ob_get_clean();

	if ( $previous_product instanceof \WC_Product ) {
		$GLOBALS['product'] = $previous_product;
	} else {
		unset( $GLOBALS['product'] );
	}

	$link = chairforce_enhance_product_card_add_to_cart_link( $link );

	if ( '' === trim( $link ) ) {
		return '';
	}

	return sprintf(
		'<div class="%1$s">%2$s</div>',
		esc_attr( chairforce_get_product_card_button_wrapper_classes( $block_attrs ) ),
		$link // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC loop add to cart.
	);
}
