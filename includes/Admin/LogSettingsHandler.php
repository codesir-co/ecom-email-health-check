<?php
/**
 * Handles the Email Log settings form and the "Clear log" button.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

use CodeSir\EmailHealthCheck\Log\EmailLog;
use CodeSir\EmailHealthCheck\Log\LogSettings;

defined( 'ABSPATH' ) || exit;

class LogSettingsHandler {

	const NONCE_ACTION = 'ecehc_log_settings';
	const NONCE_FIELD  = '_ecehc_log_nonce';

	public function register(): void {
		add_action( 'admin_init', array( $this, 'handle' ) );
	}

	public function handle(): void {
		$saving   = isset( $_POST['ecehc_save_log_settings'] );
		$clearing = isset( $_POST['ecehc_clear_log'] );

		if ( ! ( $saving || $clearing ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ecom-email-health-check' ) );
		}

		if ( $clearing ) {
			EmailLog::clear();
			$notice = 'cleared';
		} else {
			LogSettings::save(
				isset( $_POST['ecehc_log_enabled'] ),
				isset( $_POST['ecehc_log_retention'] ) ? absint( $_POST['ecehc_log_retention'] ) : LogSettings::DEFAULT_RETENTION
			);
			$notice = 'saved';
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => AdminPage::MENU_SLUG,
					'tab'    => 'log',
					'notice' => $notice,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
