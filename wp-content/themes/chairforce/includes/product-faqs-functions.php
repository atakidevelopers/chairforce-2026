<?php
/**
 * Product FAQ resolution and accordion markup helpers.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalize an ACF relationship/post ID list to positive integers.
 *
 * @param mixed $value Raw field value.
 * @return int[]
 */
function chairforce_normalize_faq_post_ids( $value ): array {
	if ( ! is_array( $value ) ) {
		return [];
	}

	$ids = [];

	foreach ( $value as $item ) {
		if ( is_object( $item ) && isset( $item->ID ) ) {
			$item = (int) $item->ID;
		}

		$id = absint( $item );

		if ( $id > 0 ) {
			$ids[] = $id;
		}
	}

	return $ids;
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
 * Load FAQ posts for a product in resolved order.
 *
 * @param int $product_id Product post ID.
 * @return WP_Post[]
 */
function chairforce_get_product_faq_posts( int $product_id ): array {
	$faq_ids = chairforce_get_product_faq_ids( $product_id );

	if ( empty( $faq_ids ) ) {
		return [];
	}

	$posts = [];

	foreach ( $faq_ids as $faq_id ) {
		$post = get_post( $faq_id );

		if (
			! $post instanceof WP_Post
			|| 'chairforce_faq' !== $post->post_type
			|| 'publish' !== $post->post_status
		) {
			continue;
		}

		$posts[] = $post;
	}

	return $posts;
}

/**
 * Build accordion markup for resolved FAQ posts.
 *
 * @param WP_Post[] $faqs FAQ posts in display order.
 * @return string
 */
function chairforce_get_product_faq_accordion_html( array $faqs ): string {
	if ( empty( $faqs ) ) {
		return '';
	}

	ob_start();
	?>
	<div class="cf-product-faqs__list" role="presentation">
		<?php
		foreach ( $faqs as $index => $faq ) :
			if ( ! $faq instanceof WP_Post ) {
				continue;
			}

			$is_first = 0 === $index;
			$panel_id = 'cf-product-faq-panel-' . (int) $faq->ID;
			?>
			<div class="cf-product-faqs__item<?php echo $is_first ? ' is-open' : ''; ?>" data-cf-product-faq-item>
				<button
					type="button"
					class="cf-product-faqs__trigger"
					aria-expanded="<?php echo $is_first ? 'true' : 'false'; ?>"
					aria-controls="<?php echo esc_attr( $panel_id ); ?>"
					id="<?php echo esc_attr( 'cf-product-faq-trigger-' . (int) $faq->ID ); ?>"
				>
					<span class="cf-product-faqs__question"><?php echo esc_html( $faq->post_title ); ?></span>
					<span class="cf-product-faqs__icon" aria-hidden="true"></span>
				</button>
				<div
					id="<?php echo esc_attr( $panel_id ); ?>"
					class="cf-product-faqs__panel"
					role="region"
					aria-labelledby="<?php echo esc_attr( 'cf-product-faq-trigger-' . (int) $faq->ID ); ?>"
					<?php echo $is_first ? '' : ' hidden'; ?>
				>
					<div class="cf-product-faqs__answer entry-content">
						<?php echo wp_kses_post( apply_filters( 'the_content', $faq->post_content ) ); ?>
					</div>
				</div>
			</div>
			<?php
		endforeach;
		?>
	</div>
	<?php

	return (string) ob_get_clean();
}

/**
 * Render the product FAQ accordion or empty-state message.
 *
 * @param int $product_id Product post ID.
 * @return string
 */
function chairforce_get_product_faqs_html( int $product_id ): string {
	$faqs = chairforce_get_product_faq_posts( $product_id );

	if ( empty( $faqs ) ) {
		return '<p class="cf-product-faqs__empty">' . esc_html__( 'No FAQs Found', 'chairforce' ) . '</p>';
	}

	return chairforce_get_product_faq_accordion_html( $faqs );
}
