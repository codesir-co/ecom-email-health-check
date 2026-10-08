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

	/** @var array{configured: bool, plugins: string[]}|null */
	private static $cache = null;

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

		$files = array();

		if ( self::wp_mail_is_overridden() ) {
			$files[] = ( new \ReflectionFunction( 'wp_mail' ) )->getFileName();
		}

		$hook_files = self::configuring_callback_files();

		// Without a mailer to probe, any phpmailer_init hook counts, to avoid false alarms.
		$hooked = null === $hook_files ? false !== has_action( 'phpmailer_init' ) : ! empty( $hook_files );

		$files = array_merge( $files, (array) $hook_files );

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
				|| $hooked
				|| false !== has_filter( 'pre_wp_mail' ),
			'plugins'    => array_values( $names ),
		);

		return self::$cache;
	}

	/**
	 * Files of the phpmailer_init callbacks that switch the mailer away from
	 * PHP mail (SMTP, sendmail, ...) when run against a throwaway PHPMailer.
	 * Many plugins hook phpmailer_init for unrelated reasons, so hooking alone
	 * does not mean SMTP is configured.
	 *
	 * @return string[]|null Null when no PHPMailer instance could be created.
	 */
	private static function configuring_callback_files(): ?array {
		global $wp_filter;

		if ( empty( $wp_filter['phpmailer_init']->callbacks ) ) {
			return array();
		}

		$files = array();

		foreach ( $wp_filter['phpmailer_init']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$mailer = self::make_mailer();
				if ( null === $mailer ) {
					return null;
				}

				try {
					call_user_func_array( $callback['function'], array( &$mailer ) );
				} catch ( \Throwable $e ) {
					continue;
				}

				if ( is_object( $mailer ) && isset( $mailer->Mailer ) && 'mail' !== $mailer->Mailer ) {
					$file = self::callback_file( $callback['function'] );
					if ( $file ) {
						$files[] = $file;
					}
				}
			}
		}

		return $files;
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
	 * Mail plugins (SES, Mailgun API, etc.) often replace the pluggable wp_mail().
	 */
	private static function wp_mail_is_overridden(): bool {
		if ( ! function_exists( 'wp_mail' ) ) {
			return false;
		}

		try {
			$file = ( new \ReflectionFunction( 'wp_mail' ) )->getFileName();
		} catch ( \ReflectionException $e ) {
			return false;
		}

		if ( ! $file ) {
			return false;
		}

		return wp_normalize_path( $file ) !== wp_normalize_path( ABSPATH . WPINC . '/pluggable.php' );
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

		$plugins_dir = trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) );
		$mu_dir      = trailingslashit( wp_normalize_path( WPMU_PLUGIN_DIR ) );

		if ( 0 === strpos( $file, $plugins_dir ) ) {
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

		if ( 0 === strpos( $file, $mu_dir ) ) {
			$segment = explode( '/', substr( $file, strlen( $mu_dir ) ) )[0];

			if ( ! function_exists( 'get_mu_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$mu_plugins = get_mu_plugins();

			return ! empty( $mu_plugins[ $segment ]['Name'] ) ? $mu_plugins[ $segment ]['Name'] : $segment;
		}

		return null;
	}
}
