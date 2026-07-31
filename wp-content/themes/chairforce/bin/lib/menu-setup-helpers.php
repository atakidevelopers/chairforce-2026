<?php
/**
 * Shared helpers for programmatic Primary Nav menu builds.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensure a nav menu item belongs to the target menu term.
 *
 * @param int $item_id Menu item post ID.
 * @param int $menu_id Nav menu term ID.
 */
function chairforce_menu_setup_assign_to_menu( int $item_id, int $menu_id ): void {
	wp_set_object_terms( $item_id, $menu_id, 'nav_menu' );
}

/**
 * Set ACF fields on a nav menu item.
 *
 * @param int                  $item_id Menu item post ID.
 * @param array<string, mixed> $fields  Field name => value.
 */
function chairforce_menu_setup_set_fields( int $item_id, array $fields ): void {
	if ( ! function_exists( 'update_field' ) ) {
		return;
	}

	foreach ( $fields as $name => $value ) {
		update_field( $name, $value, $item_id );
	}
}

/**
 * Add a product_cat term to the menu.
 *
 * @param int                  $menu_id   Nav menu term ID.
 * @param int                  $parent_id Parent menu item ID.
 * @param int                  $term_id   Taxonomy term ID.
 * @param string               $title     Menu item title.
 * @param array<string, mixed> $fields    Extra ACF fields.
 * @param string               $taxonomy  Taxonomy slug (default product_cat).
 */
function chairforce_menu_setup_add_term(
	int $menu_id,
	int $parent_id,
	int $term_id,
	string $title,
	array $fields = [],
	string $taxonomy = 'product_cat'
): int {

	$item_id = wp_update_nav_menu_item(
		$menu_id,
		0,
		[
			'menu-item-object-id' => $term_id,
			'menu-item-object'    => $taxonomy,
			'menu-item-type'      => 'taxonomy',
			'menu-item-status'    => 'publish',
			'menu-item-title'     => $title,
			'menu-item-parent-id' => $parent_id,
		]
	);

	if ( is_wp_error( $item_id ) ) {
		WP_CLI::error( $item_id->get_error_message() );
	}

	$item_id = (int) $item_id;
	wp_update_post(
		[
			'ID'         => $item_id,
			'post_title' => $title,
		]
	);
	update_post_meta( $item_id, '_menu_item_title', $title );
	chairforce_menu_setup_assign_to_menu( $item_id, $menu_id );

	chairforce_menu_setup_set_fields(
		$item_id,
		array_merge(
			[
				'link_type' => 'thumbnail-link',
			],
			$fields
		)
	);

	return $item_id;
}

/**
 * Add a custom URL menu item.
 *
 * @param int                  $menu_id   Nav menu term ID.
 * @param int                  $parent_id Parent menu item ID.
 * @param string               $title     Menu item title.
 * @param string               $url       Item URL.
 * @param array<string, mixed> $fields    Extra ACF fields.
 */
function chairforce_menu_setup_add_custom(
	int $menu_id,
	int $parent_id,
	string $title,
	string $url,
	array $fields = []
): int {

	$item_id = wp_update_nav_menu_item(
		$menu_id,
		0,
		[
			'menu-item-type'      => 'custom',
			'menu-item-url'       => $url,
			'menu-item-title'     => $title,
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent_id,
		]
	);

	if ( is_wp_error( $item_id ) ) {
		WP_CLI::error( $item_id->get_error_message() );
	}

	$item_id = (int) $item_id;
	chairforce_menu_setup_assign_to_menu( $item_id, $menu_id );
	chairforce_menu_setup_set_fields( $item_id, $fields );

	return $item_id;
}

/**
 * Add a section heading to the menu.
 *
 * @param int                  $menu_id   Nav menu term ID.
 * @param int                  $parent_id Parent menu item ID.
 * @param string               $title     Heading label.
 * @param array<string, mixed> $fields    Extra ACF fields.
 */
