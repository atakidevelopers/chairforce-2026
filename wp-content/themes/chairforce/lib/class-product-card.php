<?php

namespace Chairforce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Chairforce\Product_Card' ) ) {
	return;
}

/**
 * Renders one product card using WooCommerce's classic loop hooks
 * (`woocommerce_before_shop_loop_item`, etc.) instead of separate
 * block-editor sub-blocks.
 *
 * This is the single source of truth for "what a product card looks like" —
 * consumed by the `chairforce/product-card` dynamic block (inside
 * `woocommerce/product-template`) and, later, by any classic PHP loop
 * (Upsells, cross-sells) that still calls `wc_get_template_part( 'content', 'product' )`.
 * Extending the card (swatches, quick view, etc.) is a plain `add_action()`
 * on one of the four hooks below — it then appears everywhere this class
 * is used, with no per-context wiring.
 *
 * @see context/notes/product-grid-cards-and-load-more.md
 */
class Product_Card {

	/**
	 * WooCommerce's own default callbacks for the 5 classic loop-item hooks,
	 * exactly as registered in `wc-template-hooks.php`.
	 *
	 * WooCommerce Blocks' `ArchiveProductTemplatesCompatibility` compatibility
	 * shim globally `remove_action()`s all of these the moment it sees a
	 * `woocommerce/product-collection` block with `query.inherit: true` on a
	 * shop/category archive, and only ever re-adds them temporarily around
	 * its own recognized block names (`core/post-title`, and — only
	 * partially — the internal per-item loop wrapper). It has no knowledge
	 * of our custom `chairforce/product-card` block, so on a real archive
	 * page these come back permanently removed for the whole render unless
	 * we force them back on ourselves. See:
	 * `wp-content/plugins/woocommerce/src/Blocks/Templates/ArchiveProductTemplatesCompatibility.php`.
	 *
	 * @var array<int, array{0: string, 1: string, 2: int}> [hook, callback, priority]
	 */
	private const DEFAULT_HOOKS = [
		[ 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 ],
		[ 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 ],
		[ 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 ],
		[ 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 ],
		[ 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 ],
		[ 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 ],
		[ 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 ],
		[ 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 ],
	];

	/**
	 * Render one product card's inner markup (hooks only — no outer wrapper).
	 *
	 * Mirrors `wc-content/plugins/woocommerce/templates/content-product.php`
	 * minus its own `<li>` wrapper, since callers (our block, or a classic
	 * loop) provide their own wrapper element.
	 *
	 * @param \WC_Product $product Product to render.
	 * @return string Rendered HTML, or empty string if not visible.
	 */
	public static function render( \WC_Product $product ): string {
		if ( ! $product->is_visible() ) {
			return '';
		}

		// Avoid `global $post, $product;` here — it would collide with (and
		// clobber) this method's own `$product` parameter. Swap via
		// $GLOBALS[] directly instead.
		$original_post    = $GLOBALS['post'] ?? null;
		$original_product = $GLOBALS['product'] ?? null;

		$GLOBALS['post']    = get_post( $product->get_id() );
		$GLOBALS['product'] = $product;
		setup_postdata( $GLOBALS['post'] );

		$re_removed = self::force_attach_default_hooks();

		ob_start();

		/**
		 * Hook: woocommerce_before_shop_loop_item.
		 *
		 * @hooked woocommerce_template_loop_product_link_open - 10
		 */
		do_action( 'woocommerce_before_shop_loop_item' );

		/**
		 * Hook: woocommerce_before_shop_loop_item_title.
		 *
		 * @hooked woocommerce_show_product_loop_sale_flash - 10
		 * @hooked woocommerce_template_loop_product_thumbnail - 10
		 */
		do_action( 'woocommerce_before_shop_loop_item_title' );

		/**
		 * Hook: woocommerce_shop_loop_item_title.
		 *
		 * @hooked woocommerce_template_loop_product_title - 10
		 */
		do_action( 'woocommerce_shop_loop_item_title' );

		/**
		 * Hook: woocommerce_after_shop_loop_item_title.
		 *
		 * @hooked woocommerce_template_loop_rating - 5
		 * @hooked woocommerce_template_loop_price - 10
		 */
		do_action( 'woocommerce_after_shop_loop_item_title' );

		/**
		 * Hook: woocommerce_after_shop_loop_item.
		 *
		 * @hooked woocommerce_template_loop_product_link_close - 5
		 * @hooked woocommerce_template_loop_add_to_cart - 10
		 */
		do_action( 'woocommerce_after_shop_loop_item' );

		$html = ob_get_clean();

		self::restore_removed_hooks( $re_removed );

		$GLOBALS['post']    = $original_post;
		$GLOBALS['product'] = $original_product;

		if ( $original_post ) {
			setup_postdata( $original_post );
		} else {
			wp_reset_postdata();
		}

		return (string) $html;
	}

	/**
	 * Add back any of WooCommerce's default classic-hook callbacks that are
	 * currently missing (e.g. stripped by WC Blocks' archive compatibility
	 * shim), so our `do_action()` calls above render a normal card.
	 *
	 * @return array<int, array{0: string, 1: string, 2: int}> The subset that
	 *         we actually had to add back — i.e. that were missing before
	 *         this call, and so must be removed again afterwards to avoid
	 *         disturbing WC Blocks' own remove/restore bookkeeping for other
	 *         blocks (e.g. `core/post-title`) rendered elsewhere on the page.
	 */
	private static function force_attach_default_hooks(): array {
		$re_removed = [];

		foreach ( self::DEFAULT_HOOKS as $hook_data ) {
			[ $hook, $callback, $priority ] = $hook_data;

			if ( has_action( $hook, $callback ) ) {
				continue;
			}

			add_action( $hook, $callback, $priority );
			$re_removed[] = $hook_data;
		}

		return $re_removed;
	}

	/**
	 * Undo `force_attach_default_hooks()` — remove again only the callbacks
	 * we had to add back, restoring the ambient hook state exactly as we
	 * found it.
	 *
	 * @param array<int, array{0: string, 1: string, 2: int}> $re_removed From `force_attach_default_hooks()`.
	 */
	private static function restore_removed_hooks( array $re_removed ): void {
		foreach ( $re_removed as [ $hook, $callback, $priority ] ) {
			remove_action( $hook, $callback, $priority );
		}
	}

	/**
	 * Classic WooCommerce card classes for the wrapper element
	 * (`product`, `type-product`, stock status, sale, taxonomy classes, etc.)
	 * so existing WooCommerce/plugin CSS and JS selectors keep working.
	 *
	 * @param \WC_Product $product Product to render.
	 * @return string[] Class names (unescaped; caller is responsible for esc_attr()).
	 */
	public static function get_wrapper_classes( \WC_Product $product ): array {
		return wc_get_product_class( '', $product );
	}
}
