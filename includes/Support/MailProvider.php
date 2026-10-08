<?php
/**
 * Detects the third-party service the site sends mail through, based on the
 * settings of popular SMTP plugins.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class MailProvider {

	/**
	 * Needle (matched against plugin settings / SMTP host) => provider name and SPF domains.
	 *
	 * @return array<string, array{name: string, spf: string[]}>
	 */
	private static function known_providers(): array {
		return array(
			'sendgrid'     => array( 'name' => 'SendGrid', 'spf' => array( 'sendgrid.net' ) ),
			'mailgun'      => array( 'name' => 'Mailgun', 'spf' => array( 'mailgun.org' ) ),
			'brevo'        => array( 'name' => 'Brevo', 'spf' => array( 'brevo.com', 'sendinblue.com' ) ),
			'sendinblue'   => array( 'name' => 'Brevo', 'spf' => array( 'brevo.com', 'sendinblue.com' ) ),
			'postmark'     => array( 'name' => 'Postmark', 'spf' => array( 'mtasv.net', 'postmarkapp.com' ) ),
			'gmail'        => array( 'name' => 'Google', 'spf' => array( 'google.com' ) ),
			'office365'    => array( 'name' => 'Microsoft 365', 'spf' => array( 'protection.outlook.com' ) ),
			'outlook'      => array( 'name' => 'Microsoft 365', 'spf' => array( 'protection.outlook.com' ) ),
			'amazonses'    => array( 'name' => 'Amazon SES', 'spf' => array( 'amazonses.com' ) ),
			'amazonaws'    => array( 'name' => 'Amazon SES', 'spf' => array( 'amazonses.com' ) ),
			'sparkpost'    => array( 'name' => 'SparkPost', 'spf' => array( 'sparkpostmail.com' ) ),
			'zoho'         => array( 'name' => 'Zoho Mail', 'spf' => array( 'zoho.' ) ),
			'mailjet'      => array( 'name' => 'Mailjet', 'spf' => array( 'mailjet.com' ) ),
			'elasticemail' => array( 'name' => 'Elastic Email', 'spf' => array( 'elasticemail.com' ) ),
		);
	}

	/**
	 * @return array{name: string, spf: string[]}|null
	 */
	public static function detect(): ?array {
		$provider = null;

		foreach ( self::configured_values() as $value ) {
			$provider = self::match( $value );
			if ( $provider ) {
				break;
			}
		}

		/**
		 * Filters the detected mail provider.
		 *
		 * @param array|null $provider array( 'name' => string, 'spf' => string[] ) or null.
		 */
		$provider = apply_filters( 'ecehc_mail_provider', $provider );

		return is_array( $provider ) && ! empty( $provider['name'] ) && ! empty( $provider['spf'] ) ? $provider : null;
	}

	/**
	 * Mailer names and SMTP hosts configured in popular SMTP plugins.
	 *
	 * @return string[]
	 */
	private static function configured_values(): array {
		$values = array();

		// WP Mail SMTP.
		$wpms = get_option( 'wp_mail_smtp' );
		if ( is_array( $wpms ) ) {
			$values[] = $wpms['mail']['mailer'] ?? '';
			$values[] = $wpms['smtp']['host'] ?? '';
		}

		// Post SMTP.
		$post_smtp = get_option( 'postman_options' );
		if ( is_array( $post_smtp ) ) {
			$values[] = $post_smtp['transport_type'] ?? '';
			$values[] = $post_smtp['hostname'] ?? '';
		}

		// FluentSMTP.
		$fluent = get_option( 'fluentmail-settings' );
		if ( is_array( $fluent ) && ! empty( $fluent['connections'] ) && is_array( $fluent['connections'] ) ) {
			foreach ( $fluent['connections'] as $connection ) {
				$values[] = $connection['provider_settings']['provider'] ?? '';
				$values[] = $connection['provider_settings']['host'] ?? '';
			}
		}

		return array_filter( array_map( 'strval', $values ) );
	}

	/**
	 * @return array{name: string, spf: string[]}|null
	 */
	private static function match( string $value ): ?array {
		$value = strtolower( $value );

		if ( 'ses' === $value ) {
			$value = 'amazonses';
		}

		foreach ( self::known_providers() as $needle => $provider ) {
			if ( false !== strpos( $value, $needle ) ) {
				return $provider;
			}
		}

		return null;
	}
}
