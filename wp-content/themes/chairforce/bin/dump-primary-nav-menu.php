<?php
/**
 * Dump Primary Nav + Utility Nav structure from wp-admin (for syncing setup script).
 *
 * Usage: ddev wp eval-file wp-content/themes/chairforce/bin/dump-primary-nav-menu.php
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param int $menu_id Nav menu term ID.
 */
function chairforce_menu_dump_tree( int $menu_id ): void {
	$items = wp_get_nav_menu_items( $menu_id, [ 'post_status' => 'any' ] );

	if ( ! is_array( $items ) || empty( $items ) ) {
		WP_CLI::warning( "Menu {$menu_id} has no items." );
		return;
	}

	usort(
		$items,
		static fn( $a, $b ) => [ (int) $a->menu_order, (int) $a->ID ] <=> [ (int) $b->menu_order, (int) $b->ID ]
	);

	$parent_map = [];
	foreach ( $items as $item ) {
		$parent_map[ (int) $item->ID ] = (int) $item->menu_item_parent;
	}

	foreach ( $items as $item ) {
		$depth = 0;
		$pid   = (int) $item->menu_item_parent;

		while ( $pid ) {
			++$depth;
			$pid = $parent_map[ $pid ] ?? 0;
		}

		$indent = str_repeat( '  ', $depth );
		$acf    = function_exists( 'get_fields' ) ? ( get_fields( $item->ID ) ?: [] ) : [];
		$bits   = [
			"type={$item->type}",
			"object={$item->object}",
			"obj_id={$item->object_id}",
		];

		if ( 'custom' === $item->type && $item->url ) {
			$bits[] = 'url=' . $item->url;
		}

		foreach ( [ 'link_type', 'grid_columns', 'nav_align', 'column_span', 'child_columns', 'utility_icon' ] as $key ) {
			if ( ! empty( $acf[ $key ] ) ) {
				$bits[] = "{$key}={$acf[ $key ]}";
			}
		}

		if ( ! empty( $acf['label_mobile'] ) ) {
			$bits[] = 'label_mobile=' . wp_json_encode( $acf['label_mobile'] );
		}

		WP_CLI::log(
			sprintf(
				'%s[%d] %s %s',
				$indent,
				$item->ID,
				$item->title,
				implode( ' ', $bits )
			)
		);
	}
}

WP_CLI::log( '=== Primary Nav (1356) ===' );
chairforce_menu_dump_tree( 1356 );

WP_CLI::log( '' );
WP_CLI::log( '=== Utility Nav (1357) ===' );
chairforce_menu_dump_tree( 1357 );
