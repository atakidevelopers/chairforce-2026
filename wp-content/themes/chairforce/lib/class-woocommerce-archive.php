<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\WooCommerce_Archive' ) ) {
	return;
}

/**
 * WooCommerce shop/archive customizations (filters, swatches — Phase 3).
 */
class WooCommerce_Archive {

	/**
	 * WooCommerce_Archive constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register archive hooks.
	 */
	private function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_quick_view_assets' ], 20 );
		add_filter( 'render_block', [ $this, 'inject_quick_view_button' ], 10, 3 );
	}

	/**
	 * Append quick-view trigger to product-card images in query loops.
	 *
	 * Covers related/upsell product-collection grids that omit the block
	 * from their template (e.g. single-product "You may also like…").
	 *
	 * @param string   $block_content Rendered block HTML.
	 * @param array    $block         Block data.
	 * @param \WP_Block $instance     Block instance.
	 * @return string
	 */
	public function inject_quick_view_button( string $block_content, array $block, \WP_Block $instance ): string {
		if ( 'woocommerce/product-image' !== ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		if ( empty( $block['attrs']['isDescendentOfQueryLoop'] ) ) {
			return $block_content;
		}

		if ( str_contains( $block_content, 'cf-quick-view-trigger' ) ) {
			return $block_content;
		}

		$product_id = (int) ( $instance->context['postId'] ?? 0 );
		$button     = chairforce_get_quick_view_button_html( $product_id );

		if ( '' === $button ) {
			return $block_content;
		}

		$closing_pos = strrpos( $block_content, '</div>' );

		if ( false === $closing_pos ) {
			return $block_content . $button;
		}

		return substr( $block_content, 0, $closing_pos ) . $button . substr( $block_content, $closing_pos );
	}

	/**
	 * Enqueue WooCommerce single-product scripts/styles needed inside quick view.
	 */
	public function enqueue_quick_view_assets(): void {
		if (
			! is_shop()
			&& ! is_product_taxonomy()
			&& ! is_post_type_archive( 'product' )
			&& ! is_product()
		) {
			return;
		}

		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		wp_enqueue_script( 'wc-add-to-cart-variation' );
		wp_enqueue_script( 'wc-single-product' );

		wp_enqueue_style( 'photoswipe-default-skin' );
		wp_enqueue_style( 'photoswipe' );
	}

}
