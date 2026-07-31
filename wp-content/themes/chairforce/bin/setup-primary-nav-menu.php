<?php
/**
 * Build Primary Nav + Utility Nav menus per Figma mega menu specs.
 *
 * Synced with wp-admin menus (31 Jul 2026):
 * - Primary Nav (term_id 1356, location chairforce-primary-nav)
 * - Top Bar Utility Nav (term_id 1357, location chairforce-utility-nav)
 *
 * Usage: ddev wp eval-file wp-content/themes/chairforce/bin/setup-primary-nav-menu.php
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/menu-setup-helpers.php';

$primary_menu_id = 1356;
$utility_menu_id   = 1357;

// -------------------------------------------------------------------------
// Chairs — Pattern A (grouped text).
// -------------------------------------------------------------------------
chairforce_menu_setup_build_top_level_term(
	$primary_menu_id,
	183,
	'Chairs',
	[
		'grid_columns'   => '4',
		'label_mobile'   => 'Explore Chairs',
	],
	function ( int $menu_id, int $chairs_id ): void {
		$type_id = chairforce_menu_setup_add_heading(
			$menu_id,
			$chairs_id,
			'TYPE',
			[
				'column_span'   => '2',
				'child_columns' => '2',
			]
		);

		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$type_id,
			[
				[ 997, 'Cafe Chairs' ],
				[ 1176, 'Office Chairs' ],
				[ 263, 'Dining Chairs' ],
				[ 1298, 'Outdoor Chairs' ],
				[ 1142, 'Armchairs' ],
				[ 1147, 'Stackable Chairs' ],
				[ 1149, 'Visitors Chairs' ],
			]
		);

		$styles_id = chairforce_menu_setup_add_heading( $menu_id, $chairs_id, 'STYLES' );

		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$styles_id,
			[
				[ 260, 'Bentwood Chairs' ],
				[ 261, 'Crossback Chairs' ],
				[ 266, 'Parisian Chairs' ],
			]
		);

		$materials_id = chairforce_menu_setup_add_heading( $menu_id, $chairs_id, 'MATERIALS' );

		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$materials_id,
			[
				[ 1150, 'Plastic Chairs' ],
				[ 264, 'Metal Chairs' ],
				[ 1146, 'Timber Chairs' ],
				[ 1148, 'Upholstered Chairs' ],
				[ 1303, 'Chair Cushions' ],
			]
		);
	}
);

// -------------------------------------------------------------------------
// Stools — Pattern C (grouped thumbnails).
// -------------------------------------------------------------------------
chairforce_menu_setup_build_top_level_term(
	$primary_menu_id,
	184,
	'Stools',
	[
		'grid_columns'   => '4',
		'label_mobile'   => 'Explore Stools',
	],
	function ( int $menu_id, int $parent_id ): void {
		$type_id = chairforce_menu_setup_add_heading(
			$menu_id,
			$parent_id,
			'TYPE',
			[
				'column_span'   => '2',
				'child_columns' => '2',
			]
		);

		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$type_id,
			[
				[ 257, 'Bar Stools' ],
				[ 256, 'Counter Stools' ],
				[ 254, 'Low Stools' ],
				[ 208, 'Outdoor Stools' ],
				[ 1153, 'Kitchen Stools' ],
				[ 1154, 'Armrest Stools' ],
				[ 1158, 'Stackable Stools' ],
			]
		);

		$material_id = chairforce_menu_setup_add_heading(
			$menu_id,
			$parent_id,
			'MATERIAL',
			[
				'column_span'   => '2',
				'child_columns' => '2',
			]
		);

		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$material_id,
			[
				[ 258, 'Metal Stools' ],
				[ 1155, 'Timber Stools' ],
				[ 259, 'Replica Tolix Stools' ],
				[ 1157, 'Upholstered Stools' ],
				[ 1304, 'Stool Cushions' ],
			]
		);
	}
);

// -------------------------------------------------------------------------
// Tables & Bench Seating — Pattern B (flat grid, 3 columns).
// -------------------------------------------------------------------------
chairforce_menu_setup_build_top_level_term(
	$primary_menu_id,
	185,
	'Tables & Bench Seating',
	[
		'grid_columns'   => '3',
		'label_mobile'   => 'Explore Tables & Bench Seating',
	],
	function ( int $menu_id, int $parent_id ): void {
		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$parent_id,
			[
				[ 241, 'Folding Tables' ],
				[ 245, 'Dry Bar Tables' ],
				[ 252, 'Table & Chair Sets' ],
				[ 1301, 'Dining Tables' ],
				[ 246, 'Picnic Tables' ],
				[ 253, 'Communal Tables' ],
				[ 242, 'Kitchen Counter Tables' ],
				[ 247, 'Indoor Tables' ],
				[ 251, 'Bench Seating' ],
				[ 243, 'Mobile Tables' ],
				[ 1280, 'Alfresco Tables' ],
				[ 244, 'Bar Tables' ],
				[ 248, 'Outdoor Tables' ],
			]
		);
	}
);

// -------------------------------------------------------------------------
// Table Tops & Bases — Pattern C.
// -------------------------------------------------------------------------
chairforce_menu_setup_build_top_level_term(
	$primary_menu_id,
	186,
	'Table Tops & Bases',
	[
		'grid_columns'   => '4',
		'label_mobile'   => 'Explore Table Tops & Bases',
	],
	function ( int $menu_id, int $parent_id ): void {
		$tops_id = chairforce_menu_setup_add_heading(
			$menu_id,
			$parent_id,
			'TABLE TOPS',
			[
				'column_span'   => '2',
				'child_columns' => '2',
			]
		);

		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$tops_id,
			[
				[ 1011, 'Rectangle Table Tops' ],
				[ 1009, 'Timber Table Tops' ],
				[ 1010, 'Square Table Tops' ],
				[ 1103, 'Stone Table Tops' ],
				[ 1008, 'Round Table Tops' ],
				[ 1279, 'Alfresco Table Tops' ],
				[ 1007, 'Resin Table Tops' ],
				[ 1102, 'Outdoor Table Tops' ],
				[ 1014, 'Marble Table Tops' ],
				[ 186, 'Build Your Own Table' ],
			]
		);

		$bases_id = chairforce_menu_setup_add_heading(
			$menu_id,
			$parent_id,
			'TABLE BASES & LEGS',
			[
				'column_span'   => '2',
				'child_columns' => '2',
			]
		);

		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$bases_id,
			[
				[ 1202, 'Desk Bases' ],
				[ 235, 'Kitchen Bench Height Table Bases' ],
				[ 1203, 'Cast Iron Table Bases' ],
				[ 236, 'Coffee Table Height Bases' ],
				[ 1204, 'Aluminium Table Bases' ],
				[ 237, 'Table Legs' ],
				[ 239, 'Outdoor Table Bases' ],
				[ 238, 'Folding Table Bases' ],
				[ 233, 'Bar Height Table Bases' ],
				[ 1278, 'Alfresco Table Bases' ],
				[ 234, 'Dining Height Table Bases' ],
			]
		);
	}
);

// -------------------------------------------------------------------------
// Outdoor Furniture — Pattern C.
// -------------------------------------------------------------------------
chairforce_menu_setup_build_top_level_term(
	$primary_menu_id,
	1101,
	'Outdoor Furniture',
	[
		'grid_columns'   => '4',
		'label_mobile'   => 'Explore Outdoor Furniture',
	],
	function ( int $menu_id, int $parent_id ): void {
		$seating_id = chairforce_menu_setup_add_heading(
			$menu_id,
			$parent_id,
			'OUTDOOR SEATING',
			[
				'column_span'   => '2',
				'child_columns' => '2',
			]
		);

		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$seating_id,
			[
				[ 207, 'Outdoor Chairs' ],
				[ 208, 'Outdoor Stools' ],
				[ 209, 'Outdoor Lounges' ],
				[ 210, 'Sun Lounges' ],
			]
		);

		$tables_id = chairforce_menu_setup_add_heading(
			$menu_id,
			$parent_id,
			'OUTDOOR TABLES',
			[
				'column_span'   => '2',
				'child_columns' => '2',
			]
		);

		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$tables_id,
			[
				[ 213, 'Picnic Tables' ],
				[ 211, 'Outdoor Dining Tables' ],
				[ 1267, 'Outdoor Coffee Tables' ],
				[ 1224, 'Outdoor Counter Height Tables' ],
				[ 1060, 'Outdoor Bar Tables' ],
			]
		);
	}
);

// -------------------------------------------------------------------------
// Office — Pattern C (desktop label "Office").
// -------------------------------------------------------------------------
chairforce_menu_setup_build_top_level_term(
	$primary_menu_id,
	1232,
	'Office',
	[
		'grid_columns'   => '4',
		'label_mobile'   => 'Office Furniture',
	],
	function ( int $menu_id, int $parent_id ): void {
		$chairs_id = chairforce_menu_setup_add_heading(
			$menu_id,
			$parent_id,
			'OFFICE CHAIRS',
			[
				'column_span'   => '2',
				'child_columns' => '2',
			]
		);

		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$chairs_id,
			[
				[ 1176, 'Office Chairs' ],
				[ 1167, 'Boardroom Chairs' ],
				[ 1234, 'Computer Chairs' ],
				[ 1177, 'Office Reception Chairs' ],
				[ 1236, 'Mesh Office Chairs' ],
				[ 1242, 'Event & Conference Chairs' ],
			]
		);

		$tables_id = chairforce_menu_setup_add_heading(
			$menu_id,
			$parent_id,
			'OFFICE TABLES',
			[
				'column_span'   => '2',
				'child_columns' => '2',
			]
		);

		chairforce_menu_setup_add_term( $menu_id, $tables_id, 1199, 'Office Tables' );
	}
);

// -------------------------------------------------------------------------
// Storage — Pattern B (4 columns).
// -------------------------------------------------------------------------
chairforce_menu_setup_build_top_level_term(
	$primary_menu_id,
	1064,
	'Storage',
	[
		'grid_columns'   => '4',
		'label_mobile'   => 'Explore Storage',
	],
	function ( int $menu_id, int $parent_id ): void {
		chairforce_menu_setup_add_thumbnail_items(
			$menu_id,
			$parent_id,
			[
				[ 1061, 'Stainless Steel Benches' ],
				[ 1097, 'Sinks' ],
				[ 199, 'Drystore Shelf Units' ],
				[ 200, 'Stainless Pipe Wall Shelves' ],
				[ 1332, 'Folding Benches' ],
				[ 203, 'Cabinets' ],
				[ 201, 'Stainless Steel Shelf Units' ],
				[ 204, 'Catering Trolleys' ],
				[ 1333, 'Inset Sinks' ],
				[ 198, 'Coolroom Shelf Units' ],
				[ 1334, 'Stainless Flat Wall Shelves' ],
			]
		);
	}
);

// -------------------------------------------------------------------------
// Shop by Space — Pattern B (4 columns); top-level `venues` taxonomy terms.
// -------------------------------------------------------------------------
chairforce_menu_setup_build_top_level_custom(
	$primary_menu_id,
	'Shop by Space',
	'#',
	[
		'grid_columns'   => '4',
		'label_mobile'   => 'Explore Shop by Space',
		'nav_align'      => 'right',
	],
	function ( int $menu_id, int $parent_id ): void {
		$venue_terms = get_terms(
			[
				'taxonomy'   => 'venues',
				'parent'     => 0,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( is_wp_error( $venue_terms ) || empty( $venue_terms ) ) {
			WP_CLI::warning( 'No top-level venues terms found; Shop by Space children skipped.' );
			return;
		}

		$items = array_map(
			static fn( $term ) => [ (int) $term->term_id, $term->name ],
			$venue_terms
		);

		chairforce_menu_setup_add_thumbnail_items( $menu_id, $parent_id, $items, 'venues' );
	},
	true
);

// -------------------------------------------------------------------------
// New Arrivals — direct link, right-aligned.
// -------------------------------------------------------------------------
$new_id = chairforce_menu_setup_find_top_level_term( $primary_menu_id, 1124 );

if ( ! $new_id ) {
	$new_id = chairforce_menu_setup_add_term( $primary_menu_id, 0, 1124, 'New Arrivals', [] );
	chairforce_menu_setup_set_fields(
		$new_id,
		[
			'link_type' => 'default',
			'nav_align' => 'right',
		]
	);
} else {
	chairforce_menu_setup_set_fields(
		$new_id,
		[
			'link_type' => 'default',
			'nav_align' => 'right',
		]
	);
}

// -------------------------------------------------------------------------
// Sale — highlight link, right-aligned.
// -------------------------------------------------------------------------
$sale_id = chairforce_menu_setup_find_top_level_term( $primary_menu_id, 1223 );

if ( ! $sale_id ) {
	$sale_id = chairforce_menu_setup_add_term( $primary_menu_id, 0, 1223, 'Sale', [] );
	chairforce_menu_setup_set_fields(
		$sale_id,
		[
			'link_type' => 'highlight-link',
			'nav_align' => 'right',
		]
	);
} else {
	chairforce_menu_setup_set_fields(
		$sale_id,
		[
			'link_type' => 'highlight-link',
			'nav_align' => 'right',
		]
	);
}

// -------------------------------------------------------------------------
// Utility Nav — Showrooms, Account (Get a Quote removed in wp-admin, Jul 2026).
// -------------------------------------------------------------------------
$utility_items = wp_get_nav_menu_items( $utility_menu_id, [ 'post_status' => 'any' ] );

if ( is_array( $utility_items ) ) {
	foreach ( $utility_items as $utility_item ) {
		wp_delete_post( (int) $utility_item->ID, true );
	}
}

$showroom_url = home_url( '/showrooms/' );
$account_url  = home_url( '/my-account/' );

chairforce_menu_setup_add_custom(
	$utility_menu_id,
	0,
	'Showrooms',
	$showroom_url,
	[
		'link_type'     => 'utility-link',
		'utility_icon'  => 'map-pin',
		'label_mobile'  => 'Showrooms',
	]
);

chairforce_menu_setup_add_custom(
	$utility_menu_id,
	0,
	'Account',
	$account_url,
	[
		'link_type'     => 'utility-link',
		'utility_icon'  => 'user',
		'label_mobile'  => 'Account',
	]
);

WP_CLI::success( 'Primary Nav and Utility Nav menus built.' );
