<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Content_Types' ) ) {
	return;
}

/**
 * Registers JetEngine-era custom post types and taxonomies for the rebuild.
 *
 * Schema only (chunk 1) — ACF field groups are registered separately.
 * Slugs and rewrite rules match the live database exactly so existing rows
 * appear in wp-admin without migration.
 */
class Content_Types {

	/**
	 * Content_Types constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register required hooks.
	 */
	private function register_hooks(): void {
		add_action( 'init', [ $this, 'register_post_types' ] );
		add_action( 'init', [ $this, 'register_taxonomies' ], 11 );
	}

	/**
	 * Register custom post types from file 14 §A (bucket 2).
	 *
	 * @hooked init
	 */
	public function register_post_types(): void {
		$this->register_showrooms_post_type();
		$this->register_gallery_tabs_post_type();
		$this->register_year_carousel_post_type();
		$this->register_review_post_type();
	}

	/**
	 * Register custom taxonomies from file 14 §C (bucket 2).
	 *
	 * @hooked init, priority 11 — after post types.
	 */
	public function register_taxonomies(): void {
		$this->register_showroom_locations_taxonomy();
		$this->register_gallery_category_taxonomy();
		$this->register_venues_taxonomy();
		$this->register_sales_by_location_taxonomy();
		$this->register_feature_taxonomy();
	}

	/**
	 * Register the showrooms post type.
	 */
	private function register_showrooms_post_type(): void {
		$labels = [
			'name'          => __( 'Showrooms', 'chairforce' ),
			'singular_name' => __( 'Showroom', 'chairforce' ),
			'menu_name'     => __( 'Showrooms', 'chairforce' ),
			'all_items'     => __( 'All Showrooms', 'chairforce' ),
			'add_new_item'  => __( 'Add New Showroom', 'chairforce' ),
			'edit_item'     => __( 'Edit Showroom', 'chairforce' ),
			'view_item'     => __( 'View Showroom', 'chairforce' ),
			'search_items'  => __( 'Search Showrooms', 'chairforce' ),
			'not_found'     => __( 'No showrooms found', 'chairforce' ),
			'archives'      => __( 'Showroom archives', 'chairforce' ),
		];

		register_post_type(
			'showrooms',
			[
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'query_var'           => true,
				'rewrite'             => [
					'slug'       => 'showrooms',
					'with_front' => false,
				],
				'has_archive'         => true,
				'hierarchical'        => false,
				'exclude_from_search' => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'menu_icon'           => 'dashicons-admin-site',
				'menu_position'       => 25,
				'supports'            => [ 'title', 'thumbnail', 'revisions', "editor" ],
			]
		);
	}

	/**
	 * Register the gallery-tabs post type.
	 */
	private function register_gallery_tabs_post_type(): void {
		$labels = [
			'name'          => __( 'Gallery', 'chairforce' ),
			'singular_name' => __( 'Gallery Item', 'chairforce' ),
			'menu_name'     => __( 'Gallery', 'chairforce' ),
			'all_items'     => __( 'All Gallery Items', 'chairforce' ),
			'add_new_item'  => __( 'Add New Gallery Item', 'chairforce' ),
			'edit_item'     => __( 'Edit Gallery Item', 'chairforce' ),
			'view_item'     => __( 'View Gallery Item', 'chairforce' ),
			'search_items'  => __( 'Search Gallery', 'chairforce' ),
			'not_found'     => __( 'No gallery items found', 'chairforce' ),
			'archives'      => __( 'Gallery archives', 'chairforce' ),
		];

		register_post_type(
			'gallery-tabs',
			[
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'query_var'           => true,
				'rewrite'             => [
					'slug'       => 'gallery-tabs',
					'with_front' => false,
				],
				'has_archive'         => true,
				'hierarchical'        => false,
				'exclude_from_search' => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'menu_icon'           => 'dashicons-format-gallery',
				'menu_position'       => 26,
				'supports'            => [ 'title', 'revisions' ],
			]
		);
	}

