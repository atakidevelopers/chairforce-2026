<?php

namespace ChairforceDataNormalise\Jobs;

use ChairforceDataNormalise\Meta_Normalise_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ChairforceDataNormalise\Jobs\Normalise_Showroom_Gallery' ) ) {
	return;
}

/**
 * Converts JetEngine CSV attachment IDs on showrooms → native ACF gallery storage.
 *
 * Scoped query: post_type showrooms + meta_key showroom_gallery only.
 * Idempotent: skips rows already in ACF serialized-array shape.
 */
class Normalise_Showroom_Gallery {

	public $batch = 10;

	public $label = 'Normalise showrooms "showroom_gallery" meta';

	public $description = 'JetEngine stored showroom_gallery as comma-separated attachment IDs. Converts each showrooms post still in that legacy shape to ACF\'s native gallery storage. Posts are found dynamically (post_type = showrooms, meta_key = showroom_gallery) — no hardcoded IDs. Safe to re-run.';

	private const POST_TYPE = 'showrooms';

	private const META_KEY = 'showroom_gallery';

	private const FIELD_NAME = 'showroom_gallery';

	/**
	 * @return array<int, int> Post IDs.
	 */
	public function items(): array {
		global $wpdb;

		$post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE p.post_type = %s
				  AND pm.meta_key = %s
				  AND pm.meta_value != ''
				  AND pm.meta_value NOT LIKE 'a:%%'",
				self::POST_TYPE,
				self::META_KEY
			)
		);

		return array_map( 'intval', $post_ids );
	}

	/**
	 * @param int $post_id Showrooms post ID.
	 */
	public function process( $post_id ) {
		$post_id = (int) $post_id;

		if ( ! Meta_Normalise_Helper::acf_is_available() ) {
			return "#{$post_id}: ACF is not active — skipped.";
		}

		$post = get_post( $post_id );
		$label = $post
			? sprintf( '"%s" (#%d)', $post->post_title, $post_id )
			: sprintf( 'post #%d', $post_id );

		$raw = get_post_meta( $post_id, self::META_KEY, true );

		if ( is_array( $raw ) ) {
			return "{$label}: already ACF array storage — skipped.";
		}

		if ( ! Meta_Normalise_Helper::is_legacy_csv_string( $raw ) ) {
			return "{$label}: unexpected value type (" . gettype( $raw ) . ') — skipped, needs manual review.';
		}

		$ids = Meta_Normalise_Helper::validate_attachment_ids(
			array_map( 'intval', Meta_Normalise_Helper::parse_csv_string( (string) $raw ) )
		);

		$parsed_count = count( Meta_Normalise_Helper::parse_csv_string( (string) $raw ) );
		$dropped      = $parsed_count - count( $ids );

		if ( empty( $ids ) ) {
			delete_post_meta( $post_id, self::META_KEY );
			delete_post_meta( $post_id, '_' . self::META_KEY );

			return "{$label}: cleared legacy CSV (no valid attachment IDs).";
		}

		Meta_Normalise_Helper::write_acf_field_native( self::FIELD_NAME, $ids, $post_id );

		$note = $dropped > 0 ? " ({$dropped} invalid ID(s) dropped)" : '';

		return "{$label}: normalised {$parsed_count} legacy ID(s) → ACF gallery with " . count( $ids ) . " attachment(s){$note}.";
	}
}
