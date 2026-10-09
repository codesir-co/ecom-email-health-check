<?php
/**
 * Handles the buttons of the review request.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

defined( 'ABSPATH' ) || exit;

class ReviewPromptHandler {

	const NONCE_ACTION = 'ecehc_review_prompt';
	const NONCE_FIELD  = '_ecehc_review_nonce';

	public function register(): void {
		add_action( 'admin_init', array( $this, 'handle' ) );
	}

	public function handle(): void {
		if ( ! isset( $_POST['ecehc_review_action'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified below.
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ecom-email-health-check' ) );
		}

		$action = 'done' === sanitize_key( wp_unslash( $_POST['ecehc_review_action'] ) ) ? 'done' : 'later';

		ReviewPrompt::dismiss( get_current_user_id(), $action );

		// Come back to the tab the request was on.
		$tab = isset( $_POST['ecehc_review_tab'] ) && 'log' === sanitize_key( wp_unslash( $_POST['ecehc_review_tab'] ) ) ? 'log' : 'report';

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => AdminPage::MENU_SLUG,
					'tab'  => $tab,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