function chairforce_menu_setup_add_heading(
	int $menu_id,
	int $parent_id,
	string $title,
	array $fields = []
): int {

	$item_id = wp_update_nav_menu_item(
		$menu_id,
		0,
		[
			'menu-item-type'      => 'custom',
			'menu-item-url'       => '#',
			'menu-item-title'     => $title,
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent_id,
		]
	);

	if ( is_wp_error( $item_id ) ) {
		WP_CLI::error( $item_id->get_error_message() );
	}

	$item_id = (int) $item_id;
	chairforce_menu_setup_assign_to_menu( $item_id, $menu_id );

	chairforce_menu_setup_set_fields(
		$item_id,
		array_merge(
			[
				'link_type' => 'heading',
			],
			$fields
		)
	);

	return $item_id;
}

/**
 * Find a top-level primary nav item linked to a product category.
 *
 * @param int $menu_id Nav menu term ID.
 * @param int $term_id Product category term ID.
 */
function chairforce_menu_setup_find_top_level_term( int $menu_id, int $term_id ): int {

	$items = wp_get_nav_menu_items( $menu_id, [ 'post_status' => 'any' ] );

	if ( ! is_array( $items ) ) {
		return 0;
	}

	foreach ( $items as $item ) {
		if (
			0 === (int) $item->menu_item_parent
			&& 'taxonomy' === $item->type
			&& 'product_cat' === $item->object
			&& $term_id === (int) $item->object_id
		) {
			return (int) $item->ID;
		}
	}

	return 0;
}

/**
 * Find a top-level primary nav item by exact title.
 *
 * @param int    $menu_id Nav menu term ID.
 * @param string $title   Menu item title.
 */
function chairforce_menu_setup_find_top_level_title( int $menu_id, string $title ): int {

	$items = wp_get_nav_menu_items( $menu_id, [ 'post_status' => 'any' ] );

	if ( ! is_array( $items ) ) {
		return 0;
	}

	foreach ( $items as $item ) {
		if ( 0 === (int) $item->menu_item_parent && $title === $item->title ) {
			return (int) $item->ID;
		}
	}

	return 0;
}

/**
 * Whether a menu item already has at least one child.
 *
 * @param int $menu_id Nav menu term ID.
 * @param int $item_id Parent menu item ID.
 */
function chairforce_menu_setup_has_children( int $menu_id, int $item_id ): bool {

	$items = wp_get_nav_menu_items( $menu_id, [ 'post_status' => 'any' ] );

	if ( ! is_array( $items ) ) {
		return false;
	}

	foreach ( $items as $item ) {
		if ( (int) $item->menu_item_parent === $item_id ) {
			return true;
		}
	}

	return false;
}

/**
 * Delete a menu item and all of its descendants.
 *
 * @param int $menu_id Nav menu term ID.
 * @param int $item_id Root menu item post ID.
 */
function chairforce_menu_setup_delete_item_subtree( int $menu_id, int $item_id ): void {

	$items = wp_get_nav_menu_items( $menu_id, [ 'post_status' => 'any' ] );

	if ( ! is_array( $items ) ) {
		return;
	}

	$children_by_parent = [];

	foreach ( $items as $item ) {
		$parent = (int) $item->menu_item_parent;

		if ( ! isset( $children_by_parent[ $parent ] ) ) {
			$children_by_parent[ $parent ] = [];
		}

		$children_by_parent[ $parent ][] = (int) $item->ID;
	}

	$delete_recursive = static function ( int $id ) use ( &$delete_recursive, $children_by_parent ): void {
		foreach ( $children_by_parent[ $id ] ?? [] as $child_id ) {
			$delete_recursive( $child_id );
		}

		wp_delete_post( $id, true );
	};

	$delete_recursive( $item_id );
}

/**
 * Delete direct children of a menu item (keep the parent).
 *
 * @param int $menu_id   Nav menu term ID.
 * @param int $parent_id Parent menu item post ID.
 */
function chairforce_menu_setup_delete_children( int $menu_id, int $parent_id ): void {

	$items = wp_get_nav_menu_items( $menu_id, [ 'post_status' => 'any' ] );

	if ( ! is_array( $items ) ) {
		return;
	}

	foreach ( $items as $item ) {
		if ( (int) $item->menu_item_parent === $parent_id ) {
			chairforce_menu_setup_delete_item_subtree( $menu_id, (int) $item->ID );
		}
	}
}

/**
 * Create or reuse a top-level taxonomy menu item and run a child builder.
 *
 * @param int                  $menu_id        Nav menu term ID.
 * @param int                  $term_id        Product category term ID.
 * @param string               $title          Menu item title.
 * @param array<string, mixed> $fields         ACF fields for the top-level item.
 * @param callable             $build_children Callback receiving ( menu_id, parent_id ).
 */
