<?php
/**
 * Get Build URL path
 *
 * @param $file_name_with_sub_dir
 *
 * @return string
 */
function chairforce_get_build_url( $file_name_with_sub_dir ): string {
	return trailingslashit( get_stylesheet_directory_uri() ) . trailingslashit( 'build' ) . $file_name_with_sub_dir;
}


/**
 * Get Build Dir path
 *
 * @param $file_name_with_sub_dir
 *
 * @return string
 */
function chairforce_get_build_dir( $file_name_with_sub_dir ): string {
	return get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . $file_name_with_sub_dir;
}


/**
 * @param $post_id
 *
 * @return string
 * @noinspection PhpUnused
 */
function chairforce_get_post_thumbnail_mime_type( $post_id ): string {
	$mime_type = mime_content_type( get_attached_file( get_post_thumbnail_id( $post_id ) ) );

	return sanitize_key( str_replace( 'image', '', $mime_type ) );
}

/**
 * A Helper function for dump in readable format
 *
 * @param $var
 *
 * @return void
 * @noinspection PhpUnused
 */
function chairforce_dump( $var ): void {
	echo '<pre>';
	echo preg_replace( '(\d+\s=>)', '', var_export( $var, true ) );
	echo '</pre>';
}


if ( ! function_exists( 'chairforce_write_log' ) ) {
	/**
	 * A Helper function added to log anything for debugging
	 *
	 * @param $log
	 *
	 * @return void
	 */
	function chairforce_write_log( $log ): void {

		if ( is_array( $log ) || is_object( $log ) ) {
			$log = print_r( $log, true );
		}
		error_log( basename( __FILE__ ) . ' : ' . __LINE__ . ' : ' . $log . PHP_EOL, 3, trailingslashit( get_stylesheet_directory() ) . 'debug.log' );
	}
}

/** @noinspection PhpUnused */
/**
 * @param $array
 * @param $insert
 * @param $position
 *
 * @return array
 */
function chairforce_insert_array_at_position( $array, $insert, $position ): array {
	/*
	$array : The initial array I want to modify
	$insert : the new array I want to add, e.g. array('key' => 'value') or array('value')
	$position : the position where the new array will be inserted into. Please mind that arrays start at 0
	*/
	return array_slice( $array, 0, $position, true ) + $insert + array_slice( $array, $position, null, true );
}

/**
 * Get Taxonomy Terms for a post
 *
 * @param $taxonomy_slug
 * @param $post_id
 *
 * @return array
 */
function chairforce_get_taxonomy_terms_for_post( $taxonomy_slug, $post_id = null ): array {
	$post_id = $post_id ?: get_the_ID();

	$terms      = [];
	$taxonomies = [
		$taxonomy_slug
	];

	// Try to fetch Yoast Primary Terms:
	if ( function_exists( 'yoast_get_primary_term_id' ) ) :

		// Try to fetch
		foreach ( $taxonomies as $taxonomy ) :
			$term_id = yoast_get_primary_term_id( $taxonomy, $post_id );
			/**
			 * If no terms found, bail out
			 */
			$term = get_term_by( 'ID', $term_id, $taxonomy );
			if ( empty( $term ) || is_wp_error( $term ) ) {
				continue;
			}

			$terms[] = $term;

		endforeach;

	endif; // function_exists( 'yoast_get_primary_term_id' ).

	/**
	 * Fallback to Get Terms if we could not find it using yoast primary term
	 */
	if ( empty( $terms ) ) :
		foreach ( $taxonomies as $taxonomy ) :
			$taxonomy_terms = wp_get_post_terms( $post_id, [ $taxonomy ], [ 'number' => 1 ] // Limit to 1.
			);
			/**
			 * If no terms found, bail out
			 */
			if ( empty( $taxonomy_terms ) || is_wp_error( $taxonomy_terms ) ) {
				continue;
			}

			$terms = array_merge( $terms, $taxonomy_terms );
		endforeach;
	endif; // empty( $terms ).

	return $terms;


}

/**
 * Get the related grid options from the options page.
 *
 * @return array
 */
