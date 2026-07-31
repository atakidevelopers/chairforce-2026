<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Product_Swatches' ) ) {
	return;
}

/**
 * Read-side swatch data resolution and grid swatch markup (Mode A / select_options).
 *
 * Near-literal port of Woodmart's `woodmart_has_swatch()`,
 * `woodmart_get_option_variations()`, and `woodmart_grid_swatches_attribute()`
 * — renamed and scoped to the Chairforce theme.
 *
 * @see context/existing-functionality/03-product-card-grid-swatches.md
 * @see context/existing-functionality/02-data-model-and-storage.md §3/§5
 */
class Product_Swatches {

	/** @var string Per-product override post meta key (legacy Woodmart). */
	public const META_SWATCHES_ATTRIBUTE = '_woodmart_swatches_attribute';

	/** @var string Hardcoded grid attribute (live Theme Setting value). */
	public const DEFAULT_GRID_ATTRIBUTE = 'pa_colour';

	/** @var string Woodmart variation gallery meta (CSV attachment IDs). */
	public const VARIATION_GALLERY_META_KEY = 'wd_additional_variation_images_data';

	/**
	 * Visible swatch count before "+N" collapse (live `swatches_limit_count`).
	 *
	 * @var int
	 */
	public const SWATCHES_LIMIT = 3;

	/**
	 * Extra buffer before the limiter activates (Woodmart default filter value).
	 *
	 * @var int
	 */
	public const SWATCHES_LIMIT_BUFFER = 1;

	public function __construct() {
		// Reserved for future hooks; helpers are called statically by the block render.
	}

	/**
	 * Resolve which attribute taxonomy to show on the grid for a product.
	 *
	 * @param int $product_id Product post ID.
	 * @return string Attribute taxonomy name (e.g. `pa_colour`).
	 */
	public static function get_grid_swatches_attribute( int $product_id ): string {
		$custom = get_post_meta( $product_id, self::META_SWATCHES_ATTRIBUTE, true );

		if ( ! empty( $custom ) && is_string( $custom ) ) {
			return $custom;
		}

		return self::DEFAULT_GRID_ATTRIBUTE;
	}

	/**
	 * Read term-level swatch appearance meta for one attribute option slug.
	 *
	 * Port of `woodmart_has_swatch()`.
	 *
	 * @param int    $product_id Unused in Woodmart; kept for signature parity.
	 * @param string $attr_name  Full taxonomy name (e.g. `pa_colour`).
	 * @param string $value      Term slug.
	 * @return array{color?: string, image?: mixed, not_dropdown?: string}
	 */
	public static function has_swatch( int $product_id, string $attr_name, string $value ): array {
		unset( $product_id );

		$swatches     = [];
		$term         = get_term_by( 'slug', $value, $attr_name );
		$color        = '';
		$image        = '';
		$not_dropdown = '';

		if ( is_object( $term ) ) {
			$color        = get_term_meta( $term->term_id, 'color', true );
			$image        = get_term_meta( $term->term_id, 'image', true );
			$not_dropdown = get_term_meta( $term->term_id, 'not_dropdown', true );
		}

		if ( '' !== $color ) {
			$swatches['color'] = $color;
		}

		if ( ( $image && ! is_array( $image ) ) || ( is_array( $image ) && ! empty( $image['id'] ) ) ) {
			$swatches['image'] = $image;
		}

		if ( '' !== $not_dropdown ) {
			$swatches['not_dropdown'] = $not_dropdown;
		}

		return $swatches;
	}

