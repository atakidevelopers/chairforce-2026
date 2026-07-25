<?php

namespace Chairforce;
// exit if file is called directly
use ZipArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// if class already defined, bail out
if ( class_exists( 'Chairforce\Plugins_Manager' ) ) {
	return;
}


/**
 * This class will require the dependency plugins
 * Register the required plugins for this theme.
 *
 * The variables passed to the `tgmpa()` function should be:
 * - an array of plugin arrays;
 * - optionally a configuration array.
 * If you are not changing anything in the configuration array, you can remove the array and remove the
 * variable from the function call: `tgmpa( $plugins );`.
 * In that case, the TGMPA default settings will be used.
 *
 * @package    Chairforce
 * @subpackage Chairforce/lib
 */
class Plugins_Manager {


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.0.1
	 */
	public function __construct() {

		$this->register_hooks();

	}

	/**
	 * Register required hooks
	 */
	public function register_hooks(): void {

		add_action( 'tgmpa_register', [ $this, 'register_required_plugins' ] );
		add_action( 'wp_footer', [ $this, 'maybe_generate_required_plugins_array' ] );
	}

	/**
	 * The variables passed to the `tgmpa()` function should be:
	 * - an array of plugin arrays;
	 * - optionally a configuration array.
	 * If you are not changing anything in the configuration array, you can remove the array and remove the
	 * variable from the function call: `tgmpa( $plugins );`.
	 * In that case, the TGMPA default settings will be used.
	 *
	 * This function is hooked into `tgmpa_register`, which is fired on the WP `init` action on priority 10.
	 *
	 * @hooked tgmpa_register
	 */
	public function register_required_plugins(): void {

		if ( ! function_exists( 'tgmpa' ) ) {
			return;
		}
		/**
		 * Initialize tgmpa for theme
		 */
		tgmpa( $this->get_plugins(), $this->get_config() );

	}

	/**
	 * Get the plugins array
	 *
	 * @return array
	 */
	public function get_plugins(): array {


		/**
		 * The use of GET query strings:
		 * `tgmpa-generate-config` will generate the query string
		 * `show-source` will add the 'source' array prop for plugins that are not Wp Repo plugins
		 * `create-zip` will also create the zip for the plugins that are private or premium plugins
		 */

		// Try accessing http://example.test/?tgmpa-generate-config=1&show-source=1&create-zip=1


		$plugins = [

			[
				'name'    => 'Advanced Custom Fields PRO',
				'slug'    => 'advanced-custom-fields-pro',
				'version' => '6.8.6',
				'source'  => 'advanced-custom-fields-pro.zip',
			],

		];

		return apply_filters( 'chairforce_tgmpa_plugins', $plugins );
	}

	/**
	 * Get the configuration for the TGMPA
	 *
	 * @return array
	 */
	public function get_config(): array {
		/*
		 * Array of configuration settings. Amend each line as needed.
		 *
		 * Only uncomment the strings in the config array if you want to customize the strings.
		 */
		$config = [
			'id'           => 'chairforce',
			// Unique ID for hashing notices for multiple instances of TGMPA.
			'default_path' => $this->get_plugins_zip_dir_path(),
			// Default absolute path to bundled plugins.
			'menu'         => 'chairforce-install-plugins',
			// Menu slug.
			'parent_slug'  => 'themes.php',
			// Parent menu slug.
			'capability'   => 'edit_theme_options',
			// Capability needed to view plugin install page, should be a capability associated with the parent menu used.
			'has_notices'  => true,
			// Show admin notices or not.
			'dismissable'  => true,
			// If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => '',
			// If 'dismissable' is false, this message will be output at top of nag.
			'is_automatic' => false,
			// Automatically activate plugins after installation or not.
			'message'      => '',
			// Message to output right before the plugins table.
		];

		return apply_filters( 'chairforce_tgmpa_config', $config );

	}

	/**
	 * Get the path for the plugins zip directory
	 *
	 * @return string
	 */
	public function get_plugins_zip_dir_path(): string {

		return get_template_directory() . '/lib/plugins-zip/';

	}

