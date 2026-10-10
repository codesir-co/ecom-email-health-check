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

	/** @var string Error message captured from wp_mail_failed during the last send. */
	private $error = '';

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

		$this->error = '';
		add_action( 'wp_mail_failed', array( $this, 'capture_error' ) );

		$sent = wp_mail(
			get_option( 'admin_email' ),
			__( 'Email Health Check: Test Email', 'ecom-email-health-check' ),
			__( 'This is a test email sent from the Email Health Check plugin. If you received this, your site can send basic emails.', 'ecom-email-health-check' )
		);

		remove_action( 'wp_mail_failed', array( $this, 'capture_error' ) );

		update_option(
			self::OPTION_RESULT,
			array(
				'success' => (bool) $sent,
				'error'   => $this->error,
				'time'    => time(),
			),
			false
		);

		add_action( 'admin_notices', $sent ? array( $this, 'notice_success' ) : array( $this, 'notice_failure' ) );
	}

	/**
	 * Outcome of the most recent test email, or null if none was sent.
	 *
	 * @return array{success: bool, time: int, error: string}|null
	 */
	public static function get_last_result(): ?array {
		$last = get_option( self::OPTION_RESULT );

		if ( ! is_array( $last ) || ! isset( $last['success'] ) ) {
			return null;
		}

		return array(
			'success' => (bool) $last['success'],
			'time'    => isset( $last['time'] ) ? (int) $last['time'] : 0,
			'error'   => isset( $last['error'] ) ? (string) $last['error'] : '',
		);
	}

	/**
	 * @param \WP_Error $error Error raised by wp_mail().
	 */
	public function capture_error( $error ): void {
		if ( $error instanceof \WP_Error ) {
			$this->error = $error->get_error_message();
		}
	}

	public function notice_success(): void {
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Test email handed off for sending.', 'ecom-email-health-check' ) . '</strong> ' . esc_html__( 'Please check your admin inbox to confirm it actually arrived.', 'ecom-email-health-check' ) . '</p></div>';
	}

	public function notice_failure(): void {
		echo '<div class="notice notice-error is-dismissible">';
		echo '<p><strong>' . esc_html__( 'Test email failed to send.', 'ecom-email-health-check' ) . '</strong> ' . esc_html__( 'This is a common issue with standard hosting providers.', 'ecom-email-health-check' ) . '</p>';
		echo '<p>' . esc_html__( 'Review the checks below, and consider sending your email through an SMTP service or asking your hosting provider whether outgoing email is blocked.', 'ecom-email-health-check' ) . '</p>';
		echo '</div>';
	}
}
