<?php
use CodeSir\EmailHealthCheck\Log\EmailLog;
use CodeSir\EmailHealthCheck\Log\EmailLogger;
use CodeSir\EmailHealthCheck\Log\Schema;

// Order id capture, the schema version and the panel's query.

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
if ( ! class_exists( 'Ecehc_Throwing_Order' ) ) {
	class Ecehc_Throwing_Order extends WC_Order {
		public function get_id() {
			throw new \RuntimeException( 'broken order' );
		}
	}
}
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return strip_tags( (string) $text );
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

class Ecehc_Order_Wpdb {
	public $prefix = 'wp_';
	public $sql;
	public $inserted;

	public function suppress_errors( $suppress ) {
		return false;
	}

	public function insert( $table, $row, $formats ) {
		$this->inserted = array( $row, $formats );
		return 1;
	}

	public function prepare( $sql, ...$args ) {
		$this->sql = array( $sql, $args );
		return 'PREPARED';
	}

	public function get_results( $sql, $output = null ) {
		return array( array( 'id' => '1' ) );
	}
}

$wpdb            = new Ecehc_Order_Wpdb();
$GLOBALS['wpdb'] = $wpdb;

// Schema.
ecehc_assert_same( '2', Schema::DB_VERSION, 'the schema version includes the order_id column' );
ecehc_assert_true( false !== strpos( (string) file_get_contents( ECEHC_PLUGIN_PATH . 'includes/Log/Schema.php' ), 'order_id bigint(20) unsigned NOT NULL DEFAULT 0' ), 'the table definition has order_id' );

// Capture rules.
ecehc_assert_same( 55, EmailLogger::order_id_of( (object) array( 'id' => 'new_order', 'object' => new WC_Order( 55 ) ) ), 'a WC_Order object gives its id' );
ecehc_assert_same( 0, EmailLogger::order_id_of( (object) array( 'id' => 'customer_new_account', 'object' => new \stdClass() ) ), 'a non-order object gives 0' );
ecehc_assert_same( 0, EmailLogger::order_id_of( (object) array( 'id' => 'x', 'object' => false ) ), 'a false object gives 0' );
ecehc_assert_same( 0, EmailLogger::order_id_of( (object) array( 'id' => 'x' ) ), 'a missing object gives 0' );
ecehc_assert_same( 0, EmailLogger::order_id_of( null ), 'no email gives 0' );
ecehc_assert_same( 0, EmailLogger::order_id_of( (object) array( 'object' => new WC_Order( -3 ) ) ), 'a negative id gives 0' );
ecehc_assert_same( 0, EmailLogger::order_id_of( (object) array( 'id' => 'x', 'object' => new Ecehc_Throwing_Order( 1 ) ) ), 'an order whose get_id() throws gives 0' );

// Carried into the stored row. The source detector needs a real WooCommerce backtrace, so the
// context it would build is set directly.
$logger = new EmailLogger();
$mail   = array( 'to' => 'jane@example.com', 'subject' => 'Hi' );
$logger->track_wc_email( 'cb', (object) array( 'id' => 'new_order', 'title' => 'New order', 'object' => new WC_Order( 77 ) ) );
$throwing_logger = new EmailLogger();
ecehc_assert_same( 'cb', $throwing_logger->track_wc_email( 'cb', (object) array( 'id' => 'new_order', 'title' => 'New order', 'object' => new Ecehc_Throwing_Order( 1 ) ) ), 'track_wc_email returns the callback unchanged when get_id() throws' );
$wc_prop = new ReflectionProperty( EmailLogger::class, 'wc_email' );
$wc_prop->setAccessible( true );
$ctx_prop = new ReflectionProperty( EmailLogger::class, 'context' );
$ctx_prop->setAccessible( true );
$ctx_prop->setValue(
	$logger,
	array(
		'hash'   => md5( json_encode( array( array( 'jane@example.com' ), 'Hi' ) ) ),
		'source' => array( 'key' => 'woocommerce', 'label' => 'WooCommerce' ),
		'wc'     => $wc_prop->getValue( $logger ),
	)
);
$logger->log_success( $mail );
$keys = array_keys( $wpdb->inserted[0] );
ecehc_assert_same( 77, $wpdb->inserted[0]['order_id'], 'a WooCommerce order email is stored with its order id' );
ecehc_assert_same( '%d', $wpdb->inserted[1][ array_search( 'order_id', $keys, true ) ], 'order_id is written as an integer' );

$logger->track_wc_email( 'cb', (object) array( 'id' => 'customer_new_account', 'title' => 'New account' ) );
$ctx_prop->setValue( $logger, array( 'hash' => md5( json_encode( array( array( 'jane@example.com' ), 'Hi' ) ) ), 'source' => array( 'key' => 'woocommerce', 'label' => 'WooCommerce' ), 'wc' => $wc_prop->getValue( $logger ) ) );
$logger->log_success( $mail );
ecehc_assert_same( 0, $wpdb->inserted[0]['order_id'], 'a WooCommerce email without an order stores 0' );

$logger->log_success( $mail );
ecehc_assert_same( 0, $wpdb->inserted[0]['order_id'], 'mail without context stores 0' );

// Panel query.
$rows = EmailLog::for_order( 12 );
ecehc_assert_same( 1, count( $rows ), 'for_order returns the rows' );
ecehc_assert_true( false !== strpos( $wpdb->sql[0], 'WHERE order_id = %d ORDER BY id DESC LIMIT %d' ), 'for_order filters by order id, newest first, with a limit' );
ecehc_assert_same( array( 12, 20 ), $wpdb->sql[1], 'for_order binds an int order id and the default limit' );
$wpdb->sql = null;
ecehc_assert_same( array(), EmailLog::for_order( 0 ), 'order 0 returns nothing' );
ecehc_assert_same( null, $wpdb->sql, 'order 0 runs no query' );
EmailLog::for_order( 5, 0 );
ecehc_assert_same( array( 5, 1 ), $wpdb->sql[1], 'the limit is at least 1' );

unset( $GLOBALS['wpdb'] );
