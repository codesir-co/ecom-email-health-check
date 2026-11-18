<?php
use CodeSir\EmailHealthCheck\Support\DnsProviderDetector;
use CodeSir\EmailHealthCheck\Support\DnsProviderGuidance;

$slug = static function ( $result ) {
	return $result['slug'] ?? null;
};

// Suffix match with a boundary.
ecehc_assert_same( 'cloudflare', $slug( DnsProviderDetector::from_name_servers( array( 'anna.ns.cloudflare.com' ) ) ), 'Cloudflare name server matches' );
ecehc_assert_same( 'cloudflare', $slug( DnsProviderDetector::from_name_servers( array( 'ANNA.NS.Cloudflare.com.' ) ) ), 'case and trailing dot are ignored' );
ecehc_assert_same( 'cloudflare', $slug( DnsProviderDetector::from_name_servers( array( 'ns.cloudflare.com' ) ) ), 'the exact suffix matches' );
ecehc_assert_same( null, DnsProviderDetector::from_name_servers( array( 'ns1.notcloudflare.com' ) ), 'sibling sharing the suffix text does not match' );
ecehc_assert_same( null, DnsProviderDetector::from_name_servers( array( 'xns.cloudflare.com' ) ), 'no dot before the suffix does not match' );
ecehc_assert_same( null, DnsProviderDetector::from_name_servers( array( 'ns.cloudflare.com.evil.example' ) ), 'suffix in the middle does not match' );
ecehc_assert_same( null, DnsProviderDetector::from_name_servers( array() ), 'no names gives null' );
ecehc_assert_same( null, DnsProviderDetector::from_name_servers( array( '' ) ), 'an empty name gives null' );
ecehc_assert_same( 'godaddy', $slug( DnsProviderDetector::from_name_servers( array( 'ns57.domaincontrol.com' ) ) ), 'GoDaddy' );
ecehc_assert_same( 'namecheap', $slug( DnsProviderDetector::from_name_servers( array( 'dns1.registrar-servers.com' ) ) ), 'Namecheap' );
ecehc_assert_same( 'wix', $slug( DnsProviderDetector::from_name_servers( array( 'ns10.wixdns.net' ) ) ), 'Wix' );
ecehc_assert_same( 'route-53', $slug( DnsProviderDetector::from_name_servers( array( 'ns-1234.awsdns-12.org' ) ) ), 'Route 53 pattern' );
ecehc_assert_same( 'route-53', $slug( DnsProviderDetector::from_name_servers( array( 'ns-5.awsdns-01.co.uk' ) ) ), 'Route 53 co.uk' );
ecehc_assert_same( null, DnsProviderDetector::from_name_servers( array( 'ns-1.notawsdns-12.org' ) ), 'Route 53 sibling does not match' );
ecehc_assert_same( null, DnsProviderDetector::from_name_servers( array( 'ns-1.awsdns-12.org.evil.example' ) ), 'Route 53 pattern is anchored at the end' );
ecehc_assert_same( null, DnsProviderDetector::from_name_servers( array( 'ns-cloud-a1.googledomains.com' ) ), 'googledomains.com is ambiguous and not matched' );
ecehc_assert_same( 'godaddy', $slug( DnsProviderDetector::from_name_servers( array( 'ns1.example.net', 'ns02.domaincontrol.com' ) ) ), 'any name server can match' );

// Candidates: the domain, then parents, never a bare TLD.
ecehc_assert_same( array( 'mail.shop.example.com', 'shop.example.com', 'example.com' ), DnsProviderDetector::candidates( 'mail.shop.example.com' ), 'walks up to the registered domain' );
ecehc_assert_same( array( 'example.com' ), DnsProviderDetector::candidates( 'example.com' ), 'a registered domain is asked once' );
ecehc_assert_same( array(), DnsProviderDetector::candidates( 'com' ), 'a bare TLD is never asked' );
ecehc_assert_same( array( 'shop.example.co.uk', 'example.co.uk' ), DnsProviderDetector::candidates( 'shop.example.co.uk' ), 'a known two-part public suffix is skipped' );
ecehc_assert_same( DnsProviderDetector::MAX_LABELS, count( DnsProviderDetector::candidates( 'a.b.c.d.e.f.g.h.example.com' ) ), 'the walk is capped' );

