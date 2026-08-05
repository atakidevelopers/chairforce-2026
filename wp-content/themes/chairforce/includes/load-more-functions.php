<?php
/**
 * Load More helpers — query replay and Load More button markup.
 *
 * Shop append uses page-fetch in `src/js/shared/load-more.js` (SSR cards from
 * FSE templates). Card HTML is not rendered here.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Products per page for shop loops (Customizer columns × rows, filterable).
 */
function chairforce_get_loop_shop_per_page(): int {

	if ( function_exists( 'wc_get_default_products_per_row' ) && function_exists( 'wc_get_default_product_rows_per_page' ) ) {
		return (int) apply_filters(
			'loop_shop_per_page',
			wc_get_default_products_per_row() * wc_get_default_product_rows_per_page()
		);
	}

	$columns = max( 1, (int) get_option( 'woocommerce_catalog_columns', 4 ) );
	$rows    = max( 1, (int) get_option( 'woocommerce_catalog_rows', 4 ) );

	return (int) apply_filters( 'loop_shop_per_page', $columns * $rows );
}

/**
 * Query var keys that must not be replayed from the AJAX payload.
 *
 * @return string[]
 */
function chairforce_get_load_more_excluded_query_var_keys(): array {

	return [
		'paged',
		'page',
		'posts_per_page',
		'offset',
		'nopaging',
		'no_found_rows',
		'fields',
		'cache_results',
		'update_post_meta_cache',
		'update_post_term_cache',
		'lazy_load_term_meta',
		'suppress_filters',
		'error',
		'm',
		'p',
		'attachment',
		'attachment_id',
		'subpost',
		'subpost_id',
		'preview',
		'perm',
		'comments_per_page',
		'posts_per_archive_page',
	];
}

/**
 * Export main-query vars from page 1 for the Load More AJAX payload.
 *
 * @return array<string, mixed>
 */
function chairforce_get_load_more_query_vars_for_client( ?\WP_Query $query = null ): array {

	global $wp_query;

	$source_query = $query instanceof \WP_Query ? $query : $wp_query;

	if ( ! $source_query instanceof \WP_Query ) {
		return [];
	}

	if ( null === $query ) {
		if (
			! is_shop()
			&& ! is_product_taxonomy()
			&& ! is_post_type_archive( 'product' )
		) {
			return [];
		}
	}

	$export  = [];
	$exclude = array_flip( chairforce_get_load_more_excluded_query_var_keys() );

	foreach ( $source_query->query_vars as $key => $value ) {
		if ( isset( $exclude[ $key ] ) ) {
			continue;
		}

		if ( null === $value || '' === $value || [] === $value ) {
			continue;
		}

		$export[ $key ] = $value;
	}

	// Shop/category archives often omit post_type in query_vars; REST has no main query to infer it.
	$post_type = $export['post_type'] ?? '';

	if ( is_array( $post_type ) ) {
		$post_type = reset( $post_type );
	}

	if ( ! is_string( $post_type ) || '' === $post_type ) {
		$export['post_type'] = 'product';
	}

	return $export;
}

/**
 * Resolve post type from replayed query vars (defaults to main-query post type).
 *
 * @param array<string, mixed> $query_vars Query vars array.
 * @return string
 */
function chairforce_get_load_more_post_type_from_vars( array $query_vars ): string {

	$post_type = $query_vars['post_type'] ?? '';

	if ( is_array( $post_type ) ) {
		$post_type = reset( $post_type );
	}

	if ( is_string( $post_type ) && '' !== $post_type ) {
		return sanitize_key( $post_type );
	}

	if ( ! empty( $query_vars['wc_query'] ) && 'product_query' === $query_vars['wc_query'] ) {
		return 'product';
	}

	return 'product';
}

/**
 * Sanitize replayed main-query vars from the Load More AJAX payload.
 *
 * @param array<string, mixed> $vars Raw decoded JSON from the client.
 * @return array<string, mixed>
 */
