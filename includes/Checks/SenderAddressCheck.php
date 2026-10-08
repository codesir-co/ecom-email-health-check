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
		$domain = Domain::mail_domain();

		if ( Domain::is_free_mailbox( $domain ) ) {
			return new Result(
				false,
				sprintf(
					/* translators: 1: email address, 2: domain such as gmail.com */
					__( 'Your emails are sent from %1$s. Receivers expect mail from %2$s to come from its own servers, so mail sent from your site is likely to be rejected or sent to spam. Use an address at your own domain instead.', 'ecom-email-health-check' ),
					$from,
					$domain
				)
			);
		}

		if ( Domain::are_related( $domain, Domain::site_domain() ) ) {
			return new Result(
				true,
				sprintf(
					/* translators: %s: email address */
					__( 'Your emails are sent from %s, whose domain matches your site domain. This is good practice.', 'ecom-email-health-check' ),
					$from
				)
			);
		}

		return Result::warning(
			sprintf(
				/* translators: 1: email address, 2: domain of the From address */
				__( 'Your emails are sent from %1$s, whose domain differs from your site domain. That is fine if %2$s is a domain you control and your mail service is set up for it. The SPF, DKIM and DMARC checks below are run for %2$s.', 'ecom-email-health-check' ),
				$from,
				$domain
			)
		);
	}
}
