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

	/**
	 * The domain of the effective From address, which is the domain receivers
	 * authenticate (SPF, DKIM, DMARC). Falls back to the site domain.
	 */
	public static function mail_domain(): string {
		$domain = strtolower( self::email_domain( MailSender::from_address() ) );

		return '' !== $domain ? $domain : self::site_domain();
	}

	/**
	 * Whether two domains are the same or one is a subdomain of the other.
	 */
	public static function are_related( string $a, string $b ): bool {
		$a = strtolower( $a );
		$b = strtolower( $b );

		return $a === $b
			|| substr( $a, -strlen( $b ) - 1 ) === '.' . $b
			|| substr( $b, -strlen( $a ) - 1 ) === '.' . $a;
	}

	/**
	 * Whether a domain is a public mailbox provider (Gmail, Yahoo, ...). Site
	 * owners cannot publish DNS records for these, so mail "from" them cannot
	 * be authenticated.
	 */
	public static function is_free_mailbox( string $domain ): bool {
		$free = array(
			'gmail.com',
			'googlemail.com',
			'yahoo.com',
			'outlook.com',
			'hotmail.com',
			'live.com',
			'msn.com',
			'icloud.com',
			'me.com',
			'aol.com',
			'proton.me',
			'protonmail.com',
			'gmx.com',
			'yandex.com',
		);

		/**
		 * Filters the public mailbox domains that cannot be authenticated by a site owner.
		 *
		 * @param string[] $free Domains.
		 */
		$free = (array) apply_filters( 'ecehc_free_mailbox_domains', $free );

		return in_array( strtolower( $domain ), $free, true );
	}

	/**
	 * Shown instead of DNS results when the From domain is a public mailbox provider.
	 */
	public static function free_mailbox_notice( string $domain ): string {
		return sprintf(
			/* translators: %s: domain such as gmail.com */
			__( 'Your emails are sent from an address at %s, a domain you cannot publish DNS records for, so there is nothing to check here. Use an address at your own domain instead.', 'ecom-email-health-check' ),
			$domain
		);
	}
}
