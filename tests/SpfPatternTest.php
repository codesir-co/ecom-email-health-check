<?php
use CodeSir\EmailHealthCheck\Checks\SpfCheck;

$records = array(
	'v=spf1 include:_spf.google.com ~all'            => true,
	'v=spf1 +include:mailgun.org -all'               => true,
	'v=spf1 ~include:spf.brevo.com ~all'             => true,
	'v=spf1 ip4:203.0.113.5 -all'                    => true,
	'v=spf1 ip6:2001:db8::/32 ?all'                  => true,
	'v=spf1 a mx -all'                               => true,
	'v=spf1 a:mail.example.com -all'                 => true,
	'v=spf1 mx/24 -all'                              => true,
	'v=spf1 exists:%{i}.rbl.example.com -all'        => true,
	'v=spf1 redirect=_spf.example.com'               => true,
	'v=spf1 -all'                                    => false,
	'v=spf1 ~all'                                    => false,
	'v=spf1 +all'                                    => false,
	'v=spf1 alpha -all'                              => false, // "a" must not match the start of another word.
);

foreach ( $records as $record => $authorises ) {
	ecehc_assert_same( $authorises, 1 === preg_match( SpfCheck::MECHANISM_PATTERN, $record ), 'mechanism pattern: ' . $record );
}

ecehc_assert_same( 1, preg_match( SpfCheck::RECORD_PATTERN, 'v=spf1 -all' ), 'an SPF record is recognised' );
ecehc_assert_same( 1, preg_match( SpfCheck::RECORD_PATTERN, 'V=SPF1' ), 'the tag is case-insensitive and may stand alone' );
ecehc_assert_same( 0, preg_match( SpfCheck::RECORD_PATTERN, 'v=spf10 -all' ), 'v=spf10 is not SPF' );
ecehc_assert_same( 0, preg_match( SpfCheck::RECORD_PATTERN, 'google-site-verification=abc' ), 'other TXT records are ignored' );
