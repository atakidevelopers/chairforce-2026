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
 * Render a "button bar" for given links.
 *
 * @param array $buttons Each item: [
 *   'label'    => (string) Button text (required),
 *   'url'      => (string) HREF (required),
 *   'external' => (bool)   Open in new tab (adds target and rel),
 *   'class'    => (string) Extra classes for the button wrapper (optional),
 *   'style'    => (string) Style class, defaults to 'is-style-primary-right-icon' (optional),
 *   'icon'     => (string) Font Awesome icon class (e.g., 'fa-eye', 'fa-download') (optional),
 * ]
 * @param array $args Optional: [
 *   'wrapper_class' => (string) extra classes for the buttons container,
 *   'default_style' => (string) default style class (overrides the hard default),
 *   'default_icon'  => (string) default Font Awesome icon class (optional),
 * ]
 *
 * @return string Gutenberg buttons markup (rendered via do_blocks).
 */
function chairforce_get_buttons_markup( array $buttons, array $args = [] ): string {

	if ( empty( $buttons ) ) {
		return '';
	}
	$wrapper_extra = trim( (string) ( $args['wrapper_class'] ?? '' ) );
	$default_style = trim( (string) ( $args['default_style'] ?? 'is-style-fill' ) );

	$sanitize_classes = static function ( string $classes ): string {
		$out = [];
		foreach ( preg_split( '/\s+/', trim( $classes ) ) as $c ) {
			if ( $c !== '' ) {
				$out[] = sanitize_html_class( $c );
			}
		}

		return implode( ' ', array_unique( $out ) );
	};

	$wrapper_extra_attr = $sanitize_classes( $wrapper_extra );

	ob_start();

	// Container (optionally add className to the block JSON if wrapper classes provided)
	if ( $wrapper_extra_attr !== '' ) {
		printf(
			'<!-- wp:buttons {"className":"%1$s"} --><div class="wp-block-buttons %1$s">',
			esc_attr( $wrapper_extra_attr )
		);
	} else {
		echo '<!-- wp:buttons --><div class="wp-block-buttons">';
	}

	foreach ( $buttons as $btn ) {
		$label = isset( $btn['label'] ) ? (string) $btn['label'] : '';
		$url   = isset( $btn['url'] ) ? (string) $btn['url'] : '';
		if ( $label === '' || $url === '' ) {
			continue;
		}

		$external    = ! empty( $btn['external'] );
		$extra_class = $sanitize_classes( (string) ( $btn['class'] ?? '' ) );
		$style_class = $sanitize_classes( (string) ( $btn['style'] ?? $default_style ) );
		$button_cls  = trim( $style_class . ( $extra_class ? ' ' . $extra_class : '' ) );

		// Build rel attribute for external links
		$rel_tokens = [];
		if ( $external ) {
			$rel_tokens[] = 'noopener';
			$rel_tokens[] = 'noreferrer';
		}
		$rel_attr = $rel_tokens ? ' rel="' . esc_attr( implode( ' ', array_unique( $rel_tokens ) ) ) . '"' : '';
		$target   = $external ? ' target="_blank"' : '';

		// Handle Font Awesome icon
		$icon_class = isset( $btn['icon'] ) ? (string) $btn['icon'] : ( $args['default_icon'] ?? '' );
		$label_html = wp_kses_post( $label );
		if ( $icon_class ) {
			$icon_class = sanitize_html_class( $icon_class );
			$label_html = sprintf( '%s <i class="fa-sharp fa-light %s"></i>', $label_html, $icon_class );
		}

		printf(
			'<!-- wp:button {"className":"%1$s"} -->
					<div class="wp-block-button %1$s">
						<a class="wp-block-button__link wp-element-button" href="%2$s"%3$s%4$s>%5$s</a>
					</div>
					<!-- /wp:button -->',
			esc_attr( $button_cls ),
			esc_url( $url ),
			$target,
			$rel_attr,
			$label_html
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
 * Frontend: chairforce/editor-placeholder block render.php (modifier: header).
 */
function chairforce_render_site_header(): void {

	Chairforce\Site_Header::render();

}
