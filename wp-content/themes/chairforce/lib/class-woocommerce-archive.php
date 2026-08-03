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
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_archive_assets' ], 20 );
	}

	/**
	 * Enqueue scripts/styles for shop archive features (quick view, interactivity hydrate).
	 */
	public function enqueue_archive_assets(): void {
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

		$hydrate_path = get_theme_file_path( 'src/js-modules/interactivity-hydrate.js' );

		if ( file_exists( $hydrate_path ) ) {
			wp_enqueue_script_module(
				'chairforce/interactivity-hydrate',
				get_theme_file_uri( 'src/js-modules/interactivity-hydrate.js' ),
				[ '@wordpress/interactivity' ],
				(string) filemtime( $hydrate_path )
			);
		}
	}

}
