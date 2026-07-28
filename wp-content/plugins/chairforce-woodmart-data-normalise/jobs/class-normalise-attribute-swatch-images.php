<?php

namespace ChairforceDataNormalise\Jobs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ChairforceDataNormalise\Jobs\Normalise_Attribute_Swatch_Images' ) ) {
	return;
}

/**
 * Normalises the legacy `image` term meta shape shared by all attribute
 * taxonomies that used Woodmart's swatches metabox.
 *
 * Woodmart wrote this field directly to `wp_termmeta` as a serialized
 * `['url' => string, 'id' => string]` array — outside of ACF, which always
 * stores its own Image field as a plain attachment ID. Converts every term
 * still in that legacy shape to a plain ID (or clears empty legacy
 * placeholders), so the ACF field group (`group_pa_colour_swatch_fields`,
 * shared across all of these taxonomies) works fully natively, no runtime
 * load/save filters required.
 *
 * See context/existing-functionality/02-data-model-and-storage.md §1 and
 * context/PROGRESS.md 3c for the full data-contract background.
 *
 * Idempotent/safe to re-run: terms already holding a plain ID are skipped.
 */
class Normalise_Attribute_Swatch_Images {

	public $batch = 25;

	public $label = 'Normalise attribute taxonomies\' swatch "image" term meta';

	public $description = 'Woodmart stored the "image" term meta as a serialized [url, id] array, written directly to wp_termmeta outside of ACF, on pa_colour and 12 other attribute taxonomies. Converts every term still in that legacy shape to a plain attachment ID (ACF\'s native storage for its Image field), or clears empty legacy placeholders. Terms are found dynamically from the current database state — nothing here is a hardcoded ID list, so this runs correctly on any environment. Safe to re-run.';

	const TAXONOMIES = [
		'pa_colour',
		'pa_material',
		'pa_features',
		'pa_seat',
		'pa_size',
		'pa_arms',
		'pa_assembly',
		'pa_backrest',
		'pa_base-type',
		'pa_folding',
		'pa_height',
		'pa_indoor-outdoor',
		'pa_stackable',
	];

	/**
	 * Find terms across these taxonomies whose raw `image` meta is still the
	 * legacy serialized-array shape (populated or empty placeholder).
	 *
	 * @return array<int, int> Term IDs.
	 */
	public function items(): array {
		global $wpdb;

		$placeholders = implode( ', ', array_fill( 0, count( self::TAXONOMIES ), '%s' ) );

		$term_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT tm.term_id
				FROM {$wpdb->termmeta} tm
				INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id
				WHERE tt.taxonomy IN ({$placeholders})
				  AND tm.meta_key = 'image'
				  AND tm.meta_value LIKE 'a:%%'",
				self::TAXONOMIES
			)
		);

		return array_map( 'intval', $term_ids );
	}

	/**
	 * Normalise a single term's `image` meta.
	 *
	 * @param int $term_id Term ID (from items(), or a CSV/manual re-run).
	 * @return string Log line for the BatchPress run log.
	 */
	public function process( $term_id ) {
		$term_id    = (int) $term_id;
		$term       = get_term( $term_id );
		$term_label = ( $term && ! is_wp_error( $term ) )
			? sprintf( '"%s" (#%d, %s)', $term->name, $term_id, $term->taxonomy )
			: sprintf( 'term #%d', $term_id );

		$raw = get_term_meta( $term_id, 'image', true );

		if ( is_numeric( $raw ) ) {
			return "{$term_label}: already a plain attachment ID — skipped.";
		}

		if ( ! is_array( $raw ) ) {
			return "{$term_label}: unexpected value type (" . gettype( $raw ) . ') — skipped, needs manual review.';
		}

		$attachment_id = ( isset( $raw['id'] ) && is_numeric( $raw['id'] ) ) ? (int) $raw['id'] : 0;

		if ( ! $attachment_id ) {
			delete_term_meta( $term_id, 'image' );

			return "{$term_label}: cleared empty legacy placeholder.";
		}

		if ( ! get_post( $attachment_id ) ) {
			return "{$term_label}: legacy value pointed at attachment #{$attachment_id}, which no longer exists — skipped, needs manual review.";
		}

		update_term_meta( $term_id, 'image', $attachment_id );

		return "{$term_label}: normalised legacy array to plain attachment ID #{$attachment_id}.";
	}
}
