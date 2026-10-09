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

	/** How many of the newest rows the Email Log tab shows. */
	const VIEW_LIMIT = 100;

	/** Seconds a statistics result is reused. */
	const STATS_TTL = 60;

	/**
	 * Rows matching the filters, newest first, limited to the newest VIEW_LIMIT rows.
	 *
	 * @param array{source?: string, status?: string, type?: string} $filters  Already validated filter values.
	 * @param int                                                    $per_page Rows per page.
	 * @param int                                                    $page     Page number, from 1.
	 * @return array{rows: array<int, array<string, string>>, total: int, pages: int}
	 */
	public static function query( array $filters, int $per_page = 20, int $page = 1 ): array {
		global $wpdb;

		$table = Schema::table();
		$where = array( '1=1' );
		$args  = array();

		if ( ! empty( $filters['source'] ) ) {
			$where[] = 'source_key = %s';
			$args[]  = $filters['source'];
		}
		if ( ! empty( $filters['status'] ) ) {
			$where[] = 'status = %s';
			$args[]  = $filters['status'];
		}
		if ( ! empty( $filters['type'] ) ) {
			$where[] = 'email_type = %s';
			$args[]  = $filters['type'];
		}

		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$total     = (int) $wpdb->get_var( $args ? $wpdb->prepare( $count_sql, $args ) : $count_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total     = min( $total, self::VIEW_LIMIT );
		$pages     = max( 1, (int) ceil( $total / max( 1, $per_page ) ) );
		$page      = min( max( 1, $page ), $pages );

		$rows_sql = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
		$rows     = $wpdb->get_results( $wpdb->prepare( $rows_sql, array_merge( $args, array( min( $per_page, $total - ( $page - 1 ) * $per_page ), ( $page - 1 ) * $per_page ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'rows'  => $total > 0 ? (array) $rows : array(),
			'total' => $total,
			'pages' => $pages,
		);
	}

	/**
	 * Sources present in the log, most active first.
	 *
	 * @return array<string, string> source_key => label.
	 */
	public static function sources(): array {
		global $wpdb;

		$table = Schema::table();
		$rows  = (array) $wpdb->get_results( "SELECT source_key, MAX(source_label) AS label, COUNT(*) AS n FROM {$table} GROUP BY source_key ORDER BY n DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$sources = array();
		foreach ( $rows as $row ) {
			$sources[ $row['source_key'] ] = '' !== $row['label'] ? $row['label'] : $row['source_key'];
		}

		return $sources;
	}

	/**
	 * WooCommerce email types present in the log.
	 *
	 * @return array<string, string> email_type => label.
	 */
	public static function wc_types(): array {
		global $wpdb;

		$table = Schema::table();
		$rows  = (array) $wpdb->get_results( "SELECT email_type, MAX(email_type_label) AS label FROM {$table} WHERE email_type <> '' GROUP BY email_type ORDER BY label ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$types = array();
		foreach ( $rows as $row ) {
			$types[ $row['email_type'] ] = '' !== $row['label'] ? $row['label'] : $row['email_type'];
		}

		return $types;
	}

	/**
	 * Counts for the last $seconds seconds: totals, per source, per WooCommerce
	 * email type and the most recent failure. Cached for a minute (not cleared on
	 * every logged email, to keep sending cheap); a purge clears it.
	 *
	 * @return array{sent: int, failed: int, sources: array<string, array{label: string, sent: int, failed: int}>, types: array<string, array{label: string, sent: int, failed: int}>, last_failure: array<string, string>|null}
	 */
	public static function stats( int $seconds ): array {
		global $wpdb;

		$key    = 'ecehc_log_stats_' . $seconds;
		$cached = get_transient( $key );
		if ( is_array( $cached ) && isset( $cached['sent'], $cached['failed'], $cached['sources'], $cached['types'] ) ) {
			return $cached;
		}

		$table = Schema::table();
		$since = gmdate( 'Y-m-d H:i:s', time() - $seconds );
		$stats = array(
			'sent'         => 0,
			'failed'       => 0,
			'sources'      => array(),
			'types'        => array(),
			'last_failure' => null,
		);

		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT source_key, MAX(source_label) AS label, email_type, MAX(email_type_label) AS type_label, status, COUNT(*) AS n FROM {$table} WHERE created_at >= %s GROUP BY source_key, email_type, status", $since ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( $rows as $row ) {
			$status = self::STATUS_FAILED === $row['status'] ? 'failed' : 'sent';
			$n      = (int) $row['n'];

			$stats[ $status ] += $n;

			$source = $row['source_key'];
			if ( ! isset( $stats['sources'][ $source ] ) ) {
				$stats['sources'][ $source ] = array(
					'label'  => '' !== $row['label'] ? $row['label'] : $source,
					'sent'   => 0,
					'failed' => 0,
				);
			}
			$stats['sources'][ $source ][ $status ] += $n;

			if ( '' !== $row['email_type'] ) {
				$type = $row['email_type'];
				if ( ! isset( $stats['types'][ $type ] ) ) {
					$stats['types'][ $type ] = array(
						'label'  => '' !== $row['type_label'] ? $row['type_label'] : $type,
						'sent'   => 0,
						'failed' => 0,
					);
				}
				$stats['types'][ $type ][ $status ] += $n;
			}
		}

		uasort(
			$stats['sources'],
			static function ( $a, $b ) {
				return ( $b['sent'] + $b['failed'] ) <=> ( $a['sent'] + $a['failed'] );
			}
		);
		uasort(
			$stats['types'],
			static function ( $a, $b ) {
				return ( $b['sent'] + $b['failed'] ) <=> ( $a['sent'] + $a['failed'] );
			}
		);

		$last = $wpdb->get_row( $wpdb->prepare( "SELECT created_at, source_label, error FROM {$table} WHERE status = %s AND created_at >= %s ORDER BY id DESC LIMIT 1", self::STATUS_FAILED, $since ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( is_array( $last ) ) {
			$stats['last_failure'] = $last;
		}

		set_transient( $key, $stats, self::STATS_TTL );

		return $stats;
	}

	/**
	 * Failure share of all emails in a stats result, as a whole percentage.
	 *
	 * @param array{sent: int, failed: int} $counts Counts.
	 */
	public static function failure_rate( array $counts ): int {
		$total = $counts['sent'] + $counts['failed'];

		return $total > 0 ? (int) round( $counts['failed'] / $total * 100 ) : 0;
	}

	public static function clear_stats_cache(): void {
		delete_transient( 'ecehc_log_stats_' . DAY_IN_SECONDS );
		delete_transient( 'ecehc_log_stats_' . 7 * DAY_IN_SECONDS );
	}

	/**
	 * Deletes every log entry.
	 */
	public static function clear(): void {
		global $wpdb;

		$table = Schema::table();

		$wpdb->query( "DELETE FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		self::clear_stats_cache();
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
		 * @param int $days Days, from the log settings (1, 3 or 7). Pro can raise it.
		 */
		$days = max( 1, (int) apply_filters( 'ecehc_log_retention_days', LogSettings::retention_days() ) );

		/**
		 * Filters the maximum number of log rows kept.
		 *
		 * @param int $rows Rows, default 5000.
		 */
		$max_rows = max( 100, (int) apply_filters( 'ecehc_log_max_rows', 5000 ) );

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE created_at < %s", gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		self::clear_stats_cache();

		$cutoff = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} ORDER BY id DESC LIMIT 1 OFFSET %d", $max_rows ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $cutoff ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id <= %d", (int) $cutoff ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
}
