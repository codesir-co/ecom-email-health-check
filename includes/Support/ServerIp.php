<?php
/**
 * The server's public IPv4 address, as far as it can be told from the request.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class ServerIp {

	/**
	 * The IPv4 address mail leaves from, assumed to be the web server's address.
	 *
	 * @return string Address, or an empty string if none was found.
	 */
	public static function detect(): string {
		$ip = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '';

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			$ip = '';
		}

		/**
		 * Filters the IPv4 address that is looked up, for servers whose outgoing
		 * mail address differs from the web server's.
		 *
		 * @param string $ip Address, empty if none was found.
		 */
		$ip = (string) apply_filters( 'ecehc_server_ip', $ip );

		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ? $ip : '';
	}
}
