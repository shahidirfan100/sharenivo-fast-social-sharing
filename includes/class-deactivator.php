<?php
/**
 * Fired during plugin deactivation.
 *
 * @package ShareNivo
 */

namespace ShareNivo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Deactivator class.
 */
class Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Clear transients.
	 */
	public static function deactivate() {
		// Clear any transients.
		delete_transient( 'sharenivo_share_counts' );
		delete_transient( 'sharenova_share_counts' );

		// Note: We do NOT delete settings on deactivation.
		// Settings are only deleted on uninstall.
	}
}
