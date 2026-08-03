<?php
/**
 * Product archive shell helpers.
 *
 * Filter/sort refresh fetches the full catalog page in JS and swaps
 * `.cf-shop-archive-shell` from the response so plugin hooks (e.g. YITH)
 * run through the normal front-end request lifecycle.
 *
 * @package Chairforce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_theme_file_path( 'includes/load-more-functions.php' );
