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

		if ( $last['success'] ) {
			return new Result(
				true,
				__( 'The last test email was accepted for sending by WordPress. This does not guarantee delivery, so check your inbox to confirm it arrived.', 'ecom-email-health-check' )
			);
		}

		$message = __( 'The last test email failed to send. Your hosting provider may be blocking emails.', 'ecom-email-health-check' );
		if ( '' !== $last['error'] ) {
			/* translators: %s: error message returned by the mail system */
			$message .= ' ' . sprintf( __( 'Error: %s', 'ecom-email-health-check' ), $last['error'] );
		}

		return new Result( false, $message );
	}
}
