<?php

namespace ChairforceDataNormalise\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ChairforceDataNormalise\Jobs\Report_Showroom_Locator_Posts' ) ) {
	return;
}

/**
 * Read-only audit: showroom post_title + menu_order vs locator targets.
 *
 * Run before Normalise_Showroom_Locator_Posts.
 */
class Report_Showroom_Locator_Posts {

	public $batch = 10;

	public $label = 'Report showroom locator post titles + menu order (read-only)';

	public $description = 'Read-only audit for published showrooms posts. Logs current post_title and menu_order vs targets derived from post_slug (title: ucwords/hyphens, no "Showroom" suffix; order: fixed Sydney→Auckland tab sequence). Does not write data. Does not touch warehouse meta.';

	private const POST_TYPE = 'showrooms';

	/**
	 * Locator tab order: post_slug => menu_order.
	 *
	 * @var array<string, int>
	 */
	private const MENU_ORDER_MAP = [
		'sydney'    => 0,
		'brisbane'  => 1,
		'melbourne' => 2,
		'adelaide'  => 3,
		'perth'     => 4,
		'hobart'    => 5,
		'auckland'  => 6,
	];

	/**
	 * @return array<int, int> Post IDs.
	 */
	public function items(): array {
		$posts = get_posts(
			[
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
			]
		);

		return array_map( 'intval', $posts );
	}

	/**
	 * @param int $post_id Showroom post ID.
	 * @return string
	 */
	public function process( $post_id ) {
		$post_id = (int) $post_id;
		$post    = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || self::POST_TYPE !== $post->post_type ) {
			return sprintf( 'post #%d: not a showrooms post — skipped.', $post_id );
		}

		$slug          = sanitize_key( (string) $post->post_name );
		$target_title  = self::derive_title_from_slug( $slug );
		$target_order  = self::MENU_ORDER_MAP[ $slug ] ?? null;
		$title_action  = ( (string) $post->post_title === $target_title ) ? 'unchanged' : 'would update';
		$order_action  = null === $target_order
			? 'order unchanged (slug not in map)'
			: ( (int) $post->menu_order === $target_order ? 'unchanged' : 'would update' );

		return sprintf(
			'"%s" (#%d, slug %s): title "%s" → "%s" (%s); menu_order %d → %s (%s).',
			$post->post_title,
			$post_id,
			$slug,
			$post->post_title,
			$target_title,
			$title_action,
			(int) $post->menu_order,
			null === $target_order ? (string) (int) $post->menu_order : (string) $target_order,
			$order_action
		);
	}

	/**
	 * Derive display title from post slug (no "Showroom" suffix).
	 *
	 * @param string $slug Post slug.
	 * @return string
	 */
	public static function derive_title_from_slug( string $slug ): string {
		$slug = sanitize_key( $slug );

		if ( '' === $slug ) {
			return '';
		}

		$label = str_replace( '-', ' ', $slug );

		if ( function_exists( 'mb_convert_case' ) ) {
			return mb_convert_case( $label, MB_CASE_TITLE, 'UTF-8' );
		}

		return ucwords( strtolower( $label ) );
	}
}
