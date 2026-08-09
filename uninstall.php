<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package ShareNivo
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete current and legacy settings without touching any other plugin data.
delete_option( 'sharenivo_settings' );
delete_option( 'sharenova_settings' );

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_sharenivo_share_counts_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_sharenivo_share_counts_' ) . '%',
		$wpdb->esc_like( '_transient_sharenova_share_counts_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_sharenova_share_counts_' ) . '%'
	)
);
