<?php

/**
 * Register Post Type: chairforce_faq.
 * Plural: FAQs
 * Singular: FAQ
 * Slug: faqs
 * @return void
 */
function chairforce_register_cpt_chairforce_faq(): void {

	$labels = [
		'name'                     => __( 'FAQs', 'chairforce' ),
		'singular_name'            => __( 'FAQ', 'chairforce' ),
		'menu_name'                => __( 'FAQs', 'chairforce' ),
		'all_items'                => __( 'All FAQs', 'chairforce' ),
		'add_new'                  => __( 'Add new', 'chairforce' ),
		'add_new_item'             => __( 'Add new FAQ', 'chairforce' ),
		'edit_item'                => __( 'Edit FAQ', 'chairforce' ),
		'new_item'                 => __( 'New FAQ', 'chairforce' ),
		'view_item'                => __( 'View FAQ', 'chairforce' ),
		'view_items'               => __( 'View FAQs', 'chairforce' ),
		'search_items'             => __( 'Search FAQs', 'chairforce' ),
		'not_found'                => __( 'No FAQs found', 'chairforce' ),
		'not_found_in_trash'       => __( 'No FAQs found in trash', 'chairforce' ),
		'parent'                   => __( 'Parent FAQ:', 'chairforce' ),
		'featured_image'           => __( 'Featured image for this FAQ', 'chairforce' ),
		'set_featured_image'       => __( 'Set featured image for this FAQ', 'chairforce' ),
		'remove_featured_image'    => __( 'Remove featured image for this FAQ', 'chairforce' ),
		'use_featured_image'       => __( 'Use as featured image for this FAQ', 'chairforce' ),
		'archives'                 => __( 'FAQ archives', 'chairforce' ),
		'insert_into_item'         => __( /** @lang text */ 'Insert into FAQ', 'chairforce' ),
		'uploaded_to_this_item'    => __( 'Upload to this FAQ', 'chairforce' ),
		'filter_items_list'        => __( 'Filter FAQs list', 'chairforce' ),
		'items_list_navigation'    => __( 'FAQs list navigation', 'chairforce' ),
		'items_list'               => __( 'FAQs list', 'chairforce' ),
		'attributes'               => __( 'FAQs attributes', 'chairforce' ),
		'name_admin_bar'           => __( 'FAQ', 'chairforce' ),
		'item_published'           => __( 'FAQ published', 'chairforce' ),
		'item_published_privately' => __( 'FAQ published privately.', 'chairforce' ),
		'item_reverted_to_draft'   => __( 'FAQ reverted to draft.', 'chairforce' ),
		'item_scheduled'           => __( 'FAQ scheduled', 'chairforce' ),
		'item_updated'             => __( 'FAQ updated.', 'chairforce' ),
		'parent_item_colon'        => __( 'Parent FAQ:', 'chairforce' ),
	];

	$args = [
		'label'                 => __( 'FAQs', 'chairforce' ),
		'labels'                => $labels,
		'description'           => '',
		'public'                => true,
		'publicly_queryable'    => true, // single page URL
		'show_ui'               => true,
		'show_in_rest'          => true,
		'rest_base'             => 'faqs',
		'rest_controller_class' => 'WP_REST_Posts_Controller',
		'has_archive'           => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'delete_with_user'      => false,
		'exclude_from_search'   => false,
		'capability_type'       => 'post',
		'map_meta_cap'          => true,
		'hierarchical'          => false,
		'rewrite'               => [
			'slug'       => 'faqs',
			'with_front' => false
		],
		'query_var'             => true,
		'menu_icon'             => 'dashicons-info-outline',
		'menu_position'         => 21,
		'supports'              => [ 'title', 'editor', 'thumbnail', 'excerpt', 'author', 'revisions' ]
	];

	register_post_type( 'chairforce_faq', $args );
}

add_action( 'init', 'chairforce_register_cpt_chairforce_faq' );

/**
 * Register Taxonomy: chairforce_faq_category.
 * Plural: FAQ Categories
 * Singular: FAQ Category
 * Slug: faq-category
 * Rest Base: faq-categories
 * Post type: post
 * @return void
 */
function chairforce_register_taxonomy_chairforce_faq_category(): void {

	$labels = [
		'name'                       => __( 'FAQ Categories', 'chairforce' ),
		'singular_name'              => __( 'FAQ Category', 'chairforce' ),
		'menu_name'                  => __( 'FAQ Categories', 'chairforce' ),
		'all_items'                  => __( 'All FAQ Categories', 'chairforce' ),
		'edit_item'                  => __( 'Edit FAQ Category', 'chairforce' ),
		'view_item'                  => __( 'View FAQ Category', 'chairforce' ),
		'update_item'                => __( 'Update FAQ Category name', 'chairforce' ),
		'add_new_item'               => __( 'Add new FAQ Category', 'chairforce' ),
		'new_item_name'              => __( 'New FAQ Category name', 'chairforce' ),
		'parent_item'                => __( 'Parent FAQ Category', 'chairforce' ),
		'parent_item_colon'          => __( 'Parent FAQ Category:', 'chairforce' ),
		'search_items'               => __( 'Search FAQ Categories', 'chairforce' ),
		'popular_items'              => __( 'Popular FAQ Categories', 'chairforce' ),
		'separate_items_with_commas' => __( 'Separate FAQ Categories with commas', 'chairforce' ),
		'add_or_remove_items'        => __( 'Add or remove FAQ Categories', 'chairforce' ),
		'choose_from_most_used'      => __( 'Choose from the most used FAQ Categories', 'chairforce' ),
		'not_found'                  => __( 'No FAQ Categories found', 'chairforce' ),
		'no_terms'                   => __( 'No FAQ Categories', 'chairforce' ),
		'items_list_navigation'      => __( 'FAQ Categories list navigation', 'chairforce' ),
		'items_list'                 => __( 'FAQ Categories list', 'chairforce' ),
		'back_to_items'              => __( 'Back to FAQ Categories', 'chairforce' ),
	];


	$args = [
		'label'                 => __( 'FAQ Categories', 'chairforce' ),
		'labels'                => $labels,
		'public'                => true,
		'publicly_queryable'    => true,
		'hierarchical'          => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'show_in_nav_menus'     => true,
		'show_admin_column'     => true,
		'show_in_rest'          => true,
		'show_in_quick_edit'    => true,
		'rest_base'             => 'faq-categories',
		'rewrite'               => [ 'slug' => 'faq-category', 'with_front' => false, ],
		'rest_controller_class' => 'WP_REST_Terms_Controller',
		'query_var'             => true,
		'show_in_graphql'       => false,
	];
	register_taxonomy(
		'chairforce_faq_category',
		[ 'chairforce_faq' ],
		$args
	);

}

add_action( 'init', 'chairforce_register_taxonomy_chairforce_faq_category' );
