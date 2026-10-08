<?php
/**
 * Warns when mail appears to go out through plain PHP mail.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

use CodeSir\EmailHealthCheck\Support\MailProvider;
use CodeSir\EmailHealthCheck\Support\SmtpDetector;

defined( 'ABSPATH' ) || exit;

class SmtpConfiguredCheck implements CheckInterface {

	public function get_id(): string {
		return 'smtp_configured';
	}

	public function get_label(): string {
		return __( 'SMTP / Mail Service', 'ecom-email-health-check' );
	}

	public function run(): Result {
		if ( ! SmtpDetector::is_configured() ) {
			return Result::warning( __( 'No SMTP plugin or mail service was detected, so WordPress is probably sending email through your web host\'s default PHP mail. That mail is unauthenticated and is often filtered as spam or blocked. Use an SMTP plugin or a transactional email service to send authenticated email.', 'ecom-email-health-check' ) );
		}

		$provider = MailProvider::detect();
		$plugins  = SmtpDetector::plugin_names();
		$via      = implode( ', ', $plugins );

		if ( $provider && $via ) {
			return new Result(
				true,
				sprintf(
					/* translators: 1: mail provider name, e.g. SendGrid, 2: plugin name(s) */
					__( 'Email appears to be sent through %1$s, handled by %2$s.', 'ecom-email-health-check' ),
					$provider['name'],
					$via
				)
			);
		}

		if ( $provider ) {
			return new Result(
				true,
				sprintf(
					/* translators: %s: mail provider name, e.g. SendGrid */
					__( 'Email appears to be sent through %s.', 'ecom-email-health-check' ),
					$provider['name']
				)
			);
		}

		if ( $via ) {
			return new Result(
				true,
				sprintf(
					/* translators: %s: plugin name(s) */
					__( 'Outgoing email is handled by: %s.', 'ecom-email-health-check' ),
					$via
				)
			);
		}

		return new Result( true, __( 'A plugin or mail service that handles outgoing email was detected.', 'ecom-email-health-check' ) );
	}
}
