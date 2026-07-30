<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Load_More' ) ) {
	return;
}

require_once get_theme_file_path( 'includes/load-more-functions.php' );

/**
 * Page-1 Load More for WooCommerce Product Collection pagination blocks.
 *
 * When `loadMore` is enabled on `core/query-pagination` and the archive is not
 * paged, numbered pagination is replaced with a Load More button. Page 2+ URLs
 * keep default crawlable pagination.
 */
class Load_More {

	/**
	 * Load_More constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register hooks.
	 */
	private function register_hooks(): void {
		add_filter( 'register_block_type_args', [ $this, 'extend_query_pagination_block' ], 10, 2 );
	}

	/**
	 * Add Load More attributes and render callback to core/query-pagination.
	 *
	 * @param array<string, mixed> $args        Block type registration args.
	 * @param string               $block_type  Block name.
	 * @return array<string, mixed>
	 */
	public function extend_query_pagination_block( array $args, string $block_type ): array {

		if ( 'core/query-pagination' !== $block_type ) {
			return $args;
		}

		$args['attributes'] = array_merge(
			$args['attributes'] ?? [],
			[
				'loadMore'      => [
					'type'    => 'boolean',
					'default' => false,
				],
				'loadMoreText'  => [
					'type'    => 'string',
					'default' => __( 'Load More', 'chairforce' ),
				],
				'loadingText'   => [
					'type'    => 'string',
					'default' => __( 'Loading…', 'chairforce' ),
				],
			]
		);

		$args['render_callback'] = [ $this, 'render_query_pagination' ];

		return $args;
	}

	/**
	 * Render pagination or page-1 Load More button.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Block content.
	 * @param \WP_Block            $block      Block instance.
	 * @return string
	 */
	public function render_query_pagination( array $attributes, string $content, \WP_Block $block ): string {

		$load_more = ! empty( $attributes['loadMore'] );

		if ( ! $load_more || is_paged() ) {
			if ( function_exists( 'render_block_core_query_pagination' ) ) {
				return render_block_core_query_pagination( $attributes, $content, $block );
			}

			return $content;
		}

		if (
			! is_shop()
			&& ! is_product_taxonomy()
			&& ! is_post_type_archive( 'product' )
		) {
			if ( function_exists( 'render_block_core_query_pagination' ) ) {
				return render_block_core_query_pagination( $attributes, $content, $block );
			}

			return $content;
		}

		return $this->render_load_more_button( $attributes );
	}

	/**
	 * Output the page-1 Load More button with localized query payload.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	private function render_load_more_button( array $attributes ): string {

		global $wp_query;

		$per_page  = chairforce_get_loop_shop_per_page();
		$max_pages = 0;

		if ( $wp_query instanceof \WP_Query ) {
			$max_pages = chairforce_get_load_more_max_pages( (int) $wp_query->found_posts, $per_page );
		}

		if ( $max_pages <= 1 ) {
			return '';
		}

		$load_more_text = ! empty( $attributes['loadMoreText'] )
			? (string) $attributes['loadMoreText']
			: __( 'Load More', 'chairforce' );

		$loading_text = ! empty( $attributes['loadingText'] )
			? (string) $attributes['loadingText']
			: __( 'Loading…', 'chairforce' );

		$query_vars = chairforce_get_load_more_query_vars_for_client();

		$wrapper_attributes = get_block_wrapper_attributes(
			[
				'class' => 'cf-load-more wp-block-query-pagination',
			]
		);

		$button_attributes = sprintf(
			'class="cf-load-more__button wp-element-button" type="button" data-next-page="2" data-max-pages="%1$d" data-per-page="%2$d" data-query-vars="%3$s" data-loading-text="%4$s" aria-busy="false"',
			esc_attr( (string) $max_pages ),
			esc_attr( (string) $per_page ),
			esc_attr( wp_json_encode( $query_vars ) ),
			esc_attr( $loading_text )
		);

		return sprintf(
			'<nav %1$s><button %2$s>%3$s</button></nav>',
			$wrapper_attributes,
			$button_attributes,
			esc_html( $load_more_text )
		);
	}
}