// Filter validation.
ecehc_assert_same( null, DnsProviderDetector::validate( null ), 'null stays null' );
ecehc_assert_same( null, DnsProviderDetector::validate( 'cloudflare' ), 'a string is rejected' );
ecehc_assert_same( null, DnsProviderDetector::validate( array( 'slug' => 'x' ) ), 'missing name is rejected' );
ecehc_assert_same( null, DnsProviderDetector::validate( array( 'slug' => 1, 'name' => 'X' ) ), 'non-string slug is rejected' );
ecehc_assert_same( null, DnsProviderDetector::validate( array( 'slug' => ' ', 'name' => 'X' ) ), 'blank slug is rejected' );
ecehc_assert_same( array( 'slug' => 'acme', 'name' => 'Acme' ), DnsProviderDetector::validate( array( 'slug' => 'acme', 'name' => 'Acme', 'extra' => 1 ) ), 'a valid array is normalised' );

// name_servers(): walk-up, caching and TTLs with an injected resolver.
$calls   = array();
$resolve = static function ( array $answers ) use ( &$calls ) {
	return static function ( $host ) use ( $answers, &$calls ) {
		$calls[] = $host;
		return array_key_exists( $host, $answers ) ? $answers[ $host ] : array();
	};
};
$ttl = static function () {
	return $GLOBALS['ecehc_test_transient_ttl']['ecehc_dns_provider'] ?? null;
};

DnsProviderDetector::clear_cache();
$names = DnsProviderDetector::name_servers( 'mail.example.com', $resolve( array( 'example.com' => array( 'a.ns.cloudflare.com', 'b.ns.cloudflare.com' ) ) ) );
ecehc_assert_same( array( 'a.ns.cloudflare.com', 'b.ns.cloudflare.com' ), $names, 'a subdomain without NS records uses its parent' );
ecehc_assert_same( array( 'mail.example.com', 'example.com' ), $calls, 'the subdomain is asked first, then the parent' );
ecehc_assert_same( DnsProviderDetector::CACHE_TTL, $ttl(), 'a success is cached for the full TTL' );
ecehc_assert_same( array( 'domain' => 'mail.example.com', 'ns' => array( 'a.ns.cloudflare.com', 'b.ns.cloudflare.com' ) ), get_transient( 'ecehc_dns_provider' ), 'the domain and the name servers are cached' );

$calls = array();
DnsProviderDetector::name_servers( 'mail.example.com', $resolve( array() ) );
ecehc_assert_same( array(), $calls, 'a matching cache entry makes no lookup' );

DnsProviderDetector::name_servers( 'other.org', $resolve( array( 'other.org' => array( 'ns1.wixdns.net' ) ) ) );
ecehc_assert_same( array( 'other.org' ), $calls, 'a cache entry for another domain is not reused' );
ecehc_assert_same( 'other.org', get_transient( 'ecehc_dns_provider' )['domain'] ?? null, 'the cache now holds the new domain' );

DnsProviderDetector::clear_cache();
ecehc_assert_same( false, get_transient( 'ecehc_dns_provider' ), 'clear_cache removes the transient' );

// More than four names: only the first four are kept.
$many = array( 'n1.example.net', 'n2.example.net', 'n3.example.net', 'n4.example.net', 'n5.example.net' );
ecehc_assert_same( array_slice( $many, 0, 4 ), DnsProviderDetector::name_servers( 'big.example', $resolve( array( 'big.example' => $many ) ) ), 'the cache keeps at most four names' );

