<?php
/**
 * Product FAQ resolution helpers.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize an ACF FAQ relationship value to post IDs.
 *
 * @param mixed $value Raw field value.
 * @return int[]
 */
function chairforce_normalize_faq_post_ids( $value ): array {
	return chairforce_normalize_post_ids( $value );
}

/**
 * Merge post ID lists preserving order and removing duplicates.
 *
 * @param int[] ...$lists ID lists in priority order.
 * @return int[]
 */
function chairforce_merge_unique_post_ids( array ...$lists ): array {
	$merged = [];
	$seen   = [];

	foreach ( $lists as $list ) {
		if ( ! is_array( $list ) ) {
			continue;
		}

		foreach ( $list as $id ) {
			$id = absint( $id );

			if ( $id < 1 || isset( $seen[ $id ] ) ) {
				continue;
			}

			$seen[ $id ] = true;
			$merged[]    = $id;
		}
	}

	return $merged;
}

/**
 * Product category term IDs for a product, child terms before ancestors.
 *
 * Each assigned branch walks child → parent; duplicates across branches are skipped.
 *
 * @param int $product_id Product post ID.
 * @return int[]
 */
function chairforce_get_product_cat_term_ids_child_to_parent( int $product_id ): array {
	$terms = wp_get_post_terms(
		$product_id,
		'product_cat',
		[
			'fields' => 'all',
		]
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return [];
	}

	usort(
		$terms,
		static function ( WP_Term $a, WP_Term $b ): int {
			$depth_a = count( get_ancestors( (int) $a->term_id, 'product_cat', 'taxonomy' ) );
			$depth_b = count( get_ancestors( (int) $b->term_id, 'product_cat', 'taxonomy' ) );

			if ( $depth_a === $depth_b ) {
				return strcasecmp( $a->name, $b->name );
			}

			return $depth_b <=> $depth_a;
		}
	);

	$ordered  = [];
	$seen_ids = [];

	foreach ( $terms as $term ) {
		$current_id = (int) $term->term_id;

		while ( $current_id > 0 ) {
			if ( ! isset( $seen_ids[ $current_id ] ) ) {
				$seen_ids[ $current_id ] = true;
				$ordered[]               = $current_id;
			}

			$parent = get_term( $current_id, 'product_cat' );

			if ( ! $parent instanceof WP_Term || (int) $parent->parent < 1 ) {
				break;
			}

			$current_id = (int) $parent->parent;
		}
	}

	return $ordered;
}

/**
 * Category FAQ IDs from FAQ Configurations for a product.
 *
 * @param int $product_id Product post ID.
 * @return int[]
 */
function chairforce_get_product_category_faq_ids( int $product_id ): array {
	if ( ! function_exists( 'get_field' ) ) {
		return [];
	}

	$rows = get_field( 'faq_category_rows', 'option' );

	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return [];
	}

	$rows_by_term = [];

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$term_id = isset( $row['product_category'] ) ? absint( $row['product_category'] ) : 0;

		if ( $term_id < 1 ) {
			continue;
		}

		$rows_by_term[ $term_id ] = chairforce_normalize_faq_post_ids( $row['faqs'] ?? [] );
	}

	$term_ids = chairforce_get_product_cat_term_ids_child_to_parent( $product_id );
	$faq_ids  = [];

	foreach ( $term_ids as $term_id ) {
		if ( empty( $rows_by_term[ $term_id ] ) ) {
			continue;
		}

		$faq_ids = array_merge( $faq_ids, $rows_by_term[ $term_id ] );
	}

	return $faq_ids;
}

/**
 * Global FAQ IDs from FAQ Configurations.
 *
 * @return int[]
 */
function chairforce_get_global_faq_ids(): array {
	if ( ! function_exists( 'get_field' ) ) {
		return [];
	}

	return chairforce_normalize_faq_post_ids( get_field( 'faq_global', 'option' ) );
}

/**
 * Product-specific FAQ IDs from the product edit screen.
 *
 * @param int $product_id Product post ID.
 * @return int[]
 */
