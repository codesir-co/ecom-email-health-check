<?php
/**
 * Reports the outcome of the most recent test email.
 *
 * The report never sends mail on its own: it reflects the last explicit
 * "Send Test Email" attempt (see Admin\TestEmailHandler).
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

use CodeSir\EmailHealthCheck\Admin\TestEmailHandler;

defined( 'ABSPATH' ) || exit;

class BasicEmailCheck implements CheckInterface {

	public function get_id(): string {
		return 'basic_functionality';
	}

	public function get_label(): string {
		return __( 'Basic Email Functionality', 'ecom-email-health-check' );
	}

	public function run(): Result {
		$last = TestEmailHandler::get_last_result();

		if ( null === $last ) {
			return Result::unknown( __( 'No test email has been sent yet. Use "Send Test Email" to check that your site can send mail.', 'ecom-email-health-check' ) );
		}

		$when = $last['time'] && function_exists( 'wp_date' )
			? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last['time'] )
			: '';
		/* translators: %s: date and time the test email was sent */
		$suffix = $when ? ' ' . sprintf( __( '(Sent %s.)', 'ecom-email-health-check' ), $when ) : '';

		return new Result(
			$last['success'],
			( $last['success']
				? __( 'The last test email was accepted for sending by WordPress.', 'ecom-email-health-check' )
				: __( 'The last test email failed to send. Your hosting provider may be blocking emails.', 'ecom-email-health-check' )
			) . $suffix
		);
	}
}
