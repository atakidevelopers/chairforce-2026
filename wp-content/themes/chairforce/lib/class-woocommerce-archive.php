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
		add_action( 'template_redirect', 'chairforce_maybe_render_archive_shell_fragment', 5 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_quick_view_assets' ], 20 );
		add_filter( 'render_block', [ $this, 'append_product_filters_after_store_notices' ], 10, 2 );
		add_filter( 'render_block', [ $this, 'close_archive_shell_after_product_collection' ], 10, 2 );
	}

	/**
	 * Inject filter bar markup after store notices on product archives.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array<string, mixed> $block Block data.
	 * @return string
	 */
	public function append_product_filters_after_store_notices( string $block_content, array $block ): string {

		if ( 'woocommerce/store-notices' !== ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		if ( ! chairforce_is_product_filter_archive() ) {
			return $block_content;
		}

		$filters_markup = chairforce_render_product_filters_html();

		if ( '' === $filters_markup ) {
			return $block_content;
		}

		return $block_content . '<div class="cf-shop-archive-shell">' . $filters_markup;
	}

	/**
	 * Close the archive shell wrapper after the product collection block.
	 *
	 * @param string               $block_content Rendered block HTML.
	 * @param array<string, mixed> $block         Block data.
	 * @return string
	 */
	public function close_archive_shell_after_product_collection( string $block_content, array $block ): string {

		if ( 'woocommerce/product-collection' !== ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		if ( ! chairforce_is_product_filter_archive() ) {
			return $block_content;
		}

		return $block_content . '</div><!-- /.cf-shop-archive-shell -->';
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
