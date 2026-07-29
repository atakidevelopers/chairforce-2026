<?php

namespace ChairforceDataNormalise\Jobs;

use ChairforceDataNormalise\Meta_Normalise_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ChairforceDataNormalise\Jobs\Normalise_Jetengine_Options_Pages' ) ) {
	return;
}

/**
 * Migrates JetEngine single-blob wp_options rows to native ACF options storage.
 */
class Normalise_Jetengine_Options_Pages {

	public $batch = 1;

	public $label = 'Normalise JetEngine options pages to native ACF storage';

	public $description = 'Reads legacy JetEngine option blobs and writes each field to native ACF wp_options rows under cf-opt-hero, cf-opt-wc, and cf-opt-catalogue. Per-field idempotent with write verification. Legacy blobs are kept for rollback. Safe to re-run on production.';

	/**
	 * Native ACF post_id => migration config.
	 *
	 * @var array<string, array{group: string, legacy_blob: string, field_remaps: array<string, string>}>
	 */
	private const OPTIONS_PAGES = [
		'cf-opt-hero' => [
			'group'        => 'group_hero_banner_home_page',
			'legacy_blob'  => 'hero-banner-home-page',
			'field_remaps' => [],
		],
		'cf-opt-wc' => [
			'group'        => 'group_delivery_information_product_page',
			'legacy_blob'  => 'delivery-information-for-product-page',
			'field_remaps' => [
				'content' => 'add_delivery_information_for_product_page',
			],
		],
		'cf-opt-catalogue' => [
			'group'        => 'group_catalogue_links',
			'legacy_blob'  => 'catalogue-links',
			'field_remaps' => [
				'show_in_menu' => 'catalogue_link_for_menu',
				'home_url'     => 'catalogue_link_for_home_page',
				'footer_url'   => 'catalogue_link_for_footer',
				'blog_url'     => 'catalogue_link_for_',
			],
		],
	];

	/**
	 * @return string[] Native ACF options page post_ids.
	 */
	public function items(): array {
		return array_keys( self::OPTIONS_PAGES );
	}

	/**
	 * @param string $post_id Native ACF options page post_id.
	 */
	public function process( $post_id ) {
		$post_id = (string) $post_id;

		if ( ! Meta_Normalise_Helper::acf_is_available() ) {
			return "{$post_id}: ACF is not active — skipped.";
		}

		if ( ! isset( self::OPTIONS_PAGES[ $post_id ] ) ) {
			return "{$post_id}: unknown options page — skipped.";
		}

		$config     = self::OPTIONS_PAGES[ $post_id ];
		$legacy_blob = (string) $config['legacy_blob'];

		$blob = get_option( $legacy_blob, [] );
		if ( ! is_array( $blob ) || empty( $blob ) ) {
			return "{$post_id}: legacy blob \"{$legacy_blob}\" empty or missing — skipped.";
		}

		$fields = Meta_Normalise_Helper::flatten_acf_fields(
			acf_get_fields( $config['group'] )
		);

		if ( empty( $fields ) ) {
			return "{$post_id}: no ACF fields found for group — skipped.";
		}

		$migrated = 0;
		$skipped  = 0;
		$failed   = 0;
		$lines    = [];

		foreach ( $fields as $field ) {
			$name       = (string) $field['name'];
			$legacy_key = $config['field_remaps'][ $name ] ?? $name;

			if ( Meta_Normalise_Helper::options_field_has_native_value( $post_id, $field ) ) {
				++$skipped;
				$lines[] = "  {$name}: native value already present — skipped.";
				continue;
			}

			if ( ! array_key_exists( $legacy_key, $blob ) && ! array_key_exists( $name, $blob ) ) {
				++$skipped;
				$lines[] = "  {$name}: not in legacy blob \"{$legacy_blob}\" — skipped.";
				continue;
			}

			$stored = $blob[ $name ] ?? $blob[ $legacy_key ];
			$value  = Meta_Normalise_Helper::legacy_option_value_for_acf( $stored, $field );

			if ( ( $field['type'] ?? '' ) === 'image' && (int) $value > 0 && ! get_post( (int) $value ) ) {
				++$failed;
				$lines[] = "  {$name}: attachment #{$value} missing — not migrated.";
				continue;
			}

			$result = Meta_Normalise_Helper::write_acf_field_native( $name, $value, $post_id, $field );

			if ( ! $result['ok'] ) {
				++$failed;
				$lines[] = "  {$name}: write verification failed — needs manual review.";
				continue;
			}

			++$migrated;
			$byte_note = $result['bytes'] > 0 ? " ({$result['bytes']} bytes verified)" : '';
			$lines[]   = "  {$name}: migrated from {$legacy_blob}.{$legacy_key}{$byte_note}.";
		}

		array_unshift(
			$lines,
			"{$post_id}: migrated {$migrated} field(s), skipped {$skipped}, failed {$failed}. Legacy blob \"{$legacy_blob}\" kept."
		);

		return implode( "\n", $lines );
	}
}