	/**
	 * This will generate a config array for all the plugins of the site
	 * This is controlled using URL query strings
	 *
	 * The use of GET query strings:
	 * `tgmpa-generate-config` will generate the query string
	 * `show-source` will add the 'source' array prop for plugins that are not Wp Repo plugins
	 * `create-zip` will also create the zip for the plugins that are private or premium plugins
	 *
	 */
	public function maybe_generate_required_plugins_array(): void {


		if ( ! isset( $_GET['tgmpa-generate-config'] ) ) {
			return;
		}

		if ( ! $_GET['tgmpa-generate-config'] ) {
			return;
		}

		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		$update_file_path = ABSPATH . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'update.php';

		if ( ! file_exists( $update_file_path ) ) {
			return;
		}

		include_once $update_file_path;

		if ( ! function_exists( 'get_plugins' ) ) {
			echo 'get_plugins WordPress function could not be found';

			return;
		}

		$plugins = get_plugins();

		if ( empty( $plugins ) ) {
			echo 'Plugins could not be found.';
			echo PHP_EOL;
			echo 'Please goto Wp-Admin > Plugins > Check for installed plugins, and then check back here';

			return;
		}

		$plugins_array = [];

		foreach ( $plugins as $plugin_file_name => $plugin ):

			$plugin_slug     = dirname( $plugin_file_name );
			$plugin_name     = $plugin['Name'];
			$current_version = $plugin['Version'];


			$plugin_details = [
				'name'    => $plugin_name,
				'slug'    => $plugin_slug,
				'version' => $current_version,
			];

			$show_source = isset( $_GET['show-source'] ) && $_GET['show-source'];
			if ( $show_source ) {
				if ( ! $this->is_wp_repo_plugin( $plugin_slug ) ) {
					$plugin_details['source'] = $plugin_slug . '.zip';

					// We may also create zip files
					if ( isset( $_GET['create-zip'] ) && $_GET['create-zip'] ) {
						$this->create_plugin_zip( $plugin_slug );
					}
				}
			}


			$plugins_array[] = $plugin_details;
		endforeach;

		$this->dump( $plugins_array );

	}

	/**
	 * This will be used to check if the plugin is in the WordPress Repo
	 *
	 * @param string $plugin_slug
	 *
	 * @return bool
	 *
	 */
	public function is_wp_repo_plugin( string $plugin_slug ): bool {

		$maybe_wp_repo_url = 'https://wordpress.org/plugins/' . $plugin_slug . '/';

		return $this->http_response( $maybe_wp_repo_url );
	}

	/**
	 * Check if the URL is valid
	 * This will be used to check if the plugin is in the WordPress Repo
	 *
	 * @param $url
	 *
	 * @return bool
	 */
	public function http_response( $url ): bool {

		$handle = curl_init( $url );
		curl_setopt( $handle, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $handle, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $handle, CURLOPT_SSL_VERIFYHOST, 2 );
		curl_setopt( $handle, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $handle, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $handle, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; WordPress-Plugin-Check/1.0)' ); // Set a User-Agent

		curl_exec( $handle );
		$final_url = curl_getinfo( $handle, CURLINFO_EFFECTIVE_URL ); // Get final URL after redirects
		$httpCode  = curl_getinfo( $handle, CURLINFO_HTTP_CODE );
		curl_close( $handle );

		// Check if the final URL is different from the initial URL (indicates a redirect)
		if ( $httpCode >= 200 && $httpCode < 300 && $final_url === $url ) {
			return true;
		} else {
			echo 'Plugin not found in the WordPress Repo: ' . $url . '<br/>';

			return false;
		}
	}


	/**
	 * Create a zip file for the plugin
	 * This is useful for the plugins that are not in the WordPress Repo
	 * This will create a zip file in the `plugins-zip` directory
	 * This will be used by the TGMPA to install the plugin
	 *
	 * @param $plugin_slug
	 *
	 * @return void
	 */
	public function create_plugin_zip( $plugin_slug ): void {

		if ( ! class_exists( 'Chairforce\Zip_Archive' ) ) {
			echo 'Chairforce\Zip_Archive class not found' . '<br/>';

			return;
		}

		if ( in_array( $plugin_slug, $this->skip_zip_for_plugins(), true ) ) {
			echo 'Skipping plugin as per the configuration ' . $plugin_slug . '<br/>';

			return;
		}

		$the_folder    = WP_PLUGIN_DIR . '/' . $plugin_slug;
		$zip_file_name = $this->get_plugins_zip_dir_path() . $plugin_slug . '.zip';

		$za  = new Zip_Archive();
		$res = $za->open( $zip_file_name, ZipArchive::CREATE );
		if ( $res === true ) {
			$za->addDir( $the_folder, basename( $the_folder ) );
			$za->close();
		} else {
			echo 'Could not create a zip archive';
		}

	}

	/**
	 * If we want to skip any plugin zip, we can add plugin slug in this array
	 * Example Usage:
	 * there is `node_modules` directory in the plugin,
	 * se we want to skip it and then create a zip manually and add to `plugins-zip`
	 *
	 */
	public function skip_zip_for_plugins(): array {

		return [
			'query-monitor',
			'regenerate-thumbnails',
			'duplicate-post',
			'post-types-order'
		];

	}

	/**
	 *
	 */
	public function dump( $var ): void {
		echo '<pre>';
		echo preg_replace( '(\d+\s=>)', '', var_export( $var, true ) );
		echo '</pre>';

	}


}