	/**
	 * Register the year-carousel post type.
	 */
	private function register_year_carousel_post_type(): void {
		$labels = [
			'name'          => __( 'Year Carousel', 'chairforce' ),
			'singular_name' => __( 'Year Carousel Item', 'chairforce' ),
			'menu_name'     => __( 'Year Carousel', 'chairforce' ),
			'all_items'     => __( 'All Year Carousel Items', 'chairforce' ),
			'add_new_item'  => __( 'Add New Year Carousel Item', 'chairforce' ),
			'edit_item'     => __( 'Edit Year Carousel Item', 'chairforce' ),
			'view_item'     => __( 'View Year Carousel Item', 'chairforce' ),
			'search_items'  => __( 'Search Year Carousel', 'chairforce' ),
			'not_found'     => __( 'No year carousel items found', 'chairforce' ),
			'archives'      => __( 'Year carousel archives', 'chairforce' ),
		];

		register_post_type(
			'year-carousel',
			[
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'query_var'           => true,
				'rewrite'             => [
					'slug'       => 'year-carousel',
					'with_front' => false,
				],
				'has_archive'         => true,
				'hierarchical'        => false,
				'exclude_from_search' => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'menu_icon'           => 'dashicons-filter',
				'menu_position'       => 27,
				'supports'            => [ 'title', 'editor', 'revisions' ],
			]
		);
	}

	/**
	 * Register the review post type.
	 */
	private function register_review_post_type(): void {
		$labels = [
			'name'          => __( 'Reviews', 'chairforce' ),
			'singular_name' => __( 'Review', 'chairforce' ),
			'menu_name'     => __( 'Reviews', 'chairforce' ),
			'all_items'     => __( 'All Reviews', 'chairforce' ),
			'add_new_item'  => __( 'Add New Review', 'chairforce' ),
			'edit_item'     => __( 'Edit Review', 'chairforce' ),
			'view_item'     => __( 'View Review', 'chairforce' ),
			'search_items'  => __( 'Search Reviews', 'chairforce' ),
			'not_found'     => __( 'No reviews found', 'chairforce' ),
			'archives'      => __( 'Review archives', 'chairforce' ),
		];

		register_post_type(
			'review',
			[
				'labels'              => $labels,
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'query_var'           => true,
				'rewrite'             => [
					'slug'       => 'review',
					'with_front' => false,
				],
				'has_archive'         => true,
				'hierarchical'        => false,
				'exclude_from_search' => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'menu_icon'           => 'dashicons-format-standard',
				'menu_position'       => 28,
				'supports'            => [ 'title', 'revisions' ],
			]
		);
	}

	/**
	 * Register the showroom-locations taxonomy.
	 */
	private function register_showroom_locations_taxonomy(): void {
		$labels = [
			'name'          => __( 'Showroom Locations', 'chairforce' ),
			'singular_name' => __( 'Showroom Location', 'chairforce' ),
			'menu_name'     => __( 'Showroom Locations', 'chairforce' ),
			'all_items'     => __( 'All Showroom Locations', 'chairforce' ),
			'edit_item'     => __( 'Edit Showroom Location', 'chairforce' ),
			'view_item'     => __( 'View Showroom Location', 'chairforce' ),
			'update_item'   => __( 'Update Showroom Location', 'chairforce' ),
			'add_new_item'  => __( 'Add New Showroom Location', 'chairforce' ),
			'new_item_name' => __( 'New Showroom Location Name', 'chairforce' ),
			'search_items'  => __( 'Search Showroom Locations', 'chairforce' ),
			'not_found'     => __( 'No showroom locations found', 'chairforce' ),
		];

		register_taxonomy(
			'showroom-locations',
			[ 'showrooms' ],
			[
				'labels'            => $labels,
				'public'            => true,
				'publicly_queryable'=> true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => [
					'slug'         => 'showroom-locations',
					'with_front'   => false,
					'hierarchical' => false,
				],
				'query_var'         => true,
			]
		);
	}

	/**
	 * Register the gallery-category taxonomy.
	 */
	private function register_gallery_category_taxonomy(): void {
		$labels = [
			'name'          => __( 'Gallery Categories', 'chairforce' ),
			'singular_name' => __( 'Gallery Category', 'chairforce' ),
			'menu_name'     => __( 'Gallery Categories', 'chairforce' ),
			'all_items'     => __( 'All Gallery Categories', 'chairforce' ),
			'edit_item'     => __( 'Edit Gallery Category', 'chairforce' ),
			'view_item'     => __( 'View Gallery Category', 'chairforce' ),
			'update_item'   => __( 'Update Gallery Category', 'chairforce' ),
			'add_new_item'  => __( 'Add New Gallery Category', 'chairforce' ),
			'new_item_name' => __( 'New Gallery Category Name', 'chairforce' ),
			'search_items'  => __( 'Search Gallery Categories', 'chairforce' ),
			'not_found'     => __( 'No gallery categories found', 'chairforce' ),
		];

		register_taxonomy(
			'gallery-category',
			[ 'gallery-tabs' ],
			[
				'labels'            => $labels,
				'public'            => true,
				'publicly_queryable'=> true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => [
					'slug'         => 'gallery-category',
					'with_front'   => false,
					'hierarchical' => false,
				],
				'query_var'         => true,
			]
		);
	}

