<?php
/**
 * GuideLink: map, URL, enabled decision and markup.
 *
 * "No dead end" cannot be proven offline. The slugs in GuideLink::SLUGS were checked live
 * with curl (HTTP 200 on https://outboxhealth.com/guides/<slug>/) on 2026-11-17.
 */

use CodeSir\EmailHealthCheck\Admin\GuideLink;

$provider_guides = array(
	'Google'        => 'authenticate-email-google-workspace',
	'Microsoft 365' => 'authenticate-email-microsoft-365',
	'Mailgun'       => 'authenticate-email-mailgun',
	'SendGrid'      => 'authenticate-email-sendgrid',
);
foreach ( array( 'spf_record', 'dkim_record', 'dmarc_record' ) as $check ) {
	foreach ( $provider_guides as $provider => $slug ) {
		ecehc_assert_same( $slug, GuideLink::slug_for( $check, $provider ), "$check with $provider" );
	}
}

// Fallbacks: other provider or none.
$fallbacks = array(
	'spf_record'   => 'how-to-add-or-fix-an-spf-record',
	'dkim_record'  => 'how-to-set-up-dkim',
	'dmarc_record' => 'dmarc-for-beginners',
);
foreach ( $fallbacks as $check => $slug ) {
	foreach ( array( null, 'Brevo', 'Amazon SES', 'Postmark', '', 'google', 'Zoho Mail' ) as $provider ) {
		ecehc_assert_same( $slug, GuideLink::slug_for( $check, $provider ), "$check fallback for " . var_export( $provider, true ) );
	}
	ecehc_assert_same( $slug, GuideLink::slug_for( $check ), "$check fallback without argument" );
}

// No guide for any other check.
foreach ( array( 'smtp_configured', 'ip_blacklist', 'ptr_record', 'wc_email_setup', 'pending_orders', 'basic_email', 'unknown', '', null, 5 ) as $other ) {
	ecehc_assert_same( null, GuideLink::slug_for( $other, 'Google' ), 'no guide for ' . var_export( $other, true ) );
	ecehc_assert_same( '', GuideLink::html_for( $other, 'fail', 'Google' ), 'no markup for ' . var_export( $other, true ) );
}

// Every mapped slug is in the allow-list, every URL is exact.
ecehc_assert_same( 7, count( GuideLink::SLUGS ), 'seven slugs' );
$mapped = array_merge( array_values( GuideLink::PROVIDER_GUIDES ), array_values( GuideLink::CHECK_GUIDES ) );
foreach ( $mapped as $slug ) {
	ecehc_assert_true( in_array( $slug, GuideLink::SLUGS, true ), "$slug is in the allow-list" );
}
foreach ( GuideLink::SLUGS as $slug ) {
	ecehc_assert_true( in_array( $slug, $mapped, true ), "$slug is used by the map" );
	$url = GuideLink::url( $slug );
	ecehc_assert_same( 'https://outboxhealth.com/guides/' . $slug . '/?utm_source=plugin&utm_medium=admin&utm_campaign=fix-guide', $url, "exact url for $slug" );
	$parts = parse_url( $url );
	parse_str( $parts['query'], $query );
	ecehc_assert_same( array( 'utm_source', 'utm_medium', 'utm_campaign' ), array_keys( $query ), "only the three utm keys for $slug" );
	ecehc_assert_same( 'fix-guide', $query['utm_campaign'], 'utm_campaign' );
}
foreach ( array( 'spf-too-many-dns-lookups', 'woocommerce-emails-not-sending-or-going-to-spam', 'authenticate-email-by-mail-service', '', '../x', null ) as $bad ) {
	ecehc_assert_same( '', GuideLink::url( $bad ), 'unknown slug gives no url: ' . var_export( $bad, true ) );
	ecehc_assert_same( '', GuideLink::link_html( $bad ), 'unknown slug gives no markup' );
}

// Only fail and warning get a link.
ecehc_assert_true( '' !== GuideLink::html_for( 'spf_record', 'fail', null ), 'fail gets a link' );
ecehc_assert_true( '' !== GuideLink::html_for( 'spf_record', 'warning', null ), 'warning gets a link' );
ecehc_assert_same( '', GuideLink::html_for( 'spf_record', 'pass', null ), 'pass: no link' );
ecehc_assert_same( '', GuideLink::html_for( 'spf_record', 'unknown', null ), 'unknown: no link' );

// Markup.
$html = GuideLink::link_html( 'dmarc-for-beginners' );
ecehc_assert_true( false !== strpos( $html, 'rel="noopener noreferrer"' ), 'rel noopener noreferrer' );
ecehc_assert_true( false !== strpos( $html, 'target="_blank"' ), 'target _blank' );
ecehc_assert_true( false !== strpos( $html, 'href="https://outboxhealth.com/guides/dmarc-for-beginners/?utm_source=plugin&amp;utm_medium=admin&amp;utm_campaign=fix-guide"' ), 'href escaped (ampersands)' );
ecehc_assert_true( false === strpos( $html, '&utm_' ), 'no raw ampersand in attribute' );
ecehc_assert_true( false !== strpos( $html, 'Plain-language guide (outboxhealth.com)' ), 'link text names the destination' );
ecehc_assert_true( false !== strpos( $html, '(opens in a new tab)' ), 'screen reader text' );
ecehc_assert_true( 1 === substr_count( $html, '<a ' ), 'one link' );
ecehc_assert_true( false === stripos( $html, 'pro' ), 'no "Pro" in the markup' );
ecehc_assert_true( false === stripos( $html, 'free' ) && false === stripos( $html, 'learn more' ), 'no promotional wording' );

// Filter off switch.
ecehc_assert_true( GuideLink::is_enabled(), 'enabled by default' );
add_filter( 'ecehc_show_guide_links', '__return_false_ecehc_guide' );
function __return_false_ecehc_guide() {
	return false;
}
ecehc_assert_same( false, GuideLink::is_enabled(), 'filter false hides the links' );
ecehc_assert_same( '', GuideLink::html_for( 'spf_record', 'fail', 'Google' ), 'hidden: no markup' );
$GLOBALS['ecehc_test_hooks'] = array();
ecehc_assert_true( GuideLink::is_enabled(), 'enabled again once the filter is removed' );

// The Pro link filter does not control guide links.
add_filter( 'ecehc_show_pro_link', '__return_false_ecehc_guide' );
ecehc_assert_true( GuideLink::is_enabled(), 'ecehc_show_pro_link does not hide guide links' );
$GLOBALS['ecehc_test_hooks'] = array();