	/**
	 * Map variation attribute terms to variation IDs, stock, and image data.
	 *
	 * Port of `woodmart_get_option_variations()`.
	 *
	 * @param string       $attribute_name       Full taxonomy name.
	 * @param array<mixed> $available_variations WooCommerce variation arrays.
	 * @param string|false $option               Single slug to return, or false for all.
	 * @param int|false    $product_id           Product ID for term meta merge.
	 * @return array<string, mixed>|array<string, string|bool|int>|null
	 */
	public static function get_option_variations( string $attribute_name, array $available_variations, $option = false, $product_id = false ) {
		$swatches_to_show = [];
		$attr_key         = 'attribute_' . $attribute_name;

		foreach ( $available_variations as $variation ) {
			if ( ! isset( $variation['attributes'][ $attr_key ] ) ) {
				return null;
			}

			$val               = $variation['attributes'][ $attr_key ];
			$variation_product = wc_get_product( $variation['variation_id'] );
			$option_variation  = [
				'variation_id' => $variation['variation_id'],
				'is_in_stock'  => $variation['is_in_stock'],
			];

			if ( ! empty( $variation['image']['src'] ) && $variation_product && $variation_product->get_image_id( 'edit' ) ) {
				$option_variation['image_src']    = $variation['image']['src'];
				$option_variation['image_srcset'] = $variation['image']['srcset'];
				$option_variation['image_sizes']  = $variation['image']['sizes'];
			}

			if ( $option ) {
				if ( $val !== $option ) {
					continue;
				}

				return $option_variation;
			}

			$swatch                   = self::has_swatch( (int) $product_id, $attribute_name, $val );
			$swatches_to_show[ $val ] = array_merge( $swatch, $option_variation );
		}

		return $swatches_to_show;
	}

	/**
	 * Build ordered swatch data for a variable product grid card.
	 *
	 * @param \WC_Product $product Product object.
	 * @return array{attribute: string, swatches: array<string, array<string, mixed>>}|null
	 */
	public static function get_grid_swatches_data( \WC_Product $product ): ?array {
		if ( ! $product->is_type( 'variable' ) ) {
			return null;
		}

		$attribute_name = self::get_grid_swatches_attribute( $product->get_id() );

		if ( empty( $attribute_name ) ) {
			return null;
		}

		$available_variations = $product->get_available_variations();

		if ( empty( $available_variations ) ) {
			return null;
		}

		$swatches_to_show = self::get_option_variations(
			$attribute_name,
			$available_variations,
			false,
			$product->get_id()
		);

		if ( empty( $swatches_to_show ) || ! is_array( $swatches_to_show ) ) {
			return null;
		}

		$terms = wc_get_product_terms( $product->get_id(), $attribute_name, [ 'fields' => 'slugs' ] );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		$ordered = [];

		foreach ( $terms as $slug ) {
			if ( ! isset( $swatches_to_show[ $slug ] ) ) {
				continue;
			}

			$ordered[ $slug ] = $swatches_to_show[ $slug ];
		}

		if ( empty( $ordered ) ) {
			return null;
		}

		return [
			'attribute' => $attribute_name,
			'swatches'  => $ordered,
		];
	}