	/**
	 * Register the venues taxonomy on products.
	 */
	private function register_venues_taxonomy(): void {
		$labels = [
			'name'          => __( 'Venues', 'chairforce' ),
			'singular_name' => __( 'Venue', 'chairforce' ),
			'menu_name'     => __( 'Venues', 'chairforce' ),
			'all_items'     => __( 'All Venues', 'chairforce' ),
			'edit_item'     => __( 'Edit Venue', 'chairforce' ),
			'view_item'     => __( 'View Venue', 'chairforce' ),
			'update_item'   => __( 'Update Venue', 'chairforce' ),
			'add_new_item'  => __( 'Add New Venue', 'chairforce' ),
			'new_item_name' => __( 'New Venue Name', 'chairforce' ),
			'search_items'  => __( 'Search Venues', 'chairforce' ),
			'not_found'     => __( 'No venues found', 'chairforce' ),
		];

		register_taxonomy(
			'venues',
			[ 'product' ],
			[
				'labels'            => $labels,
				'public'            => true,
				'publicly_queryable'=> true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => [
					'slug'         => 'venue',
					'with_front'   => false,
					'hierarchical' => false,
				],
				'query_var'         => true,
			]
		);
	}

	/**
	 * Register the sales-by-location taxonomy on products.
	 */
	private function register_sales_by_location_taxonomy(): void {
		$labels = [
			'name'          => __( 'Sales By Location', 'chairforce' ),
			'singular_name' => __( 'Sales By Location', 'chairforce' ),
			'menu_name'     => __( 'Sales By Location', 'chairforce' ),
			'all_items'     => __( 'All Sales By Location', 'chairforce' ),
			'edit_item'     => __( 'Edit Sales By Location', 'chairforce' ),
			'view_item'     => __( 'View Sales By Location', 'chairforce' ),
			'update_item'   => __( 'Update Sales By Location', 'chairforce' ),
			'add_new_item'  => __( 'Add New Sales By Location', 'chairforce' ),
			'new_item_name' => __( 'New Sales By Location Name', 'chairforce' ),
			'search_items'  => __( 'Search Sales By Location', 'chairforce' ),
			'not_found'     => __( 'No sales by location terms found', 'chairforce' ),
		];

		register_taxonomy(
			'sales-by-location',
			[ 'product' ],
			[
				'labels'            => $labels,
				'public'            => true,
				'publicly_queryable'=> true,
				'hierarchical'      => true,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => [
					'slug'         => 'sales-by-location',
					'with_front'   => false,
					'hierarchical' => false,
				],
				'query_var'         => true,
			]
		);
	}

	/**
	 * Register the feature taxonomy on products.
	 */
	private function register_feature_taxonomy(): void {
		$labels = [
			'name'          => __( 'Features', 'chairforce' ),
			'singular_name' => __( 'Feature', 'chairforce' ),
			'menu_name'     => __( 'Features', 'chairforce' ),
			'all_items'     => __( 'All Features', 'chairforce' ),
			'edit_item'     => __( 'Edit Feature', 'chairforce' ),
			'view_item'     => __( 'View Feature', 'chairforce' ),
			'update_item'   => __( 'Update Feature', 'chairforce' ),
			'add_new_item'  => __( 'Add New Feature', 'chairforce' ),
			'new_item_name' => __( 'New Feature Name', 'chairforce' ),
			'search_items'  => __( 'Search Features', 'chairforce' ),
			'not_found'     => __( 'No features found', 'chairforce' ),
		];

		register_taxonomy(
			'feature',
			[ 'product' ],
			[
				'labels'            => $labels,
				'description'       => __( 'This is the feature list of the products.', 'chairforce' ),
				'public'            => true,
				'publicly_queryable'=> true,
				'hierarchical'      => false,
				'show_ui'           => true,
				'show_in_menu'      => true,
				'show_in_nav_menus' => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => [
					'slug'       => 'feature',
					'with_front' => false,
				],
				'query_var'         => true,
			]
		);
	}
}
