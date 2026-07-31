<?php
/**
 * Product archive layered-nav filter helpers (Phase 3f).
 *
 * @package Chairforce
 * @see context/plans/3f-product-filters-plan.md
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Valid panel orientation values.
 *
 * @return string[]
 */
function chairforce_get_filters_panel_orientation_choices(): array {
	return [ 'vertical', 'horizontal' ];
}

/**
 * Filters panel orientation for desktop archives.
 *
 * @return string `vertical`|`horizontal`
 */
function chairforce_get_filters_panel_desktop(): string {
	$value = function_exists( 'get_field' ) ? get_field( 'filters_panel_desktop', 'option' ) : 'vertical';

	return in_array( $value, chairforce_get_filters_panel_orientation_choices(), true ) ? $value : 'vertical';
}

/**
 * Filters panel orientation for mobile archives.
 *
 * @return string `vertical`|`horizontal`
 */
function chairforce_get_filters_panel_mobile(): string {
	$value = function_exists( 'get_field' ) ? get_field( 'filters_panel_mobile', 'option' ) : 'vertical';

	return in_array( $value, chairforce_get_filters_panel_orientation_choices(), true ) ? $value : 'vertical';
}

/**
 * Whether the current request is a WooCommerce product archive that supports layered filters.
 */
function chairforce_is_product_filter_archive(): bool {
	if (
		function_exists( 'is_shop' )
		&& ( is_shop() || is_product_taxonomy() || is_post_type_archive( 'product' ) )
	) {
		return true;
	}

	global $wp_query;

	if ( $wp_query instanceof \WP_Query ) {
		$post_types = (array) ( $wp_query->get( 'post_type' ) ?: 'product' );

		if ( in_array( 'product', $post_types, true ) && 'product_query' === $wp_query->get( 'wc_query' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Layered-nav query type for an attribute.
 *
 * Must match `WC_Query::get_layered_nav_chosen_attributes()` — WooCommerce and
 * Woodmart default to `and`, which keeps the active attribute in facet counts so
 * only terms on products in the current filtered set remain visible.
 *
 * @param string $attribute_taxonomy Full taxonomy name e.g. `pa_colour`.
 * @return string `and`|`or`
 */
function chairforce_get_layered_nav_query_type( string $attribute_taxonomy ): string {

	$attribute_slug = wc_attribute_taxonomy_slug( $attribute_taxonomy );

	if ( class_exists( 'WC_Query' ) ) {
		$chosen = \WC_Query::get_layered_nav_chosen_attributes();

		if ( isset( $chosen[ $attribute_taxonomy ]['query_type'] ) ) {
			$query_type = (string) $chosen[ $attribute_taxonomy ]['query_type'];

			if ( in_array( $query_type, [ 'and', 'or' ], true ) ) {
				return $query_type;
			}
		}
	}

	if ( isset( $_GET[ 'query_type_' . $attribute_slug ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$query_type = wc_clean( wp_unslash( (string) $_GET[ 'query_type_' . $attribute_slug ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( in_array( $query_type, [ 'and', 'or' ], true ) ) {
			return $query_type;
		}
	}

	/**
	 * Same default as `WC_Query::get_layered_nav_chosen_attributes()` and Woodmart
	 * layered-nav widgets (`query_type` std = `and`).
	 */
	return (string) apply_filters( 'woocommerce_layered_nav_default_query_type', 'and' );
}

/**
 * WC Filterer service when available.
 *
 * @return object|null
 */
function chairforce_get_wc_product_filterer() {
	if ( ! function_exists( 'wc_get_container' ) ) {
		return null;
	}

	if ( ! class_exists( '\Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer' ) ) {
		return null;
	}

	try {
		return wc_get_container()->get( \Automattic\WooCommerce\Internal\ProductAttributesLookup\Filterer::class );
	} catch ( \Throwable $e ) {
		return null;
	}
}

/**
 * Facet counts for terms in the current main-query archive context.
 *
 * @param int[]  $term_ids Term IDs.
 * @param string $taxonomy Attribute taxonomy e.g. `pa_colour`.
 * @return array<int, int> term_id => count
 */
function chairforce_get_filtered_term_product_counts( array $term_ids, string $taxonomy ): array {
	$term_ids = array_values( array_filter( array_map( 'absint', $term_ids ) ) );

	if ( empty( $term_ids ) || ! taxonomy_exists( $taxonomy ) ) {
		return [];
	}

	$filterer = chairforce_get_wc_product_filterer();

	if ( ! $filterer || ! method_exists( $filterer, 'get_filtered_term_product_counts' ) ) {
		return [];
	}

	$query_type = chairforce_get_layered_nav_query_type( $taxonomy );
	$counts     = $filterer->get_filtered_term_product_counts( $term_ids, $taxonomy, $query_type );

	return is_array( $counts ) ? $counts : [];
}

/**
 * Base catalog URL for layered-nav links on the current archive.
 *
 * @return string
 */
function chairforce_get_catalog_filter_base_url(): string {
	if ( is_shop() ) {
		return (string) wc_get_page_permalink( 'shop' );
	}

	if ( is_product_taxonomy() ) {
		$term = get_queried_object();

		if ( $term instanceof \WP_Term ) {
			$link = get_term_link( $term );

			if ( ! is_wp_error( $link ) ) {
				return (string) $link;
			}
		}
	}

	if ( is_post_type_archive( 'product' ) ) {
		$link = get_post_type_archive_link( 'product' );

		if ( is_string( $link ) && '' !== $link ) {
			return $link;
		}
	}

	return (string) wc_get_page_permalink( 'shop' );
}

/**
 * Parse layered-nav filter params from a query string or $_GET-style array.
 *
 * @param string|array<string, mixed>|null $source Query string, array, or null for $_GET.
 * @return array<string, string> Map of param name => value (`filter_colour`, `min_price`, …).
 */
function chairforce_parse_catalog_filter_params( $source = null ): array {
	if ( null === $source ) {
		$source = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$params = [];

	if ( is_string( $source ) ) {
		parse_str( ltrim( $source, '?' ), $params );
	} elseif ( is_array( $source ) ) {
		$params = $source;
	}

	$filters = [];

	foreach ( $params as $key => $value ) {
		if ( ! is_string( $key ) || ! is_scalar( $value ) ) {
			continue;
		}

		$string_value = sanitize_text_field( (string) $value );

		if ( '' === $string_value ) {
			continue;
		}

		if ( str_starts_with( $key, 'filter_' ) || in_array( $key, [ 'min_price', 'max_price', 'orderby', 'order' ], true ) ) {
			$filters[ $key ] = $string_value;
		}
	}

	return $filters;
}

/**
 * Layered-nav params only (excludes sort params unless explicitly passed in $changes).
 *
 * @return array<string, string>
 */
function chairforce_get_active_catalog_filter_params(): array {
	$params = chairforce_parse_catalog_filter_params();
	$active = [];

	foreach ( $params as $key => $value ) {
		if ( str_starts_with( $key, 'filter_' ) || in_array( $key, [ 'min_price', 'max_price' ], true ) ) {
			$active[ $key ] = $value;
		}
	}

	return $active;
}

/**
 * Build a catalog URL preserving active filter params with optional overrides/removals.
 *
 * Sort params (`orderby` / `order`) are only included when explicitly set in `$changes`.
 *
 * @param array<string, string|null> $changes Param => value; null removes param.
 * @param string|null                $base_url Base URL; defaults to current archive base.
 * @return string
 */
function chairforce_build_catalog_filter_url( array $changes, ?string $base_url = null ): string {
	$base_url = $base_url ? $base_url : chairforce_get_catalog_filter_base_url();
	$params   = chairforce_get_active_catalog_filter_params();

	foreach ( $changes as $key => $value ) {
		if ( null === $value || '' === $value ) {
			unset( $params[ $key ] );
			continue;
		}

		$params[ $key ] = $value;
	}

	unset( $params['paged'], $params['page'] );

	if ( empty( $params ) ) {
		return $base_url;
	}

	return add_query_arg( $params, $base_url );
}

/**
 * Toggle one attribute term in the layered-nav query string (OR within attribute).
 *
 * @param string $taxonomy  Attribute taxonomy e.g. `pa_colour`.
 * @param string $term_slug Term slug.
 * @return string URL with updated filter param.
 */
function chairforce_build_layered_nav_toggle_url( string $taxonomy, string $term_slug ): string {
	$term_slug = sanitize_title( $term_slug );

	if ( ! taxonomy_exists( $taxonomy ) || '' === $term_slug ) {
		return chairforce_get_catalog_filter_base_url();
	}

	$filter_name = 'filter_' . wc_attribute_taxonomy_slug( $taxonomy );
	$params      = chairforce_parse_catalog_filter_params();
	$current     = [];

	if ( ! empty( $params[ $filter_name ] ) ) {
		$current = array_filter(
			array_map(
				'sanitize_title',
				explode( ',', $params[ $filter_name ] )
			)
		);
	}

	if ( in_array( $term_slug, $current, true ) ) {
		$current = array_values( array_diff( $current, [ $term_slug ] ) );
	} else {
		$current[] = $term_slug;
	}

	return chairforce_build_catalog_filter_url(
		[
			$filter_name => empty( $current ) ? null : implode( ',', $current ),
		]
	);
}

/**
 * Lucide icon slug for a catalog filter group (attribute slug or `price`).
 *
 * Maps WooCommerce attribute slugs to the theme's curated Lucide set — no ACF.
 * Unknown slugs fall back to `filter`.
 *
 * @param string $slug Attribute slug e.g. `material`, `colour`, or `price`.
 * @return string Lucide slug registered in chairforce_get_lucide_icon_choices().
 */
function chairforce_get_filter_group_icon_slug( string $slug ): string {

	$slug = sanitize_title( $slug );

	$map = [
		'price'           => 'tag',
		'material'        => 'package',
		'colour'          => 'eye',
		'color'           => 'eye',
		'arms'            => 'sliders-horizontal',
		'stackable'       => 'package',
		'assembly'        => 'check-circle',
		'indoor-outdoor'  => 'map-pin',
		'folding'         => 'menu',
		'height'          => 'sliders-horizontal',
		'shape'           => 'grid-2x2',
		'size'            => 'sliders-horizontal',
		'seat'            => 'user',
		'type'            => 'file-text',
		'features'        => 'star',
		'backrest'        => 'shield-check',
		'base-type'       => 'truck',
		'brand'           => 'tag',
		'mounting'        => 'map-pin',
		'feet'            => 'truck',
		'castors'         => 'truck',
		'feet-castors'    => 'truck',
	];

	$icon = $map[ $slug ] ?? 'filter';

	if ( function_exists( 'chairforce_sanitize_lucide_icon_slug' ) ) {
		$icon = chairforce_sanitize_lucide_icon_slug( $icon );
	}

	return '' !== $icon ? $icon : 'filter';
}

/**
 * Number of active selections within one filter group (bar button context).
 *
 * Price counts as 1 when min/max is set; attributes count chosen terms.
 *
 * @param array<string, mixed> $group Filter group from chairforce_get_archive_filter_groups().
 * @return int
 */
function chairforce_get_filter_group_applied_count( array $group ): int {

	if ( 'price' === ( $group['type'] ?? '' ) ) {
		$params = chairforce_parse_catalog_filter_params();

		return ( ! empty( $params['min_price'] ) || ! empty( $params['max_price'] ) ) ? 1 : 0;
	}

	$count = 0;

	foreach ( (array) ( $group['terms'] ?? [] ) as $term ) {
		if ( ! empty( $term['chosen'] ) ) {
			++$count;
		}
	}

	return $count;
}

/**
 * Visible attribute filter groups for the current product archive.
 *
 * @return array<int, array<string, mixed>>
 */
function chairforce_get_archive_filter_groups(): array {
	if ( ! chairforce_is_product_filter_archive() || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return [];
	}

	if ( ! class_exists( 'WC_Query' ) ) {
		return [];
	}

	$groups   = [];
	$chosen   = \WC_Query::get_layered_nav_chosen_attributes();
	$attributes = wc_get_attribute_taxonomies();

	if ( empty( $attributes ) ) {
		return chairforce_maybe_prepend_price_filter_group( $groups );
	}

	foreach ( $attributes as $attribute ) {
		$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}

		$term_ids = wp_list_pluck( $terms, 'term_id' );
		$counts   = chairforce_get_filtered_term_product_counts( $term_ids, $taxonomy );
		$options  = [];

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$count = isset( $counts[ $term->term_id ] ) ? (int) $counts[ $term->term_id ] : 0;

			if ( $count <= 0 ) {
				continue;
			}

			$chosen_terms = $chosen[ $taxonomy ]['terms'] ?? [];
			$is_chosen    = in_array( $term->slug, $chosen_terms, true );

			$options[] = [
				'term_id'    => (int) $term->term_id,
				'slug'       => $term->slug,
				'label'      => $term->name,
				'count'      => $count,
				'chosen'     => $is_chosen,
				'toggle_url' => chairforce_build_layered_nav_toggle_url( $taxonomy, $term->slug ),
			];
		}

		if ( empty( $options ) ) {
			continue;
		}

		$attribute_slug = wc_attribute_taxonomy_slug( $taxonomy );

		$groups[] = [
			'slug'       => $attribute_slug,
			'label'      => wc_attribute_label( $taxonomy ),
			'taxonomy'   => $taxonomy,
			'type'       => 'attribute',
			'query_type' => chairforce_get_layered_nav_query_type( $taxonomy ),
			'terms'      => $options,
		];
	}

	return chairforce_maybe_prepend_price_filter_group( $groups );
}

/**
 * Min/max catalog price bounds for the current archive context.
 *
 * @return array{min: float, max: float}
 */
function chairforce_get_catalog_price_bounds(): array {
	$min = 0.0;
	$max = (float) apply_filters( 'chairforce_catalog_price_filter_max', 10000 );

	if ( class_exists( '\WC_Widget_Price_Filter' ) && is_callable( [ '\WC_Widget_Price_Filter', 'get_filtered_price' ] ) ) {
		$prices = \WC_Widget_Price_Filter::get_filtered_price();

		if ( is_object( $prices ) ) {
			if ( isset( $prices->min_price ) ) {
				$min = floor( (float) $prices->min_price );
			}

			if ( isset( $prices->max_price ) && (float) $prices->max_price > 0 ) {
				$max = ceil( (float) $prices->max_price );
			}
		}
	}

	if ( $max <= $min ) {
		$max = max( $min + 1, (float) apply_filters( 'chairforce_catalog_price_filter_max', 10000 ) );
	}

	return [
		'min' => $min,
		'max' => $max,
	];
}

/**
 * @param array<int, array<string, mixed>> $groups Filter groups.
 * @return array<int, array<string, mixed>>
 */
function chairforce_maybe_prepend_price_filter_group( array $groups ): array {
	if ( ! chairforce_is_product_filter_archive() ) {
		return $groups;
	}

	$params = chairforce_parse_catalog_filter_params();
	$min    = isset( $params['min_price'] ) ? (float) $params['min_price'] : 0.0;
	$max    = isset( $params['max_price'] ) ? (float) $params['max_price'] : 0.0;
	$bounds = chairforce_get_catalog_price_bounds();

	array_unshift(
		$groups,
		[
			'slug'      => 'price',
			'label'     => __( 'Filter by price', 'chairforce' ),
			'taxonomy'  => '',
			'type'      => 'price',
			'min'       => $min,
			'max'       => $max,
			'min_bound' => $bounds['min'],
			'max_bound' => $bounds['max'],
		]
	);

	return $groups;
}

/**
 * Render the full product filters shell markup.
 *
 * @param array<int, array<string, mixed>>|null $filter_groups Optional precomputed groups.
 * @return string
 */
function chairforce_render_product_filters_html( ?array $filter_groups = null ): string {
	if ( null !== $filter_groups && empty( $filter_groups ) ) {
		return '';
	}

	ob_start();
	get_template_part(
		'partials/product',
		'filters',
		null !== $filter_groups
			? [
				'filter_groups' => $filter_groups,
			]
			: []
	);

	return (string) ob_get_clean();
}

/**
 * Render active filter chips markup only.
 *
 * @return string
 */
function chairforce_render_product_filters_chips_html(): string {
	ob_start();
	get_template_part( 'partials/product-filters', 'chips' );

	return (string) ob_get_clean();
}

/**
 * Render filter panel markup only.
 *
 * @param array<int, array<string, mixed>>|null $filter_groups Optional precomputed groups.
 * @return string
 */
function chairforce_render_product_filters_panel_html( ?array $filter_groups = null ): string {
	if ( null !== $filter_groups && empty( $filter_groups ) ) {
		return '';
	}

	ob_start();
	get_template_part(
		'partials/product-filters',
		'panel',
		null !== $filter_groups
			? [
				'filter_groups' => $filter_groups,
			]
			: []
	);

	return (string) ob_get_clean();
}

/**
 * Build WooCommerce-style archive results count HTML.
 *
 * @param int $total  Total matching products.
 * @param int $viewing Number of products currently visible on page 1.
 * @return string
 */
function chairforce_render_product_results_count_html( int $total, int $viewing ): string {
	if ( $total <= 0 ) {
		return '';
	}

	$viewing = max( 1, min( $viewing, $total ) );

	if ( 1 === $total ) {
		$text = __( 'Showing the single result', 'chairforce' );
	} elseif ( $viewing >= $total ) {
		$text = sprintf(
			/* translators: %s: total number of products */
			__( 'Showing all %s results', 'chairforce' ),
			number_format_i18n( $total )
		);
	} else {
		$text = sprintf(
			/* translators: 1: first visible product number, 2: last visible product number, 3: total products */
			__( 'Showing %1$s&ndash;%2$s of %3$s results', 'chairforce' ),
			number_format_i18n( 1 ),
			number_format_i18n( $viewing ),
			number_format_i18n( $total )
		);
	}

	return sprintf(
		'<p class="woocommerce-result-count" aria-live="polite">%s</p>',
		wp_kses_post( $text )
	);
}

/**
 * Parse filter params from a REST request (query_vars JSON + explicit filter_* args).
 *
 * @param \WP_REST_Request $request REST request.
 * @return array<string, string>
 */
function chairforce_parse_catalog_filter_params_from_request( \WP_REST_Request $request ): array {
	$params = chairforce_parse_catalog_filter_params( $request->get_query_params() );

	$query_vars_raw = $request->get_param( 'query_vars' );

	if ( is_string( $query_vars_raw ) && '' !== $query_vars_raw ) {
		$decoded = json_decode( $query_vars_raw, true );

		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $key => $value ) {
				if ( ! is_string( $key ) || ! is_scalar( $value ) ) {
					continue;
				}

				if (
					str_starts_with( $key, 'filter_' )
					|| in_array( $key, [ 'min_price', 'max_price', 'orderby', 'order' ], true )
				) {
					$params[ $key ] = sanitize_text_field( (string) $value );
				}
			}
		}
	}

	return $params;
}

/**
 * Apply layered-nav + price params to WP_Query args using WooCommerce helpers.
 *
 * @param array<string, mixed> $query_args    WP_Query args.
 * @param array<string, string> $filter_params Catalog filter params.
 * @return array<string, mixed>
 */
function chairforce_apply_catalog_filter_params_to_query( array $query_args, array $filter_params ): array {
	if ( empty( $filter_params ) || ! function_exists( 'WC' ) || ! WC()->query instanceof \WC_Query ) {
		return $query_args;
	}

	$backup_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	foreach ( $filter_params as $key => $value ) {
		$_GET[ $key ] = $value; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$wc_query = WC()->query;
	$tax_query = isset( $query_args['tax_query'] ) && is_array( $query_args['tax_query'] )
		? $query_args['tax_query']
		: [];

	$merged_tax_query = $wc_query->get_tax_query( $tax_query, true );

	if ( ! empty( $merged_tax_query ) ) {
		$query_args['tax_query'] = $merged_tax_query;
	}

	$meta_query = isset( $query_args['meta_query'] ) && is_array( $query_args['meta_query'] )
		? $query_args['meta_query']
		: [];

	$merged_meta_query = $wc_query->get_meta_query( $meta_query, true );

	if ( ! empty( $merged_meta_query ) ) {
		$query_args['meta_query'] = $merged_meta_query;
	}

	if ( ! empty( $filter_params['min_price'] ) ) {
		$query_args['min_price'] = (float) $filter_params['min_price'];
	}

	if ( ! empty( $filter_params['max_price'] ) ) {
		$query_args['max_price'] = (float) $filter_params['max_price'];
	}

	$_GET = $backup_get; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return $query_args;
}

/**
 * Bootstrap main query context so Filterer facet counts match a catalog query.
 *
 * @param array<string, mixed>  $query_args    WP_Query args (page 1).
 * @param array<string, string> $filter_params Active filter params.
 * @return \WP_Query
 */
function chairforce_bootstrap_product_filter_main_query( array $query_args, array $filter_params ): \WP_Query {
	global $wp_query;

	$previous_query = ( $wp_query instanceof \WP_Query ) ? $wp_query : null;
	$query          = new \WP_Query( $query_args );

	$wp_query = $query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

	if ( function_exists( 'wc_setup_loop' ) ) {
		wc_setup_loop(
			[
				'total'        => (int) $query->found_posts,
				'total_pages'  => (int) $query->max_num_pages,
				'per_page'     => (int) $query->get( 'posts_per_page' ),
				'current_page' => max( 1, (int) $query->get( 'paged', 1 ) ),
				'is_shortcode' => false,
				'is_search'    => false,
			]
		);
	}

	$backup_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	foreach ( $filter_params as $key => $value ) {
		$_GET[ $key ] = $value; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	return $query;
}

/**
 * Restore global query after temporary bootstrap.
 *
 * @param \WP_Query|null $previous_query Previous global query.
 */
function chairforce_restore_product_filter_main_query( ?\WP_Query $previous_query ): void {
	global $wp_query;

	$wp_query = $previous_query; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

	if ( function_exists( 'wc_reset_loop' ) ) {
		wc_reset_loop();
	}
}

/**
 * Active filter chips for the current catalog query (attributes + price).
 *
 * @return array<int, array<string, mixed>>
 */
function chairforce_get_active_filter_chips(): array {
	if ( ! function_exists( 'WC' ) || ! class_exists( 'WC_Query' ) ) {
		return [];
	}

	$chips  = [];
	$chosen = \WC_Query::get_layered_nav_chosen_attributes();
	$params = chairforce_parse_catalog_filter_params();

	foreach ( $chosen as $taxonomy => $data ) {
		if ( empty( $data['terms'] ) || ! is_array( $data['terms'] ) ) {
			continue;
		}

		$filter_name = 'filter_' . wc_attribute_taxonomy_slug( $taxonomy );

		foreach ( $data['terms'] as $term_slug ) {
			$term = get_term_by( 'slug', $term_slug, $taxonomy );

			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$remaining = array_values(
				array_diff(
					$data['terms'],
					[ $term_slug ]
				)
			);

			$chips[] = [
				'key'         => $filter_name . ':' . $term_slug,
				'label'       => $term->name,
				'remove_url'  => chairforce_build_catalog_filter_url(
					[
						$filter_name => empty( $remaining ) ? null : implode( ',', $remaining ),
					]
				),
				'filter_name' => $filter_name,
				'term_slug'   => $term_slug,
			];
		}
	}

	if ( ! empty( $params['min_price'] ) || ! empty( $params['max_price'] ) ) {
		$min_label = ! empty( $params['min_price'] ) ? wc_price( (float) $params['min_price'] ) : '';
		$max_label = ! empty( $params['max_price'] ) ? wc_price( (float) $params['max_price'] ) : '';

		$label = trim(
			wp_strip_all_tags(
				sprintf(
					/* translators: 1: min price, 2: max price */
					__( '%1$s – %2$s', 'chairforce' ),
					$min_label ? $min_label : '…',
					$max_label ? $max_label : '…'
				)
			)
		);

		$chips[] = [
			'key'        => 'price',
			'label'      => $label,
			'remove_url' => chairforce_build_catalog_filter_url(
				[
					'min_price' => null,
					'max_price' => null,
				]
			),
			'filter_name' => 'price',
		];
	}

	return $chips;
}

/**
 * Clear-all URL for active catalog filters (base archive URL, no query params).
 *
 * @return string
 */
function chairforce_get_clear_catalog_filters_url(): string {
	return chairforce_get_catalog_filter_base_url();
}

/**
 * Whether an attribute group should render as a colour swatch grid.
 *
 * @param string $attribute_slug Attribute slug e.g. `colour`.
 * @return bool
 */
function chairforce_is_colour_filter_attribute( string $attribute_slug ): bool {
	return in_array( $attribute_slug, [ 'colour', 'color' ], true );
}

/**
 * Render one filter swatch button for colour attributes.
 *
 * @param string $taxonomy    Attribute taxonomy.
 * @param array<string, mixed> $term Term data from filter groups.
 * @param string $filter_name Filter query param name.
 * @return string
 */
function chairforce_render_filter_swatch_term( string $taxonomy, array $term, string $filter_name ): string {
	$term_slug  = (string) ( $term['slug'] ?? '' );
	$term_label = (string) ( $term['label'] ?? $term_slug );
	$is_chosen  = ! empty( $term['chosen'] );
	$swatch     = class_exists( '\Chairforce\Product_Swatches' )
		? \Chairforce\Product_Swatches::has_swatch( 0, $taxonomy, $term_slug )
		: [];

	$classes = [
		'cf-swatch',
		'cf-filter-term',
		'cf-swatch--style-3',
	];

	if ( $is_chosen ) {
		$classes[] = 'is-active';
		$classes[] = 'cf-swatch--active';
	}

	$style = '';
	$image = '';

	if ( ! empty( $swatch['color'] ) ) {
		$style     = 'background-color:' . (string) $swatch['color'];
		$classes[] = 'cf-swatch--bg';
	} elseif ( ! empty( $swatch['image'] ) && class_exists( '\Chairforce\Product_Swatches' ) ) {
		$image     = \Chairforce\Product_Swatches::get_term_swatch_image_html( $swatch['image'] );
		$classes[] = 'cf-swatch--bg';
	} else {
		$classes[] = 'cf-swatch--text';
	}

	$inner = '';

	if ( $style || $image ) {
		$inner = sprintf(
			'<span class="cf-swatch__bg" style="%s">%s</span>',
			esc_attr( $style ),
			$image // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attachment HTML from WP.
		);
	} else {
		$inner = sprintf(
			'<span class="cf-swatch__text">%s</span>',
			esc_html( $term_label )
		);
	}

	return sprintf(
		'<button type="button" class="%1$s" data-filter-name="%2$s" data-term-slug="%3$s" data-toggle-url="%4$s" aria-pressed="%5$s" title="%6$s">%7$s</button>',
		esc_attr( implode( ' ', $classes ) ),
		esc_attr( $filter_name ),
		esc_attr( $term_slug ),
		esc_url( (string) ( $term['toggle_url'] ?? '' ) ),
		$is_chosen ? 'true' : 'false',
		esc_attr( $term_label ),
		$inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	);
}
