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
	 * ShareNivo has no scheduled jobs or remote-request caches to clear.
	 */
	public static function deactivate() {
		// Settings are intentionally retained. Uninstall performs cleanup.
	}
}
