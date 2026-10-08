<?php
namespace CodeSir\EmailHealthCheck\Checks;

use CodeSir\EmailHealthCheck\Support\Domain;
use CodeSir\EmailHealthCheck\Support\MailSender;

defined( 'ABSPATH' ) || exit;

class SenderAddressCheck implements CheckInterface {

	public function get_id(): string {
		return 'from_address';
	}

	public function get_label(): string {
		return __( 'Sender Address Check', 'ecom-email-health-check' );
	}

	public function run(): Result {
		$from   = MailSender::from_address();
		$passed = Domain::email_domain( $from ) === Domain::site_domain();

		return new Result(
			$passed,
			sprintf(
				$passed
					/* translators: %s: email address */
					? __( 'Your emails are sent from %s, whose domain matches your site domain. This is good practice.', 'ecom-email-health-check' )
					/* translators: %s: email address */
					: __( 'Your emails are sent from %s, whose domain does not match your site domain. This is a common cause of spam folder delivery.', 'ecom-email-health-check' ),
				$from
			)
		);
	}
}
