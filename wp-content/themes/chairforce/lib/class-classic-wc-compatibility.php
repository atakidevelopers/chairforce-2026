<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Classic_WC_Compatibility' ) ) {
	return;
}

/**
 * Classic WooCommerce loop compatibility (legacy-template archives).
 *
 * Swaps default loop partials for block-aligned equivalents. Price uses the WC
 * template override at `woocommerce/loop/price.php`. Title, media, and swatches
 * use loop hooks. Add-to-cart stays on the default WC loop hook so block Product
 * Collection cards are unaffected. Block cards render via `parts/product-card.html`.
 */
class Classic_WC_Compatibility {

	/**
	 * Whether loop hook swaps are registered.
	 *
	 * @var bool
	 */
	private bool $loop_hooks_registered = false;

	/**
	 * Classic_WC_Compatibility constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register hooks for classic loop partial swaps that cannot use template overrides.
	 */
	private function register_hooks(): void {
		add_action( 'woocommerce_init', [ $this, 'register_loop_hooks' ] );
	}

	/**
	 * Replace default loop media, title, and swatches with theme card markup.
	 */
	public function register_loop_hooks(): void {

		if ( $this->loop_hooks_registered || ! function_exists( 'WC' ) ) {
			return;
		}

		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
		remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
		remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );

		add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'render_loop_media' ], 10 );
		add_action( 'woocommerce_shop_loop_item_title', [ $this, 'render_loop_title' ], 10 );
		add_action( 'woocommerce_after_shop_loop_item_title', [ $this, 'render_loop_swatches' ], 5 );

		$this->loop_hooks_registered = true;
	}

	/**
	 * Output product card media (image, sale badge, wishlist, quick view).
	 */
	public function render_loop_media(): void {

		$product = $this->get_loop_product();

		if ( ! $product ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted theme helper.
		echo chairforce_get_product_card_media_html( $product );
	}

	/**
	 * Output block post-title for the product card.
	 */
	public function render_loop_title(): void {

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted block render.
		echo do_blocks( '<!-- wp:post-title {"textAlign":"center","isLink":true,"style":{"spacing":{"margin":{"bottom":"0.75rem","top":"0"}},"typography":{"lineHeight":"1.4"}},"fontSize":"medium","__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->' );
	}

	/**
	 * Output product card colour swatches.
	 */
	public function render_loop_swatches(): void {

		$product = $this->get_loop_product();

		if ( ! $product ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted theme helper.
		echo chairforce_get_product_card_swatches_html( $product );
	}

	/**
	 * @return \WC_Product|null
	 */
	private function get_loop_product(): ?\WC_Product {

		global $product;

		return ( $product instanceof \WC_Product ) ? $product : null;
	}
}
