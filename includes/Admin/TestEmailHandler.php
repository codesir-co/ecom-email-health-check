<?php
/**
 * Handles the "Send Test Email" form.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

defined( 'ABSPATH' ) || exit;

class TestEmailHandler {

	const ACTION        = 'ecehc_send_test_email';
	const OPTION_RESULT = 'ecehc_last_test_email';

	public function register(): void {
		add_action( 'admin_init', array( $this, 'handle' ) );
	}

	public function handle(): void {
		if ( ! isset( $_POST[ self::ACTION ] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::ACTION ) ) {
			wp_die( 'Security check failed.' );
		}

		$sent = wp_mail(
			get_option( 'admin_email' ),
			__( 'eCommerce Email Health Check: Test Email', 'ecom-email-health-check' ),
			__( 'This is a test email sent from the eCommerce Email Health Check plugin. If you received this, your site can send basic emails.', 'ecom-email-health-check' )
		);

		update_option(
			self::OPTION_RESULT,
			array(
				'success' => (bool) $sent,
				'time'    => time(),
			),
			false
		);

		add_action( 'admin_notices', $sent ? array( $this, 'notice_success' ) : array( $this, 'notice_failure' ) );
	}

	/**
	 * Outcome of the most recent test email, or null if none was sent.
	 *
	 * @return array{success: bool, time: int}|null
	 */
	public static function get_last_result(): ?array {
		$last = get_option( self::OPTION_RESULT );

		if ( ! is_array( $last ) || ! isset( $last['success'] ) ) {
			return null;
		}

		return array(
			'success' => (bool) $last['success'],
			'time'    => isset( $last['time'] ) ? (int) $last['time'] : 0,
		);
	}

	public function notice_success(): void {
		echo '<div class="notice notice-success is-dismissible"><p><strong>Test email sent successfully!</strong> Please check your admin inbox to confirm receipt.</p></div>';
	}

	public function notice_failure(): void {
		echo '<div class="notice notice-error is-dismissible">
			<p><strong>Test email failed to send.</strong> This is a common issue with standard hosting providers.</p>
			<p>Our managed service handles all the technical details and ensures your emails are always delivered. <a href="https://codesir.co/mailsir/?utm_source=plugin&utm_medium=test_email_fail" target="_blank" style="font-weight: bold;">Fix this issue now</a>.</p>
		</div>';
	}
}
