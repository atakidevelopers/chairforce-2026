<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\WooCommerce_Single_Product' ) ) {
	return;
}

/**
 * WooCommerce single-product customizations (gallery, tabs, Quick View — Phase 3).
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
		// Phase 3: variation gallery, product tabs, Quick View, etc.
	}

}
