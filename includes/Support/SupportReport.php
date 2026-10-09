<?php
/**
 * Plain-text summary of the email setup, for pasting into a support ticket.
 *
 * It contains versions, check results, the detected mail provider and recent
 * log counts. It never contains passwords, keys, message contents or full
 * email addresses (addresses inside messages are partly hidden).
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

use CodeSir\EmailHealthCheck\Log\EmailLog;
use CodeSir\EmailHealthCheck\Log\EmailLogger;

defined( 'ABSPATH' ) || exit;

class SupportReport {

	/**
	 * @param array<string, array{label: string, status: string, message: string}> $results Results from CheckRunner::run_all().
	 */
	public static function build( array $results ): string {
		$lines   = array();
		$lines[] = 'eCommerce Email Health Check report';
		$lines[] = 'Generated: ' . gmdate( 'Y-m-d H:i' ) . ' UTC';
		$lines[] = '';
		$lines[] = sprintf(
			'Plugin %1$s | WordPress %2$s | PHP %3$s | WooCommerce %4$s',
			ECEHC_VERSION,
			get_bloginfo( 'version' ),
			PHP_VERSION,
			defined( 'WC_VERSION' ) ? WC_VERSION : 'not active'
		);

		$provider = MailProvider::detect();
		$plugins  = SmtpDetector::plugin_names();

		$lines[] = 'Mail domain: ' . Domain::mail_domain() . ' | Site domain: ' . Domain::site_domain();
		$lines[] = 'Mail provider: ' . ( $provider ? $provider['name'] : 'not detected' );
		$lines[] = 'Outgoing email handled by: ' . ( $plugins ? implode( ', ', $plugins ) : 'no plugin detected (plain PHP mail?)' );
		$lines[] = '';
		$lines[] = 'Checks:';

		foreach ( $results as $result ) {
			$lines[] = sprintf(
				'- [%1$s] %2$s: %3$s',
				strtoupper( $result['status'] ),
				$result['label'],
				EmailLogger::mask_addresses( (string) preg_replace( '/\s+/', ' ', $result['message'] ) )
			);
		}

		if ( EmailLogger::is_enabled() ) {
			$lines[] = '';
			foreach ( array(
				'last 24 hours' => DAY_IN_SECONDS,
				'last 7 days'   => 7 * DAY_IN_SECONDS,
			) as $label => $seconds ) {
				$stats   = EmailLog::stats( $seconds );
				$lines[] = sprintf(
					'Email log, %1$s: %2$d accepted, %3$d failed (%4$d%% failure rate)',
					$label,
					$stats['sent'],
					$stats['failed'],
					EmailLog::failure_rate( $stats )
				);
			}
		}

		return implode( "\n", $lines ) . "\n";
	}
}
