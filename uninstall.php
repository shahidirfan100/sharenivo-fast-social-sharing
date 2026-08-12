<?php
/**
 * ShareNivo uninstall cleanup.
 *
 * @package ShareNivo
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sharenivo_settings' );
delete_option( 'sharenivo_schema_version' );
delete_option( 'sharenova_settings' );
delete_post_meta_by_key( '_sharenivo_override' );
