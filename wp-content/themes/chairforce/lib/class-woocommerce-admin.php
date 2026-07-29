<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\WooCommerce_Admin' ) ) {
	return;
}

/**
 * WooCommerce admin customizations (product + variation edit screens).
 */
class WooCommerce_Admin {

	/**
	 * Legacy JetEngine meta key for spare-part product links.
	 */
	private const PARTS_META_KEY = 'parts';

	/**
	 * Woodmart variation gallery meta key (comma-separated attachment IDs).
	 */
	private const VARIATION_GALLERY_META_KEY = 'wd_additional_variation_images_data';

	/**
	 * POST array key for variation gallery hidden inputs (Woodmart-compatible).
	 */
	private const VARIATION_GALLERY_POST_KEY = 'wd_additional_variation_images';

	/**
	 * WooCommerce_Admin constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register admin hooks.
	 */
	private function register_hooks(): void {
		add_action( 'woocommerce_product_options_related', [ $this, 'render_parts_field' ] );
		add_action( 'woocommerce_admin_process_product_object', [ $this, 'save_parts_field' ] );
		add_action( 'woocommerce_variation_options', [ $this, 'render_variation_gallery_field' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ $this, 'save_variation_gallery_field' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_variation_gallery_assets' ] );
	}

	/**
	 * Render the Parts product search field in Product data → Linked products.
	 *
	 * @hooked woocommerce_product_options_related
	 */
	public function render_parts_field(): void {
		global $post;

		if ( ! $post instanceof \WP_Post || $post->post_type !== 'product' ) {
			return;
		}

		$part_ids = $this->get_product_parts_ids( (int) $post->ID );
		?>
		<p class="form-field <?php echo esc_attr( self::PARTS_META_KEY ); ?>_field">
			<label for="<?php echo esc_attr( self::PARTS_META_KEY ); ?>"><?php esc_html_e( 'Parts', 'chairforce' ); ?></label>
			<select
				class="wc-product-search"
				multiple="multiple"
				style="width: 50%;"
				id="<?php echo esc_attr( self::PARTS_META_KEY ); ?>"
				name="<?php echo esc_attr( self::PARTS_META_KEY ); ?>[]"
				data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'woocommerce' ); ?>"
				data-action="woocommerce_json_search_products"
				data-exclude="<?php echo esc_attr( (string) $post->ID ); ?>"
			>
				<?php foreach ( $part_ids as $part_id ) : ?>
					<?php
					$product = wc_get_product( $part_id );
					if ( ! $product ) {
						continue;
					}
					?>
					<option value="<?php echo esc_attr( (string) $part_id ); ?>" selected="selected">
						<?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
			if ( function_exists( 'wc_help_tip' ) ) {
				echo wc_help_tip( esc_html__( 'Spare part products linked to this product.', 'chairforce' ) );
			}
			?>
		</p>
		<?php
	}

	/**
	 * Save Parts selections to legacy JetEngine post meta.
	 *
	 * @param \WC_Product $product Product object.
	 *
	 * @hooked woocommerce_admin_process_product_object
	 */
	public function save_parts_field( \WC_Product $product ): void {
		if ( ! current_user_can( 'edit_post', $product->get_id() ) ) {
			return;
		}

		if ( isset( $_POST[ self::PARTS_META_KEY ] ) ) {
			$part_ids = array_map( 'absint', (array) wp_unslash( $_POST[ self::PARTS_META_KEY ] ) );
		} else {
			$part_ids = [];
		}

		$part_ids = array_values(
			array_filter(
				array_map(
					'strval',
					$part_ids
				)
			)
		);

		$product->update_meta_data( self::PARTS_META_KEY, $part_ids );
	}

	/**
	 * Render additional variation images inside each variation row.
	 *
	 * @param int      $loop           Variation loop index.
	 * @param array    $variation_data Variation data.
	 * @param \WP_Post $variation      Variation post object.
	 *
	 * @hooked woocommerce_variation_options
	 */
	public function render_variation_gallery_field( int $loop, array $variation_data, \WP_Post $variation ): void {
		unset( $loop, $variation_data );

		$attachment_ids = $this->get_variation_gallery_attachment_ids( (int) $variation->ID );
		?>
		<div
			class="cf-variation-gallery-wrapper"
			data-frame-title="<?php esc_attr_e( 'Add variation images', 'chairforce' ); ?>"
			data-frame-button="<?php esc_attr_e( 'Add to gallery', 'chairforce' ); ?>"
		>
			<h4><?php esc_html_e( 'Additional variation images', 'chairforce' ); ?></h4>

			<ul class="cf-variation-gallery-images">
				<?php foreach ( $attachment_ids as $attachment_id ) : ?>
					<?php
					$image = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
					if ( ! $image ) {
						continue;
					}
					?>
					<li class="image" data-attachment_id="<?php echo esc_attr( (string) $attachment_id ); ?>">
						<img
							src="<?php echo esc_url( $image[0] ); ?>"
							width="<?php echo esc_attr( (string) $image[1] ); ?>"
							height="<?php echo esc_attr( (string) $image[2] ); ?>"
							alt=""
						>
						<a href="#" class="delete cf-remove-variation-gallery-image" aria-label="<?php esc_attr_e( 'Remove image', 'chairforce' ); ?>">&times;</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<input
				type="hidden"
				class="cf-variation-gallery-ids"
				name="<?php echo esc_attr( self::VARIATION_GALLERY_POST_KEY ); ?>[<?php echo esc_attr( (string) $variation->ID ); ?>]"
				value="<?php echo esc_attr( implode( ',', $attachment_ids ) ); ?>"
			>

			<a href="#" class="button cf-add-variation-gallery-image">
				<?php esc_html_e( 'Add image', 'chairforce' ); ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Save additional variation images to legacy Woodmart post meta.
	 *
	 * @param int $variation_id Variation post ID.
	 * @param int $loop         Variation loop index.
	 *
	 * @hooked woocommerce_save_product_variation
	 */
	public function save_variation_gallery_field( int $variation_id, int $loop ): void {
		unset( $loop );

		if ( ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::VARIATION_GALLERY_POST_KEY ] ) ) {
			return;
		}

		$posted = wp_unslash( $_POST[ self::VARIATION_GALLERY_POST_KEY ] );

		if ( ! is_array( $posted ) || ! array_key_exists( $variation_id, $posted ) ) {
			return;
		}

		$ids = $this->normalize_variation_gallery_csv( (string) $posted[ $variation_id ] );

		if ( $ids === '' ) {
			delete_post_meta( $variation_id, self::VARIATION_GALLERY_META_KEY );
			return;
		}

		update_post_meta( $variation_id, self::VARIATION_GALLERY_META_KEY, $ids );
	}

