<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Wishlist' ) ) {
	return;
}

/**
 * Logged-in customer wishlist (custom DB table, single list per user).
 */
class Wishlist {

	/**
	 * Schema version for dbDelta upgrades.
	 */
	public const DB_VERSION = '1.0.0';

	/**
	 * Option key storing installed schema version.
	 */
	public const DB_VERSION_OPTION = 'chairforce_wishlist_db_version';

	/**
	 * Maximum product IDs accepted by the batch status REST route.
	 */
	public const STATUS_IDS_MAX = 50;

	/**
	 * Wishlist constructor.
	 */
	public function __construct() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		$this->register_hooks();
	}

	/**
	 * Register hooks.
	 */
	private function register_hooks(): void {
		add_action( 'after_setup_theme', [ $this, 'maybe_install_table' ], 20 );
	}

	/**
	 * Fully qualified wishlist items table name.
	 */
	public static function get_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'chairforce_wishlist_items';
	}

	/**
	 * Create or upgrade the wishlist table when the schema version changes.
	 */
	public function maybe_install_table(): void {
		$installed = get_option( self::DB_VERSION_OPTION, '' );

		if ( self::DB_VERSION === $installed ) {
			return;
		}

		self::install_table();
		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Run dbDelta for the wishlist items table.
	 */
	public static function install_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			product_id bigint(20) unsigned NOT NULL,
			date_added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_product (user_id, product_id),
			KEY user_id (user_id),
			KEY product_id (product_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Whether a product may be stored in a wishlist.
	 *
	 * @param int $product_id Product post ID.
	 */
	public static function is_valid_product( int $product_id ): bool {
		if ( $product_id <= 0 ) {
			return false;
		}

		if ( 'product' !== get_post_type( $product_id ) ) {
			return false;
		}

		if ( 'publish' !== get_post_status( $product_id ) ) {
			return false;
		}

		if ( ! function_exists( 'wc_get_product' ) ) {
			return false;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product || ! $product->is_visible() ) {
			return false;
		}

		return true;
	}

	/**
	 * @param int $user_id    WordPress user ID.
	 * @param int $product_id Product post ID.
	 */
	public static function is_in_wishlist( int $user_id, int $product_id ): bool {
		if ( $user_id <= 0 || $product_id <= 0 ) {
			return false;
		}

		global $wpdb;

		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND product_id = %d LIMIT 1",
				$user_id,
				$product_id
			)
		);

		return null !== $row_id;
	}

	/**
	 * @param int $user_id WordPress user ID.
	 */
	public static function get_count( int $user_id ): int {
		if ( $user_id <= 0 ) {
			return 0;
		}

		global $wpdb;

		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d",
				$user_id
			)
		);

		return max( 0, (int) $count );
	}

	/**
	 * @param int $user_id WordPress user ID.
	 * @return int[] Product IDs, newest first.
	 */
	public static function get_product_ids( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return [];
		}

		global $wpdb;

		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT product_id FROM {$table} WHERE user_id = %d ORDER BY date_added DESC, id DESC",
				$user_id
			)
		);

		if ( ! is_array( $ids ) ) {
			return [];
		}

		return array_values(
			array_map(
				static function ( $id ) {
					return absint( $id );
				},
				$ids
			)
		);
	}

	/**
	 * @param int   $user_id     WordPress user ID.
	 * @param int[] $product_ids Product post IDs to check.
	 * @return array<int, bool> Map of product ID => in wishlist.
	 */
	public static function get_status_map( int $user_id, array $product_ids ): array {
		$product_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $product_ids ),
					static function ( int $id ): bool {
						return $id > 0;
					}
				)
			)
		);

		$status = [];

		foreach ( $product_ids as $product_id ) {
			$status[ $product_id ] = false;
		}

		if ( $user_id <= 0 || [] === $product_ids ) {
			return $status;
		}

		global $wpdb;

		$table   = self::get_table_name();
		$placeholders = implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT product_id FROM {$table} WHERE user_id = %d AND product_id IN ({$placeholders})",
				array_merge( [ $user_id ], $product_ids )
			)
		);

		if ( ! is_array( $found ) ) {
			return $status;
		}

		foreach ( $found as $product_id ) {
			$status[ absint( $product_id ) ] = true;
		}

		return $status;
	}

	/**
	 * @param int $user_id    WordPress user ID.
	 * @param int $product_id Product post ID.
	 */
	public static function add_item( int $user_id, int $product_id ): bool {
		if ( $user_id <= 0 || ! self::is_valid_product( $product_id ) ) {
			return false;
		}

		if ( self::is_in_wishlist( $user_id, $product_id ) ) {
			return true;
		}

		global $wpdb;

		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert(
			$table,
			[
				'user_id'    => $user_id,
				'product_id' => $product_id,
				'date_added' => current_time( 'mysql', true ),
			],
			[ '%d', '%d', '%s' ]
		);

		return false !== $inserted;
	}

	/**
	 * @param int $user_id    WordPress user ID.
	 * @param int $product_id Product post ID.
	 */
	public static function remove_item( int $user_id, int $product_id ): bool {
		if ( $user_id <= 0 || $product_id <= 0 ) {
			return false;
		}

		global $wpdb;

		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			$table,
			[
				'user_id'    => $user_id,
				'product_id' => $product_id,
			],
			[ '%d', '%d' ]
		);

		return false !== $deleted && $deleted > 0;
	}

	/**
	 * @param int $user_id    WordPress user ID.
	 * @param int $product_id Product post ID.
	 * @return array{in_wishlist: bool, count: int}
	 */
	public static function toggle_item( int $user_id, int $product_id ): array {
		if ( self::is_in_wishlist( $user_id, $product_id ) ) {
			self::remove_item( $user_id, $product_id );

			return [
				'in_wishlist' => false,
				'count'       => self::get_count( $user_id ),
			];
		}

		self::add_item( $user_id, $product_id );

		return [
			'in_wishlist' => self::is_in_wishlist( $user_id, $product_id ),
			'count'       => self::get_count( $user_id ),
		];
	}
}
