<?php
use CodeSir\EmailHealthCheck\Support\FixFirst;

function ecehc_ff( $status, $label = 'L', $guidance = null ) {
	return array(
		'label'    => $label,
		'status'   => $status,
		'message'  => 'm',
		'guidance' => $guidance,
	);
}

// A failure outranks a warning, whatever the order.
$pick = FixFirst::pick( array( 'smtp_configured' => ecehc_ff( 'warning' ), 'dmarc_record' => ecehc_ff( 'fail' ) ) );
ecehc_assert_same( 'fix', $pick['state'], 'a failure gives a fix state' );
ecehc_assert_same( 'dmarc_record', $pick['id'], 'a failure ranks above a warning' );
ecehc_assert_same( 'fail', $pick['status'], 'the status of the picked check is returned' );

// Priority breaks ties within a severity, not the order they ran in.
$pick = FixFirst::pick( array( 'dmarc_record' => ecehc_ff( 'fail' ), 'spf_record' => ecehc_ff( 'fail' ), 'dkim_record' => ecehc_ff( 'fail' ) ) );
ecehc_assert_same( 'spf_record', $pick['id'], 'SPF ranks above DKIM and DMARC' );

$pick = FixFirst::pick( array( 'wc_email_setup' => ecehc_ff( 'warning' ), 'smtp_configured' => ecehc_ff( 'warning' ) ) );
ecehc_assert_same( 'smtp_configured', $pick['id'], 'the mail service warning ranks above WooCommerce setup warnings' );

// "Not checked" never outranks a real failure, and is never picked.
$pick = FixFirst::pick( array( 'basic_functionality' => ecehc_ff( 'unknown' ), 'dkim_record' => ecehc_ff( 'unknown' ), 'spf_record' => ecehc_ff( 'warning' ) ) );
ecehc_assert_same( 'spf_record', $pick['id'], 'a warning beats a not-checked result even when that ranks higher' );
ecehc_assert_same( 2, $pick['not_checked'], 'not-checked results are counted' );

$pick = FixFirst::pick( array( 'basic_functionality' => ecehc_ff( 'unknown' ), 'spf_record' => ecehc_ff( 'pass' ) ) );
ecehc_assert_same( 'ok', $pick['state'], 'pass plus not checked is ok' );
ecehc_assert_same( 1, $pick['not_checked'], 'the not-checked count is kept in the ok state' );

// Everything passes.
$pick = FixFirst::pick( array( 'spf_record' => ecehc_ff( 'pass' ), 'dmarc_record' => ecehc_ff( 'pass' ) ) );
ecehc_assert_same( 'ok', $pick['state'], 'all passing is ok' );
ecehc_assert_same( '', $pick['id'], 'nothing is picked when all pass' );

// Nothing conclusive.
ecehc_assert_same( 'unchecked', FixFirst::pick( array( 'dkim_record' => ecehc_ff( 'unknown' ) ) )['state'], 'only not-checked results are unchecked, not ok' );
ecehc_assert_same( 'unchecked', FixFirst::pick( array() )['state'], 'no results are unchecked' );

// Checks from other code rank after ours of the same severity, in run order.
$pick = FixFirst::pick( array( 'zz_custom' => ecehc_ff( 'fail', 'Custom' ), 'pending_orders' => ecehc_ff( 'fail' ) ) );
ecehc_assert_same( 'pending_orders', $pick['id'], 'a custom check ranks after the built-in ones' );
$pick = FixFirst::pick( array( 'b_custom' => ecehc_ff( 'fail' ), 'a_custom' => ecehc_ff( 'fail' ) ) );
ecehc_assert_same( 'b_custom', $pick['id'], 'custom checks keep their run order' );

// Next step: ours for built-in ids, the check's own message otherwise.
$pick = FixFirst::pick( array( 'zz_custom' => array( 'label' => 'Custom', 'status' => 'fail', 'message' => 'Do the custom thing.', 'guidance' => array( 'text' => 't' ) ) ) );
ecehc_assert_same( 'Do the custom thing.', $pick['step'], 'a custom check uses its own message as the step' );
ecehc_assert_true( $pick['has_guidance'], 'guidance is detected' );
ecehc_assert_same( false, FixFirst::pick( array( 'spf_record' => ecehc_ff( 'fail' ) ) )['has_guidance'], 'no guidance is detected' );
ecehc_assert_true( '' !== FixFirst::next_step( 'spf_record', 'x' ) && 'x' !== FixFirst::next_step( 'spf_record', 'x' ), 'a built-in check has its own step' );

// Malformed input does not break it.
$pick = FixFirst::pick( array( 'x' => 'not an array', 'spf_record' => ecehc_ff( 'fail' ) ) );
ecehc_assert_same( 'spf_record', $pick['id'], 'a malformed entry is ignored' );

// The announcement text.
ecehc_assert_true( 0 === strpos( FixFirst::headline( FixFirst::pick( array( 'spf_record' => ecehc_ff( 'fail', 'SPF' ) ) ) ), 'Fix this first: SPF.' ), 'the headline names the check' );
