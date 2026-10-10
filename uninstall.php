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

// Email log: table, schema version, plugin name cache and the purge cron event, on every site of a network.
global $wpdb;

$ecehc_blog_ids = is_multisite() ? get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) : array( get_current_blog_id() );

foreach ( $ecehc_blog_ids as $ecehc_blog_id ) {
	if ( is_multisite() ) {
		switch_to_blog( $ecehc_blog_id );
	}

	$ecehc_table = esc_sql( $wpdb->prefix . 'ecehc_email_log' );
	$wpdb->query( "DROP TABLE IF EXISTS `{$ecehc_table}`" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	delete_option( 'ecehc_db_version' );
	delete_option( 'ecehc_log_settings' );
	delete_option( 'ecehc_installed_at' );
	delete_option( 'ecehc_show_checklist' );
	delete_transient( 'ecehc_plugin_names' );
	delete_transient( 'ecehc_log_stats_86400' );
	delete_transient( 'ecehc_log_stats_604800' );
	wp_clear_scheduled_hook( 'ecehc_purge_email_log' );

	if ( is_multisite() ) {
		restore_current_blog();
	}
}

// Review request state (user meta, all users).
delete_metadata( 'user', 0, 'ecehc_review_prompt', '', true );
// First-run checklist state (user meta, all users).
delete_metadata( 'user', 0, 'ecehc_checklist_hidden', '', true );
delete_metadata( 'user', 0, 'ecehc_checklist_saw_log', '', true );
