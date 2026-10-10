<?php
/**
 * Runs the checks for the Health Report after the page has opened, so slow DNS
 * lookups never hold up the page itself.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

use CodeSir\EmailHealthCheck\Checks\CheckRunner;

defined( 'ABSPATH' ) || exit;

class ReportLoader {

	const ACTION       = 'ecehc_load_report';
	const NONCE_ACTION = 'ecehc_load_report';

	public function register(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
	}

	public function handle(): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}

		$results   = ( new CheckRunner() )->run_all();
		$checklist = Checklist::items( $results );

		ob_start();
		include ECEHC_PLUGIN_PATH . 'views/checklist.php';
		$checklist_html = ob_get_clean();

		ob_start();
		include ECEHC_PLUGIN_PATH . 'views/report-card.php';
		$report_html = ob_get_clean();

		wp_send_json_success(
			array(
				'checklist' => $checklist_html,
				'report'    => $report_html,
			)
		);
	}
}