function chairforce_wpgb_get_related_grid_options() {
	$related_grid_options = get_field( 'wpgb_related_grids_configuration', 'options' );

	// Transform the array into the desired format
	$related_grid_settings = [];

	if ( ! empty( $related_grid_options ) && is_array( $related_grid_options ) ) {
		foreach ( $related_grid_options as $option ) {
			if (
				isset( $option['wpgb_grid_related'], $option['related_taxonomies'] ) &&
				! empty( $option['wpgb_grid_related'] ) &&
				is_array( $option['related_taxonomies'] )
			) {
				$grid_id = $option['wpgb_grid_related'];

				// Ensure each grid id is keyed properly with its related taxonomies
				$related_grid_settings[ $grid_id ] = $option['related_taxonomies'];
			}
		}
	}

	return $related_grid_settings;
}

/**
 * Get the count of posts that have the given terms and post types
 * in the given taxonomy.
 *
 * @param array $term_ids
 * @param array $post_types
 * @param string $taxonomy
 *
 * @return int
 */
function chairforce_get_post_count_for_terms_and_post_types( array $term_ids, array $post_types, string $taxonomy ): int {
	global $wpdb;

	$term_ids   = array_map( 'absint', array_filter( $term_ids ) );
	$post_types = array_filter( $post_types, 'sanitize_key' );

	if ( empty( $term_ids ) || empty( $post_types ) ) {
		return 0;
	}

	$term_placeholders = implode( ', ', array_fill( 0, count( $term_ids ), '%d' ) );
	$pt_placeholders   = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

	$sql = "
        SELECT COUNT(*)
        FROM $wpdb->posts p
        INNER JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id
        INNER JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE tt.term_id IN ($term_placeholders)
        AND tt.taxonomy = %s
        AND p.post_type IN ($pt_placeholders)
        AND p.post_status = 'publish'
    ";

	$params = array_merge( $term_ids, array( $taxonomy ), $post_types );

	$count = (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );

	return $count;
}


/**
 * Sanitize a space-separated HTML class list.
 *
 * @param string $classes Class names.
 * @return string
 */
function chairforce_sanitize_html_classes( string $classes ): string {
	$out = [];

	foreach ( preg_split( '/\s+/', trim( $classes ) ) as $class_name ) {
		if ( '' !== $class_name ) {
			$out[] = sanitize_html_class( $class_name );
		}
	}

	return implode( ' ', array_unique( $out ) );
}

/**
 * Lucide icon class names for programmatic core/button blocks.
 *
 * Mirrors src/js-admin/button-icons.js getIconClassNames().
 *
 * @param string $icon_slug     Lucide slug (e.g. shopping-cart).
 * @param string $icon_position left|right.
 * @return string
 */
function chairforce_get_button_icon_class_names( string $icon_slug, string $icon_position = 'left' ): string {

	if ( '' === trim( $icon_slug ) ) {
		return '';
	}

	$icon_slug      = sanitize_html_class( $icon_slug );
	$position_class = 'right' === $icon_position ? 'cf-icon-right' : 'cf-icon-left';

	return "cf-has-icon {$position_class} cf-icon-{$icon_slug}";
}

/**
 * Build an HTML attribute string from an associative array.
 *
 * @param array<string, scalar|null> $attributes Attribute names and values.
 * @return string
 */
