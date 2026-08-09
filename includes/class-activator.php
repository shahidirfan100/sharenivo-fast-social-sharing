<?php
/**
 * Fired during plugin activation.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activator class.
 */
class Activator {

	/**
	 * Activate the plugin.
	 *
	 * Set default options.
	 */
	public static function activate() {
		self::set_default_options();
	}

	/**
	 * Set default plugin options.
	 */
	private static function set_default_options() {
		$default_networks = array( 'facebook', 'x', 'linkedin', 'whatsapp', 'pinterest', 'threads', 'bluesky', 'telegram', 'reddit', 'email' );

		$defaults = array(
			'enabled'            => true,
			'networks'           => $default_networks,
			'network_order'      => $default_networks,
			'post_types'         => array( 'post', 'page' ),
			'show_on_homepage'   => false,
			'auto_detect_post_types' => true,
			'position'           => 'floating_left',
			'mobile_position'    => 'sticky_bottom',
			'button_shape'       => 'circle',
			'button_size'        => 'medium',
			'show_more_network_button' => true,
			'color_scheme'       => 'brand',
			'hover_animation'    => 'lift',
			'custom_bg_color'    => '#000000',
			'custom_icon_color'  => '#ffffff',
			'custom_hover_bg'    => '#434343',
			'button_gap'         => 10,
			'margin_top'         => 20,
			'margin_bottom'      => 20,
			'enable_animations'  => true,
			'show_share_counts'  => false,
			'share_count_mode'   => 'total',
			'share_count_cache_ttl' => 60,
			'sharedcount_api_key' => '',
			'custom_css'         => '',
		);

		// Only add option if it doesn't exist.
		if ( false === get_option( 'sharenivo_settings' ) ) {
			add_option( 'sharenivo_settings', $defaults, '', 'no' );
		}
	}
}
