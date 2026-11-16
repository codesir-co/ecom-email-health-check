<?php
/**
 * Checks that the server's IP address has forward-confirmed reverse DNS (PTR).
 *
 * Gmail and Yahoo expect the sending IP address to have a PTR record whose
 * hostname resolves back to the same address. That can only be checked from
 * here when this server itself sends the mail.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

use CodeSir\EmailHealthCheck\Support\Dns;
use CodeSir\EmailHealthCheck\Support\ServerIp;
use CodeSir\EmailHealthCheck\Support\SmtpDetector;

defined( 'ABSPATH' ) || exit;

class PtrCheck implements CheckInterface {

	/** Most PTR hostnames that are looked up forward. */
	const MAX_HOSTS = 3;

	public function get_id(): string {
		return 'ptr_record';
	}

	public function get_label(): string {
		return __( 'Reverse DNS (PTR)', 'ecom-email-health-check' );
	}

	public function run(): Result {
		if ( SmtpDetector::is_configured() ) {
			return Result::unknown( __( 'Skipped: your email is sent through an SMTP plugin or mail service, so your web server\'s own IP address is not what receivers see.', 'ecom-email-health-check' ) );
		}

		$ip = ServerIp::detect();

		if ( '' === $ip && 'cli' === PHP_SAPI ) {
			return Result::unknown( __( 'Skipped on the command line, where the web server\'s IP address is not available. Set it with the ecehc_server_ip filter to check it here.', 'ecom-email-health-check' ) );
		}

		if ( '' === $ip ) {
			return Result::unknown( __( 'Your server\'s public IPv4 address could not be determined (it may be IPv6, private, or behind a proxy), so reverse DNS was not checked.', 'ecom-email-health-check' ) );
		}

		$hosts = Dns::ptr_records( $ip );

		if ( null === $hosts ) {
			return Result::unknown( __( 'The DNS lookup failed, so the reverse DNS of your server\'s IP address could not be checked. Try again later.', 'ecom-email-health-check' ) );
		}

		$forward = array();
		foreach ( array_slice( $hosts, 0, self::MAX_HOSTS ) as $host ) {
			$forward[ $host ] = Dns::a_records( $host );
		}

		return self::evaluate( $ip, $hosts, $forward );
	}

	/**
	 * Decides the result from DNS answers already collected.
	 *
	 * @param string                       $ip                  Server IPv4 address.
	 * @param string[]                     $ptr_hosts           Host names of the PTR record(s).
	 * @param array<string, string[]|null> $forward_ips_by_host IPv4 addresses each host resolves to (null if that lookup failed).
	 */
	public static function evaluate( string $ip, array $ptr_hosts, array $forward_ips_by_host ): Result {
		if ( ! $ptr_hosts ) {
			return new Result(
				false,
				sprintf(
					/* translators: %s: server IP address */
					__( 'Your server IP address %s has no reverse DNS (PTR) record. Gmail and Yahoo expect one when mail is sent straight from a server. Ask your host to set it, or send through an SMTP service instead.', 'ecom-email-health-check' ),
					$ip
				)
			);
		}

		$confirmed  = null;
		$generic    = null;
		$unresolved = null;

		foreach ( $ptr_hosts as $host ) {
			$ips = $forward_ips_by_host[ $host ] ?? null;

			if ( null === $ips ) {
				continue;
			}

			if ( ! in_array( $ip, $ips, true ) ) {
				$unresolved = null === $unresolved ? $host : $unresolved;
				continue;
			}

			if ( self::is_generic( $host, $ip ) ) {
				$generic = null === $generic ? $host : $generic;
				continue;
			}

			$confirmed = $host;
			break;
		}

		if ( null !== $confirmed ) {
			return new Result(
				true,
				sprintf(
					/* translators: 1: server IP address, 2: host name */
					__( 'Your server IP address %1$s has reverse DNS (%2$s), and that name resolves back to the same address.', 'ecom-email-health-check' ),
					$ip,
					$confirmed
				)
			);
		}

		if ( null !== $generic ) {
			return Result::warning(
				sprintf(
					/* translators: 1: server IP address, 2: host name */
					__( 'The reverse DNS of your server IP address %1$s is %2$s, a generic name that contains the IP address. Gmail and Yahoo expect a meaningful name that reflects your domain. Ask your host to change it, or send through an SMTP service instead.', 'ecom-email-health-check' ),
					$ip,
					$generic
				)
			);
		}

		if ( null !== $unresolved ) {
			return Result::warning(
				sprintf(
					/* translators: 1: server IP address, 2: host name */
					__( 'The reverse DNS of your server IP address %1$s is %2$s, but that name does not resolve back to the same address. Gmail and Yahoo expect both to match. Ask your host to fix it, or send through an SMTP service instead.', 'ecom-email-health-check' ),
					$ip,
					$unresolved
				)
			);
		}

		return Result::unknown( __( 'The DNS lookup failed, so the reverse DNS of your server\'s IP address could not be checked. Try again later.', 'ecom-email-health-check' ) );
	}

	/**
	 * Whether a host name embeds the IP address (dotted or dashed, either order), such as 203-0-113-7.example.net.
	 */
	public static function is_generic( string $host, string $ip ): bool {
		$octets = explode( '.', $ip );
		if ( 4 !== count( $octets ) ) {
			return false;
		}

		$host = strtolower( $host );

		foreach ( array( $octets, array_reverse( $octets ) ) as $order ) {
			foreach ( array( '.', '-' ) as $glue ) {
				if ( preg_match( '/(?<![0-9])' . preg_quote( implode( $glue, $order ), '/' ) . '(?![0-9])/', $host ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
