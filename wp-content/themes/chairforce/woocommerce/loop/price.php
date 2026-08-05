<?php
/**
 * Loop Price — block product-price (matches chairforce/product-card inner blocks).
 *
 * Loaded by WooCommerce via `woocommerce_template_loop_price()`; no hook swap needed.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package Chairforce
 * @version 1.6.4
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted block render.
echo do_blocks(
	'<!-- wp:woocommerce/product-price {"textAlign":"left","isDescendentOfQueryLoop":true,"fontSize":"small","className":"is-style-text-price"} /-->'
);
