<?php
/**
 * Plugin Name:       ChairForce Old (Woodmart) Data Normalise
 * Description:       Temporary batch-processing tool for one-time normalisation of legacy Woodmart/JetEngine-era data during the theme rebuild. Bundles lambry/batchpress (Tools → BatchPress). Deactivate + delete once every job below has been run on an environment.
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Requires at least: 6.0
 * Author:            Chairforce rebuild
 * License:           GPL-2.0-or-later
 * Text Domain:       chairforce-woodmart-data-normalise
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CHAIRFORCE_DATA_NORMALISE_VERSION', '1.0.0' );
define( 'CHAIRFORCE_DATA_NORMALISE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CHAIRFORCE_DATA_NORMALISE_BASENAME', plugin_basename( __FILE__ ) );

$chairforce_data_normalise_autoload = CHAIRFORCE_DATA_NORMALISE_DIR . 'vendor/autoload.php';

if ( ! file_exists( $chairforce_data_normalise_autoload ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'ChairForce Old (Woodmart) Data Normalise: run "composer install" inside this plugin\'s folder before activating.', 'chairforce-woodmart-data-normalise' );
			echo '</p></div>';
		}
	);
	return;
}

require_once $chairforce_data_normalise_autoload;

/**
 * BatchPress is a bundled dependency, not a separately-installed plugin —
 * this is the only copy of it that should ever be active on this site.
 * Its own bootstrap self-registers hooks on require (gated by is_admin()),
 * so this is intentionally a plain require, not a class instantiation.
 */
if ( ! class_exists( 'Lambry\BatchPress\Init' ) ) {
	require_once CHAIRFORCE_DATA_NORMALISE_DIR . 'vendor/batchpress/batchpress.php';
}

/**
 * Register this plugin's one-time normalisation jobs with BatchPress.
 *
 * @param array<int, string> $jobs Fully-qualified job class names.
 * @return array<int, string>
 */
add_filter(
	'batchpress/jobs',
	function ( array $jobs ): array {
		$jobs[] = ChairforceDataNormalise\Jobs\Normalise_Attribute_Swatch_Images::class;

		return $jobs;
	}
);
