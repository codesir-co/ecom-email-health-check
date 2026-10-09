<?php
/**
 * Admin menu page, view rendering and assets.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

use CodeSir\EmailHealthCheck\Checks\CheckRunner;
use CodeSir\EmailHealthCheck\Log\EmailLog;
use CodeSir\EmailHealthCheck\Log\EmailLogger;

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
		$active_tab = $this->active_tab();

		if ( 'log' === $active_tab ) {
			$this->render_log( $active_tab );
			return;
		}

		$results = ( new CheckRunner() )->run_all();
		include ECEHC_PLUGIN_PATH . 'views/admin-page.php';
	}

	/**
	 * The tab to show: "report" (default) or "log".
	 */
	private function active_tab(): string {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'report'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return 'log' === $tab ? 'log' : 'report';
	}

	/**
	 * Email Log tab: statistics, filters and the list of recent emails.
	 */
	private function render_log( string $active_tab ): void {
		$supported = EmailLogger::is_supported();
		$sources   = $supported ? EmailLog::sources() : array();
		$types     = $supported ? EmailLog::wc_types() : array();

		// Read-only filters from the URL, accepted only if they are a known value.
		$filters = array(
			'source' => '',
			'status' => '',
			'type'   => '',
		);
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$source = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$type   = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';
		$paged  = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( isset( $sources[ $source ] ) ) {
			$filters['source'] = $source;
		}
		if ( in_array( $status, array( EmailLog::STATUS_SENT, EmailLog::STATUS_FAILED ), true ) ) {
			$filters['status'] = $status;
		}
		if ( isset( $types[ $type ] ) ) {
			$filters['type'] = $type;
		}

		$result  = $supported ? EmailLog::query( $filters, 20, $paged ) : array(
			'rows'  => array(),
			'total' => 0,
			'pages' => 1,
		);
		$paged   = min( max( 1, $paged ), $result['pages'] );
		$stats24 = $supported ? EmailLog::stats( DAY_IN_SECONDS ) : null;
		$stats7  = $supported ? EmailLog::stats( 7 * DAY_IN_SECONDS ) : null;

		include ECEHC_PLUGIN_PATH . 'views/email-log.php';
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
