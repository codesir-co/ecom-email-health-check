<?php
/**
 * DNS lookup helpers.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class Dns {

	/** @var bool|null Cached result of the DNS sanity probe. */
	private static $lookups_work = null;

	/**
	 * TXT record strings for a host name.
	 *
	 * @param string $host Fully qualified host name.
	 * @return string[]|null Records (possibly empty), or null if DNS lookups are unavailable or failed.
	 */
	public static function txt_records( string $host ): ?array {
		if ( ! function_exists( 'dns_get_record' ) ) {
			return null;
		}

		$records = @dns_get_record( $host, DNS_TXT ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( false === $records ) {
			// PHP also returns false for a name that doesn't exist (NXDOMAIN), which is a
			// normal "no record" answer. Only treat it as unavailable if DNS fails for the
			// site's own domain as well.
			return ( Domain::site_domain() !== $host && self::lookups_work() ) ? array() : null;
		}

		$txt = array();
		foreach ( $records as $record ) {
			if ( isset( $record['txt'] ) ) {
				$txt[] = trim( $record['txt'] );
			}
		}

		return $txt;
	}

	/**
	 * IPv4 addresses a host name resolves to (used for DNSBL answers).
	 *
	 * @param string $host Fully qualified host name.
	 * @return string[]|null Addresses (empty when the name does not exist), or null if DNS lookups are unavailable or failed.
	 */
	public static function a_records( string $host ): ?array {
		if ( ! function_exists( 'dns_get_record' ) ) {
			return null;
		}

		$records = @dns_get_record( $host, DNS_A ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( false === $records ) {
			// Same ambiguity as txt_records(): false also means the name does not exist.
			return self::lookups_work() ? array() : null;
		}

		$ips = array();
		foreach ( $records as $record ) {
			if ( isset( $record['ip'] ) ) {
				$ips[] = (string) $record['ip'];
			}
		}

		return $ips;
	}

	/**
	 * Whether DNS lookups work at all, probed once per request against the site's own domain.
	 */
	private static function lookups_work(): bool {
		if ( null === self::$lookups_work ) {
			self::$lookups_work = false !== @dns_get_record( Domain::site_domain(), DNS_TXT ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		return self::$lookups_work;
	}
}
