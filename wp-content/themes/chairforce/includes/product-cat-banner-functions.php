<?php
/**
 * Product category banner resolution and rendering helpers.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize an ACF banner post_object value to a post ID.
 *
 * @param mixed $value Raw field value.
 * @return int
 */
function chairforce_normalize_banner_post_id( $value ): int {
	if ( is_object( $value ) && isset( $value->ID ) ) {
		return absint( $value->ID );
	}

	if ( is_array( $value ) ) {
		$ids = chairforce_normalize_post_ids( $value );

		return ! empty( $ids ) ? (int) $ids[0] : 0;
	}

	return absint( $value );
}

/**
 * Normalized Banner Configurations rows in repeater order (first row wins).
 *
 * @return array<int, array{term_id: int, banner_id: int, display_on_child_categories: bool}>
 */
function chairforce_get_banner_configuration_rows(): array {
	if ( ! function_exists( 'get_field' ) ) {
		return [];
	}

	$rows = get_field( 'banner_category_rows', 'option' );

	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return [];
	}

	$normalized = [];

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$term_id = isset( $row['product_category'] ) ? absint( $row['product_category'] ) : 0;

		if ( $term_id < 1 ) {
			continue;
		}

		$banner_id = chairforce_normalize_banner_post_id( $row['banner'] ?? 0 );

		if ( $banner_id < 1 ) {
			continue;
		}

		$normalized[] = [
			'term_id'                       => $term_id,
			'banner_id'                     => $banner_id,
			'display_on_child_categories'   => ! empty( $row['display_on_child_categories'] ),
		];
	}

	return $normalized;
}

/**
 * Whether a product_cat term is a strict ancestor of another.
 *
 * @param int $ancestor_id Potential ancestor term ID.
 * @param int $term_id     Descendant term ID.
 * @return bool
 */
function chairforce_is_product_cat_strict_ancestor( int $ancestor_id, int $term_id ): bool {
	$ancestor_id = absint( $ancestor_id );
	$term_id     = absint( $term_id );

	if ( $ancestor_id < 1 || $term_id < 1 || $ancestor_id === $term_id ) {
		return false;
	}

	$current_id = $term_id;

	while ( $current_id = (int) wp_get_term_taxonomy_parent_id( $current_id, 'product_cat' ) ) {
		if ( $current_id === $ancestor_id ) {
			return true;
		}
	}

	return false;
}

/**
 * Resolve a banner post ID for a product_cat term archive.
 *
 * Uses repeater order: first exact match on the queried term, else first inherit
 * row whose category is a strict ancestor with display_on_child_categories enabled.
 *
 * @param int $term_id Product category term ID.
 * @return int Banner post ID, or 0 when unmapped.
 */
function chairforce_resolve_product_cat_banner_id( int $term_id ): int {
	$term_id = absint( $term_id );

	if ( $term_id < 1 ) {
		return 0;
	}

	$rows = chairforce_get_banner_configuration_rows();

	if ( empty( $rows ) ) {
		return 0;
	}

	foreach ( $rows as $row ) {
		if ( (int) $row['term_id'] === $term_id ) {
			return (int) $row['banner_id'];
		}
	}

	foreach ( $rows as $row ) {
		if ( empty( $row['display_on_child_categories'] ) ) {
			continue;
		}

		if ( ! chairforce_is_product_cat_strict_ancestor( (int) $row['term_id'], $term_id ) ) {
			continue;
		}

		return (int) $row['banner_id'];
	}

	return 0;
}

/**
 * Resolve the banner post ID for the queried product_cat archive term.
 *
 * @return int Banner post ID, or 0 when unmapped or not on a product_cat archive.
 */
function chairforce_get_queried_product_cat_banner_id(): int {
	if ( ! function_exists( 'chairforce_get_queried_product_cat_term' ) ) {
		return 0;
	}

	$term = chairforce_get_queried_product_cat_term();

	if ( ! $term ) {
		return 0;
	}

	return chairforce_resolve_product_cat_banner_id( (int) $term->term_id );
}

/**
 * Render a published chairforce_banner post's block content.
 *
 * @param int $banner_id Banner post ID.
 * @return string HTML markup, or empty string when invalid/unpublished.
 */
function chairforce_get_banner_post_markup( int $banner_id ): string {
	$banner_id = absint( $banner_id );

	if ( $banner_id < 1 ) {
		return '';
	}

	$post = get_post( $banner_id );

	if ( ! $post instanceof WP_Post || 'chairforce_banner' !== $post->post_type || 'publish' !== $post->post_status ) {
		return '';
	}

	if ( ! function_exists( 'do_blocks' ) ) {
		return '';
	}

	$content = (string) $post->post_content;

	if ( '' === trim( $content ) ) {
		return '';
	}

	$markup = do_blocks( $content );

	return is_string( $markup ) ? $markup : '';
}
