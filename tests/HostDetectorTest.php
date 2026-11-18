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
$ecehc_no_host_constants = ! defined( 'WPE_APIKEY' ) && ! defined( 'PANTHEON_ENVIRONMENT' ) && ! defined( 'KINSTAMU_VERSION' ) && ! defined( 'IS_ATOMIC' );
if ( $ecehc_no_host_constants ) {
	ecehc_assert_same( null, HostDetector::detect(), 'no signals and no server IP is null' );
	ecehc_assert_same( false, get_transient( 'ecehc_host_ptr' ), 'no server IP means nothing cached' );
}
ecehc_assert_true( $ecehc_no_host_constants, 'test environment defines no host constants, so the guarded assertions ran' );

// A cached PTR name is used without a lookup.
$_SERVER['SERVER_ADDR'] = '8.8.4.4';
set_transient( 'ecehc_host_ptr', array( 'ip' => '8.8.4.4', 'host' => 'srv1.kinsta.cloud' ) );
$ecehc_no_host_constants = ! defined( 'WPE_APIKEY' ) && ! defined( 'PANTHEON_ENVIRONMENT' ) && ! defined( 'KINSTAMU_VERSION' ) && ! defined( 'IS_ATOMIC' );
if ( $ecehc_no_host_constants ) {
	$host = HostDetector::detect();
	ecehc_assert_same( 'kinsta', $host['slug'] ?? null, 'cached PTR name is used' );
	ecehc_assert_same( 'medium', $host['confidence'] ?? null, 'cached PTR match is medium' );
}
ecehc_assert_true( $ecehc_no_host_constants, 'guarded cached-PTR assertions ran' );
HostDetector::clear_cache();
ecehc_assert_same( false, get_transient( 'ecehc_host_ptr' ), 'clear_cache removes the transient' );
unset( $_SERVER['SERVER_ADDR'] );

// hostname_for_ip(): caching rules, with an injected resolver.
$calls  = 0;
$lookup = static function ( $result ) use ( &$calls ) {
	return static function ( $ip ) use ( $result, &$calls ) {
		$calls++;
		return $result;
	};
};
$ttl = static function () {
	return $GLOBALS['ecehc_test_transient_ttl']['ecehc_host_ptr'] ?? null;
};

HostDetector::clear_cache();
ecehc_assert_same( '', HostDetector::hostname_for_ip( '', $lookup( array( 'a.example' ) ) ), 'empty IP gives empty name' );
ecehc_assert_same( 0, $calls, 'empty IP makes no lookup' );

set_transient( 'ecehc_host_ptr', array( 'ip' => '1.1.1.1', 'host' => 'old.kinsta.cloud' ) );
ecehc_assert_same( 'new.wpengine.com', HostDetector::hostname_for_ip( '2.2.2.2', $lookup( array( 'new.wpengine.com', 'other.example' ) ) ), 'cached name for a different IP triggers a fresh lookup' );
ecehc_assert_same( 1, $calls, 'one lookup for the mismatched IP' );
ecehc_assert_same( array( 'ip' => '2.2.2.2', 'host' => 'new.wpengine.com' ), get_transient( 'ecehc_host_ptr' ), 'first name and the new IP are cached' );
ecehc_assert_same( HostDetector::CACHE_TTL, $ttl(), 'a successful lookup is cached for the full TTL' );
HostDetector::hostname_for_ip( '2.2.2.2', $lookup( array( 'x.example' ) ) );
ecehc_assert_same( 1, $calls, 'a matching cache entry makes no lookup' );

HostDetector::clear_cache();
ecehc_assert_same( '', HostDetector::hostname_for_ip( '3.3.3.3', $lookup( array() ) ), 'no PTR record gives empty name' );
ecehc_assert_same( array( 'ip' => '3.3.3.3', 'host' => '' ), get_transient( 'ecehc_host_ptr' ), 'an empty PTR result is cached as an empty host' );
ecehc_assert_same( HostDetector::CACHE_TTL, $ttl(), 'an empty PTR result is cached for the full TTL' );
$before = $calls;
HostDetector::hostname_for_ip( '3.3.3.3', $lookup( array( 'x.example' ) ) );
ecehc_assert_same( $before, $calls, 'the cached empty host is not looked up again' );

HostDetector::clear_cache();
ecehc_assert_same( '', HostDetector::hostname_for_ip( '4.4.4.4', $lookup( null ) ), 'a failed lookup gives empty name' );
ecehc_assert_same( array( 'ip' => '4.4.4.4', 'host' => '' ), get_transient( 'ecehc_host_ptr' ), 'a failed lookup is cached' );
ecehc_assert_same( 10 * MINUTE_IN_SECONDS, $ttl(), 'a failed lookup is cached for ten minutes only' );
$before = $calls;
HostDetector::hostname_for_ip( '4.4.4.4', $lookup( array( 'x.example' ) ) );
ecehc_assert_same( $before, $calls, 'a failed lookup is not retried while cached' );
HostDetector::clear_cache();
ecehc_assert_same( 'x.example', HostDetector::hostname_for_ip( '4.4.4.4', $lookup( array( 'x.example' ) ) ), 'clearing the cache allows a new lookup' );
HostDetector::clear_cache();

// Signal order: the first host in the documented list wins when two signals are present.
$both = static function ( $name ) {
	return in_array( $name, array( 'WPE_APIKEY', 'KINSTAMU_VERSION', 'IS_ATOMIC' ), true );
};
ecehc_assert_same( 'wp-engine', HostDetector::from_environment( $both, $none )['slug'] ?? null, 'WP Engine wins over Kinsta and WordPress.com' );
$later = static function ( $name ) {
	return in_array( $name, array( 'IS_ATOMIC', 'PANTHEON_ENVIRONMENT' ), true );
};
ecehc_assert_same( 'pantheon', HostDetector::from_environment( $later, $none )['slug'] ?? null, 'Pantheon wins over WordPress.com' );
$class_and_constant = HostDetector::from_environment( $only_constant( 'KINSTAMU_VERSION' ), $only_constant( 'WpeCommon' ) );
ecehc_assert_same( 'wp-engine', $class_and_constant['slug'] ?? null, 'hosts are checked in list order, constant or class' );

// Filter confidence normalisation.
ecehc_assert_same( 'medium', HostDetector::validate( array( 'slug' => 'a', 'name' => 'A', 'confidence' => 'certain' ) )['confidence'] ?? null, 'unknown confidence becomes medium' );
ecehc_assert_same( 'high', HostDetector::validate( array( 'slug' => 'a', 'name' => 'A', 'confidence' => 'high' ) )['confidence'] ?? null, 'high confidence is kept' );

// Guidance.
foreach ( array( 'wp-engine', 'kinsta', 'siteground', 'pantheon', 'flywheel', 'cloudways', 'godaddy', 'wordpress-com' ) as $slug ) {
	$g = HostGuidance::for_host( $slug );
	ecehc_assert_true( is_array( $g ) && '' !== $g['mail'] && '' !== $g['dns'], 'guidance exists for ' . $slug );
	ecehc_assert_true( '' === ( $g['docs'] ?? '' ) || 0 === strpos( $g['docs'], 'https://' ), 'docs link is https for ' . $slug );
}
ecehc_assert_same( null, HostGuidance::for_host( 'acme' ), 'unknown host has no guidance' );
