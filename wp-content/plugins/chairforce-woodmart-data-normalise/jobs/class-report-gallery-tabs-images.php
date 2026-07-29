<?php

namespace ChairforceDataNormalise\Jobs;

use ChairforceDataNormalise\Meta_Normalise_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ChairforceDataNormalise\Jobs\Report_Gallery_Tabs_Images' ) ) {
	return;
}

/**
 * Inventory-only job: logs URL → attachment ID resolution for gallery_images.
 *
 * Does not modify the database. Run before Normalise_Gallery_Tabs_Images.
 */
class Report_Gallery_Tabs_Images {

	public $batch = 5;

	public $label = 'Report gallery-tabs "gallery_images" URL inventory (read-only)';

	public $description = 'Read-only audit for gallery-tabs posts whose gallery_images meta is still a comma-separated URL list (JetEngine legacy). Logs each URL and whether it resolves to an attachment ID (by URL or filename fallback). Does not write any data. Review the log before running "Normalise gallery-tabs gallery_images meta".';

	private const POST_TYPE = 'gallery-tabs';

	private const META_KEY = 'gallery_images';

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

		$post  = get_post( $post_id );
		$label = $post
			? sprintf( '"%s" (#%d)', $post->post_title, $post_id )
			: sprintf( 'post #%d', $post_id );

		$raw = get_post_meta( $post_id, self::META_KEY, true );

		if ( is_array( $raw ) ) {
			return "{$label}: already ACF array storage — nothing to inventory.";
		}

		if ( ! Meta_Normalise_Helper::is_legacy_csv_string( $raw ) ) {
			return "{$label}: unexpected value type (" . gettype( $raw ) . ') — skipped.';
		}

		if ( ! str_contains( (string) $raw, 'http' ) ) {
			return "{$label}: legacy value is not URL CSV — skipped, needs manual review.";
		}

		$result = Meta_Normalise_Helper::attachment_ids_from_url_csv( (string) $raw );
		$urls   = Meta_Normalise_Helper::parse_csv_string( (string) $raw );
		$found  = count( $result['ids'] );
		$total  = count( $urls );
		$missed = $total - $found;

		$lines   = [];
		$lines[] = "{$label}: {$found}/{$total} URL(s) resolved, {$missed} not found.";
		$lines   = array_merge( $lines, $result['lines'] );

		return implode( "\n", $lines );
	}
}
