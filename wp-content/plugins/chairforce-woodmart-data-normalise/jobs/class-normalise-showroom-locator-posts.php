<?php

namespace ChairforceDataNormalise\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ChairforceDataNormalise\Jobs\Normalise_Showroom_Locator_Posts' ) ) {
	return;
}

/**
 * Normalise showroom post_title + menu_order for the locator block.
 *
 * Title: slug → ucwords (sydney → Sydney). No "Showroom" suffix.
 * Order: fixed map for known city slugs (Sydney → Auckland tab sequence).
 * Does not modify warehouse or any other post meta.
 */
class Normalise_Showroom_Locator_Posts {

	public $batch = 10;

	public $label = 'Normalise showroom locator post titles + menu order';

	public $description = 'Updates published showrooms posts: post_title from post_slug (e.g. sydney → Sydney, no suffix); menu_order from the fixed locator tab sequence for known slugs. Skips title when already correct. Safe to re-run. Does not touch warehouse ACF meta. Run "Report showroom locator post titles + menu order" first to preview.';

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

		$slug         = sanitize_key( (string) $post->post_name );
		$target_title = Report_Showroom_Locator_Posts::derive_title_from_slug( $slug );
		$updates      = [];
		$postarr      = [
			'ID' => $post_id,
		];

		if ( '' !== $target_title && (string) $post->post_title !== $target_title ) {
			$postarr['post_title'] = $target_title;
			$updates[]             = sprintf( 'title "%s" → "%s"', $post->post_title, $target_title );
		}

		if ( isset( self::MENU_ORDER_MAP[ $slug ] ) ) {
			$target_order = self::MENU_ORDER_MAP[ $slug ];

			if ( (int) $post->menu_order !== $target_order ) {
				$postarr['menu_order'] = $target_order;
				$updates[]             = sprintf( 'menu_order %d → %d', (int) $post->menu_order, $target_order );
			}
		}

		if ( count( $postarr ) === 1 ) {
			return sprintf(
				'"%s" (#%d, slug %s): already normalised — skipped.',
				$post->post_title,
				$post_id,
				$slug
			);
		}

		$result = wp_update_post( wp_slash( $postarr ), true );

		if ( is_wp_error( $result ) ) {
			return sprintf(
				'"%s" (#%d): update failed — %s',
				$post->post_title,
				$post_id,
				$result->get_error_message()
			);
		}

		return sprintf(
			'"%s" (#%d, slug %s): %s.',
			$post->post_title,
			$post_id,
			$slug,
			implode( '; ', $updates )
		);
	}
}