function chairforce_build_html_attributes_string( array $attributes ): string {
	$parts = [];

	foreach ( $attributes as $name => $value ) {
		if ( null === $value || false === $value ) {
			continue;
		}

		$name = preg_replace( '/[^a-z0-9_\-]/i', '', (string) $name );

		if ( '' === $name ) {
			continue;
		}

		$parts[] = sprintf( '%s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
	}

	return implode( ' ', $parts );
}

/**
 * Render a buttons group via core/buttons + core/button (do_blocks).
 *
 * @param array<int, array<string, mixed>> $buttons Each item: [
 *   'label'           => (string) Button text (required),
 *   'url'             => (string) HREF (required when tag is "a"),
 *   'external'        => (bool)   Open in new tab (adds target and rel),
 *   'class'           => (string) Extra classes for the .wp-block-button wrapper,
 *   'element_class'   => (string) Extra classes for the a/button element,
 *   'style'           => (string) Block style class (e.g. is-style-ghost),
 *   'icon'            => (string) Lucide icon slug (optional),
 *   'icon_position'   => (string) left|right (optional, default left),
 *   'tag'             => (string) a|button (optional, default a),
 *   'html_attributes' => (array) Extra attributes for the a/button element,
 * ]
 * @param array<string, mixed>             $args Optional: [
 *   'wrapper_class' => (string) Extra classes for the buttons container,
 *   'default_style' => (string) Default style class when button style omitted,
 *   'default_icon'  => (string) Default Lucide icon slug,
 *   'layout'        => (array)  core/buttons layout attribute (e.g. flex + justifyContent),
 * ]
 *
 * @return string Rendered buttons markup.
 */
function chairforce_get_buttons_markup( array $buttons, array $args = [] ): string {

	if ( empty( $buttons ) || ! function_exists( 'do_blocks' ) ) {
		return '';
	}

	$wrapper_extra = chairforce_sanitize_html_classes( (string) ( $args['wrapper_class'] ?? '' ) );
	$default_style = chairforce_sanitize_html_classes( (string) ( $args['default_style'] ?? 'is-style-primary' ) );
	$layout        = isset( $args['layout'] ) && is_array( $args['layout'] ) ? $args['layout'] : null;

	$buttons_attrs = [];

	if ( null !== $layout ) {
		$buttons_attrs['layout'] = $layout;
	}

	if ( '' !== $wrapper_extra ) {
		$buttons_attrs['className'] = $wrapper_extra;
	}

	$buttons_json = ! empty( $buttons_attrs )
		? wp_json_encode( $buttons_attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES )
		: '';

	ob_start();

	if ( '' !== $buttons_json ) {
		printf( '<!-- wp:buttons %s -->', $buttons_json );
	} else {
		echo '<!-- wp:buttons -->';
	}

	$buttons_wrapper_classes = chairforce_sanitize_html_classes(
		trim( 'wp-block-buttons ' . $wrapper_extra )
	);

	printf(
		'<div class="%s">',
		esc_attr( $buttons_wrapper_classes )
	);

	foreach ( $buttons as $btn ) {
		$label    = isset( $btn['label'] ) ? (string) $btn['label'] : '';
		$url      = isset( $btn['url'] ) ? (string) $btn['url'] : '';
		$tag_name = isset( $btn['tag'] ) && 'button' === $btn['tag'] ? 'button' : 'a';

		if ( '' === $label ) {
			continue;
		}

		if ( 'a' === $tag_name && '' === $url ) {
			continue;
		}

		$external       = ! empty( $btn['external'] );
		$wrapper_class  = chairforce_sanitize_html_classes( (string) ( $btn['class'] ?? '' ) );
		$style_class      = chairforce_sanitize_html_classes( (string) ( $btn['style'] ?? $default_style ) );
		$element_class    = chairforce_sanitize_html_classes( (string) ( $btn['element_class'] ?? '' ) );
		$icon_slug        = isset( $btn['icon'] ) ? sanitize_key( (string) $btn['icon'] ) : sanitize_key( (string) ( $args['default_icon'] ?? '' ) );
		$icon_position    = isset( $btn['icon_position'] ) && 'right' === $btn['icon_position'] ? 'right' : 'left';
		$icon_classes     = chairforce_get_button_icon_class_names( $icon_slug, $icon_position );
		$wrapper_classes  = chairforce_sanitize_html_classes(
			trim( implode( ' ', array_filter( [ $style_class, $wrapper_class, $icon_classes ] ) ) )
		);

		$block_attrs = [
			'tagName' => $tag_name,
		];

		if ( '' !== $wrapper_classes ) {
			$block_attrs['className'] = $wrapper_classes;
		}

		if ( '' !== $icon_slug ) {
			$block_attrs['chairforceIcon'] = $icon_slug;

			if ( 'right' === $icon_position ) {
				$block_attrs['chairforceIconPosition'] = 'right';
			}
		}

		$element_attrs = [
			'class' => chairforce_sanitize_html_classes(
				trim( 'wp-block-button__link wp-element-button ' . $element_class )
			),
		];

		if ( 'button' === $tag_name ) {
			$element_attrs['type'] = 'button';
		} else {
			$element_attrs['href'] = $url;

			if ( $external ) {
				$element_attrs['target'] = '_blank';
				$element_attrs['rel']    = 'noopener noreferrer';
			}
		}

		if ( ! empty( $btn['html_attributes'] ) && is_array( $btn['html_attributes'] ) ) {
			foreach ( $btn['html_attributes'] as $attr_name => $attr_value ) {
				if ( null === $attr_value || false === $attr_value ) {
					continue;
				}

				if ( 'class' === $attr_name ) {
					$element_attrs['class'] = chairforce_sanitize_html_classes(
						trim( $element_attrs['class'] . ' ' . (string) $attr_value )
					);
					continue;
				}

				$element_attrs[ (string) $attr_name ] = $attr_value;
			}
		}

		$block_json = wp_json_encode( $block_attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		printf(
			'<!-- wp:button %1$s -->
<div class="wp-block-button %2$s">
	<%3$s %4$s>%5$s</%3$s>
</div>
<!-- /wp:button -->',
			$block_json,
			esc_attr( $wrapper_classes ),
			$tag_name,
			chairforce_build_html_attributes_string( $element_attrs ),
			wp_kses_post( $label )
		);
	}

	echo '</div><!-- /wp:buttons -->';

	return do_blocks( ob_get_clean() );
}


/**
 * Trim a file‑name to keep the list tidy.
 *
 * @param string $name Original file name.
 * @param int $limit Max visible characters (inclusive of the ellipsis).
 *
 * @return string
 */
function chairforce_trim_filename( string $name, int $limit = 40 ): string {
	if ( mb_strlen( $name ) <= $limit ) {
		return $name;
	}

	return mb_substr( $name, 0, $limit - 1 ) . '…';
}

/**
 * Render the PHP site header (announcement, search, nav shell).
 *
 * Frontend: chairforce/site-header block render.php (parts/header.html).
 */
function chairforce_render_site_header(): void {

	Chairforce\Site_Header::render();

}

/**
 * Whether the wishlist feature is enabled (master theme option).
 */
function chairforce_is_wishlist_enabled(): bool {
	if ( ! function_exists( 'get_field' ) ) {
		return false;
	}

	$value = get_field( 'wishlist_enabled', 'option' );

	if ( null === $value || '' === $value ) {
		return true;
	}

	return (bool) $value;
}

/**
 * Whether the wishlist heart should render on product loop cards.
 */
function chairforce_is_wishlist_loop_enabled(): bool {
	if ( ! chairforce_is_wishlist_enabled() ) {
		return false;
	}

	if ( ! function_exists( 'get_field' ) ) {
		return false;
	}

	$value = get_field( 'wishlist_loop_enabled', 'option' );

	if ( null === $value || '' === $value ) {
		return true;
	}

	return (bool) $value;
}

/**
 * Whether YITH Request a Quote is active and its button API is available.
 */
function chairforce_is_ywraq_available(): bool {
	return function_exists( 'yith_ywraq_render_button' )
		|| shortcode_exists( 'yith_ywraq_button_quote' );
}

/**
 * Whether a YITH "Add to quote" visibility option is enabled.
 *
 * @param string $context `woocommerce_blocks` (product cards / WC blocks),
 *                        `single_product` (single product + quick view),
 *                        `other_pages` (classic shop loop / taxonomy archives).
 */
function chairforce_is_ywraq_quote_button_enabled( string $context = 'woocommerce_blocks' ): bool {
	if ( ! chairforce_is_ywraq_available() ) {
		return false;
	}

	$option_map = [
		'woocommerce_blocks' => 'ywraq_show_btn_woocommerce_blocks',
		'single_product'     => 'ywraq_show_btn_single_page',
		'other_pages'        => 'ywraq_show_btn_other_pages',
	];

	$option_key = $option_map[ $context ] ?? $option_map['woocommerce_blocks'];

	return 'yes' === get_option( $option_key, 'no' );
}

/**
 * Whether Request a Quote should render in the quick view drawer.
 */
function chairforce_is_quick_view_request_quote_enabled(): bool {
	if ( ! function_exists( 'get_field' ) ) {
		return false;
	}

	$value = get_field( 'quick_view_request_quote_enabled', 'option' );

	if ( null === $value || '' === $value ) {
		return true;
	}

	return (bool) $value;
}

/**
 * Render YITH Add to Quote button markup for a product.
 *
 * Mirrors the `yith/yith-ywraq-button-quote` block output.
 *
 * @param int $product_id Product post ID; defaults to the current post in loop.
 * @return string Button HTML or empty string when unavailable.
 */
function chairforce_render_ywraq_button_quote_markup( int $product_id = 0 ): string {
	if ( ! chairforce_is_ywraq_available() ) {
		return '';
	}

	if ( ! $product_id ) {
		$product_id = get_the_ID();
	}

	$product_id = absint( $product_id );

	if ( ! $product_id ) {
		return '';
	}

	if ( is_callable( 'apply_shortcodes' ) ) {
		return (string) apply_shortcodes(
			'[yith_ywraq_button_quote product="' . $product_id . '"]'
		);
	}

	return (string) do_shortcode(
		'[yith_ywraq_button_quote product="' . $product_id . '"]'
	);
}

/**
 * Whether a block is rendering inside a WooCommerce product collection loop card.
 *
 * @param array<string, mixed> $block    Parsed block.
 * @param \WP_Block|null       $instance Block instance.
 */
function chairforce_is_product_collection_loop_block( array $block, ?\WP_Block $instance ): bool {

	if ( ! empty( $block['attrs']['isDescendentOfQueryLoop'] ) ) {
		return true;
	}

	if ( $instance instanceof \WP_Block && ! empty( $instance->context['query']['isProductCollectionBlock'] ) ) {
		return true;
	}

	return false;
}

/**
 * Product ID from a product collection block render context.
 *
 * @param \WP_Block|null $instance Block instance.
 */
function chairforce_get_product_collection_block_post_id( ?\WP_Block $instance ): int {

	if ( $instance instanceof \WP_Block && ! empty( $instance->context['postId'] ) ) {
		return absint( $instance->context['postId'] );
	}

	return 0;
}

/**
 * SAVE label markup for on-sale products in collection cards (matches Figma price row).
 *
 * @param int $product_id Product post ID.
 * @return string Empty when not on sale or percentage cannot be calculated.
 */
function chairforce_get_product_card_save_label_markup( int $product_id ): string {

	if ( ! function_exists( 'wc_get_product' ) ) {
		return '';
	}

	$product = wc_get_product( $product_id );

	if ( ! $product instanceof \WC_Product || ! $product->is_on_sale() ) {
		return '';
	}

	$regular = (float) $product->get_regular_price();
	$sale    = (float) $product->get_sale_price();

	if ( $product->is_type( 'variable' ) ) {
		$regular = (float) $product->get_variation_regular_price( 'min', true );
		$sale    = (float) $product->get_variation_sale_price( 'min', true );
	}

	if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
		return '';
	}

	$percent = (int) round( ( ( $regular - $sale ) / $regular ) * 100 );

	if ( $percent <= 0 ) {
		return '';
	}

	return sprintf(
		'<span class="cf-product-card__save">%s</span>',
		esc_html(
			sprintf(
				/* translators: %d: whole-number discount percentage */
				__( 'SAVE %d%%', 'chairforce' ),
				$percent
			)
		)
	);
}

/**
 * URL for guest wishlist clicks (WooCommerce My Account login).
 *
 * wc_get_page_permalink( 'myaccount' ) can resolve to the site home when the
 * WC page option is missing or misconfigured; /my-account/ is used as fallback.
 */
function chairforce_get_wishlist_login_url(): string {
	$home_url = trailingslashit( home_url( '/' ) );

	if ( function_exists( 'WC' ) ) {
		$page_id = absint( get_option( 'woocommerce_myaccount_page_id' ) );

		if ( $page_id > 0 ) {
			$permalink = get_permalink( $page_id );

			if ( is_string( $permalink ) && $permalink !== '' && trailingslashit( $permalink ) !== $home_url ) {
				return $permalink;
			}
		}
	}

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$permalink = wc_get_page_permalink( 'myaccount' );

		if ( is_string( $permalink ) && $permalink !== '' && trailingslashit( $permalink ) !== $home_url ) {
			return $permalink;
		}
	}

	$fallback = home_url( '/my-account/' );

	if ( trailingslashit( $fallback ) !== $home_url ) {
		return $fallback;
	}

	return wp_login_url();
}
