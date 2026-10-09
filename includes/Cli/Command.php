<?php
/**
 * WP-CLI commands: wp ehc check, wp ehc log.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Cli;

use CodeSir\EmailHealthCheck\Checks\CheckRunner;
use CodeSir\EmailHealthCheck\Log\EmailLog;
use CodeSir\EmailHealthCheck\Log\EmailLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Checks your email setup and reads the email log.
 */
class Command {

	/**
	 * Runs all email health checks.
	 *
	 * Exits with status 1 when any check fails, so it can be used in scripts.
	 * Warnings and "unknown" results do not change the exit status.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp ehc check
	 *     wp ehc check --format=json
	 *
	 * @param array $args       Positional arguments (none).
	 * @param array $assoc_args Associative arguments.
	 */
	public function check( $args, $assoc_args ) {
		$results = ( new CheckRunner() )->run_all();
		$failed  = false;
		$items   = array();

		foreach ( $results as $id => $result ) {
			$failed  = $failed || 'fail' === $result['status'];
			$items[] = array(
				'check'   => $id,
				'label'   => $result['label'],
				'status'  => $result['status'],
				'message' => preg_replace( '/\s+/', ' ', $result['message'] ),
			);
		}

		\WP_CLI\Utils\format_items( \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' ), $items, array( 'check', 'label', 'status', 'message' ) );

		if ( $failed ) {
			\WP_CLI::halt( 1 );
		}
	}

	/**
	 * Shows recent logged emails or statistics.
	 *
	 * Recipients are partly hidden in the log. "sent" means WordPress accepted the
	 * email for sending, not that it was delivered.
	 *
	 * ## OPTIONS
	 *
	 * [--source=<source>]
	 * : Only emails from this source key (for example woocommerce, wordpress or a plugin folder name).
	 *
	 * [--status=<status>]
	 * : Only emails with this status.
	 * ---
	 * options:
	 *   - sent
	 *   - failed
	 * ---
	 *
	 * [--type=<type>]
	 * : Only this WooCommerce email type id (for example new_order).
	 *
	 * [--limit=<limit>]
	 * : How many emails to show, 1 to 100.
	 * ---
	 * default: 20
	 * ---
	 *
	 * [--stats]
	 * : Show accepted and failed counts for the last 24 hours and 7 days instead of emails.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp ehc log --status=failed
	 *     wp ehc log --source=woocommerce --limit=50 --format=csv
	 *     wp ehc log --stats
	 *
	 * @param array $args       Positional arguments (none).
	 * @param array $assoc_args Associative arguments.
	 */
	public function log( $args, $assoc_args ) {
		if ( ! EmailLogger::is_supported() ) {
			\WP_CLI::error( 'The email log needs WordPress 5.9 or newer.' );
		}

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'stats', false ) ) {
			$items = array();

			foreach ( array(
				'last 24 hours' => DAY_IN_SECONDS,
				'last 7 days'   => 7 * DAY_IN_SECONDS,
			) as $label => $seconds ) {
				$stats   = EmailLog::stats( $seconds );
				$items[] = array(
					'period'       => $label,
					'accepted'     => $stats['sent'],
					'failed'       => $stats['failed'],
					'failure_rate' => EmailLog::failure_rate( $stats ) . '%',
				);
			}

			\WP_CLI\Utils\format_items( $format, $items, array( 'period', 'accepted', 'failed', 'failure_rate' ) );
			return;
		}

		$status = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'status', '' );
		if ( '' !== $status && ! in_array( $status, array( EmailLog::STATUS_SENT, EmailLog::STATUS_FAILED ), true ) ) {
			\WP_CLI::error( 'The status must be "sent" or "failed".' );
		}

		$limit = min( max( 1, (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'limit', 20 ) ), EmailLog::VIEW_LIMIT );

		$result = EmailLog::query(
			array(
				'source' => (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'source', '' ),
				'status' => $status,
				'type'   => (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'type', '' ),
			),
			$limit,
			1
		);

		$items = array();
		foreach ( $result['rows'] as $row ) {
			$items[] = array(
				'time_utc'  => $row['created_at'],
				'status'    => $row['status'],
				'source'    => $row['source_label'],
				'wc_email'  => $row['email_type_label'],
				'recipient' => $row['recipient'],
				'error'     => $row['error'],
			);
		}

		\WP_CLI\Utils\format_items( $format, $items, array( 'time_utc', 'status', 'source', 'wc_email', 'recipient', 'error' ) );
	}
}