function chairforce_sanitize_load_more_query_vars( array $vars ): array {

	$allowed = [];
	$exclude = array_flip( chairforce_get_load_more_excluded_query_var_keys() );

	foreach ( $vars as $key => $value ) {
		if ( isset( $exclude[ $key ] ) ) {
			continue;
		}

		if ( 'post_type' === $key ) {
			if ( is_array( $value ) ) {
				$allowed['post_type'] = array_values(
					array_filter(
						array_map(
							static function ( $type ) {
								return sanitize_key( (string) $type );
							},
							$value
						)
					)
				);
			} elseif ( is_string( $value ) && '' !== $value ) {
				$allowed['post_type'] = sanitize_key( $value );
			}
			continue;
		}

		if ( 'post_status' === $key ) {
			if ( is_array( $value ) ) {
				$allowed['post_status'] = array_values(
					array_filter(
						array_map(
							static function ( $status ) {
								return sanitize_key( (string) $status );
							},
							$value
						)
					)
				);
			} elseif ( is_string( $value ) && '' !== $value ) {
				$allowed['post_status'] = sanitize_key( $value );
			}
			continue;
		}

		if ( 'tax_query' === $key && is_array( $value ) ) {
			$allowed['tax_query'] = chairforce_sanitize_load_more_tax_query( $value );
			continue;
		}

		if ( 'meta_query' === $key && is_array( $value ) ) {
			$allowed['meta_query'] = chairforce_sanitize_load_more_meta_query( $value );
			continue;
		}

		if ( in_array( $key, [ 'orderby', 'order', 's', 'meta_key', 'wc_query' ], true ) && is_string( $value ) ) {
			if ( 'orderby' === $key ) {
				$allowed[ $key ] = chairforce_sanitize_catalog_orderby( $value );
			} else {
				$allowed[ $key ] = sanitize_text_field( $value );
			}
			continue;
		}

		if ( str_starts_with( $key, 'filter_' ) && is_string( $value ) ) {
			$allowed[ $key ] = sanitize_text_field( $value );
			continue;
		}

		if ( in_array( $key, [ 'min_price', 'max_price' ], true ) && is_scalar( $value ) ) {
			$allowed[ $key ] = sanitize_text_field( (string) $value );
		}
	}

	$post_type = chairforce_get_load_more_post_type_from_vars( $allowed );
	$tax_names = get_object_taxonomies( $post_type, 'names' );

	foreach ( $vars as $key => $value ) {
		if ( isset( $exclude[ $key ] ) || isset( $allowed[ $key ] ) ) {
			continue;
		}

		if ( in_array( $key, $tax_names, true ) ) {
			if ( is_array( $value ) ) {
				$allowed[ $key ] = array_map( 'sanitize_title', array_map( 'strval', $value ) );
			} else {
				$allowed[ $key ] = sanitize_title( (string) $value );
			}
		}
	}

	return chairforce_normalize_load_more_ordering( $allowed );
}

/**
 * Sanitize a catalog orderby value without breaking WC compound keys.
 *
 * WooCommerce defaults to `menu_order title` (space-separated). sanitize_key()
 * strips spaces and would corrupt that to `menu_ordertitle`, breaking pagination.
 *
 * @param string $orderby Raw orderby value.
 * @return string
 */
function chairforce_sanitize_catalog_orderby( string $orderby ): string {

	$orderby = strtolower( trim( sanitize_text_field( $orderby ) ) );

	if ( '' === $orderby ) {
		return '';
	}

	if ( str_contains( $orderby, ' ' ) ) {
		$parts = preg_split( '/\s+/', $orderby ) ?: [];

		return implode(
			' ',
			array_values(
				array_filter(
					array_map(
						static function ( $part ) {
							return sanitize_key( (string) $part );
						},
						$parts
					)
				)
			)
		);
	}

	return sanitize_key( $orderby );
}

/**
 * Split WooCommerce hyphenated catalog orderby values (e.g. price-desc).
 *
 * Matches WC_Query::get_catalog_ordering_args() when reading ?orderby=price-desc.
 *
 * @param string $orderby Raw orderby value.
 * @param string $order   Optional explicit order (ASC|DESC).
 * @return array{orderby: string, order: string} Normalized orderby + order.
 */
