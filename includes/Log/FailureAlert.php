<?php
/**
 * Decides whether recent email failures are worth an alert.
 *
 * It only reads the email log, so the alert shows up inside wp-admin and does
 * not need email to work.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Log;

defined( 'ABSPATH' ) || exit;

class FailureAlert {

	/**
	 * The last 24 hours of the log, or null when logging is unsupported.
	 *
	 * @return array{sent: int, failed: int, rate: int, needs_attention: bool, last_failure: array<string, string>|null}|null
	 */
	public static function evaluate(): ?array {
		if ( ! EmailLogger::is_supported() ) {
			return null;
		}

		$stats = EmailLog::stats( DAY_IN_SECONDS );
		$rate  = EmailLog::failure_rate( $stats );

		/**
		 * Filters the number of failed emails in 24 hours that triggers the alert.
		 *
		 * @param int $failures Default 3.
		 */
		$min_failures = max( 1, (int) apply_filters( 'ecehc_alert_min_failures', 3 ) );

		/**
		 * Filters the failure rate (percent of emails in 24 hours) that triggers the alert.
		 *
		 * @param int $percent Default 10.
		 */
		$min_rate = max( 1, (int) apply_filters( 'ecehc_alert_min_rate', 10 ) );

		return array(
			'sent'            => $stats['sent'],
			'failed'          => $stats['failed'],
			'rate'            => $rate,
			'needs_attention' => $stats['failed'] >= $min_failures && $rate >= $min_rate,
			'last_failure'    => $stats['last_failure'],
		);
	}

	/**
	 * One-line summary of the numbers.
	 *
	 * @param array{sent: int, failed: int, rate: int} $alert Result of evaluate().
	 */
	public static function summary( array $alert ): string {
		return sprintf(
			/* translators: 1: accepted emails, 2: failed emails, 3: failure rate percent */
			__( 'Last 24 hours: %1$d accepted, %2$d failed (%3$d%% failure rate).', 'ecom-email-health-check' ),
			$alert['sent'],
			$alert['failed'],
			$alert['rate']
		);
	}
}
