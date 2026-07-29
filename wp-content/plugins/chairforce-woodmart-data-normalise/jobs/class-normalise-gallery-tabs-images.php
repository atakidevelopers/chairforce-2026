<?php

namespace ChairforceDataNormalise\Jobs;

use ChairforceDataNormalise\Meta_Normalise_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ChairforceDataNormalise\Jobs\Normalise_Gallery_Tabs_Images' ) ) {
	return;
}

/**
 * Converts JetEngine CSV URLs on gallery-tabs → native ACF gallery (attachment IDs).
 *
 * Unresolvable URLs are skipped (not included in the saved gallery).
 */
class Normalise_Gallery_Tabs_Images {

	public $batch = 5;

	public $label = 'Normalise gallery-tabs "gallery_images" meta';

	public $description = 'JetEngine stored gallery_images as comma-separated URLs. Resolves each URL to an attachment ID (URL match, then filename fallback), then saves as ACF\'s native ID gallery. URLs that cannot be resolved are skipped and logged. Posts are found dynamically (post_type = gallery-tabs, meta_key = gallery_images). Run the read-only URL inventory job first and review its log. Safe to re-run.';

	private const POST_TYPE = 'gallery-tabs';

	private const META_KEY = 'gallery_images';

	private const FIELD_NAME = 'gallery_images';

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
	 * @param int $post_id Gallery-tabs post ID.
	 */
	public function process( $post_id ) {
		$post_id = (int) $post_id;

		if ( ! Meta_Normalise_Helper::acf_is_available() ) {
			return "#{$post_id}: ACF is not active — skipped.";
		}

		$post  = get_post( $post_id );
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

		if ( ! str_contains( (string) $raw, 'http' ) ) {
			return "{$label}: legacy value is not URL CSV — skipped, needs manual review.";
		}

		$result = Meta_Normalise_Helper::attachment_ids_from_url_csv( (string) $raw );
		$ids    = Meta_Normalise_Helper::validate_attachment_ids( $result['ids'] );
		$urls   = Meta_Normalise_Helper::parse_csv_string( (string) $raw );
		$missed = count( $urls ) - count( $ids );

		if ( empty( $ids ) ) {
			delete_post_meta( $post_id, self::META_KEY );
			delete_post_meta( $post_id, '_' . self::META_KEY );

			return "{$label}: cleared legacy URL CSV (0 resolvable attachments). " . count( $urls ) . ' URL(s) not found.';
		}

		Meta_Normalise_Helper::write_acf_field_native( self::FIELD_NAME, $ids, $post_id );

		$note = $missed > 0 ? " {$missed} URL(s) skipped (not found)." : '';

		return "{$label}: saved ACF gallery with " . count( $ids ) . ' attachment(s) from ' . count( $urls ) . " URL(s).{$note}";
	}
}
