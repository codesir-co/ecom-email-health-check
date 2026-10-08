<?php
namespace CodeSir\EmailHealthCheck\Checks;

use CodeSir\EmailHealthCheck\Support\Dns;
use CodeSir\EmailHealthCheck\Support\Domain;
use CodeSir\EmailHealthCheck\Support\MailProvider;
use CodeSir\EmailHealthCheck\Support\ProviderGuidance;

defined( 'ABSPATH' ) || exit;

class SpfCheck implements CheckInterface {

	public function get_id(): string {
		return 'spf_record';
	}

	public function get_label(): string {
		return __( 'SPF Record Validation', 'ecom-email-health-check' );
	}

	public function run(): Result {
		if ( ! function_exists( 'dns_get_record' ) ) {
			return Result::unknown( __( 'Your server does not allow DNS lookups, so the SPF record could not be checked. Use an online SPF checker for your domain.', 'ecom-email-health-check' ) );
		}

		$domain = Domain::mail_domain();

		if ( Domain::is_free_mailbox( $domain ) ) {
			return Result::unknown( Domain::free_mailbox_notice( $domain ) );
		}

		$records = Dns::txt_records( $domain );

		if ( null === $records ) {
			return Result::unknown( __( 'The DNS lookup failed, so the SPF record could not be checked. Try again later or use an online SPF checker.', 'ecom-email-health-check' ) );
		}

		$spf_records = array();
		foreach ( $records as $record ) {
			if ( preg_match( '/^v=spf1(\s|$)/i', $record ) ) {
				$spf_records[] = $record;
			}
		}

		if ( count( $spf_records ) > 1 ) {
			return new Result( false, __( 'Multiple SPF records were found. Only one is allowed (RFC 7208), and receivers will treat your SPF as invalid. Merge them into a single record.', 'ecom-email-health-check' ) );
		}

		if ( 1 === count( $spf_records ) ) {
			if ( ! preg_match( '/\s[+\-~?]?(include:|ip4:|ip6:|a(?=[\s:\/]|$)|mx(?=[\s:\/]|$)|exists:)|\sredirect=/i', $spf_records[0] ) ) {
				return new Result( false, __( 'An SPF record exists but does not authorize any sending service (no include:, ip4:, ip6:, a, mx or redirect). Add the service that sends your email, such as your SMTP provider or host.', 'ecom-email-health-check' ) );
			}

			$provider = MailProvider::detect();

			if ( $provider ) {
				foreach ( $provider['spf'] as $spf_domain ) {
					if ( false !== stripos( $spf_records[0], $spf_domain ) ) {
						/* translators: %s: mail service name */
						return new Result( true, sprintf( __( 'A valid SPF record was found and it appears to include %s, the service your site sends email through.', 'ecom-email-health-check' ), $provider['name'] ) );
					}
				}

				if ( ! ProviderGuidance::needs_root_spf( $provider ) ) {
					return new Result(
						true,
						sprintf(
							/* translators: %s: mail service name */
							__( 'A valid SPF record was found. %s authenticates SPF through its own return-path records, so it does not need to appear in your root SPF record. Make sure you completed its domain setup.', 'ecom-email-health-check' ),
							$provider['name']
						)
					);
				}

				return Result::warning(
					sprintf(
						/* translators: %s: mail service name */
						__( 'Your site sends email through %s, but your SPF record does not appear to include it. Add the include value from your provider\'s DNS instructions to your SPF record, or emails may fail SPF. (If %s is listed inside another include, you can ignore this.)', 'ecom-email-health-check' ),
						$provider['name'],
						$provider['name']
					)
				);
			}

			return new Result( true, __( 'A valid SPF record was found. This helps authenticate your emails. Make sure it includes the service that actually sends your mail.', 'ecom-email-health-check' ) );
		}

		return new Result( false, __( 'No SPF record was found. This is a critical issue that makes your emails look suspicious to spam filters.', 'ecom-email-health-check' ) );
	}
}
