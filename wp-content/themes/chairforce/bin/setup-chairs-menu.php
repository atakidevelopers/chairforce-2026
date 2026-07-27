<?php
/**
 * One-off: build the Chairs mega menu (Pattern A) in Primary Nav.
 *
 * Usage: ddev wp eval-file wp-content/themes/chairforce/bin/setup-chairs-menu.php
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$menu_id = 1356;

/**
 * Ensure a nav menu item belongs to the target menu term.
 *
 * wp_update_nav_menu_item() should do this, but nested items created
 * programmatically can end up orphaned from the menu without an explicit assign.
 *
 * @param int $item_id Menu item post ID.
 */
function chairforce_setup_chairs_menu_assign_to_menu( int $item_id ): void {
	global $menu_id;

	wp_set_object_terms( $item_id, (int) $menu_id, 'nav_menu' );
}

/**
 * Set ACF fields on a nav menu item.
 *
 * @param int                  $item_id Menu item post ID.
 * @param array<string, mixed> $fields  Field name => value.
 */
function chairforce_setup_chairs_menu_set_fields( int $item_id, array $fields ): void {
	foreach ( $fields as $name => $value ) {
		update_field( $name, $value, $item_id );
	}
}

/**
 * Add a product_cat term to the menu.
 *
 * @param int    $parent_id Parent menu item ID.
 * @param int    $term_id   Product category term ID.
 * @param string $title     Menu item title.
 */
function chairforce_setup_chairs_menu_add_term( int $parent_id, int $term_id, string $title ): int {
	global $menu_id;

	$item_id = wp_update_nav_menu_item(
		$menu_id,
		0,
		[
			'menu-item-object-id' => $term_id,
			'menu-item-object'    => 'product_cat',
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
	chairforce_setup_chairs_menu_assign_to_menu( $item_id );

	chairforce_setup_chairs_menu_set_fields(
		$item_id,
		[
			'link_type' => 'thumbnail-link',
		]
	);

	return $item_id;
}

/**
 * Add a section heading to the menu.
 *
 * @param int                  $parent_id Parent menu item ID.
 * @param string               $title     Heading label.
 * @param array<string, mixed> $fields    Extra ACF fields.
 */
function chairforce_setup_chairs_menu_add_heading( int $parent_id, string $title, array $fields = [] ): int {
	global $menu_id;

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
	chairforce_setup_chairs_menu_assign_to_menu( $item_id );

	chairforce_setup_chairs_menu_set_fields(
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

// Skip if Chairs already exists at top level (unless children are orphaned from the menu).
$existing_chairs_id = 0;
$existing           = wp_get_nav_menu_items( $menu_id, [ 'post_status' => 'any' ] );
if ( is_array( $existing ) ) {
	foreach ( $existing as $item ) {
		if ( 0 === (int) $item->menu_item_parent && 'taxonomy' === $item->type && 'product_cat' === $item->object && 183 === (int) $item->object_id ) {
			$existing_chairs_id = (int) $item->ID;
			break;
		}
	}
}

if ( $existing_chairs_id ) {
	$has_type_child = false;
	foreach ( $existing as $item ) {
		if ( (int) $item->menu_item_parent === $existing_chairs_id && 'TYPE' === $item->title ) {
			$has_type_child = true;
			break;
		}
	}

	if ( $has_type_child ) {
		WP_CLI::warning( 'Chairs menu item already exists (ID ' . $existing_chairs_id . '). Skipping.' );
		return;
	}

	WP_CLI::warning( 'Chairs exists but submenu is missing — rebuilding children under ID ' . $existing_chairs_id . '.' );
	$chairs_id = $existing_chairs_id;
} else {
	$chairs_id = wp_update_nav_menu_item(
		$menu_id,
		0,
		[
			'menu-item-object-id' => 183,
			'menu-item-object'    => 'product_cat',
			'menu-item-type'      => 'taxonomy',
			'menu-item-status'    => 'publish',
			'menu-item-title'     => 'Chairs',
			'menu-item-parent-id' => 0,
			'menu-item-position'  => 1,
		]
	);

	if ( is_wp_error( $chairs_id ) ) {
		WP_CLI::error( $chairs_id->get_error_message() );
	}

	$chairs_id = (int) $chairs_id;
	chairforce_setup_chairs_menu_assign_to_menu( $chairs_id );

	chairforce_setup_chairs_menu_set_fields(
		$chairs_id,
		[
			'layout_variant' => 'grouped-text',
			'grid_columns'   => '4',
			'label_mobile'   => 'Explore Chairs',
		]
	);
}

// TYPE — two sub-columns (left then right per Figma).
$type_id = chairforce_setup_chairs_menu_add_heading(
	$chairs_id,
	'TYPE',
	[
		'column_span'   => '2',
		'child_columns' => '2',
	]
);

$type_items = [
	[ 997, 'Cafe Chairs' ],
	[ 1176, 'Office Chairs' ],
	[ 263, 'Dining Chairs' ],
	[ 1298, 'Outdoor Chairs' ],
	[ 1142, 'Armchairs' ],
	[ 1147, 'Stackable Chairs' ],
	[ 1149, 'Visitors Chairs' ],
];

foreach ( $type_items as $type_item ) {
	chairforce_setup_chairs_menu_add_term( $type_id, $type_item[0], $type_item[1] );
}

// STYLES.
$styles_id = chairforce_setup_chairs_menu_add_heading( $chairs_id, 'STYLES' );

$style_items = [
	[ 260, 'Bentwood Chairs' ],
	[ 261, 'Crossback Chairs' ],
	[ 266, 'Parisian Chairs' ],
];

foreach ( $style_items as $style_item ) {
	chairforce_setup_chairs_menu_add_term( $styles_id, $style_item[0], $style_item[1] );
}

// MATERIALS.
$materials_id = chairforce_setup_chairs_menu_add_heading( $chairs_id, 'MATERIALS' );

$material_items = [
	[ 1150, 'Plastic Chairs' ],
	[ 264, 'Metal Chairs' ],
	[ 1146, 'Timber Chairs' ],
	[ 1148, 'Upholstered Chairs' ],
	[ 1303, 'Chair Cushions' ],
];

foreach ( $material_items as $material_item ) {
	chairforce_setup_chairs_menu_add_term( $materials_id, $material_item[0], $material_item[1] );
}

WP_CLI::success( 'Chairs mega menu created (menu item ID ' . $chairs_id . ').' );
