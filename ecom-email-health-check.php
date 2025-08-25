<?php
/**
 * Plugin Name: eCommerce Email Health Check
 * Plugin URI:  https://github.com/codesir-co/ecom-email-health-check
 * Description: A free tool to diagnose and test your email delivery, ensuring your order confirmations and notifications always reach your customers.
 * Version:     1.0.2
 * Author:      CodeSir
 * Author URI:  https://codesir.co/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ecom-email-health-check
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
if ( ! defined( 'ECEHC_PLUGIN_FILE' ) ) {
	define( 'ECEHC_PLUGIN_FILE', __FILE__ );
}
if ( ! defined( 'ECEHC_PLUGIN_PATH' ) ) {
	define( 'ECEHC_PLUGIN_PATH', plugin_dir_path( ECEHC_PLUGIN_FILE ) );
}
// Define the plugin base file for the activation hook
if ( ! defined( 'ECEHC_PLUGIN_BASE' ) ) {
	define( 'ECEHC_PLUGIN_BASE', plugin_basename( ECEHC_PLUGIN_FILE ) );
}

// Include the necessary class files
require_once ECEHC_PLUGIN_PATH . 'includes/class-ecehc-diagnostic-tool.php';
require_once ECEHC_PLUGIN_PATH . 'includes/class-ecehc-admin-page.php';

/**
 * Main plugin class.
 */
class ecehc_Main {
	public function __construct() {
		new ecehc_Admin_Page();
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_links' ) );
	}

	// Add custom links to the plugin list
	public function add_plugin_links( $links ) {
		// We're changing "Settings" to "Health Check"
		$health_check_link = '<a href="' . esc_url( admin_url( 'admin.php?page=ecehc-dashboard' ) ) . '">' . __( 'Health Check', 'ecom-email-health-check' ) . '</a>';
		$fix_now_link = '<a href="https://codesir.co/mailsir?utm_source=plugin&utm_medium=plugin_list" target="_blank" style="font-weight: bold; color: #1e87f0;">' . __( 'Fix Now', 'ecom-email-health-check' ) . '</a>';

		array_unshift( $links, $fix_now_link, $health_check_link );

		return $links;
	}
}

// Set a transient on plugin activation to trigger a redirect
function ecehc_set_redirect_flag() {
	set_transient( 'ecehc_redirect_to_dashboard', true, 60 );
}
register_activation_hook( __FILE__, 'ecehc_set_redirect_flag' );

// Check for the transient on admin_init and perform the redirect
function ecehc_do_redirect() {
	// Check if the redirect transient is set and if the user can manage options
	if ( get_transient( 'ecehc_redirect_to_dashboard' ) && current_user_can( 'manage_options' ) ) {
		// Delete the transient to ensure it only happens once
		delete_transient( 'ecehc_redirect_to_dashboard' );

		$redirect_url = admin_url( 'admin.php?page=ecom-dashboard' );
		wp_redirect( $redirect_url );
		exit;
	}
}
add_action( 'admin_init', 'ecehc_do_redirect' );

// Initialize the plugin
add_action( 'plugins_loaded', function() {
	new ecehc_Main();
} );