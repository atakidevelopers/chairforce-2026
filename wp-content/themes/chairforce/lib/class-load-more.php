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

		$per_page = chairforce_get_loop_shop_per_page();
		$total    = 0;
		$max_pages = 0;

		if ( $wp_query instanceof \WP_Query ) {
			$total     = (int) $wp_query->found_posts;
			$max_pages = chairforce_get_load_more_max_pages( $total, $per_page );
		}

		if ( $max_pages <= 1 ) {
			return '';
		}

		$viewing = min( $per_page, $total );

		$load_more_text = ! empty( $attributes['loadMoreText'] )
			? (string) $attributes['loadMoreText']
			: __( 'Load More', 'chairforce' );

		$loading_text = ! empty( $attributes['loadingText'] )
			? (string) $attributes['loadingText']
			: __( 'Loading…', 'chairforce' );

		$query_vars = chairforce_get_load_more_query_vars_for_client();

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
						'data-next-page'     => '2',
						'data-max-pages'     => (string) $max_pages,
						'data-per-page'      => (string) $per_page,
						'data-query-vars'    => wp_json_encode( $query_vars ),
						'data-loading-text'  => $loading_text,
						'aria-busy'          => 'false',
					],
				],
			],
			[
				'layout' => [
					'type'            => 'flex',
					'justifyContent'  => 'center',
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
}
