<?php
use CodeSir\EmailHealthCheck\Support\ProviderGuidance;

$mailgun  = array( 'name' => 'Mailgun', 'spf' => array( 'mailgun.org' ) );
$postmark = array( 'name' => 'Postmark', 'spf' => array( 'mtasv.net' ) );

ecehc_assert_true( ProviderGuidance::needs_root_spf( $mailgun ), 'Mailgun needs its include in the root SPF record' );
ecehc_assert_same( false, ProviderGuidance::needs_root_spf( $postmark ), 'Postmark authenticates SPF through its return-path, no root include' );
ecehc_assert_true( ProviderGuidance::needs_root_spf( array( 'name' => 'Unknown Mail Co', 'spf' => array( 'x.example' ) ) ), 'an unknown provider keeps the old behaviour' );

$spf = ProviderGuidance::for_check( 'spf_record', $mailgun, 'example.com' );
ecehc_assert_same( 'v=spf1 include:mailgun.org ~all', $spf['records'][0]['value'], 'the SPF record suggested for Mailgun' );
ecehc_assert_same( 'example.com', $spf['records'][0]['host'], 'the SPF record host is the domain' );

$spf = ProviderGuidance::for_check( 'spf_record', $postmark, 'example.com' );
ecehc_assert_same( array(), $spf['records'], 'no root SPF record is invented for Postmark' );

$dmarc = ProviderGuidance::for_check( 'dmarc_record', null, 'example.com' );
ecehc_assert_same( '_dmarc.example.com', $dmarc['records'][0]['host'], 'the DMARC host' );
ecehc_assert_same( null, ProviderGuidance::for_check( 'spf_record', null, 'example.com' ), 'no SPF help without a known provider' );
ecehc_assert_same( null, ProviderGuidance::for_check( 'something_else', $mailgun, 'example.com' ), 'unknown checks get no help' );
