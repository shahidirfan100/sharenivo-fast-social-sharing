<?php
/**
 * Main plugin class.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class - Singleton pattern.
 */
class Main {

	/**
	 * Single instance of the class.
	 *
	 * @var Main
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return Main
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor - private to enforce singleton.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->define_hooks();
	}

	/**
	 * Load required dependencies.
	 */
	private function load_dependencies() {
		require_once SHARENIVO_PLUGIN_DIR . 'includes/class-networks.php';
		require_once SHARENIVO_PLUGIN_DIR . 'includes/class-share-meta.php';
		require_once SHARENIVO_PLUGIN_DIR . 'includes/class-renderer.php';
		require_once SHARENIVO_PLUGIN_DIR . 'includes/admin/class-admin.php';
		require_once SHARENIVO_PLUGIN_DIR . 'includes/admin/class-settings.php';
		require_once SHARENIVO_PLUGIN_DIR . 'includes/public/class-follow-widget.php';
		require_once SHARENIVO_PLUGIN_DIR . 'includes/public/class-public.php';
	}

	/**
	 * Define WordPress hooks.
	 */
	private function define_hooks() {
		// Initialize admin functionality.
		if ( is_admin() ) {
			new Admin();
		}

		// Initialize public functionality.
		new PublicDisplay();
	}

	/**
	 * Run the plugin.
	 */
	public function run() {
		// Plugin is running.
	}
}
