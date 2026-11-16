<?php
use CodeSir\EmailHealthCheck\Checks\PtrCheck;
use CodeSir\EmailHealthCheck\Checks\CheckRunner;
use CodeSir\EmailHealthCheck\Support\ProviderGuidance;
use CodeSir\EmailHealthCheck\Support\ServerIp;
use CodeSir\EmailHealthCheck\Support\FixFirst;

$ip = '203.0.113.7';

$r = PtrCheck::evaluate( $ip, array( 'mail.example.com' ), array( 'mail.example.com' => array( $ip ) ) );
ecehc_assert_same( 'pass', $r->get_status(), 'forward-confirmed meaningful PTR passes' );

$r = PtrCheck::evaluate( $ip, array( 'a.example.net', 'mail.example.com' ), array( 'a.example.net' => array( '198.51.100.1' ), 'mail.example.com' => array( '198.51.100.9', $ip ) ) );
ecehc_assert_same( 'pass', $r->get_status(), 'one good PTR among several passes' );

foreach ( array( '203-0-113-7.example.net', '203.0.113.7.example.net', '7.113.0.203.example.net', '7-113-0-203.example.net', 'ip-203-0-113-7.host.example' ) as $generic ) {
	$r = PtrCheck::evaluate( $ip, array( $generic ), array( $generic => array( $ip ) ) );
	ecehc_assert_same( 'warning', $r->get_status(), 'generic PTR warns: ' . $generic );
}

ecehc_assert_same( false, PtrCheck::is_generic( 'mail.example.com', $ip ), 'a normal name is not generic' );
ecehc_assert_same( false, PtrCheck::is_generic( 'h1203-0-113-77.example.com', $ip ), 'a longer octet is not a match' );
ecehc_assert_same( false, PtrCheck::is_generic( 'mail-7.example.com', $ip ), 'one octet alone is not a match' );

$r = PtrCheck::evaluate( $ip, array( 'mail.example.com' ), array( 'mail.example.com' => array( '198.51.100.1' ) ) );
ecehc_assert_same( 'warning', $r->get_status(), 'PTR not resolving back to the IP warns' );
$r = PtrCheck::evaluate( $ip, array( 'mail.example.com' ), array( 'mail.example.com' => array() ) );
ecehc_assert_same( 'warning', $r->get_status(), 'PTR hostname with no A record warns' );

$r = PtrCheck::evaluate( $ip, array(), array() );
ecehc_assert_same( 'fail', $r->get_status(), 'no PTR record fails' );

$r = PtrCheck::evaluate( $ip, array( 'mail.example.com' ), array( 'mail.example.com' => null ) );
ecehc_assert_same( 'unknown', $r->get_status(), 'a failed forward lookup is Not checked, never a verdict' );

// Server IP helper (shared with the blacklist check).
unset( $_SERVER['SERVER_ADDR'] );
ecehc_assert_same( '', ServerIp::detect(), 'no SERVER_ADDR gives no IP' );
$_SERVER['SERVER_ADDR'] = '203.0.113.7';
ecehc_assert_same( '203.0.113.7', ServerIp::detect(), 'a public IPv4 is used' );
$_SERVER['SERVER_ADDR'] = '192.168.1.5';
ecehc_assert_same( '', ServerIp::detect(), 'a private IP is ignored' );
$_SERVER['SERVER_ADDR'] = '2001:db8::1';
ecehc_assert_same( '', ServerIp::detect(), 'IPv6 is ignored' );
add_filter( 'ecehc_server_ip', static function () {
	return '198.51.100.20';
} );
ecehc_assert_same( '198.51.100.20', ServerIp::detect(), 'the ecehc_server_ip filter still overrides' );
unset( $_SERVER['SERVER_ADDR'] );

// Guidance.
$g = ProviderGuidance::for_check( 'ptr_record', null, 'example.com' );
ecehc_assert_true( is_array( $g ) && false !== strpos( $g['text'], 'reverse DNS' ), 'ptr_record has fix guidance' );
ecehc_assert_same( array(), $g['records'], 'ptr_record suggests no DNS records' );
ecehc_assert_same( '', $g['docs'], 'ptr_record has no docs link' );
ecehc_assert_true( false !== strpos( $g['text'], 'mail service' ), 'ptr_record says it does not apply to mail services' );

$d = ProviderGuidance::for_check( 'dmarc_record', null, 'example.com' );
ecehc_assert_true( false !== strpos( $d['text'], '5,000' ) && false !== strpos( $d['text'], 'unsubscribe' ), 'DMARC guidance mentions Gmail and Yahoo bulk sender requirements' );
ecehc_assert_same( 'v=DMARC1; p=none;', $d['records'][0]['value'], 'DMARC record suggestion is unchanged' );

// Registration and priority.
$ids = array_map( static function ( $c ) {
	return $c->get_id();
}, array_values( ( new CheckRunner() )->get_checks() ) );
$pos = array_search( 'ptr_record', $ids, true );
ecehc_assert_same( array_search( 'ip_blacklist', $ids, true ) + 1, $pos, 'the PTR check runs right after the blacklist check' );
$pick = FixFirst::pick( array( 'ptr_record' => array( 'label' => 'x', 'status' => 'warning', 'message' => 'm' ), 'ip_blacklist' => array( 'label' => 'y', 'status' => 'warning', 'message' => 'm' ) ) );
ecehc_assert_same( 'ip_blacklist', $pick['id'], 'PTR ranks below the blacklist in Fix first' );
