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
		if ( false === get_option( 'sharenivo_settings', false ) && false === get_option( 'sharenova_settings', false ) ) {
			add_option( 'sharenivo_settings', Settings::get_defaults(), '', false );
		}
		update_option( 'sharenivo_schema_version', SHARENIVO_SCHEMA_VERSION, false );
	}
}
