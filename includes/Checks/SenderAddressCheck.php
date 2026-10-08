<?php
namespace CodeSir\EmailHealthCheck\Checks;

use CodeSir\EmailHealthCheck\Support\Domain;

defined( 'ABSPATH' ) || exit;

class SenderAddressCheck implements CheckInterface {

	public function get_id(): string {
		return 'from_address';
	}

	public function get_label(): string {
		return __( 'Sender Address Check', 'ecom-email-health-check' );
	}

	public function run(): Result {
		$passed = Domain::email_domain( (string) get_option( 'admin_email' ) ) === Domain::site_domain();

		return new Result(
			$passed,
			$passed
				? __( 'Your sender address domain matches your site domain, which is good practice.', 'ecom-email-health-check' )
				: __( 'Your sender address domain does not match your site domain. This is a common cause of spam folder delivery.', 'ecom-email-health-check' )
		);
	}
}
