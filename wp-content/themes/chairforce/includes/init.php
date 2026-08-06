<?php
/**
 * Composer Auto Loader
 */
if ( ! file_exists( get_stylesheet_directory() . '/vendor/autoload.php' ) ) {
	wp_die( 'vendor directory missing. Please execute `composer install` to create vendor directory.' );
}
require_once get_stylesheet_directory() . '/vendor/autoload.php';

/**
 * Get Theme Constants
 */
require_once get_stylesheet_directory() . '/includes/constants.php';

/**
 * Get Helper functions
 */
require_once get_stylesheet_directory() . '/includes/helper-functions.php';

/**
 * Product archive filter helpers (Phase 3f).
 */
require_once get_stylesheet_directory() . '/includes/product-filters-functions.php';
require_once get_stylesheet_directory() . '/includes/archive-shell-functions.php';
require_once get_stylesheet_directory() . '/includes/product-card-functions.php';
require_once get_stylesheet_directory() . '/includes/product-card-hooks.php';
require_once get_stylesheet_directory() . '/includes/woocommerce-breadcrumb-functions.php';
require_once get_stylesheet_directory() . '/includes/product-tabs-functions.php';
require_once get_stylesheet_directory() . '/includes/product-features-functions.php';
require_once get_stylesheet_directory() . '/includes/product-reviews-functions.php';
require_once get_stylesheet_directory() . '/includes/accordion-functions.php';
require_once get_stylesheet_directory() . '/includes/product-faqs-functions.php';

/**
 * Register CPTs
 */
require_once get_stylesheet_directory() . '/includes/register-cpt.php';

/**
 * Remove Comment System
 */
//require_once get_stylesheet_directory() . '/includes/disable-comments.php';

