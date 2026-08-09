<?php
/**
 * Admin functionality.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin class.
 */
class Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_sharenivo_save_settings_action', array( '\ShareNivo\Settings', 'ajax_save_settings' ) );
		// Keep the old AJAX action working for sites upgrading from ShareNova.
		add_action( 'wp_ajax_sharenova_save_settings_action', array( '\ShareNivo\Settings', 'ajax_save_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SHARENIVO_PLUGIN_FILE ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Get cache-busting asset version.
	 *
	 * @param string $relative_path Relative path from plugin root.
	 * @return string
	 */
	private function get_asset_version( $relative_path ) {
		$path = SHARENIVO_PLUGIN_DIR . ltrim( $relative_path, '/\\' );
		if ( file_exists( $path ) ) {
			return (string) filemtime( $path );
		}

		return SHARENIVO_VERSION;
	}

	/**
	 * Add admin menu.
	 */
	public function add_menu() {
		add_options_page(
			__( 'ShareNivo Settings', 'sharenivo-fast-social-sharing' ),
			__( 'ShareNivo', 'sharenivo-fast-social-sharing' ),
			'manage_options',
			'sharenivo',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = new Settings();
		$settings->render_page();
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on our settings page.
		if ( 'settings_page_sharenivo' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'sharenivo-admin-css',
			SHARENIVO_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			$this->get_asset_version( 'assets/css/admin.css' )
		);

		wp_enqueue_script(
			'sharenivo-admin-js',
			SHARENIVO_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			$this->get_asset_version( 'assets/js/admin.js' ),
			true
		);

		wp_localize_script( 'sharenivo-admin-js', 'sharenivo_admin_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' )
		) );

		// Enqueue WordPress color picker.
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/**
	 * Add settings link to plugins page.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=sharenivo' ) ) . '">' . __( 'Settings', 'sharenivo-fast-social-sharing' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
}