function chairforce_parse_catalog_orderby( string $orderby, string $order = '' ): array {

	$orderby = strtolower( trim( sanitize_text_field( $orderby ) ) );
	$order   = strtoupper( trim( sanitize_text_field( $order ) ) );

	if ( str_contains( $orderby, '-' ) ) {
		$parts   = explode( '-', $orderby );
		$orderby = (string) ( $parts[0] ?? $orderby );

		if ( '' === $order && ! empty( $parts[1] ) ) {
			$order = strtoupper( (string) $parts[1] );
		}
	}

	$orderby = chairforce_sanitize_catalog_orderby( $orderby );

	if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
		$order = '';
	}

	return [
		'orderby' => $orderby,
		'order'   => $order,
	];
}

/**
 * Normalize orderby/order inside replayed Load More query vars.
 *
 * @param array<string, mixed> $query_vars Query vars array.
 * @return array<string, mixed>
 */
function chairforce_normalize_load_more_ordering( array $query_vars ): array {

	if ( empty( $query_vars['orderby'] ) || ! is_string( $query_vars['orderby'] ) ) {
		return $query_vars;
	}

	$parsed = chairforce_parse_catalog_orderby(
		$query_vars['orderby'],
		isset( $query_vars['order'] ) && is_string( $query_vars['order'] ) ? $query_vars['order'] : ''
	);

	$query_vars['orderby'] = $parsed['orderby'];

	if ( '' !== $parsed['order'] ) {
		$query_vars['order'] = $parsed['order'];
	}

	return $query_vars;
}

/**
 * @param array<int, mixed> $tax_query Raw tax_query.
 * @return array<int, mixed>
 */
function chairforce_sanitize_load_more_tax_query( array $tax_query ): array {

	$clean = [];

	foreach ( $tax_query as $clause ) {
		if ( ! is_array( $clause ) ) {
			continue;
		}

		if ( isset( $clause['relation'] ) && in_array( $clause['relation'], [ 'AND', 'OR' ], true ) ) {
			$clean[] = [ 'relation' => $clause['relation'] ];
			continue;
		}

		if ( empty( $clause['taxonomy'] ) || ! taxonomy_exists( (string) $clause['taxonomy'] ) ) {
			continue;
		}

		$field = isset( $clause['field'] ) ? sanitize_key( (string) $clause['field'] ) : 'term_id';
		$terms = $clause['terms'] ?? [];

		if ( ! is_array( $terms ) ) {
			$terms = [ $terms ];
		}

		if ( in_array( $field, [ 'slug', 'name' ], true ) ) {
			$terms = array_values(
				array_filter(
					array_map(
						static function ( $term ) use ( $field ) {
							return 'name' === $field
								? sanitize_text_field( (string) $term )
								: sanitize_title( (string) $term );
						},
						$terms
					)
				)
			);
		} else {
			$terms = array_values( array_filter( array_map( 'absint', $terms ) ) );
		}

		if ( empty( $terms ) ) {
			continue;
		}

		$operator = isset( $clause['operator'] )
			? strtoupper( trim( sanitize_text_field( (string) $clause['operator'] ) ) )
			: 'IN';

		if ( ! in_array( $operator, [ 'IN', 'NOT IN', 'AND', 'EXISTS', 'NOT EXISTS' ], true ) ) {
			$operator = 'IN';
		}

		$sanitized = [
			'taxonomy' => sanitize_key( $clause['taxonomy'] ),
			'field'    => $field,
			'terms'    => $terms,
			'operator' => $operator,
		];

		if ( isset( $clause['include_children'] ) ) {
			$sanitized['include_children'] = (bool) $clause['include_children'];
		}

		$clean[] = $sanitized;
	}

	return $clean;
}

/**
 * @param array<int, mixed> $meta_query Raw meta_query.
 * @return array<int, mixed>
 */
