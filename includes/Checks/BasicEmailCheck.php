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

		// date_i18n() instead of wp_date() (WordPress 5.3+): the plugin supports WordPress 5.0.
		$when = $last['time']
			? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $last['time'] ) ) ) )
			: '';
		/* translators: %s: date and time the test email was sent */
		$suffix = $when ? ' ' . sprintf( __( '(Sent %s.)', 'ecom-email-health-check' ), $when ) : '';

		if ( $last['success'] ) {
			return new Result(
				true,
				__( 'The last test email was accepted for sending by WordPress. This does not guarantee delivery, so check your inbox to confirm it arrived.', 'ecom-email-health-check' ) . $suffix
			);
		}

		if ( '' !== $last['error'] ) {
			/* translators: %s: error message returned by the mail system */
			$message = sprintf( __( 'The last test email failed to send. Error: %s', 'ecom-email-health-check' ), wp_html_excerpt( $last['error'], 300, '…' ) );
		} else {
			$message = __( 'The last test email failed to send. Your hosting provider may be blocking emails.', 'ecom-email-health-check' );
		}

		return new Result( false, $message . $suffix );
	}
}
