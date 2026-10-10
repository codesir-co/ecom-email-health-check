<?php
/**
 * Plugin Name: Email Health Check & Log for WooCommerce – SPF, DKIM, DMARC & Alerts
 * Plugin URI:  https://github.com/codesir-co/ecom-email-health-check
 * Description: Email log, SPF/DKIM/DMARC checks and failure alerts. Find out why WooCommerce order emails go to spam or never arrive.
 * Version:     1.5.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 11.2
 * Author:      CodeSir
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: ecom-email-health-check
 *
 * @package CodeSir\EmailHealthCheck
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ECEHC_VERSION', '1.5.0' );
define( 'ECEHC_PLUGIN_FILE', __FILE__ );
define( 'ECEHC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ECEHC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ECEHC_PLUGIN_BASE', plugin_basename( __FILE__ ) );

/**
 * PSR-4 style autoloader: CodeSir\EmailHealthCheck\Foo\Bar => includes/Foo/Bar.php
 */
spl_autoload_register(
	static function ( $class ) {
		$prefix = 'CodeSir\\EmailHealthCheck\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$file = ECEHC_PLUGIN_PATH . 'includes/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Tell WooCommerce this plugin works with High-Performance Order Storage (the
 * unpaid orders check reads orders through wc_get_orders()) and with the Cart
 * and Checkout blocks (the plugin does not touch the cart or checkout).
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

register_activation_hook( __FILE__, array( \CodeSir\EmailHealthCheck\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \CodeSir\EmailHealthCheck\Log\EmailLogger::class, 'unschedule' ) );

add_action(
	'plugins_loaded',
	static function () {
		\CodeSir\EmailHealthCheck\Plugin::instance()->init();
	}
);
