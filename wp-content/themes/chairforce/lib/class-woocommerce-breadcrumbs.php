<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\WooCommerce_Breadcrumbs' ) ) {
	return;
}

/**
 * Normalise WooCommerce breadcrumb markup to core-like list output.
 */
class WooCommerce_Breadcrumbs {

	/**
	 * WooCommerce_Breadcrumbs constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register breadcrumb hooks.
	 */
	private function register_hooks(): void {
		add_filter( 'render_block', [ $this, 'filter_store_breadcrumbs_block' ], 10, 2 );
	}

	/**
	 * Replace flat Store Breadcrumbs block markup with an ordered list.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Parsed block.
	 * @return string
	 */
	public function filter_store_breadcrumbs_block( string $block_content, array $block ): string {
		if ( 'woocommerce/breadcrumbs' !== ( $block['blockName'] ?? '' ) ) {
			return $block_content;
		}

		if ( ! function_exists( 'chairforce_get_wc_breadcrumb_crumbs' ) ) {
			return $block_content;
		}

		$crumbs = chairforce_get_wc_breadcrumb_crumbs();

		if ( empty( $crumbs ) ) {
			return $block_content;
		}

		$list_nav = chairforce_render_breadcrumb_list_html( $crumbs );

		if ( '' === $list_nav ) {
			return $block_content;
		}

		$updated = preg_replace(
			'#<nav\b[^>]*\bwoocommerce-breadcrumb\b[^>]*>.*?</nav>#is',
			$list_nav,
			$block_content,
			1
		);

		return is_string( $updated ) && $updated !== $block_content ? $updated : $block_content;
	}
}
