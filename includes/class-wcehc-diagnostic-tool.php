<?php
// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCEHC_Diagnostic_Tool {

	/**
	 * Runs all diagnostic checks and returns the results.
	 *
	 * @return array
	 */
	public function run_all_checks() {
		$results = array();

		// Check 1: Basic Email Functionality
		$results['basic_functionality'] = array(
			'label' => __( 'Basic Email Functionality', 'wcehc' ),
			'status' => $this->check_basic_functionality(),
			'message' => $this->get_message_for_check('basic_functionality'),
		);

		// Check 2: From Address Validation
		$results['from_address'] = array(
			'label' => __( 'Sender Address Check', 'wcehc' ),
			'status' => $this->check_from_address(),
			'message' => $this->get_message_for_check('from_address'),
		);

		// Check 3: SPF Record
		$results['spf_record'] = array(
			'label' => __( 'SPF Record Validation', 'wcehc' ),
			'status' => $this->check_spf_record(),
			'message' => $this->get_message_for_check('spf_record'),
		);

		return $results;
	}

	/**
	 * Checks if wp_mail() is configured to return a successful status.
	 * We don't actually send a real email here.
	 *
	 * @return bool
	 */
	private function check_basic_functionality() {
		$test_email_result = wp_mail( 'test@example.com', 'Test', 'Test' );
		return (bool) $test_email_result;
	}

	/**
	 * Checks if the sender's domain matches the site's domain.
	 *
	 * @return bool
	 */
	private function check_from_address() {
		$admin_email_domain = substr( strrchr( get_option( 'admin_email' ), "@" ), 1 );
		$site_domain = str_replace( 'www.', '', wp_parse_url( get_home_url(), PHP_URL_HOST ) );

		return $admin_email_domain === $site_domain;
	}

	/**
	 * Checks for the presence of an SPF record.
	 *
	 * @return bool
	 */
	private function check_spf_record() {
		$domain = str_replace( 'www.', '', wp_parse_url( get_home_url(), PHP_URL_HOST ) );
		$records = @dns_get_record( $domain, DNS_TXT );

		if ( $records ) {
			foreach ( $records as $record ) {
				if ( isset( $record['txt'] ) && strpos( $record['txt'], 'v=spf1' ) !== false ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Returns a message for a given check result.
	 *
	 * @param string $check_name
	 * @return string
	 */
	private function get_message_for_check( $check_name ) {
		switch ( $check_name ) {
			case 'basic_functionality':
				return true === $this->check_basic_functionality()
					? __( 'The WordPress `wp_mail()` function is working correctly.', 'wcehc' )
					: __( 'The `wp_mail()` function is failing. Your hosting provider may be blocking emails.', 'wcehc' );
			case 'from_address':
				return true === $this->check_from_address()
					? __( 'Your sender address domain matches your site domain, which is good practice.', 'wcehc' )
					: __( 'Your sender address domain does not match your site domain. This is a common cause of spam folder delivery.', 'wcehc' );
			case 'spf_record':
				return true === $this->check_spf_record()
					? __( 'A valid SPF record was found. This helps authenticate your emails.', 'wcehc' )
					: __( 'No SPF record was found. This is a critical issue that makes your emails look suspicious to spam filters.', 'wcehc' );
			default:
				return '';
		}
	}
}