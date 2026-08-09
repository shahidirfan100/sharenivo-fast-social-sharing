<?php
/**
 * Plugin Name:       ShareNivo - Fast Social Sharing
 * Description:       Lightweight, performant social sharing buttons for WordPress. GDPR compliant with responsive design (floating sidebar on desktop, sticky bottom bar on mobile).
 * Version:           1.4.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Shahid Irfan
 * Author URI:        https://wordpress.org/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sharenivo-fast-social-sharing
 * Domain Path:       /languages
 *
 * @package           ShareNivo
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current plugin version.
 */
define( 'SHARENIVO_VERSION', '1.4.1' );

/**
 * Main plugin file.
 */
define( 'SHARENIVO_PLUGIN_FILE', __FILE__ );

/**
 * Plugin directory path.
 */
define( 'SHARENIVO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL.
 */
define( 'SHARENIVO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function sharenivo_activate_plugin() {
	require_once SHARENIVO_PLUGIN_DIR . 'includes/class-activator.php';
	ShareNivo\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function sharenivo_deactivate_plugin() {
	require_once SHARENIVO_PLUGIN_DIR . 'includes/class-deactivator.php';
	ShareNivo\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'sharenivo_activate_plugin' );
register_deactivation_hook( __FILE__, 'sharenivo_deactivate_plugin' );

/**
 * Begins execution of the plugin.
 */
require SHARENIVO_PLUGIN_DIR . 'includes/class-main.php';

/**
 * Initialize the plugin.
 */
function sharenivo_run_plugin() {
	$plugin = ShareNivo\Main::get_instance();
	$plugin->run();
}
sharenivo_run_plugin();