function chairforce_get_product_specific_faq_ids( int $product_id ): array {
	if ( ! function_exists( 'get_field' ) ) {
		return [];
	}

	return chairforce_normalize_faq_post_ids( get_field( 'product_faqs', $product_id ) );
}

/**
 * Resolve merged FAQ post IDs for a product (deduplicated).
 *
 * Order: product-specific → category (child→parent) → global.
 *
 * @param int $product_id Product post ID.
 * @return int[]
 */
function chairforce_get_product_faq_ids( int $product_id ): array {
	$product_ids  = chairforce_get_product_specific_faq_ids( $product_id );
	$category_ids = chairforce_get_product_category_faq_ids( $product_id );
	$global_ids   = chairforce_get_global_faq_ids();

	return chairforce_merge_unique_post_ids( $product_ids, $category_ids, $global_ids );
}

/**
 * Render product FAQ accordion markup via the shared accordion component.
 *
 * @param int   $product_id Product post ID.
 * @param array{
 *     initial_visible_count?: int,
 * } $args Accordion options.
 * @return string
 */
function chairforce_get_product_faqs_html( int $product_id, array $args = [] ): string {
	return chairforce_get_accordion_html_from_post_ids(
		chairforce_get_product_faq_ids( $product_id ),
		array_merge(
			[
				'post_type'     => 'chairforce_faq',
				'empty_message' => __( 'No FAQs Found', 'chairforce' ),
				'instance_id'   => 'cf-product-faqs-' . $product_id,
			],
			$args
		)
	);
}

/**
 * Product IDs queued for FAQPage JSON-LD in wp_footer.
 *
 * @return int[]
 */
function &chairforce_faqpage_schema_get_queue(): array {
	static $queue = [];

	return $queue;
}

/**
 * Queue FAQPage schema for a product (printed in wp_footer when the block renders).
 *
 * @param int $product_id Product post ID.
 */
function chairforce_queue_faqpage_schema( int $product_id ): void {
	$product_id = absint( $product_id );

	if ( $product_id < 1 ) {
		return;
	}

	$queue = &chairforce_faqpage_schema_get_queue();

	if ( in_array( $product_id, $queue, true ) ) {
		return;
	}

	$queue[] = $product_id;
}

/**
 * Build FAQPage schema for a product's resolved FAQs.
 *
 * @param int $product_id Product post ID.
 * @return array<string, mixed>|null
 */
function chairforce_get_product_faqpage_schema( int $product_id ): ?array {
	$items = chairforce_get_accordion_items_from_post_ids(
		chairforce_get_product_faq_ids( $product_id ),
		'chairforce_faq'
	);

	if ( empty( $items ) ) {
		return null;
	}

	$main_entity = [];

	foreach ( $items as $item ) {
		$question = trim( (string) ( $item['title'] ?? '' ) );
		$answer   = trim( wp_strip_all_tags( (string) ( $item['content'] ?? '' ) ) );

		if ( '' === $question || '' === $answer ) {
			continue;
		}

		$main_entity[] = [
			'@type'          => 'Question',
			'name'           => $question,
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text'  => $answer,
			],
		];
	}

	if ( empty( $main_entity ) ) {
		return null;
	}

	return [
		'@context'   => 'https://schema.org',
		'@type'      => 'FAQPage',
		'mainEntity' => $main_entity,
	];
}

/**
 * Print queued FAQPage JSON-LD scripts in the footer.
 */
function chairforce_print_faqpage_schema_json_ld(): void {
	$queue = chairforce_faqpage_schema_get_queue();

	if ( empty( $queue ) ) {
		return;
	}

	foreach ( $queue as $product_id ) {
		$schema = chairforce_get_product_faqpage_schema( (int) $product_id );

		if ( null === $schema ) {
			continue;
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		if ( ! $json ) {
			continue;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			$json // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded.
		);
	}
}

add_action( 'wp_footer', 'chairforce_print_faqpage_schema_json_ld', 20 );
