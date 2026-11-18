<?php
use CodeSir\EmailHealthCheck\Support\HostDetector;
use CodeSir\EmailHealthCheck\Support\HostGuidance;

$none = static function () {
	return false;
};

// Constants and classes: high confidence.
$only_constant = static function ( $wanted ) {
	return static function ( $name ) use ( $wanted ) {
		return $name === $wanted;
	};
};

$by_constant = array(
	'WPE_APIKEY'           => 'wp-engine',
	'KINSTAMU_VERSION'     => 'kinsta',
	'KINSTA_CACHE_ZONE'    => 'kinsta',
	'PANTHEON_ENVIRONMENT' => 'pantheon',
	'FLYWHEEL_CONFIG_DIR'  => 'flywheel',
	'GD_SYSTEM_PLUGIN_DIR' => 'godaddy',
	'IS_ATOMIC'            => 'wordpress-com',
	'IS_WPCOM'             => 'wordpress-com',
);
foreach ( $by_constant as $constant => $slug ) {
	$host = HostDetector::from_environment( $only_constant( $constant ), $none );
	ecehc_assert_same( $slug, $host['slug'] ?? null, 'constant ' . $constant );
	ecehc_assert_same( 'high', $host['confidence'] ?? null, 'constant confidence ' . $constant );
}
$host = HostDetector::from_environment( $none, $only_constant( 'WpeCommon' ) );
ecehc_assert_same( 'WP Engine', $host['name'] ?? null, 'WpeCommon class' );
ecehc_assert_same( null, HostDetector::from_environment( $none, $none ), 'no signals is null' );

// Reverse DNS names: medium confidence, with a boundary before the suffix.
$host = HostDetector::from_hostname( 'web12.siteground.net' );
ecehc_assert_same( 'siteground', $host['slug'] ?? null, 'PTR subdomain matches' );
ecehc_assert_same( 'medium', $host['confidence'] ?? null, 'PTR confidence is medium' );
ecehc_assert_same( 'siteground', HostDetector::from_hostname( 'SiteGround.net.' )['slug'] ?? null, 'PTR exact domain, case and trailing dot' );
ecehc_assert_same( null, HostDetector::from_hostname( 'evilsiteground.net' ), 'sibling domain sharing the suffix does not match' );
ecehc_assert_same( null, HostDetector::from_hostname( 'siteground.net.evil.example' ), 'suffix in the middle does not match' );
ecehc_assert_same( null, HostDetector::from_hostname( 'notwpengine.com' ), 'no dot before the suffix does not match' );
ecehc_assert_same( null, HostDetector::from_hostname( '' ), 'empty PTR name is null' );
ecehc_assert_same( 'wp-engine', HostDetector::from_hostname( 'x.y.wpengine.com' )['slug'] ?? null, 'nested subdomain matches' );

// Filter validation.
ecehc_assert_same( null, HostDetector::validate( null ), 'null stays null' );
ecehc_assert_same( null, HostDetector::validate( 'wp-engine' ), 'a string is rejected' );
ecehc_assert_same( null, HostDetector::validate( array( 'slug' => 'x' ) ), 'missing name is rejected' );
ecehc_assert_same( null, HostDetector::validate( array( 'slug' => 1, 'name' => 'X' ) ), 'non-string slug is rejected' );
ecehc_assert_same( null, HostDetector::validate( array( 'slug' => ' ', 'name' => 'X' ) ), 'blank slug is rejected' );
ecehc_assert_same( array( 'slug' => 'acme', 'name' => 'Acme', 'confidence' => 'medium' ), HostDetector::validate( array( 'slug' => 'acme', 'name' => 'Acme', 'extra' => 1 ) ), 'a valid array is normalised' );

$_SERVER['SERVER_ADDR'] = '';
unset( $GLOBALS['ecehc_test_transients']['ecehc_host_ptr'] );

add_filter( 'ecehc_detected_host', static function () {
	return array( 'slug' => 'acme', 'name' => 'Acme Hosting', 'confidence' => 'high' );
} );
$host = HostDetector::detect();
ecehc_assert_same( 'acme', $host['slug'] ?? null, 'filter can set a host' );
$GLOBALS['ecehc_test_hooks'] = array();

add_filter( 'ecehc_detected_host', static function () {
	return 'garbage';
} );
ecehc_assert_same( null, HostDetector::detect(), 'filter returning garbage gives null' );
$GLOBALS['ecehc_test_hooks'] = array();

// No server IP: no lookup (nothing is cached) and, with no constants, no host.
if ( ! defined( 'WPE_APIKEY' ) && ! defined( 'PANTHEON_ENVIRONMENT' ) && ! defined( 'KINSTAMU_VERSION' ) && ! defined( 'IS_ATOMIC' ) ) {
	ecehc_assert_same( null, HostDetector::detect(), 'no signals and no server IP is null' );
	ecehc_assert_same( false, get_transient( 'ecehc_host_ptr' ), 'no server IP means nothing cached' );
}

// A cached PTR name is used without a lookup.
$_SERVER['SERVER_ADDR'] = '8.8.4.4';
set_transient( 'ecehc_host_ptr', array( 'ip' => '8.8.4.4', 'host' => 'srv1.kinsta.cloud' ) );
if ( ! defined( 'WPE_APIKEY' ) && ! defined( 'PANTHEON_ENVIRONMENT' ) && ! defined( 'KINSTAMU_VERSION' ) && ! defined( 'IS_ATOMIC' ) ) {
	$host = HostDetector::detect();
	ecehc_assert_same( 'kinsta', $host['slug'] ?? null, 'cached PTR name is used' );
	ecehc_assert_same( 'medium', $host['confidence'] ?? null, 'cached PTR match is medium' );
}
HostDetector::clear_cache();
ecehc_assert_same( false, get_transient( 'ecehc_host_ptr' ), 'clear_cache removes the transient' );
unset( $_SERVER['SERVER_ADDR'] );

// Guidance.
foreach ( array( 'wp-engine', 'kinsta', 'siteground', 'pantheon', 'flywheel', 'cloudways', 'godaddy', 'wordpress-com' ) as $slug ) {
	$g = HostGuidance::for_host( $slug );
	ecehc_assert_true( is_array( $g ) && '' !== $g['mail'] && '' !== $g['dns'], 'guidance exists for ' . $slug );
	ecehc_assert_true( '' === ( $g['docs'] ?? '' ) || 0 === strpos( $g['docs'], 'https://' ), 'docs link is https for ' . $slug );
}
ecehc_assert_same( null, HostGuidance::for_host( 'acme' ), 'unknown host has no guidance' );