	/**
	 * Render Mode A grid swatches markup for a product card.
	 *
	 * @param \WC_Product $product Product object.
	 * @return string Empty string when nothing to render.
	 */
	public static function render_grid_swatches( \WC_Product $product ): string {
		$data = self::get_grid_swatches_data( $product );

		if ( null === $data ) {
			return '';
		}

		$attribute_name   = $data['attribute'];
		$swatches_to_show = $data['swatches'];
		$total            = count( $swatches_to_show );
		$is_limited       = $total > ( self::SWATCHES_LIMIT + self::SWATCHES_LIMIT_BUFFER );

		$wrapper_classes = [
			'cf-swatches-grid',
			'cf-swatches-product',
			'cf-swatches-attr',
			'cf-swatches--style-3',
			'cf-swatches--dis-style-3',
			'cf-swatches--size-m',
			'cf-swatches--shape-round',
		];

		if ( $is_limited ) {
			$wrapper_classes[] = 'cf-swatches--limited';
		}

		$out   = sprintf(
			'<div class="%s">',
			esc_attr( implode( ' ', $wrapper_classes ) )
		);
		$index = 0;

		foreach ( $swatches_to_show as $slug => $swatch ) {
			$classes = [ 'cf-swatch' ];
			$style   = '';
			$image   = '';
			$data_attrs = '';

			if ( $is_limited ) {
				if ( $index >= self::SWATCHES_LIMIT ) {
					$classes[] = 'cf-swatch--hidden';
				}

				if ( self::SWATCHES_LIMIT === $index ) {
					$hidden_count = $total - self::SWATCHES_LIMIT;
					$out         .= sprintf(
						'<button type="button" class="cf-swatch-divider" aria-label="%s">+%d</button>',
						esc_attr(
							sprintf(
								/* translators: %d: number of hidden swatches */
								__( 'Show %d more swatches', 'chairforce' ),
								$hidden_count
							)
						),
						(int) $hidden_count
					);
				}
			}

			if ( ! empty( $swatch['color'] ) ) {
				$style     = 'background-color:' . $swatch['color'];
				$classes[] = 'cf-swatch--bg';
			} elseif ( ! empty( $swatch['image'] ) ) {
				$image     = self::get_term_swatch_image_html( $swatch['image'] );
				$classes[] = 'cf-swatch--bg';
			} else {
				$classes[] = 'cf-swatch--text';
			}

			if ( isset( $swatch['image_src'] ) ) {
				$data_attrs .= ' data-image-src="' . esc_url( $swatch['image_src'] ) . '"';
				$data_attrs .= ' data-image-srcset="' . esc_attr( $swatch['image_srcset'] ?? '' ) . '"';
				$data_attrs .= ' data-image-sizes="' . esc_attr( $swatch['image_sizes'] ?? '' ) . '"';
			}

			if ( isset( $swatch['is_in_stock'] ) && ! $swatch['is_in_stock'] ) {
				$classes[] = 'cf-swatch--out-of-stock';
			}

			$term      = get_term_by( 'slug', $slug, $attribute_name );
			$term_name = ( $term && ! is_wp_error( $term ) ) ? $term->name : $slug;

			$out .= sprintf(
				'<button type="button" class="%1$s" title="%2$s"%3$s>',
				esc_attr( implode( ' ', $classes ) ),
				esc_attr( $term_name ),
				$data_attrs // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attrs escaped above.
			);

			if ( $style || $image ) {
				$out .= sprintf(
					'<span class="cf-swatch__bg" style="%s">%s</span>',
					esc_attr( $style ),
					$image // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attachment HTML from WP.
				);
			}

			$out .= sprintf(
				'<span class="cf-swatch__text">%s</span>',
				esc_html( $term_name )
			);
			$out .= '</button>';

			++$index;
		}

		$out .= '</div>';

		return $out;
	}

	/**
	 * Read variation gallery attachment IDs from legacy post meta.
	 *
	 * @param int $variation_id Variation post ID.
	 * @return int[]
	 */
	public static function get_variation_gallery_attachment_ids( int $variation_id ): array {
		$value = get_post_meta( $variation_id, self::VARIATION_GALLERY_META_KEY, true );

		if ( ! is_string( $value ) || '' === $value ) {
			return [];
		}

		return array_values(
			array_filter(
				array_map( 'absint', explode( ',', $value ) )
			)
		);
	}

