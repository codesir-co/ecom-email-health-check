<?php
/**
 * Handles the "Re-check" button, which clears the cached mail service detection.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

use CodeSir\EmailHealthCheck\Support\SmtpDetector;

defined( 'ABSPATH' ) || exit;

class RecheckHandler {

	const ACTION = 'ecehc_recheck';
	const NONCE  = '_ecehc_recheck_nonce';

	public function register(): void {
		add_action( 'admin_init', array( $this, 'handle' ) );
	}

	public function handle(): void {
		if ( ! isset( $_POST[ self::ACTION ] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), self::ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ecom-email-health-check' ) );
		}

		SmtpDetector::clear_cache();

		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	public function notice(): void {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Checks refreshed.', 'ecom-email-health-check' ) . '</p></div>';
	}
}