function chairforce_sanitize_load_more_meta_query( array $meta_query ): array {

	$clean = [];

	foreach ( $meta_query as $clause ) {
		if ( ! is_array( $clause ) ) {
			continue;
		}

		if ( isset( $clause['relation'] ) && in_array( $clause['relation'], [ 'AND', 'OR' ], true ) ) {
			$clean[] = [ 'relation' => $clause['relation'] ];
			continue;
		}

		if ( empty( $clause['key'] ) ) {
			continue;
		}

		$clean[] = [
			'key'     => sanitize_key( (string) $clause['key'] ),
			'value'   => isset( $clause['value'] ) ? sanitize_text_field( (string) $clause['value'] ) : '',
			'compare' => isset( $clause['compare'] ) ? sanitize_key( (string) $clause['compare'] ) : '=',
		];
	}

	return $clean;
}

/**
 * Strip replayed filter artifacts before rebuilding layered-nav tax/meta queries.
 *
 * @param array<string, mixed>  $client_vars   Sanitized query vars from the client.
 * @param array<string, string> $filter_params Active catalog filter params.
 * @return array<string, mixed>
 */
function chairforce_prepare_load_more_client_vars( array $client_vars, array $filter_params ): array {

	foreach ( array_keys( $client_vars ) as $key ) {
		if ( str_starts_with( $key, 'filter_' ) || in_array( $key, [ 'min_price', 'max_price' ], true ) ) {
			unset( $client_vars[ $key ] );
		}
	}

	unset( $client_vars['taxonomy'], $client_vars['term'] );

	if ( ! empty( $filter_params ) ) {
		unset( $client_vars['tax_query'], $client_vars['meta_query'] );
	}

	return $client_vars;
}

/**
 * Build WP_Query args for a Load More request.
 *
 * Pagination is always derived server-side: calculated perPage + offset for the
 * requested page. Client payload must not replay posts_per_page, paged, or offset.
 *
 * @param array<string, mixed> $client_vars Sanitized query vars from AJAX.
 * @param int                  $page        Target page number (≥ 2).
 * @return array<string, mixed>
 */
function chairforce_build_load_more_query_args( array $client_vars, int $page, array $filter_params = [] ): array {

	$per_page = chairforce_get_loop_shop_per_page();
	$page     = max( 1, $page );

	$client_vars = chairforce_prepare_load_more_client_vars( $client_vars, $filter_params );

	foreach ( chairforce_get_load_more_excluded_query_var_keys() as $excluded_key ) {
		unset( $client_vars[ $excluded_key ] );
	}

	$query_args = wp_parse_args(
		$client_vars,
		[
			'post_status' => 'publish',
		]
	);

	if ( empty( $query_args['post_type'] ) ) {
		$query_args['post_type'] = chairforce_get_load_more_post_type_from_vars( $client_vars );
	}

	$query_args['post_status']    = $query_args['post_status'] ?? 'publish';
	$query_args['posts_per_page'] = $per_page;
	$query_args['offset']         = ( $page - 1 ) * $per_page;

	unset( $query_args['paged'], $query_args['page'] );

	$query_args = chairforce_apply_load_more_catalog_ordering( $query_args );
	$query_args = chairforce_apply_catalog_filter_params_to_query( $query_args, $filter_params );

	return $query_args;
}

/**
 * Apply WooCommerce catalog ordering to Load More query args.
 *
 * Plain `orderby => price` (and popularity/rating) is not enough — WC registers
 * `posts_clauses` filters via WC_Query::get_catalog_ordering_args().
 *
 * @param array<string, mixed> $query_args WP_Query args.
 * @return array<string, mixed>
 */
