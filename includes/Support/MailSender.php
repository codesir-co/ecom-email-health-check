<?php
/**
 * Resolves the address WordPress actually sends mail from.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class MailSender {

	/**
	 * The effective "From" address: WordPress default (wordpress@host),
	 * overridden by WooCommerce's From setting when set, then passed through
	 * the `wp_mail_from` filter exactly as wp_mail() does.
	 */
	public static function from_address(): string {
		$sitename = (string) wp_parse_url( network_home_url(), PHP_URL_HOST );
		if ( 'www.' === substr( $sitename, 0, 4 ) ) {
			$sitename = substr( $sitename, 4 );
		}
		$from = 'wordpress@' . $sitename;

		$woocommerce_from = get_option( 'woocommerce_email_from_address' );
		if ( is_string( $woocommerce_from ) && is_email( $woocommerce_from ) ) {
			$from = $woocommerce_from;
		}

		return (string) apply_filters( 'wp_mail_from', $from ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	}
}
