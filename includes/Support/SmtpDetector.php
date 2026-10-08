<?php
/**
 * Works out whether WordPress mail is likely handed to an SMTP / mail service
 * instead of the host's unauthenticated PHP mail, and which plugin does it.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class SmtpDetector {

	const TRANSIENT = 'ecehc_smtp_probe';

	/** How long the probe result is reused. It is also cleared whenever a plugin is (de)activated. */
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/** @var array{configured: bool, plugins: string[]}|null */
	private static $cache = null;

	/**
	 * Drop the cached probe whenever the active plugins change.
	 */
	public static function register(): void {
		add_action( 'activated_plugin', array( __CLASS__, 'clear_cache' ) );
		add_action( 'deactivated_plugin', array( __CLASS__, 'clear_cache' ) );
	}

	public static function clear_cache(): void {
		self::$cache = null;
		delete_transient( self::TRANSIENT );
	}

	/**
	 * Whether a mail service appears to be configured.
	 */
	public static function is_configured(): bool {
		return self::inspect()['configured'];
	}

	/**
	 * Names of the plugins that take over outgoing mail (set up SMTP or another
	 * mailer on phpmailer_init, or replace wp_mail()).
	 *
	 * @return string[]
	 */
	public static function plugin_names(): array {
		return self::inspect()['plugins'];
	}

	/**
	 * @return array{configured: bool, plugins: string[]}
	 */
	private static function inspect(): array {
		if ( null !== self::$cache ) {
			return self::$cache;
		}

		$files   = array();
		$wp_mail = self::wp_mail_file();

		if ( $wp_mail ) {
			$files[] = $wp_mail;
		}

		$probe = self::cached_probe();

		// Without a mailer to probe, or when a callback could not be judged, any hook counts, to avoid false alarms.
		$hooked = null === $probe || $probe['inconclusive']
			? false !== has_action( 'phpmailer_init' )
			: false;

		if ( $probe ) {
			$files = array_merge( $files, $probe['files'] );
		}

		$names = array();
		foreach ( $files as $file ) {
			$name = $file ? self::plugin_name_for_file( $file ) : null;
			if ( $name ) {
				$names[ $name ] = $name;
			}
		}

		self::$cache = array(
			'configured' => null !== MailProvider::detect()
				|| ! empty( $files )
				|| ( $probe && $probe['confirmed'] )
				|| $hooked
				|| false !== has_filter( 'pre_wp_mail' ),
			'plugins'    => array_values( $names ),
		);

		return self::$cache;
	}

	/**
	 * The probe result, reused from a transient so other plugins' callbacks are
	 * not run on every page view.
	 *
	 * @return array{confirmed: bool, files: string[], inconclusive: bool}|null
	 */
	private static function cached_probe(): ?array {
		$cached = get_transient( self::TRANSIENT );

		if ( is_array( $cached ) && isset( $cached['confirmed'], $cached['files'], $cached['inconclusive'] ) ) {
			return $cached;
		}

		$probe = self::probe_phpmailer_init();

		if ( null !== $probe ) {
			set_transient( self::TRANSIENT, $probe, self::CACHE_TTL );
		}

		return $probe;
	}

	/**
	 * Runs each phpmailer_init callback against a throwaway PHPMailer to see which
	 * ones switch the mailer away from PHP mail (SMTP, sendmail, ...). Many plugins
	 * hook phpmailer_init for unrelated reasons, so hooking alone proves nothing.
	 *
	 * 'confirmed' is true when a callback switched the mailer, 'files' lists the
	 * files of those callbacks, and 'inconclusive' is true when a callback threw
	 * and so could not be judged.
	 *
	 * @return array{confirmed: bool, files: string[], inconclusive: bool}|null Null when no PHPMailer could be created.
	 */
	private static function probe_phpmailer_init(): ?array {
		global $wp_filter;

		$probe = array(
			'confirmed'    => false,
			'files'        => array(),
			'inconclusive' => false,
		);

		if ( empty( $wp_filter['phpmailer_init']->callbacks ) ) {
			return $probe;
		}

		foreach ( $wp_filter['phpmailer_init']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				// WordPress could not call it either.
				if ( ! is_callable( $callback['function'] ) ) {
					continue;
				}

				$mailer = self::make_mailer();
				if ( null === $mailer ) {
					return null;
				}

				try {
					call_user_func_array( $callback['function'], array( &$mailer ) );
				} catch ( \Throwable $e ) {
					$probe['inconclusive'] = true;
					continue;
				}

				if ( is_object( $mailer ) && isset( $mailer->Mailer ) && 'mail' !== $mailer->Mailer ) {
					$probe['confirmed'] = true;
					$file               = self::callback_file( $callback['function'] );
					if ( $file ) {
						$probe['files'][] = $file;
					}
				}
			}
		}

		return $probe;
	}

	/**
	 * A fresh PHPMailer, as wp_mail() would create.
	 *
	 * @return object|null
	 */
	private static function make_mailer() {
		if ( ! class_exists( '\PHPMailer\PHPMailer\PHPMailer', false ) && file_exists( ABSPATH . WPINC . '/PHPMailer/PHPMailer.php' ) ) {
			require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
			require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
			require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
		}

		if ( class_exists( '\PHPMailer\PHPMailer\PHPMailer', false ) ) {
			return new \PHPMailer\PHPMailer\PHPMailer( true );
		}

		if ( ! class_exists( '\PHPMailer', false ) && file_exists( ABSPATH . WPINC . '/class-phpmailer.php' ) ) {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
		}

		return class_exists( '\PHPMailer', false ) ? new \PHPMailer( true ) : null;
	}

	/**
	 * File defining wp_mail() when a plugin replaces the pluggable function
	 * (SES, Mailgun API, etc.), or null when core's own version is in use.
	 */
	private static function wp_mail_file(): ?string {
		if ( ! function_exists( 'wp_mail' ) ) {
			return null;
		}

		try {
			$file = ( new \ReflectionFunction( 'wp_mail' ) )->getFileName();
		} catch ( \ReflectionException $e ) {
			return null;
		}

		if ( ! $file || wp_normalize_path( $file ) === wp_normalize_path( ABSPATH . WPINC . '/pluggable.php' ) ) {
			return null;
		}

		return $file;
	}

	/**
	 * The file a hook callback is defined in, or null if it cannot be found.
	 *
	 * @param mixed $callback Hook callback.
	 */
	private static function callback_file( $callback ): ?string {
		try {
			if ( $callback instanceof \Closure || ( is_string( $callback ) && false === strpos( $callback, '::' ) && function_exists( $callback ) ) ) {
				$reflection = new \ReflectionFunction( $callback );
			} elseif ( is_string( $callback ) && false !== strpos( $callback, '::' ) ) {
				$reflection = new \ReflectionMethod( $callback );
			} elseif ( is_array( $callback ) && 2 === count( $callback ) ) {
				$reflection = new \ReflectionMethod( $callback[0], $callback[1] );
			} elseif ( is_object( $callback ) ) {
				$reflection = new \ReflectionMethod( $callback, '__invoke' );
			} else {
				return null;
			}
		} catch ( \ReflectionException $e ) {
			return null;
		}

		$file = $reflection->getFileName();

		return $file ? $file : null;
	}

	/**
	 * Plugin name for a file inside the plugins or mu-plugins directory.
	 * Files outside them (themes, core) and this plugin itself are ignored.
	 */
	private static function plugin_name_for_file( string $file ): ?string {
		$file = wp_normalize_path( $file );

		if ( 0 === strpos( $file, wp_normalize_path( ECEHC_PLUGIN_PATH ) ) ) {
			return null;
		}

		$plugins_dir = self::dir_prefix( $file, WP_PLUGIN_DIR );
		$mu_dir      = self::dir_prefix( $file, WPMU_PLUGIN_DIR );

		if ( null !== $plugins_dir ) {
			$segment = explode( '/', substr( $file, strlen( $plugins_dir ) ) )[0];

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			foreach ( get_plugins() as $plugin_file => $data ) {
				$matches = false !== strpos( $plugin_file, '/' )
					? strtok( $plugin_file, '/' ) === $segment
					: $plugin_file === $segment;

				if ( $matches && ! empty( $data['Name'] ) ) {
					return $data['Name'];
				}
			}

			return $segment;
		}

		if ( null !== $mu_dir ) {
			$segment = explode( '/', substr( $file, strlen( $mu_dir ) ) )[0];

			if ( ! function_exists( 'get_mu_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$mu_plugins = get_mu_plugins();

			return ! empty( $mu_plugins[ $segment ]['Name'] ) ? $mu_plugins[ $segment ]['Name'] : $segment;
		}

		return null;
	}

	/**
	 * The directory prefix (with trailing slash) of $dir that $file sits under,
	 * trying the symlink-resolved path too, or null if the file is elsewhere.
	 */
	private static function dir_prefix( string $file, string $dir ): ?string {
		$candidates = array( wp_normalize_path( $dir ) );
		$real       = realpath( $dir );

		if ( $real ) {
			$candidates[] = wp_normalize_path( $real );
		}

		foreach ( $candidates as $candidate ) {
			$prefix = trailingslashit( $candidate );
			if ( 0 === strpos( $file, $prefix ) ) {
				return $prefix;
			}
		}

		return null;
	}
}
