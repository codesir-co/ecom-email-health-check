<?php
/**
 * Works out whether WordPress mail is likely handed to an SMTP / mail service
 * instead of the host's unauthenticated PHP mail.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class SmtpDetector {

	/**
	 * Whether a mail service appears to be configured.
	 *
	 * Detection is deliberately generous: a plugin we do not know about that
	 * hooks the mailer or replaces wp_mail() counts as configured, so the
	 * warning only shows when nothing at all touches outgoing mail.
	 */
	public static function is_configured(): bool {
		return null !== MailProvider::detect()
			|| self::wp_mail_is_overridden()
			|| false !== has_action( 'phpmailer_init' );
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
}
