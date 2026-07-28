<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Attribute_Swatch_Preview_Column' ) ) {
	return;
}

/**
 * Admin-only "Preview" column on WooCommerce attribute term-list screens
 * (`edit-tags.php?taxonomy=pa_*`), rendering the color/image swatch for
 * each term so merchandisers can scan values without opening every term.
 *
 * Rebuilds Woodmart's equivalent column
 * (`inc/integrations/woocommerce/modules/swatches.php`,
 * `woodmart_product_attributes_thumbnail_column_content()`) — same visual
 * language, simpler code, since the underlying `image` term meta is now a
 * plain attachment ID (see context/PROGRESS.md 3c) rather than the legacy
 * `['url','id']` array Woodmart's version had to unwrap.
 *
 * Applies to every registered attribute taxonomy dynamically
 * (`wc_get_attribute_taxonomies()`), not a fixed list — matches Woodmart's
 * original behaviour and automatically covers any future attribute too.
 */
class Attribute_Swatch_Preview_Column {

	const COLUMN_KEY = 'cf_swatch_preview';

	public function __construct() {
		$this->register_hooks();
	}

	private function register_hooks(): void {
		add_action( 'admin_init', [ $this, 'register_column_hooks' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_styles' ] );
	}

	/**
	 * @hooked admin_init
	 */
	public function register_column_hooks(): void {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return;
		}

		foreach ( wc_get_attribute_taxonomies() as $attribute ) {
			$taxonomy = 'pa_' . $attribute->attribute_name;

			add_filter( "manage_edit-{$taxonomy}_columns", [ $this, 'add_preview_column' ] );
			add_filter( "manage_{$taxonomy}_custom_column", [ $this, 'render_preview_column' ], 10, 3 );
		}
	}

	/**
	 * Insert the Preview column right after Name, leaving every other
	 * column (including any added by other plugins) exactly where it was.
	 *
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function add_preview_column( array $columns ): array {
		$reordered = [];

		foreach ( $columns as $key => $label ) {
			$reordered[ $key ] = $label;

			if ( 'name' === $key ) {
				$reordered[ self::COLUMN_KEY ] = __( 'Preview', 'chairforce' );
			}
		}

		return $reordered;
	}

	/**
	 * @param string $content     Existing (empty) column content.
	 * @param string $column_name Column being rendered.
	 * @param int    $term_id     Term ID.
	 * @return string
	 */
	public function render_preview_column( string $content, string $column_name, int $term_id ): string {
		if ( self::COLUMN_KEY !== $column_name ) {
			return $content;
		}

		$color        = get_term_meta( $term_id, 'color', true );
		$image_id     = get_term_meta( $term_id, 'image', true );
		$not_dropdown = get_term_meta( $term_id, 'not_dropdown', true );

		$image_url = ( $image_id && is_numeric( $image_id ) )
			? wp_get_attachment_image_url( (int) $image_id, 'thumbnail' )
			: false;

		if ( $image_url ) {
			return sprintf(
				'<div class="cf-attr-preview cf-attr-preview--image"><img src="%s" alt=""></div>',
				esc_url( $image_url )
			);
		}

		if ( $color ) {
			return sprintf(
				'<div class="cf-attr-preview cf-attr-preview--color" style="background-color:%s;"></div>',
				esc_attr( $color )
			);
		}

		if ( $not_dropdown ) {
			$term = get_term( $term_id );

			return sprintf(
				'<div class="cf-attr-preview cf-attr-preview--text"><span>%s</span></div>',
				esc_html( ( $term && ! is_wp_error( $term ) ) ? $term->name : '' )
			);
		}

		return $content;
	}

	/**
	 * @hooked admin_enqueue_scripts
	 */
	public function maybe_enqueue_styles(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'edit-tags' !== $screen->base || 0 !== strpos( (string) $screen->taxonomy, 'pa_' ) ) {
			return;
		}

		wp_add_inline_style( 'common', $this->get_inline_css() );
	}

	/**
	 * @return string
	 */
	private function get_inline_css(): string {
		return '
			.cf-attr-preview {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				box-sizing: border-box;
				min-width: 35px;
				min-height: 35px;
				border-radius: 35px;
			}

			.cf-attr-preview--image,
			.cf-attr-preview--text {
				outline: 1px solid rgba(0, 0, 0, .075);
				outline-offset: -1px;
			}

			.cf-attr-preview--image img {
				display: block;
				width: 35px;
				height: 35px;
				border-radius: inherit;
				object-fit: cover;
			}

			.cf-attr-preview--text {
				padding: 0 7px;
			}

			.cf-attr-preview--text span {
				font-weight: 600;
				font-size: 13px;
			}
		';
	}
}
