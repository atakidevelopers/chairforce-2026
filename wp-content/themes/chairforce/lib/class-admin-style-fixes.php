<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Admin_Style_Fixes' ) ) {
	return;
}

/**
 * Small, permanent wp-admin CSS overrides for third-party plugin conflicts.
 *
 * Not part of the Sass/webpack build — `src/sass-admin/` only loads on
 * block-editor screens (`enqueue_block_editor_assets`), but these fixes
 * need to apply on every wp-admin screen, so they're a tiny inline style
 * attached to WordPress's own always-enqueued `common` admin stylesheet.
 */
class Admin_Style_Fixes {

	public function __construct() {
		$this->register_hooks();
	}

	private function register_hooks(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_style_overrides' ] );
	}

	/**
	 * @hooked admin_enqueue_scripts
	 */
	public function enqueue_admin_style_overrides(): void {
		wp_add_inline_style( 'common', $this->get_inline_css() );
	}

	/**
	 * @return string
	 */
	private function get_inline_css(): string {
		return '
			/*
			 * Verifone Hosted (assets/css/auto_setup.css) ships an
			 * unscoped `button { border-radius: 32px !important; ... }`
			 * rule meant only for its own gateway setup screen. Every
			 * other declaration in it loses to more specific admin CSS,
			 * but the !important border-radius wins everywhere and
			 * rounds every wp-admin button. Restoring native square
			 * corners globally.
			 */
			.wp-core-ui p .button {
				border-radius: unset !important;
			}
		';
	}
}
