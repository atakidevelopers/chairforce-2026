<?php

namespace ChairforceDataNormalise\Jobs;

use ChairforceDataNormalise\Meta_Normalise_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ChairforceDataNormalise\Jobs\Normalise_Venue_Image' ) ) {
	return;
}

/**
 * Converts JetEngine CSV attachment IDs on venues terms → native ACF gallery storage.
 *
 * Scoped query: taxonomy venues + meta_key venue_image only.
 */
class Normalise_Venue_Image {

	public $batch = 10;

	public $label = 'Normalise venues "venue_image" term meta';

	public $description = 'JetEngine stored venue_image as comma-separated attachment IDs on venues taxonomy terms. Converts each term still in that legacy shape to ACF\'s native gallery storage. Terms are found dynamically (taxonomy = venues, meta_key = venue_image) — no hardcoded IDs. Safe to re-run.';

	private const TAXONOMY = 'venues';

	private const META_KEY = 'venue_image';

	private const FIELD_NAME = 'venue_image';

	/**
	 * @return array<int, int> Term IDs.
	 */
	public function items(): array {
		global $wpdb;

		$term_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT tm.term_id
				FROM {$wpdb->termmeta} tm
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id
				WHERE tt.taxonomy = %s
				  AND tm.meta_key = %s
				  AND tm.meta_value != ''
				  AND tm.meta_value NOT LIKE 'a:%%'",
				self::TAXONOMY,
				self::META_KEY
			)
		);

		return array_map( 'intval', $term_ids );
	}

	/**
	 * @param int $term_id Venues term ID.
	 */
	public function process( $term_id ) {
		$term_id = (int) $term_id;

		if ( ! Meta_Normalise_Helper::acf_is_available() ) {
			return "term #{$term_id}: ACF is not active — skipped.";
		}

		$term  = get_term( $term_id );
		$label = ( $term && ! is_wp_error( $term ) )
			? sprintf( '"%s" (#%d, %s)', $term->name, $term_id, $term->taxonomy )
			: sprintf( 'term #%d', $term_id );

		$raw = get_term_meta( $term_id, self::META_KEY, true );

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
		$acf_ref      = 'term_' . $term_id;

		if ( empty( $ids ) ) {
			delete_term_meta( $term_id, self::META_KEY );
			delete_term_meta( $term_id, '_' . self::META_KEY );

			return "{$label}: cleared legacy CSV (no valid attachment IDs).";
		}

		Meta_Normalise_Helper::write_acf_field_native( self::FIELD_NAME, $ids, $acf_ref );

		$note = $dropped > 0 ? " ({$dropped} invalid ID(s) dropped)" : '';

		return "{$label}: normalised {$parsed_count} legacy ID(s) → ACF gallery with " . count( $ids ) . " attachment(s){$note}.";
	}
}
