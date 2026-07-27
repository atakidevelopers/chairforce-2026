<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Primary_Walker' ) ) {
	return;
}

/**
 * Custom nav walker for primary and utility header menus.
 */
class Primary_Walker extends \Walker_Nav_Menu {

	/**
	 * @var \WP_Post|null
	 */
	private $current_item = null;

	/**
	 * @inheritDoc
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ): void {

		if ( ! $args ) {
			$args = (object) [];
		}

		$item = $data_object;
		$this->current_item = $item;

		$indent = $depth ? str_repeat( "\t", $depth ) : '';

		$classes   = empty( $item->classes ) ? [] : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		if ( 0 === $depth && chairforce_menu_is_primary( $args ) ) {
			$classes[] = 'site-header__nav-item';
		}

		if ( 0 === $depth && chairforce_menu_is_utility( $args ) ) {
			$classes[] = 'site-header__utility-item';
		}

		if ( $this->has_children && 0 === $depth && chairforce_menu_is_primary( $args ) ) {
			$classes[] = 'site-header__nav-item--has-mega';
		}

		$class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$output .= $indent . '<li' . $class_names . '>';

		$link_type = chairforce_menu_get_link_type( $item, $args );

		if ( 'heading' === $link_type ) {
			$title = apply_filters( 'the_title', $item->title, $item->ID );

			if ( 'mobile-drawer' === chairforce_menu_get_context( $args ) && chairforce_menu_is_primary( $args ) ) {
				$item_output = sprintf(
					'<span class="site-header__mobile-drawer-heading">%s</span>',
					esc_html( $title )
				);
			} else {
				$item_output = sprintf(
					'<span class="site-header__mega-menu-heading">%s</span>',
					esc_html( $title )
				);
			}

			$item_output = apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
			$output     .= $indent . "\t" . $item_output;
			return;
		}

		if ( 'divider' === $link_type ) {
			$item_output = '<hr class="site-header__menu-divider" />';
			$item_output = apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
			$output     .= $indent . "\t" . $item_output;
			return;
		}

		$is_mobile_primary = chairforce_menu_is_primary( $args ) && 'mobile-drawer' === chairforce_menu_get_context( $args );

		$atts           = [];
		$atts['class']  = chairforce_menu_is_utility( $args ) ? 'site-header__utility-link' : 'site-header__nav-link';
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target ) ? $item->target : '';
		$atts['rel']    = ! empty( $item->xfn ) ? $item->xfn : '';

		if ( 'highlight-link' === $link_type ) {
			$atts['class'] .= ' site-header__nav-link--sale';
		}

		if ( $is_mobile_primary ) {
			$atts['class'] = 'site-header__mobile-drawer-link';

			if ( 'highlight-link' === $link_type ) {
				$atts['class'] .= ' site-header__mobile-drawer-link--sale';
			}

			if ( 0 === $depth && $this->has_children ) {
				$tag                     = 'button';
				$atts['type']            = 'button';
				$atts['class']          .= ' site-header__mobile-drill-trigger';
				$atts['aria-expanded']   = 'false';
				$mobile_label            = function_exists( 'get_field' ) ? (string) get_field( 'label_mobile', $item->ID ) : '';
				$atts['data-drill-title'] = $mobile_label ? $mobile_label : apply_filters( 'the_title', $item->title, $item->ID );
			} else {
				$tag          = 'a';
				$atts['href'] = ! empty( $item->url ) ? $item->url : '#';
				$atts['class'] .= ' site-header__mobile-drawer-link--leaf';
			}
		} elseif ( 0 === $depth && $this->has_children && chairforce_menu_is_primary( $args ) ) {
			$tag                       = 'button';
			$atts['type']              = 'button';
			$atts['aria-expanded']     = 'false';
			$atts['aria-haspopup']     = 'true';
			$atts['aria-controls']     = 'chairforce-mega-menu-' . chairforce_menu_get_item_slug( $item );
			$atts['data-menu-item-id'] = (string) $item->ID;
		} else {
			$tag          = 'a';
			$atts['href'] = ! empty( $item->url ) ? $item->url : '#';
		}

		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( is_scalar( $value ) && '' !== $value && false !== $value ) {
				$value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = apply_filters( 'nav_menu_item_title', chairforce_menu_render_labels( $item, $title ), $item, $args, $depth );

		if ( $is_mobile_primary && $this->has_children ) {
			$title .= '<span class="site-header__mobile-chevron" aria-hidden="true"></span>';
		} elseif ( 0 === $depth && $this->has_children && chairforce_menu_is_primary( $args ) ) {
			$title .= '<span class="site-header__nav-chevron" aria-hidden="true"></span>';
		}

		$item_output  = $args->before ?? '';
		$item_output .= '<' . $tag . $attributes . '>';
		$item_output .= ( $args->link_before ?? '' ) . $title . ( $args->link_after ?? '' );
		$item_output .= '</' . $tag . '>';
		$item_output .= $args->after ?? '';

		$item_output = apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );

		$output .= $indent . "\t" . $item_output;

	}

	/**
	 * @inheritDoc
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ): void {

		$indent = $depth ? str_repeat( "\t", $depth ) : '';
		$output .= "$indent</li>\n";

	}

	/**
	 * @inheritDoc
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ): void {

		if ( ! $args || ! chairforce_menu_is_primary( $args ) ) {
			parent::start_lvl( $output, $depth, $args );
			return;
		}

		ob_start();

		get_template_part(
			'includes/menu/walker/primary-walker',
			'start',
			[
				'depth'   => $depth,
				'item'    => $this->current_item,
				'context' => chairforce_menu_get_context( $args ),
			]
		);

		$output .= ob_get_clean();

	}

	/**
	 * @inheritDoc
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ): void {

		if ( ! $args || ! chairforce_menu_is_primary( $args ) ) {
			parent::end_lvl( $output, $depth, $args );
			return;
		}

		ob_start();

		get_template_part(
			'includes/menu/walker/primary-walker',
			'end',
			[
				'depth'   => $depth,
				'item'    => $this->current_item,
				'context' => chairforce_menu_get_context( $args ),
			]
		);

		$output .= ob_get_clean();

	}

}
