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
 * Migrates JetEngine single-blob wp_options rows to native ACF main Theme Options storage.
 */
class Normalise_Jetengine_Options_Pages {

	public $batch = 1;

	public $label = 'Normalise JetEngine options pages to native ACF storage';

	public $description = 'Reads legacy JetEngine option blobs and writes each field to native ACF wp_options rows on main Theme Options (options_{field_name}). Maps legacy blob keys to cf_* ACF field names. Per-field idempotent with write verification. Legacy blobs are kept for rollback. Safe to re-run on production.';

	/**
	 * ACF options post_id for main Theme Options.
	 */
	private const OPTIONS_POST_ID = 'options';

	/**
	 * Legacy blob => migration config.
	 *
	 * field_remaps: ACF field name => JetEngine blob key inside the legacy option array.
	 *
	 * @var array<string, array{legacy_blob: string, field_remaps: array<string, string>}>
	 */
	private const LEGACY_OPTION_BLOBS = [
		'hero-banner-home-page' => [
			'legacy_blob'  => 'hero-banner-home-page',
			'field_remaps' => [
				'cf_banner_title_1'        => 'banner_title_1',
				'cf_banner_sub_title_1'    => 'banner_sub_title_1',
				'cf_banner_button_text_1'  => 'banner_button_text_1',
				'cf_banner_button_link_1'  => 'banner_button_link_1',
				'cf_banner_image_1'        => 'banner_image_1',
				'cf_banner_title_2'        => 'banner_title_2',
				'cf_banner_sub_title_2'    => 'banner_sub_title_2',
				'cf_banner_button_text_2'  => 'banner_button_text_2',
				'cf_banner_button_link_2'  => 'banner_button_link_2',
				'cf_banner_image_2'        => 'banner_image_2',
			],
		],
		'delivery-information-for-product-page' => [
			'legacy_blob'  => 'delivery-information-for-product-page',
			'field_remaps' => [
				'cf_product_delivery_information' => 'add_delivery_information_for_product_page',
			],
		],
		'catalogue-links' => [
			'legacy_blob'  => 'catalogue-links',
			'field_remaps' => [
				'cf_catalogue_show_in_menu' => 'catalogue_link_for_menu',
				'cf_catalogue_home_url'     => 'catalogue_link_for_home_page',
				'cf_catalogue_footer_url'   => 'catalogue_link_for_footer',
				'cf_catalogue_blog_url'       => 'catalogue_link_for_',
			],
		],
	];

	/**
	 * @return string[] Legacy JetEngine option blob names.
	 */
	public function items(): array {
		return array_keys( self::LEGACY_OPTION_BLOBS );
	}

	/**
	 * @param string $legacy_blob_name Legacy JetEngine wp_options blob name.
	 */
	public function process( $legacy_blob_name ) {
		$legacy_blob_name = (string) $legacy_blob_name;

		if ( ! Meta_Normalise_Helper::acf_is_available() ) {
			return "{$legacy_blob_name}: ACF is not active — skipped.";
		}

		if ( ! isset( self::LEGACY_OPTION_BLOBS[ $legacy_blob_name ] ) ) {
			return "{$legacy_blob_name}: unknown legacy blob — skipped.";
		}

		$config      = self::LEGACY_OPTION_BLOBS[ $legacy_blob_name ];
		$legacy_blob = (string) $config['legacy_blob'];

		$blob = get_option( $legacy_blob, [] );
		if ( ! is_array( $blob ) || empty( $blob ) ) {
			return "{$legacy_blob_name}: legacy blob \"{$legacy_blob}\" empty or missing — skipped.";
		}

		$migrated = 0;
		$skipped  = 0;
		$failed   = 0;
		$lines    = [];

		foreach ( $config['field_remaps'] as $acf_name => $legacy_key ) {
			$field = function_exists( 'acf_get_field' ) ? acf_get_field( $acf_name ) : null;

			if ( ! is_array( $field ) || empty( $field['name'] ) ) {
				++$failed;
				$lines[] = "  {$acf_name}: ACF field not registered — skipped.";
				continue;
			}

			if ( Meta_Normalise_Helper::options_field_has_native_value( self::OPTIONS_POST_ID, $field ) ) {
				++$skipped;
				$lines[] = "  {$acf_name}: native value already present — skipped.";
				continue;
			}

			if ( ! array_key_exists( $legacy_key, $blob ) && ! array_key_exists( $acf_name, $blob ) ) {
				++$skipped;
				$lines[] = "  {$acf_name}: not in legacy blob \"{$legacy_blob}\" — skipped.";
				continue;
			}

			$stored = $blob[ $acf_name ] ?? $blob[ $legacy_key ];
			$value  = Meta_Normalise_Helper::legacy_option_value_for_acf( $stored, $field );

			if ( ( $field['type'] ?? '' ) === 'image' && (int) $value > 0 && ! get_post( (int) $value ) ) {
				++$failed;
				$lines[] = "  {$acf_name}: attachment #{$value} missing — not migrated.";
				continue;
			}

			$result = Meta_Normalise_Helper::write_acf_field_native( $acf_name, $value, self::OPTIONS_POST_ID, $field );

			if ( ! $result['ok'] ) {
				++$failed;
				$lines[] = "  {$acf_name}: write verification failed — needs manual review.";
				continue;
			}

			++$migrated;
			$byte_note = $result['bytes'] > 0 ? " ({$result['bytes']} bytes verified)" : '';
			$lines[]   = "  {$acf_name}: migrated from {$legacy_blob}.{$legacy_key}{$byte_note}.";
		}

		array_unshift(
			$lines,
			"{$legacy_blob_name}: migrated {$migrated} field(s), skipped {$skipped}, failed {$failed}. Legacy blob \"{$legacy_blob}\" kept."
		);

		return implode( "\n", $lines );
	}
}
