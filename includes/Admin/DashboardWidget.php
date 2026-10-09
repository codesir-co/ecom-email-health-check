<?php
/**
 * Dashboard widget showing recent email failures.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

use CodeSir\EmailHealthCheck\Log\EmailLogger;
use CodeSir\EmailHealthCheck\Log\FailureAlert;

defined( 'ABSPATH' ) || exit;

class DashboardWidget {

	const ID = 'ecehc_email_health';

	public function register(): void {
		add_action( 'wp_dashboard_setup', array( $this, 'add_widget' ) );
	}

	public function add_widget(): void {
		if ( ! current_user_can( 'manage_options' ) || ! EmailLogger::is_supported() ) {
			return;
		}

		wp_add_dashboard_widget( self::ID, __( 'Email Health', 'ecom-email-health-check' ), array( $this, 'render' ) );
	}

	public function render(): void {
		$alert = FailureAlert::evaluate();

		if ( null === $alert ) {
			return;
		}

		$log_url = add_query_arg(
			array(
				'page' => AdminPage::MENU_SLUG,
				'tab'  => 'log',
			),
			admin_url( 'admin.php' )
		);

		if ( 0 === $alert['sent'] + $alert['failed'] ) {
			echo '<p>' . esc_html__( 'No emails have been logged in the last 24 hours.', 'ecom-email-health-check' ) . '</p>';
		} elseif ( $alert['needs_attention'] ) {
			echo '<p><span class="ecehc-status ecehc-status-fail">&#10006; ' . esc_html__( 'Needs attention', 'ecom-email-health-check' ) . '</span></p>';
		} else {
			echo '<p><span class="ecehc-status ecehc-status-pass">&#10004; ' . esc_html__( 'Looks fine', 'ecom-email-health-check' ) . '</span></p>';
		}

		echo '<p>' . esc_html( FailureAlert::summary( $alert ) ) . '</p>';

		if ( $alert['last_failure'] ) {
			echo '<p>' . esc_html(
				sprintf(
					/* translators: 1: date and time, 2: source name, 3: error message */
					__( 'Last failure: %1$s, %2$s: %3$s', 'ecom-email-health-check' ),
					get_date_from_gmt( $alert['last_failure']['created_at'], 'Y-m-d H:i' ),
					$alert['last_failure']['source_label'],
					'' !== $alert['last_failure']['error'] ? $alert['last_failure']['error'] : __( 'no error message', 'ecom-email-health-check' )
				)
			) . '</p>';
		}

		echo '<p><a href="' . esc_url( $log_url ) . '">' . esc_html__( 'View the email log', 'ecom-email-health-check' ) . '</a></p>';
		echo '<p class="description">' . esc_html__( '"Accepted" means WordPress handed the email off without an error, not that it was delivered.', 'ecom-email-health-check' ) . '</p>';
	}
}
