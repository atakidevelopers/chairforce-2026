<?php
/**
 * Product card within loops — Chairforce layout (shared with block card Sass).
 *
 * @see lib/class-classic-wc-compatibility.php
 * @see chairforce_get_product_card_blocks_markup() — block card composition reference
 *
 * @package Chairforce
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'wc-block-product cf-product-card', $product ); ?>>
	<div class="cf-product-card__inner wp-block-chairforce-product-card">
		<?php
		/**
		 * Hook: woocommerce_before_shop_loop_item.
		 */
		do_action( 'woocommerce_before_shop_loop_item' );

		/**
		 * Hook: woocommerce_before_shop_loop_item_title.
		 *
		 * @hooked Classic_WC_Compatibility::render_loop_media - 10
		 */
		do_action( 'woocommerce_before_shop_loop_item_title' );

		/**
		 * Hook: woocommerce_shop_loop_item_title.
		 *
		 * @hooked Classic_WC_Compatibility::render_loop_title - 10
		 */
		do_action( 'woocommerce_shop_loop_item_title' );

		/**
		 * Hook: woocommerce_after_shop_loop_item_title.
		 *
		 * @hooked Classic_WC_Compatibility::render_loop_swatches - 5
		 * @hooked woocommerce_template_loop_price - 10 (theme: woocommerce/loop/price.php)
		 */
		do_action( 'woocommerce_after_shop_loop_item_title' );

		/**
		 * Hook: woocommerce_after_shop_loop_item.
		 *
		 * @hooked woocommerce_template_loop_add_to_cart - 10
		 */
		do_action( 'woocommerce_after_shop_loop_item' );
		?>
	</div>
</li>
