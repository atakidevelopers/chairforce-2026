<?php
/**
 * Header menu filters — classes, link types, custom item output.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'nav_menu_css_class',
	function ( $class_array, $item, $args, $depth ) {

		if ( ! chairforce_menu_is_chairforce_nav( $args ) ) {
			return $class_array;
		}

		$link_type = chairforce_menu_get_link_type( $item, $args );
		if ( $link_type ) {
			$class_array[] = 'site-header__link-type-' . sanitize_html_class( $link_type );
		}

		if ( 0 === $depth && function_exists( 'get_field' ) ) {
			$nav_align = get_field( 'nav_align', $item->ID );
			if ( 'right' === $nav_align ) {
				$class_array[] = 'site-header__nav-item--align-right';
			}

			$grid_columns = get_field( 'grid_columns', $item->ID );
			if ( $grid_columns ) {
				$class_array[] = 'site-header__grid-columns-' . (int) $grid_columns;
			}
		}

		if ( function_exists( 'get_field' ) ) {
			$column_span = get_field( 'column_span', $item->ID );
			if ( $column_span ) {
				$class_array[] = 'site-header__col-span-' . (int) $column_span;
			}

			$child_columns = get_field( 'child_columns', $item->ID );
			if ( $child_columns ) {
				$class_array[] = 'site-header__child-columns-' . (int) $child_columns;
			}

			$visibility = get_field( 'visibility', $item->ID );
			if ( $visibility && ! in_array( $visibility, array( 'none', 'both' ), true ) ) {
				$class_array[] = sanitize_html_class( $visibility );
			}
		}

		return $class_array;

	},
	10,
	4
);

add_filter(
	'walker_nav_menu_start_el',
	function ( $item_output, $item, $depth, $args ) {

		if ( ! chairforce_menu_is_chairforce_nav( $args ) ) {
			return $item_output;
		}

		$link_type = chairforce_menu_get_link_type( $item, $args );

		switch ( $link_type ) {
			case 'thumbnail-link':
				if ( 'mobile-drawer' === chairforce_menu_get_context( $args ) ) {
					return $item_output;
				}

				ob_start();
				get_template_part(
					'includes/menu/chairforce-menu-thumbnail',
					'link',
					[
						'item'  => $item,
						'depth' => $depth,
						'args'  => $args,
					]
				);
				return ob_get_clean();

			case 'utility-link':
				ob_start();
				get_template_part(
					'includes/menu/menu',
					'utility-link',
					[ 'item' => $item ]
				);
				return ob_get_clean();

			default:
				return $item_output;
		}

	},
	10,
	4
);

add_filter(
	'body_class',
	function ( $classes ) {

		if ( wp_is_mobile() ) {
			$classes[] = 'wp-is-mobile';
		}

		return $classes;

	}
);

add_action(
	'wp_footer',
	function () {
		echo '<div class="site-header__backdrop" aria-hidden="true"></div>';
	}
);
