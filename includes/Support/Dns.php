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
	 * Host names the PTR (reverse DNS) record of an IPv4 address points to.
	 *
	 * @param string $ip IPv4 address.
	 * @return string[]|null Host names (empty when there is no PTR record), or null if DNS lookups are unavailable, failed or the address is invalid.
	 */
	public static function ptr_records( string $ip ): ?array {
		if ( ! function_exists( 'dns_get_record' ) || ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return null;
		}

		$records = @dns_get_record( implode( '.', array_reverse( explode( '.', $ip ) ) ) . '.in-addr.arpa', DNS_PTR ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( false === $records ) {
			// Same ambiguity as txt_records(): false also means the name does not exist.
			return self::lookups_work() ? array() : null;
		}

		$hosts = array();
		foreach ( $records as $record ) {
			$host = isset( $record['target'] ) ? strtolower( rtrim( trim( (string) $record['target'] ), '.' ) ) : '';

			// PTR data is controlled by whoever runs the reverse zone: keep only plausible host names.
			if ( '' !== $host && strlen( $host ) <= 253 && preg_match( '/^[a-z0-9_]([a-z0-9_.-]*[a-z0-9_])?$/', $host ) ) {
				$hosts[] = $host;
			}
		}

		return $hosts;
	}

	/**
	 * Name server host names of a domain (its NS records).
	 *
	 * @param string $host Domain name, for example example.com.
	 * @return string[]|null Up to ten host names (empty when the name has no NS records), or null if DNS lookups are unavailable, failed or the name is invalid.
	 */
	public static function ns_records( string $host ): ?array {
		$host = strtolower( rtrim( trim( $host ), '.' ) );

		if ( ! function_exists( 'dns_get_record' ) || '' === $host || strlen( $host ) > 253 || ! preg_match( '/^[a-z0-9_]([a-z0-9_.-]*[a-z0-9_])?$/', $host ) ) {
			return null;
		}

		$records = @dns_get_record( $host, DNS_NS ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( false === $records ) {
			// Same ambiguity as txt_records(): false also means the name does not exist.
			return self::lookups_work() ? array() : null;
		}

		$names = array();
		foreach ( $records as $record ) {
			$name = isset( $record['target'] ) ? strtolower( rtrim( trim( (string) $record['target'] ), '.' ) ) : '';

			// NS data is controlled by the domain's owner: keep only plausible host names, and few of them.
			if ( '' !== $name && strlen( $name ) <= 253 && preg_match( '/^[a-z0-9_]([a-z0-9_.-]*[a-z0-9_])?$/', $name ) ) {
				$names[] = $name;
			}
			if ( count( $names ) >= 10 ) {
				break;
			}
		}

		return $names;
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
