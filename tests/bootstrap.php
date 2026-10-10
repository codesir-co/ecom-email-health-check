<?php
/**
 * Test bootstrap: no WordPress, no Composer. It loads the plugin's autoloader and
 * defines the few WordPress functions the pure-logic classes use, with simple
 * in-memory versions. Run all tests with: php tests/run.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'ECEHC_VERSION', 'test' );
define( 'ECEHC_PLUGIN_PATH', dirname( __DIR__ ) . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MINUTE_IN_SECONDS', 60 );

// The same PSR-4 style autoloader as the main plugin file.
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

$GLOBALS['ecehc_test_options'] = array();

function __( $text ) {
	return $text;
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function apply_filters( $hook, $value ) {
	return $value;
}

function is_email( $email ) {
	return false !== filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
}

function wp_normalize_path( $path ) {
	return str_replace( '\\', '/', $path );
}

function wp_json_encode( $data ) {
	return json_encode( $data );
}

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['ecehc_test_options'] ) ? $GLOBALS['ecehc_test_options'][ $name ] : $default;
}

function update_option( $name, $value ) {
	$GLOBALS['ecehc_test_options'][ $name ] = $value;
	return true;
}

// A tiny assertion library.
$GLOBALS['ecehc_test_failures'] = array();
$GLOBALS['ecehc_test_count']    = 0;

function ecehc_assert_same( $expected, $actual, $label ) {
	++$GLOBALS['ecehc_test_count'];
	if ( $expected !== $actual ) {
		$GLOBALS['ecehc_test_failures'][] = $label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true );
	}
}

function ecehc_assert_true( $condition, $label ) {
	ecehc_assert_same( true, (bool) $condition, $label );
}
