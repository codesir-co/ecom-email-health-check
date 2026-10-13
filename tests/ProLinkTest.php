<?php
/**
 * ProLink: waiting list URL, enabled decision, markup and "own screen only" logic.
 */

use CodeSir\EmailHealthCheck\Admin\ProLink;

$url   = ProLink::url();
$parts = parse_url( $url );
parse_str( $parts['query'], $query );

ecehc_assert_same( 'https', $parts['scheme'], 'url is https' );
ecehc_assert_same( 'outboxhealth.com', $parts['host'], 'url host' );
ecehc_assert_same( '/waitlist/', $parts['path'], 'url path' );
ecehc_assert_same( 'plugin', $query['utm_source'], 'utm_source' );
ecehc_assert_same( 'admin', $query['utm_medium'], 'utm_medium' );
ecehc_assert_same( 'waitlist', $query['utm_campaign'], 'utm_campaign' );
ecehc_assert_same( 3, count( $query ), 'exactly three query params' );

// Enabled by default; the filter can hide it.
ecehc_assert_true( ProLink::is_enabled(), 'enabled by default' );
add_filter( 'ecehc_show_pro_link', '__return_false_ecehc' );
function __return_false_ecehc() {
	return false;
}
ecehc_assert_same( false, ProLink::is_enabled(), 'filter false hides the link' );
ecehc_assert_same( 'Thanks', ProLink::filter_footer( 'Thanks', ProLink::SCREEN_ID ), 'hidden: footer unchanged' );
ecehc_assert_same( array( 'a' ), ProLink::filter_row_meta( array( 'a' ), 'p/p.php', 'p/p.php' ), 'hidden: row meta unchanged' );
$GLOBALS['ecehc_test_hooks'] = array();
ecehc_assert_true( ProLink::is_enabled(), 'enabled again once the filter is removed' );

// Markup.
$html = ProLink::link_html();
ecehc_assert_true( false !== strpos( $html, 'rel="noopener noreferrer"' ), 'rel noopener noreferrer' );
ecehc_assert_true( false !== strpos( $html, 'target="_blank"' ), 'target _blank' );
ecehc_assert_true( false !== strpos( $html, 'href="https://outboxhealth.com/waitlist/?utm_source=plugin&amp;utm_medium=admin&amp;utm_campaign=waitlist"' ), 'href escaped (ampersands)' );
ecehc_assert_true( false !== strpos( $html, '>Follow the Pro plan</a>' ), 'link text' );
ecehc_assert_true( false !== strpos( $html, 'title="A Pro version is being planned. Free to ignore."' ), 'honest title' );
ecehc_assert_true( false === strpos( $html, '&utm_' ), 'no raw ampersand in attribute' );

// Footer: only on our screen, existing text kept.
ecehc_assert_same( 'toplevel_page_ecom-dashboard', ProLink::SCREEN_ID, 'screen id' );
ecehc_assert_true( ProLink::is_our_screen( 'toplevel_page_ecom-dashboard' ), 'our screen' );
foreach ( array( 'dashboard', 'plugins', 'woocommerce_page_wc-settings', 'toplevel_page_other', '', null, false ) as $other ) {
	ecehc_assert_same( 'Thanks', ProLink::filter_footer( 'Thanks', $other ), 'footer untouched on ' . var_export( $other, true ) );
}
$footer = ProLink::filter_footer( 'Thanks', 'toplevel_page_ecom-dashboard' );
ecehc_assert_true( 0 === strpos( $footer, 'Thanks' ), 'existing footer text kept' );
ecehc_assert_true( false !== strpos( $footer, 'Follow the Pro plan' ), 'footer link appended' );
ecehc_assert_same( ProLink::link_html(), ProLink::filter_footer( '', 'toplevel_page_ecom-dashboard' ), 'empty footer: link only' );

// Plugin row meta: only our own row.
$base = 'ecom-email-health-check/ecom-email-health-check.php';
$meta = ProLink::filter_row_meta( array( 'By CodeSir' ), $base, $base );
ecehc_assert_same( 2, count( $meta ), 'own row gets one link' );
ecehc_assert_same( 'By CodeSir', $meta[0], 'existing meta kept' );
ecehc_assert_same( array( 'x' ), ProLink::filter_row_meta( array( 'x' ), 'other/other.php', $base ), 'other plugin row untouched' );
ecehc_assert_same( array( 'x' ), ProLink::filter_row_meta( array( 'x' ), '', '' ), 'empty basename untouched' );
