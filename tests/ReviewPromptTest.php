<?php
// ReviewPrompt: the request follows a moment where the plugin helped, never a timer alone.

use CodeSir\EmailHealthCheck\Admin\ReviewPrompt;

// current_user_can() may already be stubbed by another test file; both read $GLOBALS['ecehc_test_can'].
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) {
		return ! empty( $GLOBALS['ecehc_test_can'] );
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 7;
	}
}
if ( ! function_exists( 'get_user_meta' ) ) {
	function get_user_meta( $user_id, $key, $single = false ) {
		return $GLOBALS['ecehc_rp_meta'];
	}
}

$setup = static function ( array $args = array() ) {
	$args                            = array_merge(
		array(
			'admin' => true,
			'meta'  => '',
			'age'   => 2 * DAY_IN_SECONDS,
			'test'  => null,
			'pass'  => null,
		),
		$args
	);
	$GLOBALS['ecehc_test_can']      = $args['admin'];
	$GLOBALS['ecehc_rp_meta']        = $args['meta'];
	$GLOBALS['ecehc_test_options']   = array( ReviewPrompt::OPTION_INSTALLED => time() - $args['age'] );
	if ( null !== $args['test'] ) {
		$GLOBALS['ecehc_test_options']['ecehc_last_test_email'] = array( 'success' => $args['test'], 'time' => time() );
	}
	if ( null !== $args['pass'] ) {
		$GLOBALS['ecehc_test_options'][ ReviewPrompt::OPTION_PASSED ] = $args['pass'];
	}
};

$setup( array( 'admin' => false, 'test' => true ) );
ecehc_assert_same( false, ReviewPrompt::should_show(), 'not an administrator' );

$setup( array( 'test' => true, 'meta' => array( 'done' => true ) ) );
ecehc_assert_same( false, ReviewPrompt::should_show(), 'done hides for good' );

$setup( array( 'test' => true, 'meta' => array( 'later' => 2, 'until' => 1 ) ) );
ecehc_assert_same( false, ReviewPrompt::should_show(), 'later twice hides for good' );

$setup( array( 'test' => true, 'meta' => array( 'later' => 1, 'until' => time() + 3600 ) ) );
ecehc_assert_same( false, ReviewPrompt::should_show(), 'inside the later window' );

$setup( array( 'test' => true, 'meta' => array( 'later' => 1, 'until' => time() - 5 ) ) );
ecehc_assert_same( true, ReviewPrompt::should_show(), 'after the later window' );

// Regression: the old rule showed the request after 7 days even if nothing ever worked.
$setup( array( 'age' => 30 * DAY_IN_SECONDS ) );
ecehc_assert_same( false, ReviewPrompt::should_show(), 'no success at 30 days: never' );

$setup( array( 'age' => 30 * DAY_IN_SECONDS, 'test' => false ) );
ecehc_assert_same( false, ReviewPrompt::should_show(), 'failed test email at 30 days: never' );

$setup( array( 'age' => 3600, 'test' => true ) );
ecehc_assert_same( false, ReviewPrompt::should_show(), 'test email worked but younger than a day' );

$setup( array( 'age' => 3600, 'pass' => time() ) );
ecehc_assert_same( false, ReviewPrompt::should_show(), 'report passed but younger than a day' );

$setup( array( 'test' => true ) );
ecehc_assert_same( true, ReviewPrompt::should_show(), 'test email worked, a day old' );

$setup( array( 'pass' => time() - 100 ) );
ecehc_assert_same( true, ReviewPrompt::should_show(), 'report passed, a day old' );

// note_report().
$row = static function ( $status ) {
	return array( 'label' => 'x', 'status' => $status, 'message' => '', 'guidance' => null );
};

$setup();
ReviewPrompt::note_report( array( 'a' => $row( 'pass' ), 'b' => $row( 'unknown' ) ) );
$first = $GLOBALS['ecehc_test_options'][ ReviewPrompt::OPTION_PASSED ] ?? null;
ecehc_assert_true( is_int( $first ) && $first > 0, 'all-pass (Not checked allowed) sets the option' );

$GLOBALS['ecehc_test_options'][ ReviewPrompt::OPTION_PASSED ] = 12345;
ReviewPrompt::note_report( array( 'a' => $row( 'pass' ) ) );
ecehc_assert_same( 12345, $GLOBALS['ecehc_test_options'][ ReviewPrompt::OPTION_PASSED ], 'keeps the earliest timestamp' );

foreach ( array(
	'fail'    => array( $row( 'pass' ), $row( 'fail' ) ),
	'warning' => array( $row( 'pass' ), $row( 'warning' ) ),
	'empty'   => array(),
	'junk'    => array( 'x', null, 5 ),
	'no status' => array( array( 'label' => 'x' ) ),
	'array status' => array( array( 'status' => array( 'pass' ) ) ),
) as $name => $results ) {
	$setup();
	ReviewPrompt::note_report( $results );
	ecehc_assert_true( ! array_key_exists( ReviewPrompt::OPTION_PASSED, $GLOBALS['ecehc_test_options'] ), "$name: option not set" );
}
