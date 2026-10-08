<?php
/**
 * Looks for a DMARC policy record.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

use CodeSir\EmailHealthCheck\Support\Dns;
use CodeSir\EmailHealthCheck\Support\Domain;

defined( 'ABSPATH' ) || exit;

class DmarcCheck implements CheckInterface {

	public function get_id(): string {
		return 'dmarc_record';
	}

	public function get_label(): string {
		return __( 'DMARC Record', 'ecom-email-health-check' );
	}

	public function run(): Result {
		$domain = Domain::mail_domain();

		if ( Domain::is_free_mailbox( $domain ) ) {
			return Result::unknown( Domain::free_mailbox_notice( $domain ) );
		}

		$records = Dns::txt_records( '_dmarc.' . $domain );

		if ( null === $records ) {
			return Result::unknown( __( 'The DNS lookup was not possible on this server, so the DMARC record could not be checked. Use an online DMARC checker for your domain.', 'ecom-email-health-check' ) );
		}

		foreach ( $records as $record ) {
			if ( ! preg_match( '/^v=DMARC1\s*(;|$)/i', $record ) ) {
				continue;
			}

			if ( preg_match( '/;\s*p\s*=\s*none\b/i', $record ) ) {
				return new Result( true, __( 'A DMARC record was found, but its policy is "none" (monitoring only). Once your email is authenticated correctly, consider moving to "quarantine" or "reject".', 'ecom-email-health-check' ) );
			}

			return new Result( true, __( 'A DMARC record was found. This tells receiving servers how to treat email that fails authentication.', 'ecom-email-health-check' ) );
		}

		return new Result( false, __( 'No DMARC record was found. Without one, mailbox providers have no policy for spoofed or unauthenticated email from your domain, which can hurt deliverability.', 'ecom-email-health-check' ) );
	}
}
