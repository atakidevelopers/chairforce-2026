<?php

namespace ChairforceDataNormalise;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ChairforceDataNormalise\Meta_Normalise_Helper' ) ) {
	return;
}

/**
 * Shared helpers for JetEngine → ACF meta normalisation jobs.
 */
class Meta_Normalise_Helper {

	/**
	 * Whether ACF is available for update_field().
	 */
	public static function acf_is_available(): bool {
		return function_exists( 'update_field' ) && function_exists( 'acf_get_fields' );
	}

	/**
	 * Legacy JetEngine gallery values are CSV strings; ACF stores serialized arrays.
	 *
	 * @param mixed $raw Raw meta value.
	 */
	public static function is_legacy_csv_string( $raw ): bool {
		if ( ! is_string( $raw ) || $raw === '' ) {
			return false;
		}

		if ( str_starts_with( $raw, 'a:' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Parse a comma-separated string into trimmed non-empty parts.
	 *
	 * @return string[]
	 */
	public static function parse_csv_string( string $raw ): array {
		return array_values(
			array_filter(
				array_map( 'trim', explode( ',', $raw ) )
			)
		);
	}

	/**
	 * Validate attachment IDs; return only IDs that exist and are attachments.
	 *
	 * @param int[] $ids Attachment IDs.
	 * @return int[]
	 */
	public static function validate_attachment_ids( array $ids ): array {
		$valid = [];

		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id <= 0 ) {
				continue;
			}

			$post = get_post( $id );
			if ( ! $post || $post->post_type !== 'attachment' ) {
				continue;
			}

			$valid[] = $id;
		}

		return $valid;
	}

	/**
	 * Resolve an attachment ID from a stored media URL.
	 *
	 * JetEngine gallery_images may store production URLs on a local clone.
	 *
	 * @param string $url Media URL.
	 */
	public static function resolve_attachment_id_from_url( string $url ): int {
		$attachment_id = attachment_url_to_postid( $url );
		if ( $attachment_id ) {
			return (int) $attachment_id;
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! is_string( $path ) || $path === '' ) {
			return 0;
		}

		$filename = basename( $path );
		if ( $filename === '' ) {
			return 0;
		}

		global $wpdb;

		$attachment_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s LIMIT 1",
				'%' . $wpdb->esc_like( $filename ) . '%'
			)
		);

