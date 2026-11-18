<?php
/**
 * Detects the web host from constants and classes the host's own mu-plugins define,
 * with the reverse DNS (PTR) name of the server's IP address as a weaker fallback.
 *
 * Nothing here sends data anywhere: the PTR lookup is the same one the reverse DNS
 * check makes, and its answer is cached for a few hours.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class HostDetector {

	const TRANSIENT = 'ecehc_host_ptr';
	const CACHE_TTL = 6 * HOUR_IN_SECONDS;

	/**
	 * Known hosts: display name, constants and classes that give the host away
	 * (high confidence) and domain suffixes of the server's reverse DNS name
	 * (medium confidence).
	 *
	 * Constants are the ones the hosts' own platform plugins define. Not verified against
	 * a live account of every host, so a wrong guess shows nothing rather than something
	 * dangerous: the result is only used to pick advice text.
	 * - SiteGround and Cloudways have no signal here that is both documented and safe
	 *   (a SiteGround plugin can be installed on any host), so they are matched by
	 *   reverse DNS only, and that can miss them.
	 *
	 * @return array<string, array{name: string, constants: string[], classes: string[], suffixes: string[]}>
	 */
	private static function hosts(): array {
		return array(
			'wp-engine'     => array(
				'name'      => 'WP Engine',
				'constants' => array( 'WPE_APIKEY' ),
				'classes'   => array( 'WpeCommon' ),
				'suffixes'  => array( 'wpengine.com', 'wpenginepowered.com' ),
			),
			'kinsta'        => array(
				'name'      => 'Kinsta',
				'constants' => array( 'KINSTAMU_VERSION', 'KINSTA_CACHE_ZONE' ),
				'classes'   => array(),
				'suffixes'  => array( 'kinsta.cloud', 'kinsta.com' ),
			),
			'siteground'    => array(
				'name'      => 'SiteGround',
				'constants' => array(),
				'classes'   => array(),
				'suffixes'  => array( 'siteground.com', 'siteground.biz', 'siteground.eu', 'siteground.net' ),
			),
			'pantheon'      => array(
				'name'      => 'Pantheon',
				'constants' => array( 'PANTHEON_ENVIRONMENT' ),
				'classes'   => array(),
				'suffixes'  => array( 'pantheonsite.io', 'getpantheon.com' ),
			),
			'flywheel'      => array(
				'name'      => 'Flywheel',
				'constants' => array( 'FLYWHEEL_CONFIG_DIR' ),
				'classes'   => array(),
				'suffixes'  => array( 'getflywheel.com', 'flywheelsites.com' ),
			),
			'cloudways'     => array(
				'name'      => 'Cloudways',
				'constants' => array(),
				'classes'   => array(),
				'suffixes'  => array( 'cloudways.com', 'cloudwaysapps.com' ),
			),
			'godaddy'       => array(
				'name'      => 'GoDaddy',
				'constants' => array( 'GD_SYSTEM_PLUGIN_DIR' ),
				'classes'   => array(),
				'suffixes'  => array( 'secureserver.net', 'godaddy.com' ),
			),
			'wordpress-com' => array(
				'name'      => 'WordPress.com',
				'constants' => array( 'IS_ATOMIC', 'IS_WPCOM' ),
				'classes'   => array(),
				'suffixes'  => array( 'wordpress.com', 'automattic.com' ),
			),
		);
	}

	/**
	 * The detected host, or null when none is recognised.
	 *
	 * @return array{slug: string, name: string, confidence: string}|null
	 */
	public static function detect(): ?array {
		$host = self::from_environment(
			static function ( string $constant ): bool {
				return defined( $constant ) && constant( $constant );
			},
			static function ( string $class_name ): bool {
				return class_exists( $class_name, false );
			}
		);

		if ( null === $host ) {
			$host = self::from_hostname( self::server_hostname() );
		}

		/**
		 * Filters the detected hosting provider, to correct or add one.
		 *
		 * @param array{slug: string, name: string, confidence: string}|null $host Detected host, or null.
		 *        A returned array needs a non-empty string slug and name; anything else is ignored
		 *        and treated as "no host". Host-specific advice exists only for the built-in slugs.
		 */
		$filtered = apply_filters( 'ecehc_detected_host', $host );

		return self::validate( $filtered );
	}

	/**
	 * Host from constants and classes.
	 *
	 * @param callable $has_constant Receives a constant name, returns whether it is defined and truthy.
	 * @param callable $has_class    Receives a class name, returns whether it is loaded.
	 * @return array{slug: string, name: string, confidence: string}|null
	 */
	public static function from_environment( callable $has_constant, callable $has_class ): ?array {
		foreach ( self::hosts() as $slug => $host ) {
			foreach ( $host['constants'] as $constant ) {
				if ( $has_constant( $constant ) ) {
					return array( 'slug' => $slug, 'name' => $host['name'], 'confidence' => 'high' );
				}
			}
			foreach ( $host['classes'] as $class_name ) {
				if ( $has_class( $class_name ) ) {
					return array( 'slug' => $slug, 'name' => $host['name'], 'confidence' => 'high' );
				}
			}
		}

		return null;
	}

	/**
	 * Host from a reverse DNS name: the name must equal a known domain or end in a dot plus it,
	 * so "evilsiteground.net" does not match "siteground.net".
	 *
	 * @param string $hostname Reverse DNS host name, may be empty.
	 * @return array{slug: string, name: string, confidence: string}|null
	 */
	public static function from_hostname( string $hostname ): ?array {
		$hostname = strtolower( rtrim( trim( $hostname ), '.' ) );

		if ( '' === $hostname ) {
			return null;
		}

		foreach ( self::hosts() as $slug => $host ) {
			foreach ( $host['suffixes'] as $suffix ) {
				$length = strlen( $suffix );
				if ( $hostname === $suffix || ( strlen( $hostname ) > $length && '.' . $suffix === substr( $hostname, -( $length + 1 ) ) ) ) {
					return array( 'slug' => $slug, 'name' => $host['name'], 'confidence' => 'medium' );
				}
			}
		}

		return null;
	}

	/**
	 * Accepts only null or an array with non-empty string slug and name (what a filter returned).
	 *
	 * @param mixed $value Filtered value.
	 * @return array{slug: string, name: string, confidence: string}|null
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

		$confidence = isset( $value['confidence'] ) && in_array( $value['confidence'], array( 'high', 'medium' ), true ) ? $value['confidence'] : 'medium';

		return array( 'slug' => $slug, 'name' => $name, 'confidence' => $confidence );
	}

	/**
	 * First reverse DNS name of the server's IP address, cached. Empty when the address is
	 * unknown, has no PTR record or the lookup failed (failures are not cached).
	 */
	private static function server_hostname(): string {
		$ip = ServerIp::detect();

		if ( '' === $ip ) {
			return '';
		}

		$cached = get_transient( self::TRANSIENT );
		if ( is_array( $cached ) && isset( $cached['ip'], $cached['host'] ) && $ip === $cached['ip'] && is_string( $cached['host'] ) ) {
			return $cached['host'];
		}

		$hosts = Dns::ptr_records( $ip );

		if ( null === $hosts ) {
			return '';
		}

		$host = $hosts ? (string) reset( $hosts ) : '';
		set_transient( self::TRANSIENT, array( 'ip' => $ip, 'host' => $host ), self::CACHE_TTL );

		return $host;
	}

	/**
	 * Forgets the cached reverse DNS name.
	 */
	public static function clear_cache(): void {
		delete_transient( self::TRANSIENT );
	}
}
