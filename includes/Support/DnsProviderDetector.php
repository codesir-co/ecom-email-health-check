<?php
/**
 * Detects the DNS provider (where the domain's SPF, DKIM and DMARC records are edited)
 * from the domain's NS records.
 *
 * The NS query is an ordinary DNS query made through the server's resolver; nothing is
 * sent to us or to a third-party service. The answer is cached for a few hours.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class DnsProviderDetector {

	const TRANSIENT = 'ecehc_dns_provider';
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;
	const FAILURE_TTL = 10 * MINUTE_IN_SECONDS;

	/** Name servers kept in the cache, and labels walked up at most. */
	const MAX_NAME_SERVERS = 4;
	const MAX_LABELS = 6;

	/**
	 * Known DNS providers: display name and domain suffixes of their name servers.
	 *
	 * Only suffixes believed to be reliable are listed; a wrong or missing entry shows nothing
	 * rather than something misleading. Not verified against a live account of every provider.
	 * - Google Domains name servers (googledomains.com) are also used by Google Cloud DNS, so they
	 *   are not matched; Squarespace Domains uses squarespacedns.com.
	 * - Route 53 uses names like ns-123.awsdns-45.com (the number and TLD vary), so it is matched
	 *   with a pattern in addition to suffixes.
	 *
	 * @return array<string, array{name: string, suffixes: string[], pattern?: string}>
	 */
	private static function providers(): array {
		return array(
			'cloudflare' => array(
				'name'     => 'Cloudflare',
				'suffixes' => array( 'ns.cloudflare.com' ),
			),
			'godaddy' => array(
				'name'     => 'GoDaddy',
				'suffixes' => array( 'domaincontrol.com' ),
			),
			'namecheap' => array(
				'name'     => 'Namecheap',
				'suffixes' => array( 'registrar-servers.com' ),
			),
			'squarespace' => array(
				'name'     => 'Squarespace',
				'suffixes' => array( 'squarespacedns.com' ),
			),
			'route-53' => array(
				'name'     => 'Amazon Route 53',
				'suffixes' => array(),
				'pattern'  => '/(^|\.)awsdns-\d+\.(com|net|org|co\.uk)$/',
			),
			'wix' => array(
				'name'     => 'Wix',
				'suffixes' => array( 'wixdns.net' ),
			),
			'wordpress-com' => array(
				'name'     => 'WordPress.com',
				'suffixes' => array( 'wordpress.com' ),
			),
			'hostinger' => array(
				'name'     => 'Hostinger',
				'suffixes' => array( 'dns-parking.com' ),
			),
			'bluehost' => array(
				'name'     => 'Bluehost',
				'suffixes' => array( 'bluehost.com' ),
			),
			'digitalocean' => array(
				'name'     => 'DigitalOcean',
				'suffixes' => array( 'digitalocean.com' ),
			),
		);
	}

	/**
	 * The DNS provider of the domain mail is sent from, or null when none is recognised.
	 *
	 * @return array{slug: string, name: string}|null
	 */
	public static function detect(): ?array {
		return self::detect_for(
			Domain::mail_domain(),
			static function ( string $host ): ?array {
				return Dns::ns_records( $host );
			}
		);
	}

	/**
	 * Provider for a given domain, with an injectable NS lookup.
	 *
	 * @param string   $domain Domain where records are edited.
	 * @param callable $lookup Receives a host name, returns name server names, an empty array (none) or null (failed).
	 * @return array{slug: string, name: string}|null
	 */
	public static function detect_for( string $domain, callable $lookup ): ?array {
		$domain = strtolower( rtrim( trim( $domain ), '.' ) );
		$match  = null;

		// A public mailbox domain (gmail.com) cannot be edited by the site owner: no lookup.
		if ( '' !== $domain && ! Domain::is_free_mailbox( $domain ) ) {
			$match = self::from_name_servers( self::name_servers( $domain, $lookup ) );
		}

		/**
		 * Filters the detected DNS provider, to correct or add one.
		 *
		 * @param array{slug: string, name: string}|null $provider Detected provider, or null.
		 *        A returned array needs a non-empty string slug and name; anything else is ignored
		 *        and treated as "no provider". Provider-specific notes exist only for the built-in slugs.
		 * @param string                                  $domain   The domain whose records are edited.
		 */
		return self::validate( apply_filters( 'ecehc_detected_dns_provider', $match, $domain ) );
	}

	/**
	 * Provider from name server host names: a name must equal a known domain or end in a dot plus it,
	 * so "notcloudflare.com" does not match "cloudflare.com".
	 *
	 * @param string[] $names Name server host names.
	 * @return array{slug: string, name: string}|null
	 */
	public static function from_name_servers( array $names ): ?array {
		foreach ( $names as $name ) {
			$name = strtolower( rtrim( trim( (string) $name ), '.' ) );

			if ( '' === $name ) {
				continue;
			}

			foreach ( self::providers() as $slug => $provider ) {
				foreach ( $provider['suffixes'] as $suffix ) {
					$length = strlen( $suffix );
					if ( $name === $suffix || '.' . $suffix === substr( $name, -( $length + 1 ) ) ) {
						return array(
							'slug' => $slug,
							'name' => $provider['name'],
						);
					}
				}
				if ( isset( $provider['pattern'] ) && preg_match( $provider['pattern'], $name ) ) {
					return array(
						'slug' => $slug,
						'name' => $provider['name'],
					);
				}
			}
		}

		return null;
	}

	/**
	 * Domains to ask for NS records, most specific first: the domain, then each parent, never a bare
	 * TLD. Name servers usually exist only at the registered domain, so a subdomain such as
	 * mail.example.com falls back to example.com. A small list of common two-part public suffixes
	 * (co.uk and similar) is also not asked; this is not a full public suffix list, so an unlisted
	 * one costs one extra harmless query.
	 *
	 * @param string $domain Lower-case domain.
	 * @return string[]
	 */
	public static function candidates( string $domain ): array {
		$suffixes = array( 'co.uk', 'org.uk', 'me.uk', 'ac.uk', 'com.au', 'net.au', 'org.au', 'co.nz', 'co.za', 'com.br', 'co.in', 'co.jp', 'com.mx', 'com.tr', 'com.cn', 'com.sg', 'com.hk' );
		$labels   = explode( '.', $domain );
		$found    = array();

		while ( count( $labels ) >= 2 && count( $found ) < self::MAX_LABELS ) {
			$candidate = implode( '.', $labels );
			if ( ! in_array( $candidate, $suffixes, true ) ) {
				$found[] = $candidate;
			}
			array_shift( $labels );
		}

		return $found;
	}

	/**
	 * Name servers of a domain, cached. Walks up to the parent domains until one has NS records.
	 * Empty when there are none. A failed lookup is also cached, but only for ten minutes, so a
	 * slow or broken resolver is not retried on every render. Re-check clears the cache.
	 *
	 * @param string   $domain Lower-case domain.
	 * @param callable $lookup Receives a host name, returns names, an empty array or null (failed).
	 * @return string[]
	 */
	public static function name_servers( string $domain, callable $lookup ): array {
		$cached = get_transient( self::TRANSIENT );
		if ( is_array( $cached ) && isset( $cached['domain'], $cached['ns'] ) && $domain === $cached['domain'] && is_array( $cached['ns'] ) ) {
			return array_values( array_filter( $cached['ns'], 'is_string' ) );
		}

		$names = array();

		foreach ( self::candidates( $domain ) as $candidate ) {
			$found = $lookup( $candidate );

			if ( null === $found ) {
				set_transient(
					self::TRANSIENT,
					array(
						'domain' => $domain,
						'ns'     => array(),
					),
					self::FAILURE_TTL
				);
				return array();
			}

			if ( $found ) {
				$names = array_slice( array_values( array_map( 'strval', $found ) ), 0, self::MAX_NAME_SERVERS );
				break;
			}
		}

		set_transient(
			self::TRANSIENT,
			array(
				'domain' => $domain,
				'ns'     => $names,
			),
			self::CACHE_TTL
		);

		return $names;
	}

	/**
	 * Accepts only null or an array with non-empty string slug and name (what a filter returned).
	 *
	 * @param mixed $value Filtered value.
	 * @return array{slug: string, name: string}|null
	 */
	public static function validate( $value ): ?array {
		if ( ! is_array( $value ) || ! isset( $value['slug'], $value['name'] ) || ! is_string( $value['slug'] ) || ! is_string( $value['name'] ) ) {
			return null;
		}

		$slug = trim( $value['slug'] );
		$name = trim( $value['name'] );

		if ( '' === $slug || '' === $name ) {
			return null;
		}

		return array(
			'slug' => $slug,
			'name' => $name,
		);
	}

	/**
	 * Forgets the cached name servers.
	 */
	public static function clear_cache(): void {
		delete_transient( self::TRANSIENT );
	}
}
