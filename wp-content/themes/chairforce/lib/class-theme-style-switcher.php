<?php

namespace Chairforce;
// exit if file is called directly
use WP_Theme_JSON_Data;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// if class already defined, bail out
if ( class_exists( 'Chairforce\Theme_Style_Switcher' ) ) {
	return;
}


/**
 * Theme style switcher (cookie-based, merges style JSON into theme.json).
 *
 * On Cloudways: full-page cache (Breeze/Varnish) can serve cached HTML and ignore
 * the style cookie. Exclude these pages from full-page cache, or add "Vary: Cookie"
 * so the style switch works after reload.
 *
 * @package    Chairforce
 * @subpackage Chairforce/lib
 */
class Theme_Style_Switcher {

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
	public function register_hooks() {

		add_action( 'after_setup_theme', [ $this, 'maybe_change_theme_style' ] );

		add_action( 'wp_head', [ $this, 'maybe_output_theme_style_client_override' ], 1 );
		add_action( 'wp_footer', [ $this, 'maybe_output_theme_style_switcher_frontend' ] );
	}

	/**
	 * Modifies the theme JSON based on the selected theme style from the cookie.
	 *
	 * If the `chairforce_theme_style` cookie is set, it attempts to load the corresponding theme style JSON file
	 * and update the provided theme JSON with the selected style's data.
	 *
	 * @param WP_Theme_JSON_Data $theme_json The current theme JSON object.
	 *
	 * @return WP_Theme_JSON_Data Modified theme JSON with the selected style, or the original theme JSON if no style is selected.
	 */

	public function maybe_use_theme_style_changer( $theme_json ) {

		if ( empty( $_COOKIE['chairforce_theme_style'] ) ) {
			return $theme_json;
		}

		$requested_theme_style = sanitize_key( $_COOKIE['chairforce_theme_style'] );

		if ( ! $requested_theme_style ) {
			return $theme_json;
		}

		$style_json = $this->get_theme_json_for_style( $requested_theme_style );
		if ( ! $style_json ) {
			return $theme_json;
		}

		// Return the modified theme JSON data.
		return $theme_json->update_with( $style_json );
	}

	/**
	 * Retrieves the JSON configuration for a specific theme style.
	 *
	 * Loads the theme style JSON file from the 'styles' directory and decodes it.
	 * Uses case-insensitive file matching so style names work on Linux (Cloudways) and Windows (Laragon).
	 * For 'default', falls back to theme.json if styles/default.json is missing.
	 *
	 * @param string $style_name The name of the theme style.
	 *
	 * @return array|null The decoded theme style JSON data, or null if the file does not exist or contains invalid JSON.
	 */
	public function get_theme_json_for_style( $style_name ) {

		$theme_dirs = $this->get_theme_directories();
		$file_path  = null;

		// 'default' can use theme.json when styles/default.json is not present.
		// Prefer child theme theme.json, then fallback to parent theme.json.
		if ( $style_name === 'default' ) {
			foreach ( $theme_dirs as $theme_dir ) {
				$theme_json_path = $theme_dir . '/theme.json';
				if ( is_readable( $theme_json_path ) ) {
					$file_path = $theme_json_path;
					break;
				}
			}
		}

		if ( ! $file_path ) {
			foreach ( $theme_dirs as $theme_dir ) {
				$styles_dir = $theme_dir . '/styles/';
				if ( ! is_dir( $styles_dir ) ) {
					continue;
				}

				$requested_lower = strtolower( $style_name );
				$candidates      = glob( $styles_dir . '*.json' );
				foreach ( $candidates as $candidate ) {
					if ( strtolower( basename( $candidate, '.json' ) ) === $requested_lower ) {
						$file_path = $candidate;
						break 2;
					}
				}

				if ( file_exists( $styles_dir . $style_name . '.json' ) ) {
					$file_path = $styles_dir . $style_name . '.json';
					break;
				}
			}
		}

		if ( ! $file_path || ! is_readable( $file_path ) ) {
			return null;
		}

		$file_contents = file_get_contents( $file_path );
		if ( false === $file_contents ) {
			return null;
		}

		$json_data = json_decode( $file_contents, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return null;
		}

		return $json_data;
	}

