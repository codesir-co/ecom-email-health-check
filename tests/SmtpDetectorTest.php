<?php
use CodeSir\EmailHealthCheck\Log\EmailLogger;
use CodeSir\EmailHealthCheck\Support\SmtpDetector;

// Regression test for the 1.3.0 bug: the email log's own pre_wp_mail hook was counted as a mail service.
$method = new ReflectionMethod( SmtpDetector::class, 'has_foreign_pre_wp_mail' );
$method->setAccessible( true );

$hook            = new stdClass();
$GLOBALS['wp_filter'] = array( 'pre_wp_mail' => $hook );

$hook->callbacks = array();
ecehc_assert_same( false, $method->invoke( null ), 'no pre_wp_mail callbacks means no mail service' );

$hook->callbacks = array( PHP_INT_MAX => array( 'logger' => array( 'function' => array( new EmailLogger(), 'forget_context' ), 'accepted_args' => 1 ) ) );
ecehc_assert_same( false, $method->invoke( null ), "the plugin's own logger hook alone is NOT a mail service" );

$hook->callbacks[10] = array( 'other' => array( 'function' => function ( $pre ) {
	return $pre;
}, 'accepted_args' => 1 ) );
ecehc_assert_same( true, $method->invoke( null ), 'a pre_wp_mail hook from another plugin counts' );

unset( $GLOBALS['wp_filter'] );
