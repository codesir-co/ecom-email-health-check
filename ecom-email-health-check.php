<?php
/**
 * Plugin Name: WooCommerce Email Health Check
 * Plugin URI:  https://your-saas-domain.com/
 * Description: A free tool to diagnose and test your email delivery, ensuring your order confirmations and notifications always reach your customers.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://your-saas-domain.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ecehc
 */

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
if ( ! defined( 'ecehc_PLUGIN_PATH' ) ) {
	define( 'ecehc_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
// Define the plugin base file for the activation hook
if ( ! defined( 'ecehc_PLUGIN_BASE' ) ) {
	define( 'ecehc_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}

// Include the necessary class files
require_once ecehc_PLUGIN_PATH . 'includes/class-ecehc-diagnostic-tool.php';
require_once ecehc_PLUGIN_PATH . 'includes/class-ecehc-admin-page.php';

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
		$health_check_link = '<a href="' . esc_url( admin_url( 'admin.php?page=ecehc-dashboard' ) ) . '">' . __( 'Health Check', 'ecehc' ) . '</a>';
		$fix_now_link = '<a href="https://your-saas-domain.com/?utm_source=plugin&utm_medium=plugin_list" target="_blank" style="font-weight: bold; color: #1e87f0;">' . __( 'Fix Now', 'ecehc' ) . '</a>';

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

		$redirect_url = admin_url( 'admin.php?page=ecehc-dashboard' );
		wp_redirect( $redirect_url );
		exit;
	}
}
add_action( 'admin_init', 'ecehc_do_redirect' );

// Initialize the plugin
add_action( 'plugins_loaded', function() {
	new ecehc_Main();
} );