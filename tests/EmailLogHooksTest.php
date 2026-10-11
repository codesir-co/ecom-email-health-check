<?php
use CodeSir\EmailHealthCheck\Log\EmailLog;

// Regression test for the public events: their name, arguments and row shape must not change.
class Ecehc_Fake_Wpdb {
	public $prefix = 'wp_';
	public $inserted;

	public function suppress_errors( $suppress ) {
		return false;
	}

	public function insert( $table, $row, $formats ) {
		$this->inserted = $row;
		return 1;
	}
}

$GLOBALS['wpdb'] = new Ecehc_Fake_Wpdb();

$logged = array();
add_action(
	'ecehc_email_logged',
	static function ( $row ) use ( &$logged ) {
		$logged[] = $row;
	}
);
add_filter(
	'ecehc_log_row',
	static function ( $row ) {
		$row['source_label'] = 'Changed by a filter';
		$row['unknown_key']  = 'dropped';
		return $row;
	}
);

$written = EmailLog::insert(
	array(
		'status'           => 'failed',
		'recipient_domain' => 'example.com',
	)
);

ecehc_assert_true( $written, 'insert reports the row as written' );
ecehc_assert_same( 1, count( $logged ), 'ecehc_email_logged fires once per written row' );
ecehc_assert_same(
	array( 'created_at', 'status', 'source_key', 'source_label', 'email_type', 'email_type_label', 'recipient', 'recipient_domain', 'error' ),
	array_keys( $logged[0] ),
	'the ecehc_email_logged row keeps its documented keys'
);
ecehc_assert_same( 'failed', $logged[0]['status'], 'the logged row carries the status' );
ecehc_assert_same( 'Changed by a filter', $logged[0]['source_label'], 'ecehc_log_row can change a value' );
ecehc_assert_true( ! array_key_exists( 'unknown_key', $logged[0] ), 'ecehc_log_row cannot add columns' );
ecehc_assert_same( $logged[0], $GLOBALS['wpdb']->inserted, 'the action receives exactly what was written' );

unset( $GLOBALS['wpdb'] );
