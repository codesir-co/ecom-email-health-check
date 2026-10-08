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
		if ( ! function_exists( 'dns_get_record' ) ) {
			return Result::unknown( __( 'Your server does not allow DNS lookups, so the SPF record could not be checked. Use an online SPF checker for your domain.', 'ecom-email-health-check' ) );
		}

		$records = @dns_get_record( Domain::site_domain(), DNS_TXT ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( false === $records ) {
			return Result::unknown( __( 'The DNS lookup failed, so the SPF record could not be checked. Try again later or use an online SPF checker.', 'ecom-email-health-check' ) );
		}

		foreach ( $records as $record ) {
			if ( isset( $record['txt'] ) && false !== strpos( $record['txt'], 'v=spf1' ) ) {
				return new Result( true, __( 'A valid SPF record was found. This helps authenticate your emails.', 'ecom-email-health-check' ) );
			}
		}

		return new Result( false, __( 'No SPF record was found. This is a critical issue that makes your emails look suspicious to spam filters.', 'ecom-email-health-check' ) );
	}
}