function chairforce_apply_load_more_catalog_ordering( array $query_args ): array {

	$post_types = (array) ( $query_args['post_type'] ?? 'product' );

	if (
		! in_array( 'product', $post_types, true )
		&& ! in_array( 'product_variation', $post_types, true )
	) {
		return $query_args;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->query instanceof \WC_Query ) {
		return $query_args;
	}

	$orderby = isset( $query_args['orderby'] ) ? (string) $query_args['orderby'] : '';
	$order   = isset( $query_args['order'] ) ? (string) $query_args['order'] : '';

	$parsed   = chairforce_parse_catalog_orderby( $orderby, $order );
	$ordering = WC()->query->get_catalog_ordering_args( $parsed['orderby'], $parsed['order'] );

	$query_args = array_merge( $query_args, $ordering );
	$query_args['wc_query'] = 'product_query';

	return $query_args;
}

/**
 * Max pages for Load More using calculated loop_shop_per_page.
 *
 * @param int      $found_posts Total matching products.
 * @param int|null $per_page    Optional override; defaults to loop_shop_per_page.
 * @return int
 */
function chairforce_get_load_more_max_pages( int $found_posts, ?int $per_page = null ): int {

	$per_page = $per_page ?? chairforce_get_loop_shop_per_page();

	if ( $per_page <= 0 || $found_posts <= 0 ) {
		return 0;
	}

	return (int) ceil( $found_posts / $per_page );
}

/**
 * Render Load More block markup for a catalog query (filter replace refresh).
 *
 * @param \WP_Query              $query      Executed product query.
 * @param array<string, mixed>   $query_vars Query vars payload for the button.
 * @return string Empty when pagination is not needed.
 */
function chairforce_render_load_more_html_for_query( \WP_Query $query, array $query_vars ): string {

	$per_page  = chairforce_get_loop_shop_per_page();
	$total     = (int) $query->found_posts;
	$max_pages = chairforce_get_load_more_max_pages( $total, $per_page );

	if ( $max_pages <= 1 ) {
		return '';
	}

	$viewing = min( $per_page, $total );

	$load_more_text = __( 'Load More', 'chairforce' );
	$loading_text   = __( 'Loading…', 'chairforce' );

	$wrapper_attributes = get_block_wrapper_attributes(
		[
			'class'      => 'cf-load-more wp-block-query-pagination',
			'data-total' => (string) $total,
		]
	);

	$progress_percent = $total > 0 ? round( ( $viewing / $total ) * 100, 2 ) : 0;

	$status_text = sprintf(
		/* translators: 1: number of products currently visible, 2: total products in the query */
		__( 'Viewing %1$s of %2$s', 'chairforce' ),
		number_format_i18n( $viewing ),
		number_format_i18n( $total )
	);

	$button_markup = chairforce_get_buttons_markup(
		[
			[
				'label'           => $load_more_text,
				'style'           => 'is-style-ghost',
				'element_class'   => 'cf-load-more__button',
				'tag'             => 'button',
				'html_attributes' => [
					'data-next-page'    => '2',
					'data-max-pages'    => (string) $max_pages,
					'data-per-page'     => (string) $per_page,
					'data-query-vars'   => wp_json_encode( $query_vars ),
					'data-loading-text' => $loading_text,
					'aria-busy'         => 'false',
				],
			],
		],
		[
			'layout' => [
				'type'           => 'flex',
				'justifyContent' => 'center',
			],
		]
	);

	return sprintf(
		'<div %1$s>
			<div class="cf-load-more__progress" role="progressbar" aria-valuemin="0" aria-valuemax="%2$d" aria-valuenow="%3$d" aria-label="%7$s">
				<span class="cf-load-more__progress-bar" style="width:%4$s%%"></span>
			</div>
			<p class="cf-load-more__status">%5$s</p>
			%6$s
		</div>',
		$wrapper_attributes,
		esc_attr( (string) $total ),
		esc_attr( (string) $viewing ),
		esc_attr( (string) $progress_percent ),
		esc_html( $status_text ),
		$button_markup,
		esc_attr(
			sprintf(
				/* translators: 1: number of products currently visible, 2: total products in the query */
				__( 'Viewing %1$s of %2$s products', 'chairforce' ),
				number_format_i18n( $viewing ),
				number_format_i18n( $total )
			)
		)
	);
}
