<?php
/**
 * Handles the "Hide checklist" button.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

defined( 'ABSPATH' ) || exit;

class ChecklistHandler {

	const NONCE_ACTION = 'ecehc_hide_checklist';
	const NONCE_FIELD  = '_ecehc_checklist_nonce';

	public function register(): void {
		add_action( 'admin_init', array( $this, 'handle' ) );
	}

	public function handle(): void {
		if ( ! isset( $_POST['ecehc_hide_checklist'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified below.
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ecom-email-health-check' ) );
		}

		Checklist::hide( get_current_user_id() );

		wp_safe_redirect( add_query_arg( 'page', AdminPage::MENU_SLUG, admin_url( 'admin.php' ) ) );
		exit;
	}
}