		return $attachment_id;
	}

	/**
	 * Resolve CSV attachment IDs from a legacy URL list.
	 *
	 * @return array{ids: int[], lines: string[]}
	 */
	public static function attachment_ids_from_url_csv( string $raw ): array {
		$urls  = self::parse_csv_string( $raw );
		$ids   = [];
		$lines = [];

		foreach ( $urls as $url ) {
			if ( ! str_contains( $url, 'http' ) ) {
				$lines[] = "SKIP (not a URL): {$url}";
				continue;
			}

			$attachment_id = self::resolve_attachment_id_from_url( $url );
			if ( ! $attachment_id ) {
				$lines[] = "NOT FOUND: {$url}";
				continue;
			}

			$method = attachment_url_to_postid( $url ) ? 'url' : 'filename';
			$ids[]  = $attachment_id;
			$lines[] = "OK ({$method}): {$url} → #{$attachment_id}";
		}

		return [
			'ids'   => $ids,
			'lines' => $lines,
		];
	}

	/**
	 * Flatten ACF field definitions, skipping layout-only fields.
	 *
	 * @param array<int, array<string, mixed>>|false $fields ACF fields.
	 * @return array<int, array<string, mixed>>
	 */
	public static function flatten_acf_fields( $fields ): array {
		if ( ! is_array( $fields ) ) {
			return [];
		}

		$flat = [];

		foreach ( $fields as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$type = $field['type'] ?? '';
			if ( in_array( $type, [ 'tab', 'accordion', 'message' ], true ) ) {
				continue;
			}

			if ( $type === 'group' && ! empty( $field['sub_fields'] ) ) {
				$flat = array_merge( $flat, self::flatten_acf_fields( $field['sub_fields'] ) );
				continue;
			}

			if ( empty( $field['name'] ) ) {
				continue;
			}

			$flat[] = $field;
		}

		return $flat;
	}

	/**
	 * Resolve a legacy blob key for an ACF field name.
	 *
	 * @param string               $slug       Options page slug.
	 * @param array<string, mixed> $field      ACF field config.
	 * @param array<string, array<string, string>> $remaps Slug => [ acf_name => legacy_key ].
	 */
	public static function legacy_option_field_key( string $slug, array $field, array $remaps ): string {
		$name = (string) ( $field['name'] ?? '' );

		return $remaps[ $slug ][ $name ] ?? $name;
	}

	/**
	 * Convert a legacy blob value to an ACF-ready value.
	 *
	 * @param mixed                $stored Legacy value.
	 * @param array<string, mixed> $field  ACF field config.
	 * @return mixed
	 */
	public static function legacy_option_value_for_acf( $stored, array $field ) {
		$name = $field['name'] ?? '';
		$type = $field['type'] ?? '';

		if ( $type === 'true_false' || $name === 'show_in_menu' ) {
			return (int) filter_var( $stored, FILTER_VALIDATE_BOOLEAN );
		}

		if ( $type === 'image' && $stored !== '' && $stored !== null ) {
			return (int) $stored;
		}

		return $stored;
	}

	/**
	 * Detect whether a single ACF options field already has a native stored value.
	 *
	 * Reference-only rows (value empty from a partial/failed run) return false so the
	 * field is migrated again on re-run.
	 *
	 * @param string               $slug  Options page post_id.
	 * @param array<string, mixed> $field ACF field config.
	 */
	public static function options_field_has_native_value( string $slug, array $field ): bool {
		$name = (string) ( $field['name'] ?? '' );
		if ( $name === '' ) {
			return false;
		}

		$option_name = "{$slug}_{$name}";

		if ( ! self::option_exists( $option_name ) ) {
			return false;
		}

		$native = get_option( $option_name );

		$type = $field['type'] ?? '';

		if ( $type === 'true_false' || $name === 'show_in_menu' ) {
			return self::option_exists( $option_name );
		}

		if ( $type === 'image' ) {
			return (int) $native > 0;
		}

		if ( is_string( $native ) ) {
			return strlen( $native ) > 0;
		}

		return $native !== false && $native !== null && $native !== '';
	}

	/**
	 * WordPress get_option() returns false when missing; option_exists checks the table.
	 *
	 * @param string $option_name wp_options.option_name.
	 */
	private static function option_exists( string $option_name ): bool {
		global $wpdb;

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_id FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				$option_name
			)
		);
	}

	/**
	 * @deprecated Use options_field_has_native_value() per field instead.
	 */
	public static function options_page_has_native_acf_storage( string $slug ): bool {
		global $wpdb;

		$like = $wpdb->esc_like( $slug ) . '\_%';

		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s ESCAPE '\\\\'",
				$like
			)
		);

		return $count > 0;
	}

	/**
	 * Write ACF field value + reference meta without triggering acf/update_value bridges.
	 *
	 * @param string               $field_name ACF field name.
	 * @param mixed                $value      Value in ACF-native shape.
	 * @param int|string           $post_id    Post ID, term_{id}, or options slug.
	 * @param array<string, mixed> $field      ACF field config (required for options pages).
	 * @return array{ok: bool, bytes: int}    Whether read-back verification passed.
	 */
	public static function write_acf_field_native( string $field_name, $value, $post_id, array $field = [] ): array {
		if ( ! function_exists( 'acf_update_metadata' ) ) {
			return [
				'ok'    => false,
				'bytes' => 0,
			];
		}

		if ( is_string( $value ) && is_string( $post_id ) && ! str_starts_with( $post_id, 'term_' ) && ! is_numeric( $post_id ) ) {
			$value = wp_slash( $value );
		}

		if ( ( $field['type'] ?? '' ) === 'true_false' ) {
			$value = $value ? 1 : 0;
		}

		$field_key = (string) ( $field['key'] ?? '' );
		if ( $field_key === '' && function_exists( 'acf_get_field' ) ) {
			$resolved = acf_get_field( $field_name );
			if ( is_array( $resolved ) ) {
				$field_key = (string) ( $resolved['key'] ?? '' );
				$field     = array_merge( $field, $resolved );
			}
		}

		if ( ! empty( $field ) && function_exists( 'acf_update_metadata_by_field' ) ) {
			acf_update_metadata_by_field( $post_id, $field, $value );
			acf_update_metadata_by_field( $post_id, $field, $field_key, true );
		} else {
			acf_update_metadata( $post_id, $field_name, $value );
			if ( $field_key !== '' ) {
				acf_update_metadata( $post_id, $field_name, $field_key, true );
			}
		}

		$bytes = 0;
		$ok    = true;

		if ( is_string( $post_id ) && ! str_starts_with( $post_id, 'term_' ) && ! is_numeric( $post_id ) ) {
			$option_name = "{$post_id}_{$field_name}";
			$stored      = get_option( $option_name );

			if ( ( $field['type'] ?? '' ) === 'true_false' ) {
				$ok    = self::option_exists( $option_name );
				$bytes = $ok ? 1 : 0;
			} elseif ( is_string( $stored ) ) {
				$bytes = strlen( $stored );
				$ok    = $bytes > 0;
			} elseif ( ( $field['type'] ?? '' ) === 'image' ) {
				$bytes = (int) $stored;
				$ok    = $bytes > 0;
			} else {
				$ok = self::option_exists( $option_name );
			}
		}

		return [
			'ok'    => $ok,
			'bytes' => $bytes,
		];
	}
}
