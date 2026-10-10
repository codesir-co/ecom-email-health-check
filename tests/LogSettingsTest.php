<?php
use CodeSir\EmailHealthCheck\Log\LogSettings;

$GLOBALS['ecehc_test_options'] = array();
ecehc_assert_true( LogSettings::is_enabled(), 'logging is on by default' );
ecehc_assert_same( 7, LogSettings::retention_days(), 'default retention is 7 days' );

LogSettings::save( false, 3 );
ecehc_assert_same( false, LogSettings::is_enabled(), 'logging can be turned off' );
ecehc_assert_same( 3, LogSettings::retention_days(), 'a valid retention is kept' );

LogSettings::save( true, 99 );
ecehc_assert_same( 7, LogSettings::retention_days(), 'an unknown retention falls back to the default' );

$GLOBALS['ecehc_test_options'][ LogSettings::OPTION ] = 'corrupt';
ecehc_assert_true( LogSettings::is_enabled(), 'a corrupt option means defaults' );
ecehc_assert_same( 7, LogSettings::retention_days(), 'a corrupt option means the default retention' );
