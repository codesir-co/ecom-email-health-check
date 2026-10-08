<?php
/**
 * Activation hook and post-activation redirect.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck;

defined( 'ABSPATH' ) || exit;

class Activator {

	const REDIRECT_TRANSIENT = 'ecehc_redirect_to_dashboard';

	public static function activate(): void {
		set_transient( self::REDIRECT_TRANSIENT, true, 60 );
	}

	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_redirect' ) );
	}

	public function maybe_redirect(): void {
		if ( ! get_transient( self::REDIRECT_TRANSIENT ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Never interrupt AJAX requests or network admin screens.
		if ( wp_doing_ajax() || is_network_admin() ) {
			return;
		}

		delete_transient( self::REDIRECT_TRANSIENT );

		// Don't hijack bulk activation (several plugins activated at once).
		if ( isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . Admin\AdminPage::MENU_SLUG ) );
		exit;
	}
}
