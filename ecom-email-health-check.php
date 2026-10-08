<?php
/**
 * Plugin Name: eCommerce Email Health Check
 * Plugin URI:  https://github.com/codesir-co/ecom-email-health-check
 * Description: A free tool to diagnose and test your email delivery, ensuring your order confirmations and notifications always reach your customers.
 * Version:     1.1.1
 * Requires PHP: 7.4
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

define( 'ECEHC_VERSION', '1.1.1' );
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

register_activation_hook( __FILE__, array( \CodeSir\EmailHealthCheck\Activator::class, 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\CodeSir\EmailHealthCheck\Plugin::instance()->init();
	}
);
