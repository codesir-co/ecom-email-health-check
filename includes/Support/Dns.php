<?php
/**
 * DNS lookup helpers.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class Dns {

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
			return null;
		}

		$txt = array();
		foreach ( $records as $record ) {
			if ( isset( $record['txt'] ) ) {
				$txt[] = trim( $record['txt'] );
			}
		}

		return $txt;
	}
}