	/**
	 * Enqueue variation gallery admin assets on variable product edit screens.
	 *
	 * @param string $hook_suffix Current admin screen hook suffix.
	 *
	 * @hooked admin_enqueue_scripts
	 */
	public function enqueue_variation_gallery_assets( string $hook_suffix ): void {
		global $post;

		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		if ( ! $post instanceof \WP_Post || $post->post_type !== 'product' ) {
			return;
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$script_path = 'assets/js/variation-gallery-admin.js';
		$style_path  = 'assets/css/variation-gallery-admin.css';
		$script_file = get_theme_file_path( $script_path );
		$style_file  = get_theme_file_path( $style_path );

		if ( ! file_exists( $script_file ) || ! file_exists( $style_file ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'chairforce-variation-gallery-admin',
			get_theme_file_uri( $script_path ),
			[ 'jquery', 'jquery-ui-sortable' ],
			(string) filemtime( $script_file ),
			true
		);
		wp_enqueue_style(
			'chairforce-variation-gallery-admin',
			get_theme_file_uri( $style_path ),
			[],
			(string) filemtime( $style_file )
		);
	}

	/**
	 * Get spare part product IDs from legacy post meta.
	 *
	 * @param int $post_id Product post ID.
	 * @return int[]
	 */
	private function get_product_parts_ids( int $post_id ): array {
		$value = get_post_meta( $post_id, self::PARTS_META_KEY, true );

		if ( ! is_array( $value ) ) {
			$value = maybe_unserialize( $value );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values(
			array_filter(
				array_map( 'absint', $value )
			)
		);
	}

	/**
	 * Get attachment IDs for a variation gallery from legacy post meta.
	 *
	 * @param int $variation_id Variation post ID.
	 * @return int[]
	 */
	private function get_variation_gallery_attachment_ids( int $variation_id ): array {
		return Product_Swatches::get_variation_gallery_attachment_ids( $variation_id );
	}

	/**
	 * Normalize a comma-separated attachment ID string for storage.
	 *
	 * @param string $raw Raw CSV value.
	 */
	private function normalize_variation_gallery_csv( string $raw ): string {
		$ids = array_values(
			array_filter(
				array_map( 'absint', explode( ',', $raw ) )
			)
		);

		if ( empty( $ids ) ) {
			return '';
		}

		return implode( ',', $ids );
	}

}
