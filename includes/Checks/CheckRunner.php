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
		);

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
		$domain   = Domain::site_domain();

		foreach ( $this->get_checks() as $check ) {
			if ( isset( $results[ $check->get_id() ] ) ) {
				_doing_it_wrong(
					__METHOD__,
					sprintf(
						/* translators: %s: check id */
						esc_html__( 'Duplicate diagnostic check id "%s" was skipped.', 'ecom-email-health-check' ),
						esc_html( $check->get_id() )
					),
					ECEHC_VERSION
				);
				continue;
			}

			$result                      = $check->run();
			$results[ $check->get_id() ] = array(
				'label'    => $check->get_label(),
				'status'   => $result->get_status(),
				'message'  => $result->get_message(),
				'guidance' => $result->passed() ? null : ProviderGuidance::for_check( $check->get_id(), $provider, $domain ),
			);
		}

		return $results;
	}
}