	/**
	 * Adds a filter to modify the theme JSON based on the selected theme style.
	 *
	 * Checks if the theme has a `theme.json` file and, if so, adds a filter to potentially modify
	 * the theme JSON based on the selected style (from the cookie or other source).
	 * The filter is only applied on the frontend, not in the admin dashboard.
	 */
	public function maybe_change_theme_style() {

		if ( is_admin() ) {
			return;
		}

		if ( ! $this->is_enabled() ) {
			return;
		}


		// Check to make sure the theme has a theme.json file and the $_GET['chairforce_change_style'] is set
		if ( wp_theme_has_theme_json() ) {

			add_filter( 'wp_theme_json_data_theme', [ $this, 'maybe_use_theme_style_changer' ] );
		}

	}

	/**
	 *
	 */
	public function is_enabled() {

		return false;
	}

	/**
	 * Adds a filter to modify the theme JSON based on the selected theme style.
	 *
	 * Checks if the theme has a `theme.json` file and, if so, adds a filter to potentially modify
	 * the theme JSON based on the selected style (from the cookie or other source).
	 * The filter is only applied on the frontend, not in the admin dashboard.
	 *
	 * @hooked wp_footer
	 */
	public function maybe_output_theme_style_switcher_frontend() {

		if ( ! $this->is_enabled() ) {
			return;
		}

		$styles = $this->get_theme_styles();

		$cookie_secure = is_ssl();
		?>
        <div id="theme-style-switcher">
            <p><strong><?php _e( 'Choose Style', 'chairforce' ); ?></strong></p>
            <ul id="style-list" data-styles='<?php echo esc_attr( json_encode( $styles ) ); ?>' data-cookie-secure="<?php echo $cookie_secure ? '1' : '0'; ?>"></ul>
        </div>
        <div id="theme-style-switcher-icon">&#9881;</div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const styleListEl = document.getElementById('style-list');
                if (!styleListEl) return;
                const stylesData = styleListEl.getAttribute('data-styles');
                const cookieSecure = styleListEl.getAttribute('data-cookie-secure') === '1';

                const styleConfigurations = stylesData ? JSON.parse(stylesData) : [];

                const styleList = document.getElementById('style-list');
                styleConfigurations.forEach(function (config) {
                    let li = document.createElement('li');

                    // Create a div for the style box with class 'switcher-item'
                    let styleBox = document.createElement('div');
                    styleBox.classList.add('switcher-item');
                    styleBox.setAttribute('data-style', config.style);
                    styleBox.setAttribute('data-title', config.title); // Use for hover title
                    styleBox.style.backgroundColor = config.colors.base;
                    styleBox.style.color = config.colors.contrast;

                    // Text example (Aa)
                    let textExample = document.createElement('div');
                    textExample.classList.add('theme-style-text-example');
                    textExample.style.color = config.colors.contrast;
                    textExample.textContent = 'Aa';
                    styleBox.appendChild(textExample);

                    // Color swatches for primary and secondary colors
                    let colorSwatches = document.createElement('div');
                    colorSwatches.classList.add('color-swatch');

                    let primarySwatch = document.createElement('div');
                    primarySwatch.classList.add('swatch');
                    primarySwatch.style.backgroundColor = config.colors.primary;
                    colorSwatches.appendChild(primarySwatch);

                    let secondarySwatch = document.createElement('div');
                    secondarySwatch.classList.add('swatch');
                    secondarySwatch.style.backgroundColor = config.colors.secondary;
                    colorSwatches.appendChild(secondarySwatch);

                    styleBox.appendChild(colorSwatches);

                    li.appendChild(styleBox);
                    styleList.appendChild(li);

                    // Handle click event for the style box
                    styleBox.addEventListener('click', function () {
                        setCookie('chairforce_theme_style', config.style, 7);
                        location.reload(); // Reload the page to apply the style
                    });
                });

                // Function to set a cookie (SameSite=Lax and Secure on HTTPS for Cloudways compatibility)
                function setCookie(name, value, days) {
                    var expires = "";
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        expires = "; expires=" + date.toUTCString();
                    }
                    var cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
                    if (cookieSecure) cookie += "; Secure";
                    document.cookie = cookie;
                }