function chairforce_menu_setup_build_top_level_term(
	int $menu_id,
	int $term_id,
	string $title,
	array $fields,
	callable $build_children
): int {

	$parent_id = chairforce_menu_setup_find_top_level_term( $menu_id, $term_id );

	if ( $parent_id && chairforce_menu_setup_has_children( $menu_id, $parent_id ) ) {
		WP_CLI::log( sprintf( 'Skipping %s — already built (ID %d).', $title, $parent_id ) );
		return $parent_id;
	}

	if ( ! $parent_id ) {
		$parent_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			[
				'menu-item-object-id' => $term_id,
				'menu-item-object'    => 'product_cat',
				'menu-item-type'      => 'taxonomy',
				'menu-item-status'    => 'publish',
				'menu-item-title'     => $title,
				'menu-item-parent-id' => 0,
			]
		);

		if ( is_wp_error( $parent_id ) ) {
			WP_CLI::error( $parent_id->get_error_message() );
		}

		$parent_id = (int) $parent_id;
		chairforce_menu_setup_assign_to_menu( $parent_id, $menu_id );
	} else {
		WP_CLI::log( sprintf( 'Rebuilding children for %s (ID %d).', $title, $parent_id ) );
	}

	wp_update_post(
		[
			'ID'         => $parent_id,
			'post_title' => $title,
		]
	);
	update_post_meta( $parent_id, '_menu_item_title', $title );
	chairforce_menu_setup_set_fields( $parent_id, $fields );

	$build_children( $menu_id, $parent_id );

	return $parent_id;
}

/**
 * Create or reuse a top-level custom menu item and run a child builder.
 *
 * @param int                  $menu_id        Nav menu term ID.
 * @param string               $title          Menu item title.
 * @param string               $url            Item URL.
 * @param array<string, mixed> $fields         ACF fields for the top-level item.
 * @param callable             $build_children Callback receiving ( menu_id, parent_id ).
 * @param bool                 $force_rebuild  When true, replace existing children.
 */
function chairforce_menu_setup_build_top_level_custom(
	int $menu_id,
	string $title,
	string $url,
	array $fields,
	callable $build_children,
	bool $force_rebuild = false
): int {

	$parent_id = chairforce_menu_setup_find_top_level_title( $menu_id, $title );

	if ( $parent_id && chairforce_menu_setup_has_children( $menu_id, $parent_id ) && ! $force_rebuild ) {
		WP_CLI::log( sprintf( 'Skipping %s — already built (ID %d).', $title, $parent_id ) );
		return $parent_id;
	}

	if ( ! $parent_id ) {
		$parent_id = chairforce_menu_setup_add_custom( $menu_id, 0, $title, $url, $fields );
	} else {
		if ( $force_rebuild ) {
			WP_CLI::log( sprintf( 'Force rebuilding children for %s (ID %d).', $title, $parent_id ) );
			chairforce_menu_setup_delete_children( $menu_id, $parent_id );
		} else {
			WP_CLI::log( sprintf( 'Rebuilding children for %s (ID %d).', $title, $parent_id ) );
		}

		chairforce_menu_setup_set_fields( $parent_id, $fields );
	}

	$build_children( $menu_id, $parent_id );

	return $parent_id;
}

/**
 * Add thumbnail-link children in menu order (flat grid).
 *
 * @param int                                                       $menu_id Nav menu term ID.
 * @param int                                                       $parent_id Parent menu item ID.
 * @param array<int, array{0: int, 1: string}>|array<int, string> $items   term_id/title pairs or map.
 * @param string                                                    $taxonomy Taxonomy slug (default product_cat).
 */
function chairforce_menu_setup_add_thumbnail_items(
	int $menu_id,
	int $parent_id,
	array $items,
	string $taxonomy = 'product_cat'
): void {

	foreach ( $items as $key => $value ) {
		if ( is_array( $value ) ) {
			[ $term_id, $title ] = $value;
		} else {
			$term_id = (int) $key;
			$title   = (string) $value;
		}

		chairforce_menu_setup_add_term( $menu_id, $parent_id, (int) $term_id, $title, [], $taxonomy );
	}
}
