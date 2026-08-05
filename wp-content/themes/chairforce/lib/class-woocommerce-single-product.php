<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\WooCommerce_Single_Product' ) ) {
	return;
}

/**
 * WooCommerce single-product customizations (swatches, tabs — Phase 3e/3g).
 *
 * Gallery swap is now handled natively by WooCommerce's Variation Gallery feature
 * (WooCommerce → Settings → Advanced → Features → "Variation gallery"). The canary
 * populates gallery_image_ids and gallery_images_html in woocommerce_available_variation,
 * which drives both the block Product Gallery (Interactivity API) and the classic gallery
 * swap in single-product-swatches.js.
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
		add_action( 'init', [ $this, 'remove_legacy_upsell_grid' ], 20 );

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

		add_filter( 'woocommerce_product_tabs', [ $this, 'register_product_tabs' ], 98 );
	}

	/**
	 * Drop legacy PHP upsell loop — single product template uses block upsells.
	 */
	public function remove_legacy_upsell_grid(): void {
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	}

	/**
	 * Register custom single-product tabs via WooCommerce core tab API.
	 *
	 * Works with the Product Details block (legacy + accordion) and classic templates.
	 *
	 * @param array<string, array<string, mixed>> $tabs Product tabs.
	 * @return array<string, array<string, mixed>>
	 */
	public function register_product_tabs( array $tabs ): array {

		unset( $tabs['additional_information'] );

		if ( isset( $tabs['reviews'] ) ) {
			$tabs['reviews']['priority'] = 50;
		}

		$product_id = get_the_ID();

		if ( $product_id <= 0 ) {
			return $tabs;
		}

		if ( chairforce_get_product_dimensions_html( $product_id ) ) {
			$tabs['cf_dimensions'] = [
				'title'    => __( 'Dimensions', 'chairforce' ),
				'priority' => 15,
				'callback' => [ $this, 'render_dimensions_tab' ],
			];
		}

		if ( chairforce_get_product_care_html( $product_id ) ) {
			$tabs['cf_care'] = [
				'title'    => __( 'Care', 'chairforce' ),
				'priority' => 20,
				'callback' => [ $this, 'render_care_tab' ],
			];
		}

		if ( ! empty( chairforce_get_product_parts_ids( $product_id ) ) ) {
			$tabs['cf_parts'] = [
				'title'    => __( 'Parts', 'chairforce' ),
				'priority' => 25,
				'callback' => [ $this, 'render_parts_tab' ],
			];
		}

		if ( chairforce_get_product_additional_information_html( $product_id ) ) {
			$tabs['cf_additional_information'] = [
				'title'    => __( 'Additional Information', 'chairforce' ),
				'priority' => 30,
				'callback' => [ $this, 'render_additional_information_tab' ],
			];
		}

		if ( chairforce_get_product_delivery_information_html() ) {
			$tabs['cf_delivery_information'] = [
				'title'    => __( 'Delivery Information', 'chairforce' ),
				'priority' => 35,
				'callback' => [ $this, 'render_delivery_information_tab' ],
			];
		}

		if ( chairforce_get_product_info_html() ) {
			$tabs['cf_product_info'] = [
				'title'    => __( 'Product Info', 'chairforce' ),
				'priority' => 40,
				'callback' => [ $this, 'render_product_info_tab' ],
			];
		}

		return $tabs;
	}

	/**
	 * Output the Dimensions tab panel.
	 *
	 * @param string               $key Tab key.
	 * @param array<string, mixed> $tab Tab config.
	 * @return void
	 */
	public function render_dimensions_tab( string $key, array $tab ): void {
		chairforce_render_product_tab_panel(
			'dimensions',
			chairforce_get_product_dimensions_html( get_the_ID() )
		);
	}

	/**
	 * Output the Care tab panel.
	 *
	 * @param string               $key Tab key.
	 * @param array<string, mixed> $tab Tab config.
	 * @return void
	 */
	public function render_care_tab( string $key, array $tab ): void {
		chairforce_render_product_tab_panel(
			'care',
			chairforce_get_product_care_html( get_the_ID() )
		);
	}

	/**
	 * Output the Parts tab panel.
	 *
	 * @param string               $key Tab key.
	 * @param array<string, mixed> $tab Tab config.
	 * @return void
	 */
	public function render_parts_tab( string $key, array $tab ): void {
		chairforce_render_product_parts_tab( get_the_ID() );
	}

	/**
	 * Output the Additional Information tab panel.
	 *
	 * @param string               $key Tab key.
	 * @param array<string, mixed> $tab Tab config.
	 * @return void
	 */
	public function render_additional_information_tab( string $key, array $tab ): void {
		chairforce_render_product_tab_panel(
			'additional-information',
			chairforce_get_product_additional_information_html( get_the_ID() )
		);
	}

	/**
	 * Output the Delivery Information tab panel.
	 *
	 * @param string               $key Tab key.
	 * @param array<string, mixed> $tab Tab config.
	 * @return void
	 */
	public function render_delivery_information_tab( string $key, array $tab ): void {
		chairforce_render_product_tab_panel(
			'delivery-information',
			chairforce_get_product_delivery_information_html()
		);
	}

	/**
	 * Output the Product Info tab panel.
	 *
	 * @param string               $key Tab key.
	 * @param array<string, mixed> $tab Tab config.
	 * @return void
	 */
	public function render_product_info_tab( string $key, array $tab ): void {
		chairforce_render_product_tab_panel(
			'product-info',
			chairforce_get_product_info_html()
		);
	}

	/**
	 * Expose variation gallery HTML for the classic gallery swap.
	 *
	 * Used when the WC Variation Gallery feature (Settings → Advanced → Features) is
	 * disabled. When the feature IS enabled it populates gallery_images_html natively,
	 * making cf_variation_gallery_html redundant — but keeping it here costs nothing
	 * and ensures the classic path always has a value to fall back to.
	 *
	 * Image ID priority:
	 *   1. WC native _product_image_gallery meta (populated by the BatchPress migration job)
	 *   2. Woodmart wd_additional_variation_images_data (original legacy meta)
	 *
	 * @param array<string, mixed>  $data      Variation JSON payload.
	 * @param \WC_Product           $product   Parent variable product.
	 * @param \WC_Product_Variation $variation Variation product.
	 * @return array<string, mixed>
	 */
	public function add_variation_gallery_html( array $data, $product, $variation ): array {
		if ( ! $variation instanceof \WC_Product_Variation || ! $product instanceof \WC_Product ) {
			return $data;
		}

		if ( ! function_exists( 'wc_get_product_gallery_html' ) ) {
			return $data;
		}

		// 1. Try WC native gallery meta (_product_image_gallery on the variation).
		$extra_ids = array_map( 'intval', $variation->get_gallery_image_ids() );

		// 2. Fall back to the original Woodmart meta.
		if ( empty( $extra_ids ) ) {
			$extra_ids = Product_Swatches::get_variation_gallery_attachment_ids( $variation->get_id() );
		}

		// Always lead with the variation's featured image.
		$featured_id = (int) $variation->get_image_id();
		if ( $featured_id && ! in_array( $featured_id, $extra_ids, true ) ) {
			array_unshift( $extra_ids, $featured_id );
		}

		if ( count( $extra_ids ) < 2 ) {
			return $data;
		}

		$gallery_html = wc_get_product_gallery_html( $product, $extra_ids );

		if ( '' !== $gallery_html ) {
			$data['cf_variation_gallery_html'] = $gallery_html;
		}

		return $data;
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

}
