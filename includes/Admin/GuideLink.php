<?php
/**
 * A plain text link to a plain-language guide, inside the "How to fix this" panel of a
 * failing SPF, DKIM or DMARC check.
 *
 * The plugin makes no request and sends no data: it only prints a link that the user may
 * click. The URL comes from a fixed allow-list and carries only fixed tracking parameters.
 * Pure logic (map, URL, enabled decision, markup) lives here so it can be tested.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

defined( 'ABSPATH' ) || exit;

class GuideLink {

	const BASE_URL = 'https://outboxhealth.com/guides/';

	/**
	 * Every guide slug that may ever be linked (allow-list). All were checked live with
	 * curl (HTTP 200) on 2026-11-17. Re-check them before adding or keeping a slug.
	 */
	const SLUGS = array(
		'authenticate-email-google-workspace',
		'authenticate-email-microsoft-365',
		'authenticate-email-mailgun',
		'authenticate-email-sendgrid',
		'how-to-add-or-fix-an-spf-record',
		'how-to-set-up-dkim',
		'dmarc-for-beginners',
	);

	/**
	 * Guide for a detected provider name (as returned by MailProvider::detect()), shared by
	 * the SPF, DKIM and DMARC checks.
	 */
	const PROVIDER_GUIDES = array(
		'Google'        => 'authenticate-email-google-workspace',
		'Microsoft 365' => 'authenticate-email-microsoft-365',
		'Mailgun'       => 'authenticate-email-mailgun',
		'SendGrid'      => 'authenticate-email-sendgrid',
	);

	/**
	 * Fallback guide per check id when there is no provider guide.
	 */
	const CHECK_GUIDES = array(
		'spf_record'   => 'how-to-add-or-fix-an-spf-record',
		'dkim_record'  => 'how-to-set-up-dkim',
		'dmarc_record' => 'dmarc-for-beginners',
	);

	/**
	 * The guide slug for a check, or null when no guide matches.
	 *
	 * @param mixed       $check_id      Check id (the key of the result).
	 * @param string|null $provider_name Detected provider name, or null.
	 */
	public static function slug_for( $check_id, $provider_name = null ): ?string {
		if ( ! is_string( $check_id ) || ! isset( self::CHECK_GUIDES[ $check_id ] ) ) {
			return null;
		}

		if ( is_string( $provider_name ) && isset( self::PROVIDER_GUIDES[ $provider_name ] ) ) {
			return self::PROVIDER_GUIDES[ $provider_name ];
		}

		return self::CHECK_GUIDES[ $check_id ];
	}

	/**
	 * The guide URL with its fixed tracking parameters, or an empty string for an unknown slug.
	 *
	 * @param mixed $slug Guide slug.
	 */
	public static function url( $slug ): string {
		if ( ! is_string( $slug ) || ! in_array( $slug, self::SLUGS, true ) ) {
			return '';
		}

		return self::BASE_URL . $slug . '/?' . http_build_query(
			array(
				'utm_source'   => 'plugin',
				'utm_medium'   => 'admin',
				'utm_campaign' => 'fix-guide',
			),
			'',
			'&'
		);
	}

	/**
	 * Whether guide links may be shown at all.
	 */
	public static function is_enabled(): bool {
		/**
		 * Filters whether plain text guide links are shown in the "How to fix this" panel.
		 *
		 * @param bool $show Default true.
		 */
		return (bool) apply_filters( 'ecehc_show_guide_links', true );
	}

	/**
	 * The link markup for a slug, or an empty string when the slug is unknown.
	 *
	 * @param mixed $slug Guide slug.
	 */
	public static function link_html( $slug ): string {
		$url = self::url( $slug );
		if ( '' === $url ) {
			return '';
		}

		return '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">'
			. esc_html__( 'Plain-language guide (outboxhealth.com)', 'ecom-email-health-check' )
			. '<span class="screen-reader-text"> ' . esc_html__( '(opens in a new tab)', 'ecom-email-health-check' ) . '</span>'
			. '</a>';
	}

	/**
	 * Markup for a result of the report, or an empty string. Only failing and warning checks
	 * with a matching guide get a link.
	 *
	 * @param mixed       $check_id      Check id.
	 * @param mixed       $status        Result status ('pass', 'warning', 'fail', 'unknown').
	 * @param string|null $provider_name Detected provider name, or null.
	 */
	public static function html_for( $check_id, $status, $provider_name = null ): string {
		if ( ! in_array( $status, array( 'fail', 'warning' ), true ) || ! self::is_enabled() ) {
			return '';
		}

		$slug = self::slug_for( $check_id, $provider_name );

		return null === $slug ? '' : self::link_html( $slug );
	}
}