// No NS records anywhere: empty, cached for the full TTL.
DnsProviderDetector::clear_cache();
$calls = array();
ecehc_assert_same( array(), DnsProviderDetector::name_servers( 'none.example.com', $resolve( array() ) ), 'no NS records gives an empty list' );
ecehc_assert_same( array( 'none.example.com', 'example.com' ), $calls, 'every candidate was asked' );
ecehc_assert_same( DnsProviderDetector::CACHE_TTL, $ttl(), 'an empty result is cached for the full TTL' );
$calls = array();
DnsProviderDetector::name_servers( 'none.example.com', $resolve( array( 'example.com' => array( 'x.ns.cloudflare.com' ) ) ) );
ecehc_assert_same( array(), $calls, 'the cached empty result is not looked up again' );

// A failed lookup: empty, cached for ten minutes only, stops the walk.
DnsProviderDetector::clear_cache();
$calls = array();
ecehc_assert_same( array(), DnsProviderDetector::name_servers( 'fail.example.com', $resolve( array( 'fail.example.com' => null ) ) ), 'a failed lookup gives an empty list' );
ecehc_assert_same( array( 'fail.example.com' ), $calls, 'a failure stops the walk' );
ecehc_assert_same( 10 * MINUTE_IN_SECONDS, $ttl(), 'a failure is cached for ten minutes only' );
DnsProviderDetector::clear_cache();

// detect_for(): end to end with the filter.
$cf = $resolve( array( 'example.com' => array( 'x.ns.cloudflare.com' ) ) );
ecehc_assert_same( 'cloudflare', $slug( DnsProviderDetector::detect_for( 'Example.com.', $cf ) ), 'detect_for finds the provider' );
DnsProviderDetector::clear_cache();
ecehc_assert_same( null, DnsProviderDetector::detect_for( '', $cf ), 'an empty domain gives null' );
$calls = array();
ecehc_assert_same( null, DnsProviderDetector::detect_for( 'gmail.com', $resolve( array() ) ), 'a public mailbox domain gives null' );
ecehc_assert_same( array(), $calls, 'a public mailbox domain makes no lookup' );
ecehc_assert_same( null, DnsProviderDetector::detect_for( 'unknown.example', $resolve( array( 'unknown.example' => array( 'ns1.unknown.example' ) ) ) ), 'an unknown provider gives null' );

DnsProviderDetector::clear_cache();
add_filter( 'ecehc_detected_dns_provider', static function () {
	return array( 'slug' => 'acme', 'name' => 'Acme DNS' );
}, 10, 2 );
ecehc_assert_same( 'acme', $slug( DnsProviderDetector::detect_for( 'example.com', $cf ) ), 'filter can set a provider' );
$GLOBALS['ecehc_test_hooks'] = array();

add_filter( 'ecehc_detected_dns_provider', static function () {
	return 'garbage';
}, 10, 2 );
ecehc_assert_same( null, DnsProviderDetector::detect_for( 'example.com', $cf ), 'filter returning garbage gives null' );
$GLOBALS['ecehc_test_hooks'] = array();

$seen = array();
add_filter( 'ecehc_detected_dns_provider', static function ( $provider, $domain ) use ( &$seen ) {
	$seen = array( $provider, $domain );
	return $provider;
}, 10, 2 );
DnsProviderDetector::detect_for( 'example.com', $cf );
ecehc_assert_same( 'example.com', $seen[1] ?? null, 'the filter receives the domain' );
ecehc_assert_same( 'cloudflare', $seen[0]['slug'] ?? null, 'the filter receives the detected provider' );
$GLOBALS['ecehc_test_hooks'] = array();
DnsProviderDetector::clear_cache();

// Parent walk: only the parent domain has name servers.
DnsProviderDetector::clear_cache();
$parent_only = static function ( $host ) {
	return 'example.com' === $host ? array( 'a.ns.cloudflare.com' ) : array();
};
ecehc_assert_same( 'cloudflare', $slug( DnsProviderDetector::detect_for( 'mail.shop.example.com', $parent_only ) ), 'detect_for reaches the provider through the parent walk' );
DnsProviderDetector::clear_cache();
ecehc_assert_same( 'route-53', $slug( DnsProviderDetector::from_name_servers( array( 'ns-9.awsdns-02.net' ) ) ), 'Route 53 .net name server' );

