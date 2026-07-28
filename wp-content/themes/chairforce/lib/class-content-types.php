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
		$this->register_jetengine_gallery_storage_filters();
	}

	/**
	 * Bridge JetEngine comma-separated gallery meta and ACF gallery fields.
	 *
	 * JetEngine stored gallery fields as a single comma-separated string (attachment
	 * IDs or URLs depending on value_format). ACF expects an array of attachment IDs.
	 */
	private function register_jetengine_gallery_storage_filters(): void {
		$id_gallery_fields = [
			'showroom_gallery' => 'post',
			'venue_image'      => 'term',
		];

		foreach ( $id_gallery_fields as $field_name => $object_type ) {
			add_filter(
				"acf/load_value/name={$field_name}",
				function ( $value, $post_id ) use ( $field_name, $object_type ) {
					return $this->load_jetengine_id_gallery_value( $value, $post_id, $field_name, $object_type );
				},
				10,
				3
			);

			add_filter(
				"acf/update_value/name={$field_name}",
				function ( $value ) {
					return $this->save_jetengine_id_gallery_value( $value );
				},
				10,
				3
			);
		}

		add_filter( 'acf/load_value/name=gallery_images', [ $this, 'load_jetengine_url_gallery_value' ], 10, 3 );
		add_filter( 'acf/update_value/name=gallery_images', [ $this, 'save_jetengine_url_gallery_value' ], 10, 3 );
	}

	/**
	 * Load a JetEngine ID gallery stored as comma-separated attachment IDs.
	 *
	 * @param mixed  $value       Current ACF value.
	 * @param mixed  $object_ref  Post ID or `term_{id}` reference.
	 * @param string $meta_key    Meta key name.
	 * @param string $object_type Either `post` or `term`.
	 * @return array<int>|mixed
	 */
	private function load_jetengine_id_gallery_value( $value, $object_ref, string $meta_key, string $object_type ) {
		if ( is_array( $value ) && ! empty( $value ) ) {
			return $value;
		}

		$raw = $this->get_legacy_meta_value( $object_ref, $meta_key, $object_type );
		if ( ! is_string( $raw ) || $raw === '' ) {
			return $value;
		}

		if ( str_starts_with( $raw, 'a:' ) ) {
			return $value;
		}

		return array_values(
			array_filter(
				array_map( 'intval', explode( ',', $raw ) )
			)
		);
	}

	/**
	 * Save a JetEngine ID gallery as comma-separated attachment IDs.
	 *
	 * @param mixed $value ACF gallery value.
	 * @return string|mixed
	 */
	private function save_jetengine_id_gallery_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$ids = array_values(
			array_filter(
				array_map( 'intval', $value )
			)
		);

		return implode( ',', $ids );
	}

	/**
	 * Load gallery_images stored as comma-separated URLs.
	 *
	 * @param mixed $value   Current ACF value.
	 * @param mixed $post_id Post ID.
	 * @return array<int>|mixed
	 */
	public function load_jetengine_url_gallery_value( $value, $post_id ) {
		if ( is_array( $value ) && ! empty( $value ) ) {
			return $value;
		}

		$raw = $this->get_legacy_meta_value( $post_id, 'gallery_images', 'post' );
		if ( ! is_string( $raw ) || $raw === '' || ! str_contains( $raw, 'http' ) ) {
			return $value;
		}

		$urls = array_values(
			array_filter(
				array_map( 'trim', explode( ',', $raw ) )
			)
		);

		$ids = [];
		foreach ( $urls as $url ) {
			$attachment_id = $this->resolve_attachment_id_from_url( $url );
			if ( $attachment_id ) {
				$ids[] = $attachment_id;
			}
		}

		return $ids;
	}

	/**
	 * Resolve an attachment ID from a stored media URL.
	 *
	 * JetEngine gallery_images stores full URLs that may use a different domain than
	 * the current site (e.g. production URL in meta on a local clone). Fall back to
	 * filename lookup when attachment_url_to_postid misses.
	 *
	 * @param string $url Media URL.
	 */
	private function resolve_attachment_id_from_url( string $url ): int {
		$attachment_id = attachment_url_to_postid( $url );
		if ( $attachment_id ) {
			return $attachment_id;
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! is_string( $path ) || $path === '' ) {
			return 0;
		}

		$filename = basename( $path );
		if ( $filename === '' ) {
			return 0;
		}

		global $wpdb;

		$attachment_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
				'%' . $wpdb->esc_like( $filename )
			)
		);

		return $attachment_id;
	}

	/**
	 * Save gallery_images as comma-separated URLs (JetEngine value_format: url).
	 *
	 * @param mixed $value ACF gallery value (attachment IDs).
	 * @return string|mixed
	 */
	public function save_jetengine_url_gallery_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		$urls = [];
		foreach ( $value as $attachment_id ) {
			$url = wp_get_attachment_url( (int) $attachment_id );
			if ( $url ) {
				$urls[] = $url;
			}
		}

		return implode( ',', $urls );
	}

	/**
	 * Read raw legacy meta for post or term objects.
	 *
	 * @param mixed  $object_ref  Post ID or `term_{id}` reference.
	 * @param string $meta_key    Meta key.
	 * @param string $object_type Either `post` or `term`.
	 * @return mixed
	 */
	private function get_legacy_meta_value( $object_ref, string $meta_key, string $object_type ) {
		if ( $object_type === 'term' ) {
			$term_id = $this->resolve_term_id( $object_ref );
			if ( ! $term_id ) {
				return null;
			}

			return get_term_meta( $term_id, $meta_key, true );
		}

		return get_post_meta( (int) $object_ref, $meta_key, true );
	}

	/**
	 * Resolve an ACF term reference to a numeric term ID.
	 *
	 * @param mixed $object_ref Post ID or `term_{id}` reference.
	 */
	private function resolve_term_id( $object_ref ): int {
		if ( is_string( $object_ref ) && str_starts_with( $object_ref, 'term_' ) ) {
			return (int) substr( $object_ref, 5 );
		}

		return (int) $object_ref;
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
				'supports'            => [ 'title', 'thumbnail', 'revisions' ],
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
