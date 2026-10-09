<?php
/**
 * Fired on plugin uninstall. Remove the transient and stored options.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_transient( 'ecehc_redirect_to_dashboard' );
delete_option( 'ecehc_last_test_email' );
delete_transient( 'ecehc_smtp_probe' );
delete_transient( 'ecehc_blacklist' );
