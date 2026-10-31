<?php
use CodeSir\EmailHealthCheck\Log\EmailLogger;

// Proves the email log only observes: its hooks hand back their input unchanged,
// and a failing log write can never throw into wp_mail().

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $message;
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return strip_tags( (string) $text );
	}
}

// A database whose insert() does what the test tells it to.
class Ecehc_Safety_Wpdb {
	public $prefix  = 'wp_';
	public $mode    = 'ok';
	public $inserts = 0;

	public function suppress_errors( $suppress ) {
		return false;
	}

	public function insert( $table, $row, $formats ) {
		++$this->inserts;
		if ( 'exception' === $this->mode ) {
			throw new \RuntimeException( 'database exploded' );
		}
		if ( 'error' === $this->mode ) {
			throw new \Error( 'database fatal' );
		}
		return 'false' === $this->mode ? false : 1;
	}
}

$wpdb_fake         = new Ecehc_Safety_Wpdb();
$GLOBALS['wpdb']   = $wpdb_fake;
$logger            = new EmailLogger();
$returns_normally  = static function ( callable $call ) {
	try {
		$result = $call();
		return null === $result;
	} catch ( \Throwable $e ) {
		return false;
	}
};

// 1. The wp_mail filter returns its input unchanged, including odd inputs.
$mail = array(
	'to'          => array( 'jane@example.com' ),
	'subject'     => 'Hello',
	'message'     => 'Body',
	'headers'     => array( 'Content-Type: text/html' ),
	'attachments' => array(),
);
ecehc_assert_same( $mail, $logger->capture_context( $mail ), 'capture_context returns a full args array unchanged' );
ecehc_assert_same( array(), $logger->capture_context( array() ), 'capture_context returns an empty array unchanged' );
ecehc_assert_same( array( 'subject' => 'No recipient' ), $logger->capture_context( array( 'subject' => 'No recipient' ) ), 'capture_context copes with a missing "to"' );
ecehc_assert_same( array( 'to' => 'a@example.com' ), $logger->capture_context( array( 'to' => 'a@example.com' ) ), 'capture_context copes with a missing subject' );
ecehc_assert_same( array( 'to' => null, 'subject' => null ), $logger->capture_context( array( 'to' => null, 'subject' => null ) ), 'capture_context copes with null values' );
ecehc_assert_same( 'not an array', $logger->capture_context( 'not an array' ), 'capture_context returns a string unchanged' );
ecehc_assert_same( null, $logger->capture_context( null ), 'capture_context returns null unchanged' );
ecehc_assert_same( false, $logger->capture_context( false ), 'capture_context returns false unchanged' );
ecehc_assert_same( 42, $logger->capture_context( 42 ), 'capture_context returns an int unchanged' );

// 2. pre_wp_mail and the WooCommerce callback filter return what they were given.
foreach ( array( null, true, false, 'short circuit', array( 'x' ) ) as $pre ) {
	ecehc_assert_same( $pre, $logger->forget_context( $pre ), 'forget_context returns ' . var_export( $pre, true ) . ' unchanged' );
}

$callback = static function () {
	return true;
};
$wc_email = (object) array(
	'id'    => 'new_order',
	'title' => '<b>New order</b>',
);
ecehc_assert_true( $callback === $logger->track_wc_email( $callback, $wc_email ), 'track_wc_email returns the callback unchanged' );
ecehc_assert_same( 'mail_cb', $logger->track_wc_email( 'mail_cb', null ), 'track_wc_email returns the callback unchanged without an email object' );
ecehc_assert_same( array( 'a', 'b' ), $logger->track_wc_email( array( 'a', 'b' ), 'not an object' ), 'track_wc_email returns an array callback unchanged' );

// 3. Logging returns nothing and never throws, whatever the database does.
$cases = array( 'ok', 'false', 'exception', 'error' );
foreach ( $cases as $mode ) {
	$wpdb_fake->mode = $mode;
	ecehc_assert_true( $returns_normally( static function () use ( $logger, $mail ) {
		return $logger->log_success( $mail );
	} ), "log_success returns nothing when the insert is '$mode'" );
	ecehc_assert_true( $returns_normally( static function () use ( $logger, $mail ) {
		return $logger->log_failure( new WP_Error( 'wp_mail_failed', 'Could not reach jane@example.com', $mail ) );
	} ), "log_failure returns nothing when the insert is '$mode'" );
}

// 4. Throwing callbacks on the public log hooks.
$wpdb_fake->mode = 'ok';
add_filter(
	'ecehc_log_row',
	static function () {
		throw new \RuntimeException( 'filter exploded' );
	}
);
ecehc_assert_true( $returns_normally( static function () use ( $logger, $mail ) {
	return $logger->log_success( $mail );
} ), 'log_success returns nothing when an ecehc_log_row callback throws' );
ecehc_assert_true( $returns_normally( static function () use ( $logger, $mail ) {
	return $logger->log_failure( new WP_Error( 'wp_mail_failed', 'boom', $mail ) );
} ), 'log_failure returns nothing when an ecehc_log_row callback throws' );

$GLOBALS['ecehc_test_hooks'] = array();
add_action(
	'ecehc_email_logged',
	static function () {
		throw new \Error( 'action exploded' );
	}
);
ecehc_assert_true( $returns_normally( static function () use ( $logger, $mail ) {
	return $logger->log_success( $mail );
} ), 'log_success returns nothing when an ecehc_email_logged callback throws' );
ecehc_assert_true( $returns_normally( static function () use ( $logger, $mail ) {
	return $logger->log_failure( new WP_Error( 'wp_mail_failed', 'boom', $mail ) );
} ), 'log_failure returns nothing when an ecehc_email_logged callback throws' );
$GLOBALS['ecehc_test_hooks'] = array();

// 5. Odd arguments to the event callbacks.
$odd = array( null, 'string', 5, false, new \stdClass(), array(), array( 'to' => array( 'a@example.com' ) ) );
foreach ( $odd as $value ) {
	ecehc_assert_true( $returns_normally( static function () use ( $logger, $value ) {
		return $logger->log_success( $value );
	} ), 'log_success returns nothing for ' . gettype( $value ) );
	ecehc_assert_true( $returns_normally( static function () use ( $logger, $value ) {
		return $logger->log_failure( $value );
	} ), 'log_failure returns nothing for a non-WP_Error ' . gettype( $value ) );
}
foreach ( array( '', 'text', null, array(), new \stdClass() ) as $data ) {
	ecehc_assert_true( $returns_normally( static function () use ( $logger, $data ) {
		return $logger->log_failure( new WP_Error( 'wp_mail_failed', 'boom', $data ) );
	} ), 'log_failure returns nothing for WP_Error data of type ' . gettype( $data ) );
}

// 6. A normal mail causes exactly one insert, and a non-WP_Error causes none.
$wpdb_fake->inserts = 0;
$logger->log_success( $mail );
ecehc_assert_same( 1, $wpdb_fake->inserts, 'one logged email is one database insert' );
$wpdb_fake->inserts = 0;
$logger->log_failure( 'not a WP_Error' );
ecehc_assert_same( 0, $wpdb_fake->inserts, 'a non-WP_Error writes nothing' );

unset( $GLOBALS['wpdb'] );
