<?php
// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ecehc_Admin_Page {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'handle_test_email' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );

	}

	public function add_admin_menu() {
		add_menu_page(
			__( 'Email Health Check', 'ecom-email-health-check' ),
			__( 'Email Health Check', 'ecom-email-health-check' ),
			'manage_options',
			'ecom-dashboard',
			array( $this, 'render_admin_page' ),
			'dashicons-email-alt', // Icon for the menu item
			60                    // Position in the menu (optional)
		);
	}

	public function render_admin_page() {
		$diagnostic_tool = new ecehc_Diagnostic_Tool();
		$results = $diagnostic_tool->run_all_checks();
		include_once ECEHC_PLUGIN_PATH . 'views/admin-page.php';
	}

	public function handle_test_email() {
		if ( isset( $_POST['ecehc_send_test_email'] ) && current_user_can( 'manage_options' ) ) {
			// Check for the nonce to prevent CSRF attacks
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'ecehc_send_test_email' ) ) {
				wp_die( 'Security check failed.' );
			}

			$to = get_option('admin_email');
			$subject = __( 'eCommerce Email Health Check: Test Email', 'ecom-email-health-check' );
			$message = __( 'This is a test email sent from the eCommerce Email Health Check plugin. If you received this, your site can send basic emails.', 'ecom-email-health-check' );

			$sent = wp_mail( $to, $subject, $message );

			if ( $sent ) {
				add_action( 'admin_notices', function() {
					echo '<div class="notice notice-success is-dismissible"><p><strong>Test email sent successfully!</strong> Please check your admin inbox to confirm receipt.</p></div>';
				});
			} else {
				add_action( 'admin_notices', function() {
					echo '<div class="notice notice-error is-dismissible">
                            <p><strong>Test email failed to send.</strong> This is a common issue with standard hosting providers.</p>
                            <p>Our managed service handles all the technical details and ensures your emails are always delivered. <a href="https://codesir.co/mailsir/?utm_source=plugin&utm_medium=test_email_fail" target="_blank" style="font-weight: bold;">Fix this issue now</a>.</p>
                          </div>';
				});
			}
		}
	}

	public function enqueue_admin_styles( $hook ) {
		if ( 'toplevel_page_ecom-dashboard' !== $hook ) {
			return;
		}

		// Enqueue the stylesheet
		wp_enqueue_style(
			'ecom-email-health-check-admin-style',
			plugins_url( 'assets/style.css', ECEHC_PLUGIN_FILE ),
			array(),
			'1.0.0',
		);
	}
}