                // Handle the toggle for the switcher
                const switcher = document.getElementById('theme-style-switcher');
                const switcherIcon = document.getElementById('theme-style-switcher-icon');
                switcherIcon.addEventListener('click', function () {
                    if (switcher.style.display === "none") {
                        switcher.style.display = "block";
                    } else {
                        switcher.style.display = "none";
                    }
                });
            });
        </script>
        <style>
            #theme-style-switcher {
                width: 200px;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
                border-radius: 10px;
                transition: all 0.3s ease;
                position: fixed;
                left: 10px;
                bottom: 60px;
                background: #868686;
                color: var(--wp--preset--color--body);
                padding: var(--wp--preset--spacing--m, 20px);
                z-index: 10;
                display: none;
            }

            #theme-style-switcher ul {
                list-style-type: none;
                padding: 0;
            }

            #theme-style-switcher li {
                margin: 5px 0;
            }

            #theme-style-switcher-icon {
                position: fixed;
                left: 10px;
                bottom: 20px;
                background: #333;
                color: #fff;
                width: 40px;
                height: 40px;
                text-align: center;
                border-radius: 50%;
                cursor: pointer;
                z-index: 1001;
                border: 1px solid #fff;
                display: flex;
                justify-content: center;
                align-items: baseline;
                font-size: 24px;
                transition: all 0.3s ease;
            }

            #theme-style-switcher-icon:hover {
                background-color: #555;
            }

            .switcher-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px;
                border-radius: 8px;
                cursor: pointer;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
                position: relative;
            }

            .switcher-item:hover {
                background-color: #9c9c9c;
            }

            .switcher-item:hover::after {
                content: attr(data-title);
                position: absolute;
                top: -20px;
                left: 0;
                padding: 5px 10px;
                background: #000;
                color: #fff;
                font-size: 12px;
                border-radius: 4px;
            }

            .theme-style-text-example {
                font-size: 24px;
            }

            .color-swatch {
                display: flex;
                align-items: center;
            }

            .color-swatch .swatch {
                width: 20px;
                height: 20px;
                border-radius: 50%;
                margin-right: 10px;
            }

            .color-swatch .swatch:last-child {
                margin-right: 0;
            }

        </style>

		<?php
	}

	/**
	 * Retrieves the available theme styles, using caching for performance.
	 *
	 * This function checks if a cached version of the theme styles is available. If not, it regenerates the
	 * theme styles configuration and stores it in a transient for future use. The cache is automatically
	 * bypassed in development mode.
	 *
	 * If a `clear_theme_switcher_cache` GET parameter is set, the cache is cleared.
	 *
	 * @return array The array of available theme styles.
	 */
	public function get_theme_styles() {

		if ( isset( $_GET['clear_theme_switcher_cache'] ) && sanitize_key( $_GET['clear_theme_switcher_cache'] ) === 'true' ) {
			$this->clear_theme_styles_cache();
		}


		// Check if we're in development mode
		$is_dev_mode = defined( 'WP_ENVIRONMENT_TYPE' ) && WP_ENVIRONMENT_TYPE === 'development';

		// Cache key version so transients get fresh config with 'palette' after deploy (client-side override)
		$cache_key = 'chairforce_theme_switcher_config_v2';

		// If not in development mode, attempt to get the cached styles
		if ( ! $is_dev_mode ) {
			$cached_styles = get_transient( $cache_key );
			if ( $cached_styles !== false ) {
				return $cached_styles;
			}
		}

		// Regenerate the theme styles configuration
		$styles = $this->generate_theme_styles_config();

		// If not in development mode, store the styles in a transient
		if ( ! $is_dev_mode ) {
			set_transient( $cache_key, $styles, HOUR_IN_SECONDS );
		}

		return $styles;
	}

	/**
	 * Clears the cached theme styles configuration.
	 *
	 * This function deletes the cached theme styles (stored as a transient) to force regeneration
	 * of the theme styles on the next request.
	 */
	public function clear_theme_styles_cache() {
		delete_transient( 'chairforce_theme_switcher_config_v2' );
	}

	/**
	 * Generates the theme styles configuration by scanning the 'styles' directory.
	 *
	 * Always includes theme.json (as "default") so the switcher works when /styles/
	 * is missing on the server (e.g. Cloudways). Uses get_template_directory() for
	 * consistent paths on Linux (case-sensitive) and with child themes.
	 *
	 * @return array The generated theme styles configuration.
	 */
	public function generate_theme_styles_config() {
		$theme_dirs = $this->get_theme_directories();

		$style_files = [];

		foreach ( $theme_dirs as $theme_dir ) {
			$theme_json = $theme_dir . '/theme.json';
			if ( is_readable( $theme_json ) && ! isset( $style_files['default'] ) ) {
				$style_files['default'] = $theme_json;
			}

			$styles_dir = $theme_dir . '/styles/';
			if ( ! is_dir( $styles_dir ) ) {
				continue;
			}

			$theme_styles = glob( $styles_dir . '*.json' );
			if ( empty( $theme_styles ) ) {
				continue;
			}

			foreach ( $theme_styles as $theme_style ) {
				$style_name = strtolower( basename( $theme_style, '.json' ) );
				if ( ! isset( $style_files[ $style_name ] ) ) {
					$style_files[ $style_name ] = $theme_style;
				}
			}
		}


		$styles = [];

		foreach ( $style_files as $file ) {
			$style_name = basename( $file, '.json' );

			$file_contents = file_get_contents( $file );
			if ( false === $file_contents ) {
				continue;
			}

			$json_data = json_decode( $file_contents, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				continue;
			}

			// Update Style_name

			$colors_to_get = [
				'body'       => '#000000',
				'background' => '#ffffff',
				'primary'    => '#FFCB05',
				'secondary'  => '#1F2937',
				'foreground' => '#2B3379',
			];

			$colors = [];

			$style_color_palette = $json_data['settings']['color']['palette'] ?? [];

			foreach ( $colors_to_get as $slug => $default ) {

				$filtered_color = wp_list_filter( $style_color_palette, [ 'slug' => $slug ] );

				if ( ! empty( $filtered_color ) ) {
					$color_data  = array_shift( $filtered_color );
					$color_value = $color_data['color'];
				} else {
					$color_value = $default;
				}

				$colors[ $slug ] = $color_value;

			}

			// Map to keys expected by frontend (base, contrast, primary, secondary)
			$colors['base']     = $colors['background'];
			$colors['contrast'] = $colors['body'];

			$style_name = ( $style_name === 'theme' ) ? 'default' : $style_name;

			$style_title = $json_data['title'] ?? ucfirst( $style_name );

			// Include full palette for client-side override (works when full-page cache ignores cookie)
			$palette = $json_data['settings']['color']['palette'] ?? [];
			$palette_simple = array_map( function ( $item ) {
				return isset( $item['slug'], $item['color'] ) ? [ 'slug' => $item['slug'], 'color' => $item['color'] ] : null;
			}, $palette );
			$palette_simple = array_values( array_filter( $palette_simple ) );

			$styles[] = [
				'style'   => $style_name,
				'title'   => $style_title,
				'colors'  => $colors,
				'palette' => $palette_simple,
			];
		}

		return $styles;

	}

	/**
	 * Returns theme directories in child-first order.
	 *
	 * @return array
	 */
	private function get_theme_directories() {
		$dirs = [ get_stylesheet_directory() ];
		$template_dir = get_template_directory();
		if ( $template_dir !== $dirs[0] ) {
			$dirs[] = $template_dir;
		}

		return $dirs;
	}

	/**
	 * Outputs script in head that applies the selected style from cookie via CSS variables.
	 * Works when full-page cache (e.g. Cloudways Breeze) serves cached HTML and PHP/cookie
	 * are not read on the server — the browser reads the cookie and applies the style.
	 *
	 * @hooked wp_head priority 1
	 */
	public function maybe_output_theme_style_client_override() {

		if ( ! $this->is_enabled() ) {
			return;
		}

		$styles = $this->get_theme_styles();
		if ( empty( $styles ) ) {
			return;
		}

		$styles_json = wp_json_encode( $styles );
		if ( ! $styles_json ) {
			return;
		}

		?>
		<script>
		(function() {
			var styles = <?php echo $styles_json; ?>;
			var cookie = document.cookie.split(';').filter(function(c){ return c.trim().indexOf('chairforce_theme_style=') === 0; })[0];
			var name = cookie ? cookie.replace(/^[^=]*=/, '').trim() : '';
			if (!name) return;
			var style = styles.filter(function(s){ return s.style === name; })[0];
			if (!style || !style.palette || !style.palette.length) return;
			var rules = [];
			for (var i = 0; i < style.palette.length; i++) {
				var p = style.palette[i];
				if (p.slug && p.color) rules.push('--wp--preset--color--' + p.slug + ':' + p.color);
			}
			if (rules.length) {
				var el = document.createElement('style');
				el.id = 'chairforce-theme-style-override';
				el.textContent = ':root{' + rules.join(';') + '}';
				document.documentElement.appendChild(el);
			}
		})();
		</script>
		<?php
	}


}
