<?php
use CodeSir\EmailHealthCheck\Admin\OrderEmailsPanel;

// The order screen panel: screens, meta box gating and render input handling.
// wc_get_page_screen_id() and wc_get_order() cannot be undefined once declared, so the
// "WooCommerce missing" cases run first.

if ( ! class_exists( 'WC_Order' ) ) {
	class WC_Order {
		private $id;

		public function __construct( $id ) {
			$this->id = $id;
		}

		public function get_id() {
			return $this->id;
		}
	}
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! function_exists( 'esc_sql' ) ) {
	function esc_sql( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text ) {
		echo esc_html( $text );
	}
}
if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show ) {
		return '6.5';
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) {
		return ! empty( $GLOBALS['ecehc_test_can'] );
	}
}
if ( ! function_exists( 'add_meta_box' ) ) {
	function add_meta_box( ...$args ) {
		$GLOBALS['ecehc_test_meta_boxes'][] = $args;
	}
}
if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $args, $url ) {
		return $url . '?' . http_build_query( $args );
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path ) {
		return 'http://example.test/wp-admin/' . $path;
	}
}
if ( ! function_exists( 'get_date_from_gmt' ) ) {
	function get_date_from_gmt( $date, $format ) {
		return $date;
	}
}

class Ecehc_Panel_Wpdb {
	public $prefix = 'wp_';
	public $args;

	public function suppress_errors( $suppress ) {
		return false;
	}

	public function prepare( $sql, ...$args ) {
		$this->args = $args;
		return 'PREPARED';
	}

	public function get_results( $sql, $output = null ) {
		return array();
	}
}

$wpdb            = new Ecehc_Panel_Wpdb();
$GLOBALS['wpdb'] = $wpdb;
$panel           = new OrderEmailsPanel();

$register = static function () use ( $panel ) {
	$GLOBALS['ecehc_test_meta_boxes'] = array();
	$panel->add_meta_box();
	return $GLOBALS['ecehc_test_meta_boxes'];
};
$render = static function ( $object ) use ( $panel ) {
	ob_start();
	$panel->render( $object );
	return ob_get_clean();
};

// Without WooCommerce helpers.
$GLOBALS['ecehc_test_can'] = true;
ecehc_assert_same( array( 'shop_order' ), OrderEmailsPanel::screens(), 'screens is only shop_order without wc_get_page_screen_id' );
ecehc_assert_same( array(), $register(), 'no meta box without wc_get_order' );

if ( ! function_exists( 'wc_get_page_screen_id' ) ) {
	function wc_get_page_screen_id( $name ) {
		return 'woocommerce_page_wc-orders';
	}
}
if ( ! function_exists( 'wc_get_order' ) ) {
	function wc_get_order( $id ) {
		return false;
	}
}

// Screens.
ecehc_assert_same( array( 'shop_order', 'woocommerce_page_wc-orders' ), OrderEmailsPanel::screens(), 'screens lists legacy and HPOS ids' );

// Registration gating.
$boxes = $register();
ecehc_assert_same( 1, count( $boxes ), 'the meta box is registered when all conditions hold' );
ecehc_assert_same( OrderEmailsPanel::ID, $boxes[0][0], 'the meta box id' );
ecehc_assert_same( array( $panel, 'render' ), $boxes[0][2], 'the meta box renders through the panel' );
ecehc_assert_same( array( 'shop_order', 'woocommerce_page_wc-orders' ), $boxes[0][3], 'the meta box is added to both order screens' );
ecehc_assert_same( 'normal', $boxes[0][4], 'the meta box sits in the wide main column, so the table fits' );

$GLOBALS['ecehc_test_can'] = false;
ecehc_assert_same( array(), $register(), 'no meta box without manage_options' );
$GLOBALS['ecehc_test_can'] = true;

$GLOBALS['ecehc_test_options']['ecehc_log_settings'] = array( 'enabled' => false );
ecehc_assert_same( array(), $register(), 'no meta box with the email log turned off' );
$GLOBALS['ecehc_test_options'] = array();
ecehc_assert_same( 1, count( $register() ), 'the meta box returns when the log is on again' );

// Render.
$html = $render( new WC_Order( 41 ) );
ecehc_assert_same( array( 41, 20 ), $wpdb->args, 'render looks up a WC_Order by its id' );
ecehc_assert_true( false !== strpos( $html, 'No emails are logged for this order' ), 'render shows the empty message' );
ecehc_assert_true( false !== strpos( $html, 'tab=log' ), 'render links to the email log tab' );

$wpdb->args = null;
$render( (object) array( 'ID' => '42' ) );
ecehc_assert_same( array( 42, 20 ), $wpdb->args, 'render looks up a WP_Post-like object by its ID' );

$GLOBALS['ecehc_test_can'] = false;
$wpdb->args                = null;
ecehc_assert_same( '', $render( new WC_Order( 43 ) ), 'render outputs nothing without manage_options' );
ecehc_assert_same( null, $wpdb->args, 'render runs no query without manage_options' );

unset( $GLOBALS['wpdb'], $GLOBALS['ecehc_test_can'], $GLOBALS['ecehc_test_meta_boxes'] );
