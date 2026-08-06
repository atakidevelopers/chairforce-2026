<?php
/**
 * Product category term helpers for the shared category swiper.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve WooCommerce product_cat thumbnail attachment ID.
 *
 * @param int $term_id Product category term ID.
 * @return int Attachment ID, or 0 when unset.
 */
function chairforce_get_product_cat_term_thumbnail_id( int $term_id ): int {
	if ( $term_id <= 0 ) {
		return 0;
	}

	$attachment_id = absint( get_term_meta( $term_id, 'thumbnail_id', true ) );

	return $attachment_id > 0 ? $attachment_id : 0;
}

/**
 * Build swiper item rows from WP_Term objects.
 *
 * @param WP_Term[] $terms Terms in display order.
 * @return array<int, array{id: int, title: string, label: string, url: string, image_id: int}>
 */
function chairforce_get_category_swiper_items_from_terms( array $terms ): array {
	$items = [];

	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$term_id = (int) $term->term_id;
		$link    = get_term_link( $term );

		if ( is_wp_error( $link ) ) {
			continue;
		}

		$items[] = [
			'id'       => $term_id,
			'title'    => $term->name,
			'label'    => $term->name,
			'url'      => (string) $link,
			'image_id' => chairforce_get_product_cat_term_thumbnail_id( $term_id ),
		];
	}

	return $items;
}

/**
 * Build swiper item rows from term IDs, preserving input order.
 *
 * @param int[] $term_ids Term IDs in display order.
 * @return array<int, array{id: int, title: string, label: string, url: string, image_id: int}>
 */
function chairforce_get_category_swiper_items_from_term_ids( array $term_ids ): array {
	$terms = [];

	foreach ( $term_ids as $term_id ) {
		$term = get_term( absint( $term_id ), 'product_cat' );

		if ( $term instanceof WP_Term && ! is_wp_error( $term ) ) {
			$terms[] = $term;
		}
	}

	return chairforce_get_category_swiper_items_from_terms( $terms );
}

/**
 * Allowed order values for {@see chairforce_sort_product_cat_swiper_term_ids()}.
 *
 * @return string[]
 */
function chairforce_get_product_cat_swiper_term_orderby_options(): array {
	return [
		'manual',
		'menu_order',
		'name',
		'slug',
		'term_id',
		'count',
		'description',
		'term_group',
	];
}

/**
 * Normalize block order attribute for WP_Term_Query.
 *
 * @param string $order Block order setting (`asc` or `desc`).
 * @return string `ASC` or `DESC`.
 */
function chairforce_normalize_product_cat_swiper_term_order( string $order ): string {
	return 'desc' === strtolower( $order ) ? 'DESC' : 'ASC';
}

/**
 * Sort selected product_cat term IDs for the manual category swiper block.
 *
 * @param int[]  $term_ids Term IDs in editor selection order.
 * @param string $order_by Block order setting. `manual` preserves selection order.
 * @param string $order    Sort direction (`asc` or `desc`). Ignored when `$order_by` is `manual`.
 * @return int[]
 */
function chairforce_sort_product_cat_swiper_term_ids( array $term_ids, string $order_by = 'manual', string $order = 'asc' ): array {
	$term_ids = array_values(
		array_filter(
			array_map( 'absint', $term_ids ),
			static function ( int $term_id ): bool {
				return $term_id > 0;
			}
		)
	);

	if ( empty( $term_ids ) || 'manual' === $order_by ) {
		return $term_ids;
	}

	if ( ! in_array( $order_by, chairforce_get_product_cat_swiper_term_orderby_options(), true ) ) {
		return $term_ids;
	}

	$terms = get_terms(
		[
			'taxonomy'   => 'product_cat',
			'include'    => $term_ids,
			'hide_empty' => false,
			'orderby'    => $order_by,
			'order'      => chairforce_normalize_product_cat_swiper_term_order( $order ),
		]
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return $term_ids;
	}

	$sorted_ids = array_map(
		static function ( WP_Term $term ): int {
			return (int) $term->term_id;
		},
		$terms
	);

	// Keep any unmatched IDs (edge case) in their original selection order.
	foreach ( $term_ids as $term_id ) {
		if ( ! in_array( $term_id, $sorted_ids, true ) ) {
			$sorted_ids[] = $term_id;
		}
	}

	return $sorted_ids;
}

/**
 * Get immediate child product_cat terms for a parent term.
 *
 * @param int $parent_term_id Parent term ID.
 * @return WP_Term[]
 */
function chairforce_get_product_cat_child_terms( int $parent_term_id ): array {
	if ( $parent_term_id <= 0 ) {
		return [];
	}

	$terms = get_terms(
		[
			'taxonomy'   => 'product_cat',
			'parent'     => $parent_term_id,
			'hide_empty' => false,
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		]
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return [];
	}

	return $terms;
}

/**
 * Resolve the queried product_cat term on taxonomy archives.
 *
 * @return WP_Term|null
 */
function chairforce_get_queried_product_cat_term(): ?WP_Term {
	if ( ! is_tax( 'product_cat' ) ) {
		return null;
	}

	$term = get_queried_object();

	if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) {
		return null;
	}

	return $term;
}

/**
 * Build swiper items for immediate children of the queried product_cat archive term.
 *
 * @return array<int, array{id: int, title: string, label: string, url: string, image_id: int}>
 */
function chairforce_get_queried_product_cat_child_swiper_items(): array {
	$term = chairforce_get_queried_product_cat_term();

	if ( ! $term ) {
		return [];
	}

	return chairforce_get_category_swiper_items_from_terms(
		chairforce_get_product_cat_child_terms( (int) $term->term_id )
	);
}
