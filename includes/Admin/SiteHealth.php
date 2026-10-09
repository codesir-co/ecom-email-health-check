<?php
/**
 * Shows the email checks as a test on Tools > Site Health.
 *
 * It is an asynchronous test because the DNS lookups can be slow.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

use CodeSir\EmailHealthCheck\Checks\CheckRunner;
use CodeSir\EmailHealthCheck\Log\EmailLogger;
use CodeSir\EmailHealthCheck\Log\FailureAlert;

defined( 'ABSPATH' ) || exit;

class SiteHealth {

	/** Dashes, not underscores: Site Health only rewrites the first underscore when building the AJAX action. */
	const TEST = 'ecehc-email';

	const FAILURES_TEST = 'ecehc_email_failures';

	public function register(): void {
		add_filter( 'site_status_tests', array( $this, 'add_test' ) );
		add_action( 'wp_ajax_health-check-' . self::TEST, array( $this, 'ajax_run' ) );
	}

	/**
	 * @param array $tests Site Health tests.
	 * @return array
	 */
	public function add_test( $tests ): array {
		$tests = (array) $tests;

		$tests['async'][ self::TEST ] = array(
			'label' => __( 'Email deliverability', 'ecom-email-health-check' ),
			'test'  => self::TEST,
		);

		// A cheap database read, so it runs directly. Only added when emails are being logged.
		if ( EmailLogger::is_enabled() ) {
			$tests['direct'][ self::FAILURES_TEST ] = array(
				'label' => __( 'Recent email failures', 'ecom-email-health-check' ),
				'test'  => array( $this, 'test_failures' ),
			);
		}

		return $tests;
	}

	/**
	 * Site Health test: are many recent emails failing?
	 *
	 * @return array<string, mixed>
	 */
	public function test_failures(): array {
		$alert    = FailureAlert::evaluate();
		$empty    = null === $alert || 0 === $alert['sent'] + $alert['failed'];
		$problems = null !== $alert && $alert['needs_attention'];

		if ( $problems ) {
			$label = __( 'Many of your recent emails are failing', 'ecom-email-health-check' );
		} elseif ( $empty ) {
			$label = __( 'No emails were logged in the last 24 hours', 'ecom-email-health-check' );
		} else {
			$label = __( 'Your recent emails are going out without errors', 'ecom-email-health-check' );
		}

		return array(
			'label'       => $label,
			'status'      => $problems ? 'recommended' : 'good',
			'badge'       => array(
				'label' => __( 'Email', 'ecom-email-health-check' ),
				'color' => $problems ? 'orange' : 'blue',
			),
			'description' => ( $empty ? '' : '<p>' . esc_html( FailureAlert::summary( $alert ) ) . '</p>' ) . '<p>' . esc_html( FailureAlert::note() ) . '</p>',
			'actions'     => sprintf(
				'<p><a href="%1$s">%2$s</a></p>',
				esc_url(
					add_query_arg(
						array(
							'page' => AdminPage::MENU_SLUG,
							'tab'  => 'log',
						),
						admin_url( 'admin.php' )
					)
				),
				esc_html__( 'Open the email log', 'ecom-email-health-check' )
			),
			'test'        => self::FAILURES_TEST,
		);
	}

	public function ajax_run(): void {
		check_ajax_referer( 'health-check-site-status' );

		if ( ! current_user_can( 'view_site_health_checks' ) ) {
			wp_send_json_error();
		}

		wp_send_json_success( $this->build_result( ( new CheckRunner() )->run_all() ) );
	}

	/**
	 * Turns the check results into the structure Site Health expects.
	 *
	 * Failures and warnings are "recommended", not "critical": a missing DNS
	 * record is worth fixing but should not make the whole site look broken.
	 * "Not checked" results are listed but never count against the site.
	 *
	 * @param array<string, array{label: string, status: string, message: string}> $results Results from CheckRunner.
	 * @return array<string, mixed>
	 */
	public function build_result( array $results ): array {
		$problems = 0;
		$unknown  = 0;
		$items    = '';

		foreach ( $results as $result ) {
			if ( 'fail' === $result['status'] || 'warning' === $result['status'] ) {
				++$problems;
			}

			if ( 'unknown' === $result['status'] ) {
				++$unknown;
			}

			$items .= sprintf(
				'<li><strong>%1$s</strong> (%2$s): %3$s</li>',
				esc_html( $result['label'] ),
				esc_html( $this->status_label( $result['status'] ) ),
				esc_html( $result['message'] )
			);
		}

		$link = sprintf(
			'<p><a href="%1$s">%2$s</a></p>',
			esc_url( admin_url( 'admin.php?page=' . AdminPage::MENU_SLUG ) ),
			esc_html__( 'Open the Email Health Check report', 'ecom-email-health-check' )
		);

		if ( $problems ) {
			$label = __( 'Your store\'s email deliverability could be improved', 'ecom-email-health-check' );
		} elseif ( $unknown ) {
			$label = __( 'Some email checks could not be completed', 'ecom-email-health-check' );
		} else {
			$label = __( 'Your email deliverability checks look healthy', 'ecom-email-health-check' );
		}

		return array(
			'label'       => $label,
			'status'      => $problems ? 'recommended' : 'good',
			'badge'       => array(
				'label' => __( 'Email', 'ecom-email-health-check' ),
				'color' => $problems ? 'orange' : 'blue',
			),
			'description' => '<ul>' . $items . '</ul>',
			'actions'     => $link,
			'test'        => self::TEST,
		);
	}

	private function status_label( string $status ): string {
		switch ( $status ) {
			case 'pass':
				return __( 'Pass', 'ecom-email-health-check' );
			case 'warning':
				return __( 'Warning', 'ecom-email-health-check' );
			case 'fail':
				return __( 'Fail', 'ecom-email-health-check' );
			default:
				return __( 'Not checked', 'ecom-email-health-check' );
		}
	}
}
