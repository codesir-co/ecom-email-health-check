<?php
/**
 * Domain helpers.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class Domain {

	/**
	 * The site's host name without a leading "www." (only a leading prefix is stripped).
	 */
	public static function site_domain(): string {
		return preg_replace( '/^www\./i', '', (string) wp_parse_url( get_home_url(), PHP_URL_HOST ) );
	}

	/**
	 * The domain part of an email address.
	 */
	public static function email_domain( string $email ): string {
		return (string) substr( (string) strrchr( $email, '@' ), 1 );
	}
}
