<?php
namespace CodeSir\EmailHealthCheck\Checks;

use CodeSir\EmailHealthCheck\Support\Domain;

defined( 'ABSPATH' ) || exit;

class SpfCheck implements CheckInterface {

	public function get_id(): string {
		return 'spf_record';
	}

	public function get_label(): string {
		return __( 'SPF Record Validation', 'ecom-email-health-check' );
	}

	public function run(): Result {
		$passed = $this->has_spf_record( Domain::site_domain() );

		return new Result(
			$passed,
			$passed
				? __( 'A valid SPF record was found. This helps authenticate your emails.', 'ecom-email-health-check' )
				: __( 'No SPF record was found. This is a critical issue that makes your emails look suspicious to spam filters.', 'ecom-email-health-check' )
		);
	}

	private function has_spf_record( string $domain ): bool {
		$records = @dns_get_record( $domain, DNS_TXT ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( $records ) {
			foreach ( $records as $record ) {
				if ( isset( $record['txt'] ) && false !== strpos( $record['txt'], 'v=spf1' ) ) {
					return true;
				}
			}
		}
		return false;
	}
}
