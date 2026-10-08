<?php
/**
 * Admin menu page, view rendering and assets.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

use CodeSir\EmailHealthCheck\Checks\CheckRunner;

defined( 'ABSPATH' ) || exit;

class AdminPage {

	const MENU_SLUG = 'ecom-dashboard';

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	public function add_admin_menu(): void {
		add_menu_page(
			__( 'Email Health Check', 'ecom-email-health-check' ),
			__( 'Email Health Check', 'ecom-email-health-check' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' ),
			'dashicons-email-alt',
			60
		);
	}

	public function render(): void {
		$results = ( new CheckRunner() )->run_all();
		include ECEHC_PLUGIN_PATH . 'views/admin-page.php';
	}

	public function enqueue_admin_styles( $hook ): void {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'ecom-email-health-check-admin-style',
			ECEHC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			ECEHC_VERSION
		);

		wp_enqueue_script(
			'ecom-email-health-check-admin',
			ECEHC_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			ECEHC_VERSION,
			true
		);
	}
}
