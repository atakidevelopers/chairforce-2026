<?php
/**
 * Export Primary Nav menu to JSON for restore/reference.
 *
 * Usage:
 *   ddev wp eval-file wp-content/themes/chairforce/bin/backup-primary-nav-menu.php
 *
 * Writes: bin/backups/primary-nav-{Y-m-d-His}.json
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$menu_id = 1356;
$items   = wp_get_nav_menu_items( $menu_id, [ 'post_status' => 'any' ] );

if ( ! is_array( $items ) ) {
	WP_CLI::error( 'Primary Nav menu items could not be loaded.' );
}

$export = [
	'exported_at' => gmdate( 'c' ),
	'menu_id'     => $menu_id,
	'menu_name'   => wp_get_nav_menu_object( $menu_id )->name ?? 'Primary Nav',
	'items'       => [],
];

foreach ( $items as $item ) {
	$acf = function_exists( 'get_fields' ) ? ( get_fields( $item->ID ) ?: [] ) : [];

	$export['items'][] = [
		'id'       => (int) $item->ID,
		'parent'   => (int) $item->menu_item_parent,
		'order'    => (int) $item->menu_order,
		'title'    => $item->title,
		'type'     => $item->type,
		'object'   => $item->object,
		'object_id'=> (int) $item->object_id,
		'url'      => $item->url,
		'acf'      => $acf,
	];
}

$backup_dir = __DIR__ . '/backups';

if ( ! is_dir( $backup_dir ) ) {
	wp_mkdir_p( $backup_dir );
}

$filename = sprintf( 'primary-nav-%s.json', gmdate( 'Y-m-d-His' ) );
$path     = $backup_dir . '/' . $filename;

$written = file_put_contents(
	$path,
	wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
);

if ( false === $written ) {
	WP_CLI::error( "Failed to write backup to {$path}" );
}

WP_CLI::success( sprintf( 'Primary Nav backup saved (%d items): %s', count( $export['items'] ), $path ) );
