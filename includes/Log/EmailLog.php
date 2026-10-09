<?php
/**
 * Reads and writes the email log table.
 *
 * Only the minimum is stored: time, status, source, WooCommerce email type, a
 * masked recipient and its domain, and an error message with any email
 * addresses masked. Never the message body, subject, headers or a full address.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Log;

defined( 'ABSPATH' ) || exit;

class EmailLog {

	const STATUS_SENT   = 'sent';
	const STATUS_FAILED = 'failed';

	/**
	 * @param array<string, mixed> $row Row values (created_at, status, source_key, ...).
	 * @return bool Whether the row was written.
	 */
	public static function insert( array $row ): bool {
		global $wpdb;

		$defaults = array(
			'created_at'       => gmdate( 'Y-m-d H:i:s' ),
			'status'           => self::STATUS_SENT,
			'source_key'       => 'unknown',
			'source_label'     => '',
			'email_type'       => '',
			'email_type_label' => '',
			'recipient'        => '',
			'recipient_domain' => '',
			'error'            => '',
		);

		/**
		 * Filters a log row before it is written.
		 *
		 * @param array $row Row values.
		 */
		$row = array_merge( $defaults, array_intersect_key( (array) apply_filters( 'ecehc_log_row', array_merge( $defaults, $row ) ), $defaults ) );

		// Fit every value to its column so strict SQL mode cannot drop the row.
		$limits = array(
			'status'           => 10,
			'source_key'       => 100,
			'source_label'     => 150,
			'email_type'       => 100,
			'email_type_label' => 150,
			'recipient'        => 255,
			'recipient_domain' => 190,
			'error'            => 500,
		);
		foreach ( $limits as $column => $length ) {
			$value         = is_scalar( $row[ $column ] ) ? (string) $row[ $column ] : '';
			$row[ $column ] = function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $length ) : substr( $value, 0, $length );
		}
		$row['created_at'] = is_scalar( $row['created_at'] ) ? (string) $row['created_at'] : gmdate( 'Y-m-d H:i:s' );

		$suppress = $wpdb->suppress_errors( true );
		$written  = false !== $wpdb->insert( Schema::table(), $row, array_fill( 0, count( $row ), '%s' ) );
		$wpdb->suppress_errors( $suppress );

		if ( $written ) {
			/**
			 * Fires after an email was logged.
			 *
			 * @param array $row Row values that were written.
			 */
			do_action( 'ecehc_email_logged', $row );
		}

		return $written;
	}

	/**
	 * Newest rows first.
	 *
	 * @return array<int, array<string, string>>
	 */
	public static function recent( int $limit = 100 ): array {
		global $wpdb;

		$table = Schema::table();

		return (array) $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
	}

	/**
	 * Deletes rows older than the retention period, then the oldest rows over the cap.
	 */
	public static function purge(): void {
		global $wpdb;

		$table = Schema::table();

		/**
		 * Filters how many days of log rows are kept.
		 *
		 * @param int $days Days, default 7.
		 */
		$days = max( 1, (int) apply_filters( 'ecehc_log_retention_days', 7 ) );

		/**
		 * Filters the maximum number of log rows kept.
		 *
		 * @param int $rows Rows, default 5000.
		 */
		$max_rows = max( 100, (int) apply_filters( 'ecehc_log_max_rows', 5000 ) );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$cutoff = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} ORDER BY id DESC LIMIT 1 OFFSET %d", $max_rows ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $cutoff ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id <= %d", (int) $cutoff ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
}
