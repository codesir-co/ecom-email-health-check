<?php
/**
 * Handles the "Send Test Email" form.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

use CodeSir\EmailHealthCheck\Log\EmailLogger;

defined( 'ABSPATH' ) || exit;

class TestEmailHandler {

	const ACTION        = 'ecehc_send_test_email';
	const OPTION_RESULT = 'ecehc_last_test_email';

	/** Test emails one user may send within RATE_WINDOW. */
	const RATE_LIMIT = 5;

	/** Seconds the rate limit counts over. */
	const RATE_WINDOW = 5 * MINUTE_IN_SECONDS;

	/** @var string Error message captured from wp_mail_failed during the last send. */
	private $error = '';

	/** @var array{type: string, message: string, to: string}|null What to tell the user after handling the form. */
	private $outcome = null;

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

		// Recipient: the admin address unless another valid address was typed.
		// Not sanitize_text_field(): it would strip valid characters such as a percent sign in the local part and silently change the address. Control characters are removed, then is_email() decides.
		$to = isset( $_POST['ecehc_test_to'] ) ? trim( (string) preg_replace( '/[\x00-\x1F\x7F]/', '', wp_unslash( $_POST['ecehc_test_to'] ) ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- validated with is_email() below.
		$to = '' === $to ? (string) get_option( 'admin_email' ) : $to;

		if ( ! is_email( $to ) ) {
			$this->outcome = array(
				'type'    => 'invalid',
				'message' => '',
				'to'      => '',
			);
			add_action( 'admin_notices', array( $this, 'show_outcome' ) );
			return;
		}

		if ( $this->rate_limited() ) {
			$this->outcome = array(
				'type'    => 'limited',
				'message' => '',
				'to'      => '',
			);
			add_action( 'admin_notices', array( $this, 'show_outcome' ) );
			return;
		}

		$subject = isset( $_POST['ecehc_test_subject'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['ecehc_test_subject'] ) ) ) : '';
		$subject = '' === $subject ? __( 'Email Health Check: Test Email', 'ecom-email-health-check' ) : $subject;
		$subject = function_exists( 'mb_substr' ) ? mb_substr( $subject, 0, 150 ) : substr( $subject, 0, 150 );

		$this->error = '';
		add_action( 'wp_mail_failed', array( $this, 'capture_error' ) );

		$sent = wp_mail(
			$to,
			$subject,
			__( 'This is a test email sent from the Email Health Check plugin. If you received this, your site can send basic emails.', 'ecom-email-health-check' )
		);

		remove_action( 'wp_mail_failed', array( $this, 'capture_error' ) );

		update_option(
			self::OPTION_RESULT,
			array(
				'success' => (bool) $sent,
				'error'   => EmailLogger::mask_addresses( $this->error ),
				'time'    => time(),
			),
			false
		);

		$this->outcome = array(
			'type'    => $sent ? 'success' : 'failure',
			'message' => EmailLogger::mask_addresses( $this->error ),
			'to'      => EmailLogger::mask_recipient( $to )['masked'],
		);
		add_action( 'admin_notices', array( $this, 'show_outcome' ) );
	}

	/**
	 * Counts this send against the current user's limit; true when the limit is already used up.
	 * The limit stops the form being used to flood an inbox.
	 */
	private function rate_limited(): bool {
		$key   = 'ecehc_test_rate_' . get_current_user_id();
		$state = get_transient( $key );
		$now   = time();

		// The window starts at the first send and is not extended by later ones.
		if ( ! is_array( $state ) || ! isset( $state['count'], $state['start'] ) || $now - (int) $state['start'] >= self::RATE_WINDOW ) {
			$state = array(
				'count' => 0,
				'start' => $now,
			);
		}

		if ( (int) $state['count'] >= self::RATE_LIMIT ) {
			return true;
		}

		++$state['count'];
		set_transient( $key, $state, max( 1, self::RATE_WINDOW - ( $now - (int) $state['start'] ) ) );

		return false;
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

	/**
	 * Prints the notice for what the form did.
	 */
	public function show_outcome(): void {
		if ( null === $this->outcome ) {
			return;
		}

		$type = $this->outcome['type'];

		if ( 'invalid' === $type ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'That does not look like a valid email address. No test email was sent.', 'ecom-email-health-check' ) . '</p></div>';
			return;
		}

		if ( 'limited' === $type ) {
			echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html__( 'You have sent several test emails in the last few minutes. Please wait a little before sending another.', 'ecom-email-health-check' ) . '</p></div>';
			return;
		}

		if ( 'success' === $type ) {
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Test email handed off for sending.', 'ecom-email-health-check' ) . '</strong> ';
			echo esc_html(
				sprintf(
					/* translators: %s: partly hidden email address the test was sent to */
					__( 'It was sent to %s. Please check that inbox to confirm it actually arrived.', 'ecom-email-health-check' ),
					$this->outcome['to']
				)
			);

			if ( EmailLogger::is_enabled() ) {
				$log_url = add_query_arg(
					array(
						'page' => AdminPage::MENU_SLUG,
						'tab'  => 'log',
					),
					admin_url( 'admin.php' )
				);
				echo ' <a href="' . esc_url( $log_url ) . '">' . esc_html__( 'View it in the Email Log', 'ecom-email-health-check' ) . '</a>';
			}

			echo '</p></div>';
			return;
		}

		echo '<div class="notice notice-error is-dismissible">';
		echo '<p><strong>' . esc_html__( 'Test email failed to send.', 'ecom-email-health-check' ) . '</strong> ' . esc_html__( 'This is a common issue with standard hosting providers.', 'ecom-email-health-check' ) . '</p>';

		if ( '' !== $this->outcome['message'] ) {
			echo '<p>' . esc_html(
				sprintf(
					/* translators: %s: error message from the mail system */
					__( 'The mail system said: %s', 'ecom-email-health-check' ),
					$this->outcome['message']
				)
			) . '</p>';
		}

		echo '<p>' . esc_html__( 'Review the checks below, and consider sending your email through an SMTP service or asking your hosting provider whether outgoing email is blocked.', 'ecom-email-health-check' ) . '</p>';
		echo '</div>';
	}
}
