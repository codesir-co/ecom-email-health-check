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

function _n( $single, $plural, $number ) {
	return 1 === (int) $number ? $single : $plural;
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

// A minimal hook system: tests register callbacks with add_filter()/add_action(); run.php clears them after each file.
$GLOBALS['ecehc_test_hooks'] = array();

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['ecehc_test_hooks'][ $hook ][] = array( $callback, $accepted_args );
	return true;
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	return add_filter( $hook, $callback, $priority, $accepted_args );
}

function apply_filters( $hook, $value, ...$args ) {
	foreach ( $GLOBALS['ecehc_test_hooks'][ $hook ] ?? array() as $entry ) {
		$value = call_user_func_array( $entry[0], array_slice( array_merge( array( $value ), $args ), 0, $entry[1] ) );
	}
	return $value;
}

function do_action( $hook, ...$args ) {
	foreach ( $GLOBALS['ecehc_test_hooks'][ $hook ] ?? array() as $entry ) {
		call_user_func_array( $entry[0], array_slice( $args, 0, $entry[1] ) );
	}
}

function esc_html__( $text ) {
	return $text;
}

function _doing_it_wrong( $function_name, $message, $version ) {
	$GLOBALS['ecehc_test_doing_it_wrong'][] = $message;
}

function get_home_url() {
	return 'https://shop.example.com';
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( $url, $component );
}

// Approximation: is_email() uses filter_var() (close to, but not exactly, WordPress's version).
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
