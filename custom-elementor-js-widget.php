<?php
/**
 * Plugin Name: Custom JS Widget for Elementor
 * Description: A premium widget for Elementor that allows adding raw JavaScript to pages with execution placement control.
 * Version: 1.0.1
 * Author: Custom Developer
 * Text Domain: custom-js-widget
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.2
 */

declare(strict_types=1);

namespace CustomElementorJSWidget;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main plugin file path constant.
 */
define('CJSW_PLUGIN_FILE', __FILE__);
define('CJSW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CJSW_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Load the main plugin class.
 */
require_once CJSW_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Initialize the plugin.
 */
function init(): void
{
	Plugin::instance();
}

add_action('plugins_loaded', __NAMESPACE__ . '\\init');
