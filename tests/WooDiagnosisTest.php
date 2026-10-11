<?php
use CodeSir\EmailHealthCheck\Support\WooDiagnosis;

function ecehc_wd( array $statuses ) {
	$results = array();
	foreach ( $statuses as $id => $status ) {
		$results[ $id ] = array( 'label' => $id, 'status' => $status, 'message' => 'm' );
	}
	return $results;
}

$clean = array(
	'smtp_configured' => 'pass',
	'from_address'    => 'pass',
	'spf_record'      => 'pass',
	'dkim_record'     => 'pass',
	'dmarc_record'    => 'pass',
	'ip_blacklist'    => 'pass',
	'wc_email_setup'  => 'pass',
	'pending_orders'  => 'pass',
);
$some_mail = array( 'wc_sent' => 12, 'wc_failed' => 0 );

$d = WooDiagnosis::diagnose( ecehc_wd( $clean ), $some_mail );
ecehc_assert_same( 'none', $d['cause'], 'nothing wrong: no clear cause' );
ecehc_assert_same( 'on', $d['log'], 'log state on' );
ecehc_assert_true( false === strpos( $d['headline'], 'Most likely' ) && false !== strpos( $d['headline'], 'clear cause' ), 'the no-cause wording does not claim a cause' );

// Each cause.
$d = WooDiagnosis::diagnose( ecehc_wd( array( 'wc_email_setup' => 'fail' ) + $clean ), $some_mail );
ecehc_assert_same( 'woo_setup_broken', $d['cause'], 'a broken WooCommerce setup' );
ecehc_assert_same( 'wc_settings', $d['link'], 'links to WooCommerce settings' );

$d = WooDiagnosis::diagnose( ecehc_wd( array( 'wc_email_setup' => 'warning' ) + $clean ), $some_mail );
ecehc_assert_same( 'woo_setup_warning', $d['cause'], 'turned-off WooCommerce emails' );

$d = WooDiagnosis::diagnose( ecehc_wd( array( 'pending_orders' => 'warning' ) + $clean ), $some_mail );
ecehc_assert_same( 'unpaid_orders', $d['cause'], 'unpaid orders' );
ecehc_assert_same( 'pending_orders', $d['check_id'], 'points at the unpaid orders check' );

$d = WooDiagnosis::diagnose( ecehc_wd( array( 'smtp_configured' => 'warning' ) + $clean ), $some_mail );
ecehc_assert_same( 'plain_php_mail', $d['cause'], 'no SMTP service' );

$d = WooDiagnosis::diagnose( ecehc_wd( array( 'from_address' => 'fail' ) + $clean ), $some_mail );
ecehc_assert_same( 'from_address', $d['cause'], 'a From address at a public mailbox domain' );
$d = WooDiagnosis::diagnose( ecehc_wd( array( 'from_address' => 'warning' ) + $clean ), $some_mail );
ecehc_assert_same( 'from_domain_differs', $d['cause'], 'a From domain that differs from the site is only a possible cause' );
ecehc_assert_true( 0 === strpos( $d['headline'], 'Possibly' ), 'the From domain mismatch wording is hedged' );

$d = WooDiagnosis::diagnose( ecehc_wd( array( 'dmarc_record' => 'fail', 'spf_record' => 'fail' ) + $clean ), $some_mail );
ecehc_assert_same( 'dns_records', $d['cause'], 'missing DNS records' );
ecehc_assert_same( 'spf_record', $d['check_id'], 'the first failing record in SPF, DKIM, DMARC order' );

$d = WooDiagnosis::diagnose( ecehc_wd( array( 'ip_blacklist' => 'fail' ) + $clean ), $some_mail );
ecehc_assert_same( 'blacklist', $d['cause'], 'a blacklisted IP' );

// Log failures: recent WooCommerce failures point at the mail system, ahead of softer causes, behind a broken setup.
$failing_log = array( 'wc_sent' => 3, 'wc_failed' => 4 );
$d           = WooDiagnosis::diagnose( ecehc_wd( array( 'smtp_configured' => 'warning', 'pending_orders' => 'warning' ) + $clean ), $failing_log );
ecehc_assert_same( 'log_failures', $d['cause'], 'recent WooCommerce failures in the log rank above a missing SMTP service' );
ecehc_assert_same( 'log_failed', $d['link'], 'links to the failed emails' );
ecehc_assert_true( false !== strpos( $d['headline'], '4' ), 'the failure count is shown' );
$d = WooDiagnosis::diagnose( ecehc_wd( array( 'wc_email_setup' => 'fail' ) + $clean ), $failing_log );
ecehc_assert_same( 'woo_setup_broken', $d['cause'], 'a broken setup ranks above log failures' );

// Log off: no log-based cause, and the note says so.
$d = WooDiagnosis::diagnose( ecehc_wd( $clean ), null );
ecehc_assert_same( 'none', $d['cause'], 'log off and nothing wrong: no clear cause' );
ecehc_assert_same( 'off', $d['log'], 'log state off' );
ecehc_assert_true( false !== strpos( $d['log_note'], 'is off' ), 'the note says the log is off' );
$d = WooDiagnosis::diagnose( ecehc_wd( $clean ), null, false );
ecehc_assert_same( 'unsupported', $d['log'], 'log state unsupported' );
$d = WooDiagnosis::diagnose( ecehc_wd( array( 'smtp_configured' => 'warning' ) + $clean ), null );
ecehc_assert_same( 'plain_php_mail', $d['cause'], 'check-based causes work with the log off' );

// Log on but no WooCommerce mail at all.
$d = WooDiagnosis::diagnose( ecehc_wd( $clean ), array( 'wc_sent' => 0, 'wc_failed' => 0 ) );
ecehc_assert_same( 'no_woo_mail_logged', $d['cause'], 'no WooCommerce mail logged in the window' );
$d = WooDiagnosis::diagnose( ecehc_wd( array( 'smtp_configured' => 'warning' ) + $clean ), array( 'wc_sent' => 0, 'wc_failed' => 0 ) );
ecehc_assert_same( 'plain_php_mail', $d['cause'], 'a failing check wins over an empty log' );

// Unknown and missing results are not problems.
$d = WooDiagnosis::diagnose( ecehc_wd( array( 'spf_record' => 'unknown', 'dkim_record' => 'unknown' ) ), $some_mail );
ecehc_assert_same( 'none', $d['cause'], 'not-checked results never count as a cause' );
$d = WooDiagnosis::diagnose( array(), $some_mail );
ecehc_assert_same( 'none', $d['cause'], 'no results give no cause' );

// Every cause says "most likely" or "possibly" (never claims certainty).
foreach ( array( 'wc_email_setup' => 'fail', 'smtp_configured' => 'warning', 'ip_blacklist' => 'fail', 'pending_orders' => 'warning', 'dkim_record' => 'fail' ) as $id => $s ) {
	$h = WooDiagnosis::diagnose( ecehc_wd( array( $id => $s ) ), $some_mail )['headline'];
	ecehc_assert_true( 0 === strpos( $h, 'Most likely' ), 'hedged wording for ' . $id );
}
