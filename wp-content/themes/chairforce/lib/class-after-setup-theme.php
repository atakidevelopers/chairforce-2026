<?php

namespace Chairforce;
// exit if file is called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// if class already defined, bail out
if ( class_exists( 'Chairforce\After_Setup_Theme' ) ) {
	return;
}


/**
 * This class will set up theme
 *
 * @package    Chairforce
 * @subpackage Chairforce/lib
 */
class After_Setup_Theme {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.1
	 */
	public function __construct() {

		$this->register_hooks();

	}

	/**
	 * Register required hooks
	 */
	public function register_hooks() {
		add_action( 'after_setup_theme', [ $this, 'setup_theme' ] );
		add_action( 'after_setup_theme', [ $this, 'load_theme_textdomain' ] );

//		add_action( 'wp_enqueue_scripts', [ $this, 'remove_default_block_styling' ] );

		/**
		 * Custom Pattern Categories
		 */
		add_action( 'init', [ $this, 'register_pattern_categories' ] );

	}

	/**
	 * Theme Setup like:
	 * add_theme_support , image sizes, menu register
	 * @hooked after_setup_theme
	 */
	public function setup_theme() {

		/*
		 * Theme Support
		 */
		add_post_type_support( 'page', 'excerpt' );

		/**
		 * Image Sizes
		 */
		add_image_size( CHAIRFORCE_MENU_THUMB_SIZE, 108, 108, true );

		/**
		 * Navigation menu locations
		 */
		register_nav_menus(
			[
				CHAIRFORCE_MENU_PRIMARY => esc_html__( 'Primary Navigation', 'chairforce' ),
				CHAIRFORCE_MENU_UTILITY => esc_html__( 'Utility Navigation', 'chairforce' ),
			]
		);

	}

	/**
	 * Remove in-line default styling for Gutenberg blocks
	 * @hooked wp_enqueue_scripts
	 * @noinspection PhpUnused
	 */
	public function remove_default_block_styling() {

		$remove_style_for_blocks = [
			'button',
			'buttons',
			'post-title',
			'heading',
			'paragraph',
			'quote',
			'post-comments'
		];

		foreach ( $remove_style_for_blocks as $block ) {
			wp_dequeue_style( "wp-block-$block" );
		}
	}

	/**
	 * Load the theme text domain for translation.
	 * Uses get_template_directory() so the parent theme's languages folder is used
	 * even when a child theme is active (Cloudways/local consistency, case-sensitive FS).
	 *
	 * @since    0.0.1
	 */
	public function load_theme_textdomain() {

		$languages_path = get_template_directory() . '/languages';
		if ( is_readable( $languages_path ) ) {
			load_theme_textdomain( 'chairforce', $languages_path );
		}

	}

	/**
	 * Register Custom Pattern Categories.
	 * Can fail on some hosts due to caching (e.g. Cloudways Breeze/Redis/OPcache)
	 * or init order; ensure caches are cleared after deployment.
	 */
	public function register_pattern_categories() {

		if ( ! function_exists( 'register_block_pattern_category' ) ) {
			return;
		}

		$categories_to_register = [
			'elements' => __( 'Elements', 'chairforce' ),
			'section'  => __( 'Section', 'chairforce' ),
		];

		foreach ( $categories_to_register as $category_slug => $category_label ) {
			register_block_pattern_category(
				$category_slug,
				[
					'label' => $category_label,
				]
			);
		}
	}


}
