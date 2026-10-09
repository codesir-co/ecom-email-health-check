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

// Email log: table, schema version and the purge cron event.
global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'ecehc_email_log' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
delete_option( 'ecehc_db_version' );
wp_clear_scheduled_hook( 'ecehc_purge_email_log' );
