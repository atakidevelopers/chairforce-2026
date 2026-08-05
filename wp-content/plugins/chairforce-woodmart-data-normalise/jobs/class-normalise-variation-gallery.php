<?php

namespace ChairforceDataNormalise\Jobs;

use ChairforceDataNormalise\Meta_Normalise_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ChairforceDataNormalise\Jobs\Normalise_Variation_Gallery' ) ) {
	return;
}

/**
 * Clones Woodmart variation gallery meta to WooCommerce's native variation gallery prop.
 *
 * Woodmart stored additional variation images as a CSV in `wd_additional_variation_images_data`
 * on each `product_variation` post. WooCommerce's native variation gallery (canary feature,
 * `wc_feature_woocommerce_additional_variation_images_enabled`) reads from `_product_image_gallery`
 * on the variation post — the same CSV format.
 *
 * This job finds all variations that still have Woodmart data but no native gallery, validates
 * attachment IDs, and writes the CSV to `_product_image_gallery`. The original Woodmart meta is
 * preserved untouched for backward compatibility (theme code that still reads it continues working
 * during the transition).
 *
 * Idempotent: variations that already have `_product_image_gallery` set are skipped.
 * Safe to re-run.
 */
class Normalise_Variation_Gallery {

	public $batch = 25;

	public $label = 'Normalise product variation galleries (Woodmart → WC native)';

	public $description = 'Copies wd_additional_variation_images_data (Woodmart CSV) to _product_image_gallery (WooCommerce native variation gallery prop). Required for the WC block Product Gallery to recognise per-variation image sets. Original Woodmart meta is kept. Requires wc_feature_woocommerce_additional_variation_images_enabled = yes. Idempotent — safe to re-run.';

	private const SOURCE_META_KEY = 'wd_additional_variation_images_data';

	private const TARGET_META_KEY = '_product_image_gallery';

	/**
	 * Return all variation IDs that have Woodmart gallery data but no native gallery meta yet.
	 *
	 * @return array<int, int> Variation post IDs.
	 */
	public function items(): array {
		global $wpdb;

		$variation_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT pm.post_id
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p
					ON p.ID = pm.post_id
					AND p.post_type = 'product_variation'
				LEFT JOIN {$wpdb->postmeta} pm_native
					ON pm_native.post_id = pm.post_id
					AND pm_native.meta_key = %s
				WHERE pm.meta_key = %s
				  AND pm.meta_value != ''
				  AND pm_native.post_id IS NULL",
				self::TARGET_META_KEY,
				self::SOURCE_META_KEY
			)
		);

		return array_map( 'intval', $variation_ids );
	}

	/**
	 * Migrate a single variation's gallery meta.
	 *
	 * @param int $variation_id Variation post ID.
	 * @return string Log entry.
	 */
	public function process( $variation_id ) {
		$variation_id = (int) $variation_id;

		$variation = wc_get_product( $variation_id );
		$label     = $variation
			? sprintf( 'Variation #%d (parent #%d)', $variation_id, $variation->get_parent_id() )
			: sprintf( 'Variation #%d', $variation_id );

		// Idempotency guard: skip if native gallery was set between items() and process().
		$existing_native = get_post_meta( $variation_id, self::TARGET_META_KEY, true );
		if ( '' !== (string) $existing_native ) {
			return "{$label}: already has native gallery — skipped.";
		}

		$raw = get_post_meta( $variation_id, self::SOURCE_META_KEY, true );

		if ( ! Meta_Normalise_Helper::is_legacy_csv_string( $raw ) ) {
			return "{$label}: unexpected source value (" . gettype( $raw ) . ') — skipped, needs manual review.';
		}

		$parsed_ids = array_map( 'intval', Meta_Normalise_Helper::parse_csv_string( (string) $raw ) );
		$valid_ids  = Meta_Normalise_Helper::validate_attachment_ids( $parsed_ids );

		$parsed_count = count( $parsed_ids );
		$valid_count  = count( $valid_ids );
		$dropped      = $parsed_count - $valid_count;

		if ( empty( $valid_ids ) ) {
			return "{$label}: no valid attachment IDs in source (raw: {$raw}) — skipped.";
		}

		// Write as CSV — same format WC uses for _product_image_gallery on both
		// parent products and variations.
		update_post_meta( $variation_id, self::TARGET_META_KEY, implode( ',', $valid_ids ) );

		// Clear WC's cached variation data for the parent product so the block
		// gallery pre-renders the newly added images on the next page load.
		if ( $variation ) {
			wc_delete_product_transients( $variation->get_parent_id() );
		}

		$note = $dropped > 0 ? " ({$dropped} invalid attachment ID(s) dropped)" : '';

		return "{$label}: copied {$parsed_count} ID(s) → {$valid_count} valid attachment(s){$note}.";
	}
}
