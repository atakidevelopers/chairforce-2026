<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\WooCommerce_Single_Product' ) ) {
	return;
}

/**
 * WooCommerce single-product customizations (swatches, gallery swap — Phase 3e).
 *
 * @see context/plans/3e-single-product-swatches-and-gallery-plan.md
 */
class WooCommerce_Single_Product {

	/**
	 * WooCommerce_Single_Product constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register single-product hooks.
	 */
	private function register_hooks(): void {
		add_filter(
			'woocommerce_dropdown_variation_attribute_options_html',
			[ $this, 'prepend_attribute_swatches_html' ],
			10,
			2
		);

		add_filter(
			'woocommerce_available_variation',
			[ $this, 'add_variation_gallery_html' ],
			10,
			3
		);
	}

	/**
	 * Prepend swatch markup before each attribute's real `<select>`.
	 *
	 * @param string               $html Dropdown HTML.
	 * @param array<string, mixed> $args Dropdown args.
	 * @return string
	 */
	public function prepend_attribute_swatches_html( string $html, array $args ): string {
		$product   = $args['product'] ?? null;
		$attribute = $args['attribute'] ?? '';

		if ( ! $product instanceof \WC_Product || ! $product->is_type( 'variable' ) || empty( $attribute ) ) {
			return $html;
		}

		$swatches = Product_Swatches::render_single_product_swatches( $product, (string) $attribute, $args );

		if ( '' === $swatches ) {
			return $html;
		}

		return $swatches . $html;
	}

	/**
	 * Expose variation gallery wrapper HTML for frontend gallery swap.
	 *
	 * @param array<string, mixed> $data     Variation JSON payload.
	 * @param \WC_Product            $product Parent variable product.
	 * @param \WC_Product_Variation  $variation Variation product.
	 * @return array<string, mixed>
	 */
	public function add_variation_gallery_html( array $data, $product, $variation ): array {
		if ( ! $variation instanceof \WC_Product_Variation || ! $product instanceof \WC_Product ) {
			return $data;
		}

		if ( ! function_exists( 'wc_get_product_gallery_html' ) ) {
			return $data;
		}

		$image_ids = Product_Swatches::get_variation_gallery_image_ids( $variation );

		if ( count( $image_ids ) < 2 ) {
			return $data;
		}

		$gallery_html = wc_get_product_gallery_html( $product, $image_ids );

		if ( '' !== $gallery_html ) {
			// Full gallery root HTML — required by wc_variations_gallery_replace().
			$data['cf_variation_gallery_html'] = $gallery_html;
		}

		return $data;
	}
}