	/**
	 * Whether any option of this attribute row has swatch term meta.
	 *
	 * @param int          $product_id     Product post ID.
	 * @param string       $attribute_name Full taxonomy name.
	 * @param array<mixed> $option_slugs   Term slugs for this product/attribute.
	 */
	public static function attribute_has_swatches( int $product_id, string $attribute_name, array $option_slugs ): bool {
		foreach ( $option_slugs as $slug ) {
			if ( ! empty( self::has_swatch( $product_id, $attribute_name, (string) $slug ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render single-product swatches for one attribute row (prepended before the real `<select>`).
	 *
	 * @param \WC_Product $product        Variable product.
	 * @param string      $attribute_name Full taxonomy name (e.g. `pa_colour`).
	 * @param array<mixed> $args          `wc_dropdown_variation_attribute_options()` args.
	 * @return string Empty when this attribute has no swatch data.
	 */
	public static function render_single_product_swatches( \WC_Product $product, string $attribute_name, array $args ): string {
		$options = $args['options'] ?? [];

		if ( empty( $options ) ) {
			$attributes = $product->get_variation_attributes();
			$options    = $attributes[ $attribute_name ] ?? [];
		}

		if ( empty( $options ) || ! self::attribute_has_swatches( $product->get_id(), $attribute_name, $options ) ) {
			return '';
		}

		$select_id = ! empty( $args['id'] ) ? (string) $args['id'] : sanitize_title( $attribute_name );
		$selected  = $args['selected'] ?? '';

		if ( false === $selected ) {
			$selected = '';
		}

		$wrapper_classes = [
			'cf-swatches-product',
			'cf-swatches-single',
			'cf-swatches-attr',
			'cf-swatches--style-3',
			'cf-swatches--dis-style-3',
			'cf-swatches--size-single',
			'cf-swatches--shape-round',
		];

		$out = sprintf(
			'<div class="%s" data-id="%s">',
			esc_attr( implode( ' ', $wrapper_classes ) ),
			esc_attr( $select_id )
		);

		if ( taxonomy_exists( $attribute_name ) ) {
			$terms = wc_get_product_terms(
				$product->get_id(),
				$attribute_name,
				[ 'fields' => 'all' ]
			);

			foreach ( $terms as $term ) {
				if ( ! in_array( $term->slug, $options, true ) ) {
					continue;
				}

				$out .= self::render_single_product_swatch_button(
					$product->get_id(),
					$attribute_name,
					$term->slug,
					$term->name,
					$selected
				);
			}
		} else {
			foreach ( $options as $option ) {
				$out .= self::render_single_product_swatch_button(
					$product->get_id(),
					$attribute_name,
					(string) $option,
					(string) $option,
					$selected
				);
			}
		}

		$out .= '</div>';

		return $out;
	}

	/**
	 * Build one single-product swatch button.
	 *
	 * Disabled/enabled state is synced client-side after attribute changes.
	 */
	private static function render_single_product_swatch_button(
		int $product_id,
		string $attribute_name,
		string $slug,
		string $label,
		$selected
	): string {
		$swatch  = self::has_swatch( $product_id, $attribute_name, $slug );
		$classes = [ 'cf-swatch' ];
		$style   = '';
		$image   = '';

		if ( ! empty( $swatch['color'] ) ) {
			$style     = 'background-color:' . $swatch['color'];
			$classes[] = 'cf-swatch--bg';
		} elseif ( ! empty( $swatch['image'] ) ) {
			$image     = self::get_term_swatch_image_html( $swatch['image'] );
			$classes[] = 'cf-swatch--bg';
		} else {
			$classes[] = 'cf-swatch--text';
		}

		if ( $selected && sanitize_title( (string) $selected ) === $slug ) {
			$classes[] = 'cf-swatch--active';
		}

		$button = sprintf(
			'<button type="button" class="%1$s" data-value="%2$s" title="%3$s">',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $slug ),
			esc_attr( $label )
		);

		if ( $style || $image ) {
			$button .= sprintf(
				'<span class="cf-swatch__bg" style="%s">%s</span>',
				esc_attr( $style ),
				$image // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		$button .= sprintf(
			'<span class="cf-swatch__text">%s</span>',
			esc_html( $label )
		);
		$button .= '</button>';

		return $button;
	}

	/**
	 * Build deduped image IDs for a variation gallery swap (featured + CSV meta).
	 *
	 * @param \WC_Product_Variation $variation Variation product.
	 * @return int[]
	 */
	public static function get_variation_gallery_image_ids( \WC_Product_Variation $variation ): array {
		$ids         = [];
		$featured_id = (int) $variation->get_image_id( 'edit' );

		if ( $featured_id ) {
			$ids[] = $featured_id;
		}

		foreach ( self::get_variation_gallery_attachment_ids( $variation->get_id() ) as $attachment_id ) {
			if ( ! in_array( $attachment_id, $ids, true ) ) {
				$ids[] = $attachment_id;
			}
		}

		return $ids;
	}

	/**
	 * @param mixed $image_meta Term `image` meta (attachment ID, legacy array, or URL).
	 * @return string Image HTML or empty string.
	 */
	public static function get_term_swatch_image_html( $image_meta ): string {
		if ( is_numeric( $image_meta ) ) {
			$html = wp_get_attachment_image( (int) $image_meta, 'woocommerce_thumbnail' );

			return $html ? $html : '';
		}

		if ( is_array( $image_meta ) && ! empty( $image_meta['id'] ) ) {
			$html = wp_get_attachment_image( (int) $image_meta['id'], 'full' );

			return $html ? $html : '';
		}

		if ( is_string( $image_meta ) && $image_meta ) {
			return sprintf(
				'<img src="%s" alt="">',
				esc_url( $image_meta )
			);
		}

		return '';
	}
}