// Combined-note rule.
$gd_dns  = array( 'slug' => 'godaddy', 'name' => 'GoDaddy' );
$gd_host = array( 'slug' => 'godaddy', 'name' => 'GoDaddy', 'confidence' => 'high' );
ecehc_assert_true( DnsProviderGuidance::is_same_company( $gd_dns, $gd_host ), 'same slug is one company' );
ecehc_assert_true( DnsProviderGuidance::is_same_company( array( 'slug' => 'a', 'name' => 'Acme' ), array( 'slug' => 'b', 'name' => 'ACME', 'confidence' => 'high' ) ), 'same name ignoring case is one company' );
ecehc_assert_same( false, DnsProviderGuidance::is_same_company( array( 'slug' => 'cloudflare', 'name' => 'Cloudflare' ), $gd_host ), 'different companies are not combined' );
ecehc_assert_same( false, DnsProviderGuidance::is_same_company( null, $gd_host ), 'no DNS provider is not combined' );
ecehc_assert_same( false, DnsProviderGuidance::is_same_company( $gd_dns, null ), 'no host is not combined' );
ecehc_assert_same( false, DnsProviderGuidance::is_same_company( null, null ), 'nothing detected is not combined' );

ecehc_assert_same( false, DnsProviderGuidance::is_same_company( $gd_dns, array( 'slug' => 'godaddy', 'name' => 'GoDaddy', 'confidence' => 'medium' ) ), 'a medium-confidence host is not combined' );
ecehc_assert_same( false, DnsProviderGuidance::is_same_company( $gd_dns, array( 'slug' => 'godaddy', 'name' => 'GoDaddy' ) ), 'a host without confidence is not combined' );
ecehc_assert_same( false, DnsProviderGuidance::is_same_company( $gd_dns, array( 'name' => 'GoDaddy', 'confidence' => 'high' ) ), 'a malformed host (no slug) is not combined' );
ecehc_assert_same( false, DnsProviderGuidance::is_same_company( array( 'slug' => 'godaddy' ), $gd_host ), 'a malformed DNS provider (no name) is not combined' );

// Guidance table.
foreach ( array( 'cloudflare', 'godaddy', 'namecheap', 'squarespace', 'route-53', 'wix', 'wordpress-com', 'hostinger', 'bluehost', 'digitalocean' ) as $known ) {
	$guidance = DnsProviderGuidance::for_provider( $known );
	ecehc_assert_true( is_array( $guidance ) && '' !== $guidance['note'], 'note for ' . $known );
	ecehc_assert_true( 0 === strpos( $guidance['docs'] ?? '', 'https://' ) || '' === ( $guidance['docs'] ?? '' ), 'docs link is https or empty for ' . $known );
}
ecehc_assert_same( '', DnsProviderGuidance::for_provider( 'hostinger' )['docs'], 'unverified link is omitted' );
ecehc_assert_same( '', DnsProviderGuidance::for_provider( 'bluehost' )['docs'], 'unverified link is omitted for Bluehost' );
ecehc_assert_same( null, DnsProviderGuidance::for_provider( 'acme' ), 'unknown slug has no note' );
ecehc_assert_same( null, DnsProviderGuidance::for_provider( '' ), 'empty slug has no note' );

// Dns::ns_records() refuses implausible names before any query is made.
ecehc_assert_same( null, \CodeSir\EmailHealthCheck\Support\Dns::ns_records( '' ), 'ns_records: empty name is null' );
ecehc_assert_same( null, \CodeSir\EmailHealthCheck\Support\Dns::ns_records( 'bad host!.example' ), 'ns_records: invalid characters are null' );
ecehc_assert_same( null, \CodeSir\EmailHealthCheck\Support\Dns::ns_records( str_repeat( 'a', 254 ) ), 'ns_records: over-long name is null' );
