<?php
/**
 * The email log table.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Log;

defined( 'ABSPATH' ) || exit;

class Schema {

	const DB_VERSION        = '1';
	const OPTION_DB_VERSION = 'ecehc_db_version';

	public static function table(): string {
		global $wpdb;

		return $wpdb->prefix . 'ecehc_email_log';
	}

	/**
	 * Creates or upgrades the table when the stored schema version is older.
	 * Cheap to call on every request: it is one autoloaded option read.
	 */
	public static function maybe_install(): void {
		if ( get_option( self::OPTION_DB_VERSION ) === self::DB_VERSION ) {
			return;
		}

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				created_at datetime NOT NULL,
				status varchar(10) NOT NULL,
				source_key varchar(100) NOT NULL,
				source_label varchar(150) NOT NULL,
				email_type varchar(100) NOT NULL DEFAULT '',
				email_type_label varchar(150) NOT NULL DEFAULT '',
				recipient varchar(255) NOT NULL DEFAULT '',
				recipient_domain varchar(190) NOT NULL DEFAULT '',
				error varchar(500) NOT NULL DEFAULT '',
				PRIMARY KEY  (id),
				KEY created_at (created_at),
				KEY source_key (source_key,created_at)
			) {$charset};"
		);

		// Only remember the schema version if the table really exists, so a failed
		// CREATE TABLE is retried instead of leaving a permanently empty log.
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) === $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			update_option( self::OPTION_DB_VERSION, self::DB_VERSION );
		}
	}

	public static function drop(): void {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		delete_option( self::OPTION_DB_VERSION );
	}
}
