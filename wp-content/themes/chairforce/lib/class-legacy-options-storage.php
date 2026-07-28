<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Legacy_Options_Storage' ) ) {
	return;
}

/**
 * Bridges ACF options-page fields to JetEngine's single-blob wp_options storage.
 *
 * JetEngine options pages store all fields in one serialized option row keyed by
 * the page slug (e.g. option_name `hero-banner-home-page`). ACF expects per-field
 * storage — these filters keep reads/writes on the legacy blob keys.
 */
class Legacy_Options_Storage {

	/**
	 * @var array<string, array<string, string>> Option slug => field name remaps (new => legacy).
	 */
	private array $field_key_remaps = [
		'catalogue-links' => [
			'catalogue_link_for_blog_page' => 'catalogue_link_for_',
		],
	];

	/**
	 * Legacy_Options_Storage constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register ACF value filters for each legacy options page.
	 */
	private function register_hooks(): void {
		$option_slugs = [
			'hero-banner-home-page',
			'delivery-information-for-product-page',
			'catalogue-links',
		];

		foreach ( $option_slugs as $slug ) {
			add_filter(
				"acf/load_value",
				function ( $value, $post_id, $field ) use ( $slug ) {
					return $this->load_legacy_option_value( $value, $post_id, $field, $slug );
				},
				10,
				3
			);

			add_filter(
				"acf/update_value",
				function ( $value, $post_id, $field ) use ( $slug ) {
					return $this->update_legacy_option_value( $value, $post_id, $field, $slug );
				},
				10,
				3
			);
		}
	}

	/**
	 * Load a field value from a JetEngine options blob.
	 *
	 * @param mixed  $value   Current value.
	 * @param string $post_id ACF options post ID.
	 * @param array  $field   ACF field array.
	 * @param string $slug    Legacy option slug.
	 * @return mixed
	 */
	private function load_legacy_option_value( $value, $post_id, $field, string $slug ) {
		if ( $post_id !== $slug || empty( $field['name'] ) ) {
			return $value;
		}

		$data  = $this->get_legacy_option_blob( $slug );
		$name  = (string) $field['name'];
		$legacy_key = $this->get_legacy_field_key( $slug, $name );

		if ( ! array_key_exists( $legacy_key, $data ) && ! array_key_exists( $name, $data ) ) {
			return $value;
		}

		$stored = $data[ $name ] ?? $data[ $legacy_key ];

		return $this->format_legacy_value_for_acf( $stored, $field );
	}

	/**
	 * Save a field value into a JetEngine options blob.
	 *
	 * Returning false prevents ACF from writing a separate wp_options row.
	 *
	 * @param mixed  $value   Submitted value.
	 * @param string $post_id ACF options post ID.
	 * @param array  $field   ACF field array.
	 * @param string $slug    Legacy option slug.
	 * @return false|mixed
	 */
	private function update_legacy_option_value( $value, $post_id, $field, string $slug ) {
		if ( $post_id !== $slug || empty( $field['name'] ) ) {
			return $value;
		}

		$data = $this->get_legacy_option_blob( $slug );
		$name = (string) $field['name'];

		$data[ $name ] = $this->format_acf_value_for_legacy( $value, $field );

		$legacy_key = $this->get_legacy_field_key( $slug, $name );
		if ( $legacy_key !== $name ) {
			unset( $data[ $legacy_key ] );
		}

		update_option( $slug, $data, false );

		return false;
	}

	/**
	 * Get the legacy JetEngine options blob.
	 *
	 * @param string $slug Option slug.
	 * @return array<string, mixed>
	 */
	private function get_legacy_option_blob( string $slug ): array {
		$data = get_option( $slug, [] );

		return is_array( $data ) ? $data : [];
	}

	/**
	 * Resolve a legacy storage key for renamed fields.
	 *
	 * @param string $slug       Option slug.
	 * @param string $field_name ACF field name.
	 */
	private function get_legacy_field_key( string $slug, string $field_name ): string {
		return $this->field_key_remaps[ $slug ][ $field_name ] ?? $field_name;
	}

	/**
	 * Convert legacy stored values to ACF-friendly shapes.
	 *
	 * @param mixed $stored Legacy value.
	 * @param array $field  ACF field config.
	 * @return mixed
	 */
	private function format_legacy_value_for_acf( $stored, array $field ) {
		$name = $field['name'] ?? '';

		if ( ( $field['type'] ?? '' ) === 'true_false' || $name === 'catalogue_link_for_menu' ) {
			return filter_var( $stored, FILTER_VALIDATE_BOOLEAN );
		}

		if ( $field['type'] === 'image' && $stored !== '' && $stored !== null ) {
			return (int) $stored;
		}

		return $stored;
	}

	/**
	 * Convert ACF submitted values back to JetEngine storage shapes.
	 *
	 * @param mixed $value ACF value.
	 * @param array $field ACF field config.
	 * @return mixed
	 */
	private function format_acf_value_for_legacy( $value, array $field ) {
		if ( $field['type'] === 'true_false' ) {
			return $value ? 'true' : 'false';
		}

		if ( $field['type'] === 'image' && $value ) {
			return (string) (int) $value;
		}

		return $value;
	}
}
