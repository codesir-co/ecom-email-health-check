<?php
namespace CodeSir\EmailHealthCheck\Checks;

defined( 'ABSPATH' ) || exit;

class BasicEmailCheck implements CheckInterface {

	public function get_id(): string {
		return 'basic_functionality';
	}

	public function get_label(): string {
		return __( 'Basic Email Functionality', 'ecom-email-health-check' );
	}

	public function run(): Result {
		$passed = (bool) wp_mail( 'test@example.com', 'Test', 'Test' );

		return new Result(
			$passed,
			$passed
				? __( 'The WordPress `wp_mail()` function is working correctly.', 'ecom-email-health-check' )
				: __( 'The `wp_mail()` function is failing. Your hosting provider may be blocking emails.', 'ecom-email-health-check' )
		);
	}
}
