<?php
/**
 * Looks the web server's IP address up on a few public DNS blacklists.
 *
 * Only lists whose operators allow free, keyless DNS queries from any site are
 * used, by default DroneBL ("free of charge for both commercial and
 * non-commercial purposes") and PSBL ("anybody is free to use"). Spamhaus is
 * deliberately NOT queried: its free mirrors are for low-volume non-commercial
 * use and refuse shared hosting and public resolvers. Barracuda requires
 * registering the querying resolver, SORBS has shut down, and the SpamCop,
 * UCEPROTECT terms for this kind of use could not be confirmed.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

use CodeSir\EmailHealthCheck\Support\Dns;
use CodeSir\EmailHealthCheck\Support\SmtpDetector;

defined( 'ABSPATH' ) || exit;

class BlacklistCheck implements CheckInterface {

	const TRANSIENT = 'ecehc_blacklist';

	public function get_id(): string {
		return 'ip_blacklist';
	}

	public function get_label(): string {
		return __( 'Server IP Blacklists', 'ecom-email-health-check' );
	}

	public static function clear_cache(): void {
		delete_transient( self::TRANSIENT );
	}

	/**
	 * Blacklist zones to query, zone => display name.
	 *
	 * @return array<string, string>
	 */
	private function zones(): array {
		/**
		 * Filters the DNS blacklist zones that are queried (zone => name).
		 * Only add lists whose terms allow queries from any site.
		 *
		 * @param array<string, string> $zones Zones.
		 */
		$zones = (array) apply_filters(
			'ecehc_dnsbl_zones',
			array(
				'dnsbl.dronebl.org' => 'DroneBL',
				'psbl.surriel.com'  => 'PSBL',
			)
		);

		$valid = array();
		foreach ( $zones as $zone => $name ) {
			if ( is_string( $zone ) && is_string( $name ) && preg_match( '/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', $zone ) ) {
				$valid[ $zone ] = $name;
			}
		}

		return $valid;
	}

	/**
	 * The IPv4 address mail leaves from, assumed to be the web server's address.
	 */
	private function server_ip(): string {
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

	public function run(): Result {
		if ( SmtpDetector::is_configured() ) {
			return Result::unknown( __( 'Skipped: your email is sent through an SMTP plugin or mail service, so your web server\'s own IP address is not what receivers see.', 'ecom-email-health-check' ) );
		}

		$ip = $this->server_ip();

		if ( '' === $ip && 'cli' === PHP_SAPI ) {
			return Result::unknown( __( 'Skipped on the command line, where the web server\'s IP address is not available. Set it with the ecehc_server_ip filter to check it here.', 'ecom-email-health-check' ) );
		}

		if ( '' === $ip ) {
			return Result::unknown( __( 'Your server\'s public IPv4 address could not be determined (it may be IPv6, private, or behind a proxy), so blacklists were not checked.', 'ecom-email-health-check' ) );
		}

		$cached = get_transient( self::TRANSIENT );
		if ( is_array( $cached ) && isset( $cached['ip'], $cached['zones'] ) && is_array( $cached['listed'] ?? null ) && is_array( $cached['failed'] ?? null ) && is_array( $cached['checked'] ?? null ) && $cached['ip'] === $ip && $cached['zones'] === md5( implode( ',', array_keys( $this->zones() ) ) ) ) {
			return $this->build_result( $ip, $cached['listed'], $cached['failed'], $cached['checked'] );
		}

		$reversed = implode( '.', array_reverse( explode( '.', $ip ) ) );
		$listed   = array();
		$failed   = array();
		$checked  = array();

		$resolver_down = false;

		foreach ( $this->zones() as $zone => $name ) {
			// If the resolver itself is failing, don't wait on more lookups.
			if ( $resolver_down ) {
				$failed[] = $name;
				continue;
			}

			$answers = Dns::a_records( $reversed . '.' . $zone );

			if ( null === $answers ) {
				$failed[]      = $name;
				$resolver_down = true;
				continue;
			}

			// 127.0.0.x means listed; any other answer (e.g. 127.255.255.x) means the query was refused, not that the IP is clean.
			$is_listed = false;
			foreach ( $answers as $answer ) {
				if ( 0 === strpos( $answer, '127.0.0.' ) ) {
					$is_listed = true;
					break;
				}
			}

			if ( $is_listed ) {
				$listed[]  = $name;
				$checked[] = $name;
			} elseif ( $answers ) {
				$failed[] = $name;
			} else {
				$checked[] = $name;
			}
		}

		// A lookup problem is retried sooner than a real answer.
		$zones = md5( implode( ',', array_keys( $this->zones() ) ) );

		set_transient( self::TRANSIENT, compact( 'ip', 'zones', 'listed', 'failed', 'checked' ), $failed ? HOUR_IN_SECONDS : 12 * HOUR_IN_SECONDS );

		return $this->build_result( $ip, $listed, $failed, $checked );
	}

	/**
	 * @param string[] $listed  Names of lists that list the IP.
	 * @param string[] $failed  Names of lists that could not be queried.
	 * @param string[] $checked Names of lists that answered.
	 */
	private function build_result( string $ip, array $listed, array $failed, array $checked ): Result {
		if ( $listed ) {
			return new Result(
				false,
				sprintf(
					/* translators: 1: server IP address, 2: list names */
					__( 'Your server IP address %1$s is listed on: %2$s. Mail sent straight from this server is likely to be rejected or sent to spam. Ask your host to resolve it, or send through an SMTP service instead.', 'ecom-email-health-check' ),
					$ip,
					implode( ', ', $listed )
				)
			);
		}

		if ( ! $checked ) {
			return Result::unknown( __( 'The DNS lookups to the blacklists failed, so your server IP address could not be checked. Try again later.', 'ecom-email-health-check' ) );
		}

		return new Result(
			true,
			sprintf(
				/* translators: 1: server IP address, 2: list names */
				__( 'Your server IP address %1$s is not listed on %2$s. Only these free lists are checked: major lists such as Spamhaus cannot be queried by a plugin under their terms, so a clean result here does not guarantee a clean reputation.', 'ecom-email-health-check' ),
				$ip,
				implode( ', ', $checked )
			)
		);
	}
}
