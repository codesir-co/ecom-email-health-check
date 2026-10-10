<?php
/**
 * Runs every registered check once.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

use CodeSir\EmailHealthCheck\Support\Domain;
use CodeSir\EmailHealthCheck\Support\MailProvider;
use CodeSir\EmailHealthCheck\Support\ProviderGuidance;

defined( 'ABSPATH' ) || exit;

class CheckRunner {

	/**
	 * @return CheckInterface[]
	 */
	public function get_checks(): array {
		$checks = array(
			new BasicEmailCheck(),
			new SmtpConfiguredCheck(),
			new SenderAddressCheck(),
			new SpfCheck(),
			new DkimCheck(),
			new DmarcCheck(),
			new BlacklistCheck(),
		);

		if ( class_exists( 'WooCommerce' ) ) {
			$checks[] = new PendingOrdersCheck();
			$checks[] = new WooCommerceEmailCheck();
		}

		/**
		 * Filters the list of diagnostic checks.
		 *
		 * @param CheckInterface[] $checks Check instances.
		 */
		return array_filter(
			(array) apply_filters( 'ecehc_checks', $checks ),
			static function ( $check ) {
				return $check instanceof CheckInterface;
			}
		);
	}

	/**
	 * @return array<string, array{label: string, status: string, message: string, guidance: array|null}>
	 */
	public function run_all(): array {
		$results  = array();
		$provider = MailProvider::detect();
		$domain   = Domain::mail_domain();

		foreach ( $this->get_checks() as $check ) {
			if ( isset( $results[ $check->get_id() ] ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %s: check id */
						esc_html__( 'Duplicate diagnostic check id "%s" was skipped.', 'ecom-email-health-check' ),
						esc_html( $check->get_id() )
					),
					esc_html( ECEHC_VERSION )
				);
				continue;
			}

			$result  = $check->run();
			$message = $result->get_message();

			// Make it clear which domain the DNS checks looked at when it is not the site's own.
			if ( in_array( $check->get_id(), array( 'spf_record', 'dkim_record', 'dmarc_record' ), true ) && $domain !== Domain::site_domain() && ! Domain::is_free_mailbox( $domain ) ) {
				$message .= ' ' . sprintf(
					/* translators: %s: domain of the From address */
					__( '(Checked for %s, the domain of your From address.)', 'ecom-email-health-check' ),
					$domain
				);
			}

			$results[ $check->get_id() ] = array(
				'label'    => $check->get_label(),
				'status'   => $result->get_status(),
				'message'  => $message,
				'guidance' => in_array( $result->get_status(), array( Result::FAIL, Result::WARNING ), true ) ? ProviderGuidance::for_check( $check->get_id(), $provider, $domain ) : null,
			);
		}

		return $results;
	}
}
