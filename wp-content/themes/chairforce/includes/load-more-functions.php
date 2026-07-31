<?php
/**
 * Load More helpers — query replay + product-card template part render.
 *
 * Page-1 Load More only.
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
function chairforce_get_load_more_query_vars_for_client(): array {

	if (
		! is_shop()
		&& ! is_product_taxonomy()
		&& ! is_post_type_archive( 'product' )
	) {
		return [];
	}

	global $wp_query;

	if ( ! $wp_query instanceof \WP_Query ) {
		return [];
	}

	$export  = [];
	$exclude = array_flip( chairforce_get_load_more_excluded_query_var_keys() );

	foreach ( $wp_query->query_vars as $key => $value ) {
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
			$allowed[ $key ] = sanitize_text_field( $value );
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

	$orderby = sanitize_key( $orderby );

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

		$sanitized = [
			'taxonomy' => sanitize_key( $clause['taxonomy'] ),
			'field'    => $field,
			'terms'    => $terms,
			'operator' => isset( $clause['operator'] ) ? sanitize_key( (string) $clause['operator'] ) : 'IN',
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
 * Build WP_Query args for a Load More request.
 *
 * Pagination is always derived server-side: calculated perPage + offset for the
 * requested page. Client payload must not replay posts_per_page, paged, or offset.
 *
 * @param array<string, mixed> $client_vars Sanitized query vars from AJAX.
 * @param int                  $page        Target page number (≥ 2).
 * @return array<string, mixed>
 */
function chairforce_build_load_more_query_args( array $client_vars, int $page ): array {

	$per_page = chairforce_get_loop_shop_per_page();
	$page     = max( 2, $page );

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
 * Parsed product-card blocks from the shared template part (Option A).
 *
 * Root blocks from `parts/product-card.html` are wrapped in a synthetic
 * `core/null` parent so WP_Block renders the same markup as the archive grid.
 *
 * @return array<string, mixed>
 */
function chairforce_get_product_card_template_parsed_block(): array {

	static $parsed_block = null;

	if ( null !== $parsed_block ) {
		return $parsed_block;
	}

	$template_path = get_theme_file_path( 'parts/product-card.html' );

	if ( ! is_readable( $template_path ) ) {
		return [
			'blockName'    => 'core/null',
			'attrs'        => [],
			'innerBlocks'  => [],
			'innerContent' => [],
		];
	}

	$inner_blocks = parse_blocks( (string) file_get_contents( $template_path ) );
	$inner_blocks = array_values(
		array_filter(
			$inner_blocks,
			static function ( $block ) {
				return ! empty( $block['blockName'] );
			}
		)
	);

	$parsed_block = [
		'blockName'    => 'core/null',
		'attrs'        => [],
		'innerBlocks'  => $inner_blocks,
		'innerContent' => array_fill( 0, count( $inner_blocks ), null ),
	];

	return $parsed_block;
}

/**
 * Render one product card as `<li class="wc-block-product">` via product-card blocks.
 *
 * `core/post-title` reads global `$post` during its render callback — not just
 * block context `postId`. Always set/restore global post around WP_Block render.
 *
 * @param int                  $post_id Post ID.
 * @param array<string, mixed> $context Block context (query, displayLayout, queryId).
 * @return string
 */
function chairforce_render_product_template_item( int $post_id, array $context ): string {

	$loop_post = get_post( $post_id );

	if ( ! $loop_post instanceof \WP_Post ) {
		return '';
	}

	global $post;

	$previous_post = ( $post instanceof \WP_Post ) ? $post : null;
	$post          = $loop_post;

	$block_instance    = chairforce_get_product_card_template_parsed_block();
	$available_context = array_merge(
		$context,
		[
			'postType' => $loop_post->post_type,
			'postId'   => $post_id,
		]
	);

	$block_content = (
		new \WP_Block(
			$block_instance,
			$available_context
		)
	)->render( [ 'dynamic' => false ] );

	if ( $previous_post instanceof \WP_Post ) {
		$post = $previous_post;
	} else {
		unset( $post );
	}

	$li_directives = '';

	if ( function_exists( 'wc_interactivity_api_load_product' ) && function_exists( 'wp_interactivity_data_wp_context' ) ) {
		wc_interactivity_api_load_product(
			'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce',
			$post_id
		);

		$li_directives = wp_interactivity_data_wp_context(
			[
				'productId'   => $post_id,
				'variationId' => null,
			],
			'woocommerce/products'
		);
	}

	$post_classes = implode( ' ', get_post_class( 'wc-block-product', $post_id ) );

	return sprintf(
		'<li class="%1$s" data-wp-interactive="woocommerce/product-collection"%2$s data-wp-key="product-item-%3$d">%4$s</li>',
		esc_attr( $post_classes ),
		$li_directives ? ' ' . $li_directives : '',
		$post_id,
		$block_content
	);
}

/**
 * Render HTML for a batch of products from a query.
 *
 * @param \WP_Query            $query   Product query (already executed).
 * @param array<string, mixed> $context Block context for inner blocks.
 * @return string Concatenated `<li>` markup.
 */
function chairforce_render_product_template_items( \WP_Query $query, array $context ): string {

	$html = '';

	foreach ( $query->posts as $loop_post ) {
		if ( ! $loop_post instanceof \WP_Post ) {
			continue;
		}

		$html .= chairforce_render_product_template_item( (int) $loop_post->ID, $context );
	}

	wp_reset_postdata();

	return $html;
}

/**
 * Block context for load-more card renders.
 *
 * @param string|null $post_type Optional post type from the replayed query.
 * @return array<string, mixed>
 */
function chairforce_get_load_more_block_context( ?string $post_type = null ): array {

	$columns  = max( 1, (int) get_option( 'woocommerce_catalog_columns', 4 ) );
	$per_page = chairforce_get_loop_shop_per_page();
	$post_type = $post_type ? sanitize_key( $post_type ) : 'product';

	return [
		'queryId'       => 0,
		'query'         => [
			'perPage'                  => $per_page,
			'postType'                 => $post_type,
			'isProductCollectionBlock' => true,
		],
		'displayLayout' => [
			'type'    => 'flex',
			'columns' => $columns,
		],
	];
}
