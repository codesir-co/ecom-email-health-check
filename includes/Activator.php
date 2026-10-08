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
		if ( get_transient( self::REDIRECT_TRANSIENT ) && current_user_can( 'manage_options' ) ) {
			delete_transient( self::REDIRECT_TRANSIENT );

			wp_redirect( admin_url( 'admin.php?page=' . Admin\AdminPage::MENU_SLUG ) );
			exit;
		}
	}
}
