<?php
/**
 * DNS setup guidance for the detected mail provider.
 *
 * Only what each provider's own documentation states is published here: the SPF
 * include when the provider documents one for the root domain, and where to find
 * the per-account DKIM records. Per-account values are never invented.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class ProviderGuidance {

	/**
	 * Raw provider data, keyed by the name MailProvider::detect() returns.
	 *
	 * - spf: SPF include to put in the root domain's record, or null when the
	 *   provider authenticates SPF through its own return-path / MAIL FROM records.
	 * - docs: the provider's official setup page.
	 *
	 * @return array<string, array{spf: string|null, docs: string}>
	 */
	private static function providers(): array {
		return array(
			'Mailgun'       => array(
				'spf'  => 'mailgun.org',
				'docs' => 'https://help.mailgun.com/hc/en-us/articles/32884700912923-Domain-Verification-Setup-Guide',
			),
			'Mailjet'       => array(
				'spf'  => 'spf.mailjet.com',
				'docs' => 'https://documentation.mailjet.com/hc/en-us/articles/360049641733-Authenticating-Domains-with-SPF-and-DKIM-A-Complete-Guide',
			),
			'Elastic Email' => array(
				'spf'  => '_spf.elasticemail.com',
				'docs' => 'https://help.elasticemail.com/en/articles/6379963-importance-of-spf-and-dkim-on-your-domain',
			),
			'Google'        => array(
				'spf'  => '_spf.google.com',
				'docs' => 'https://support.google.com/a/answer/33786',
			),
			'Microsoft 365' => array(
				'spf'  => 'spf.protection.outlook.com',
				'docs' => 'https://learn.microsoft.com/en-us/defender-office-365/email-authentication-spf-configure',
			),
			'SendGrid'      => array(
				'spf'  => null,
				'docs' => 'https://www.twilio.com/docs/sendgrid/ui/account-and-settings/how-to-set-up-domain-authentication',
			),
			'Brevo'         => array(
				'spf'  => null,
				'docs' => 'https://help.brevo.com/hc/en-us/articles/12163873383186-Authenticate-your-domain-with-Brevo-Brevo-code-DKIM-DMARC',
			),
			'Postmark'      => array(
				'spf'  => null,
				'docs' => 'https://postmarkapp.com/support/article/how-do-i-set-up-spf-for-postmark',
			),
			'Amazon SES'    => array(
				'spf'  => null,
				'docs' => 'https://docs.aws.amazon.com/ses/latest/dg/mail-from.html',
			),
			'SparkPost'     => array(
				'spf'  => null,
				'docs' => 'https://support.sparkpost.com/docs/faq/sender-id-spf-failures',
			),
		);
	}

	/**
	 * Whether the provider needs its include in the root domain's SPF record.
	 * Unknown providers are assumed to, which keeps the existing SPF warning.
	 */
	public static function needs_root_spf( array $provider ): bool {
		$data = self::providers()[ $provider['name'] ] ?? null;

		return null === $data || null !== $data['spf'];
	}

	/**
	 * Fix guidance for a failing check, or null when there is nothing to add.
	 *
	 * @param string     $check_id Check ID (spf_record, dkim_record, dmarc_record).
	 * @param array|null $provider Result of MailProvider::detect().
	 * @param string     $domain   Site domain.
	 * @return array{text: string, records: array<int, array{type: string, host: string, value: string}>, docs: string}|null
	 */
	public static function for_check( string $check_id, ?array $provider, string $domain ): ?array {
		$data = $provider ? ( self::providers()[ $provider['name'] ] ?? null ) : null;
		$name = $provider ? $provider['name'] : '';
		$docs = $data ? $data['docs'] : '';

		switch ( $check_id ) {
			case 'spf_record':
				if ( ! $data ) {
					return null;
				}

				if ( null === $data['spf'] ) {
					return array(
						/* translators: %s: mail provider name */
						'text'    => sprintf( __( '%1$s authenticates SPF through its own return-path records, shown in your %1$s dashboard, so no include is needed in your root domain\'s SPF record. Complete its domain setup there. Keep any SPF record you already have for other senders.', 'ecom-email-health-check' ), $name ),
						'records' => array(),
						'docs'    => $docs,
					);
				}

				return array(
					'text'    => sprintf(
						/* translators: 1: SPF include, e.g. include:mailgun.org */
						__( 'If you have no SPF record, publish this one. If you already have one, add %1$s to it instead: a domain may only have a single SPF record, and a second one breaks both.', 'ecom-email-health-check' ),
						'include:' . $data['spf']
					),
					'records' => array(
						array(
							'type'  => 'TXT',
							'host'  => $domain,
							'value' => 'v=spf1 include:' . $data['spf'] . ' ~all',
						),
					),
					'docs'    => $docs,
				);

			case 'dkim_record':
				if ( ! $data ) {
					return null;
				}

				return array(
					'text'    => self::dkim_text( $name ),
					'records' => array(),
					'docs'    => $docs,
				);

			case 'dmarc_record':
				return array(
					'text'    => __( 'DMARC works only once SPF or DKIM passes for your sending service. Start in monitoring mode, then tighten the policy once your reports look clean. If you already have a DMARC record, edit it instead of adding a second one.', 'ecom-email-health-check' ),
					'records' => array(
						array(
							'type'  => 'TXT',
							'host'  => '_dmarc.' . $domain,
							'value' => 'v=DMARC1; p=none;',
						),
					),
					'docs'    => $docs,
				);
		}

		return null;
	}

	/**
	 * Where to find (or generate) the DKIM records, per provider documentation.
	 */
	private static function dkim_text( string $name ): string {
		switch ( $name ) {
			case 'Google':
				return __( 'In the Google Admin console go to Apps, Google Workspace, Gmail, Authenticate email, generate the record (the default host is google._domainkey), publish it as a TXT record and then turn DKIM on.', 'ecom-email-health-check' );
			case 'Microsoft 365':
				return __( 'Microsoft 365 uses two CNAME records, selector1._domainkey and selector2._domainkey, whose targets are specific to your tenant. Publish them, then enable DKIM signing for your domain.', 'ecom-email-health-check' );
			case 'Amazon SES':
				return __( 'Amazon SES gives you three CNAME records (token._domainkey) for Easy DKIM. Copy them from your SES identity, because the values are specific to it.', 'ecom-email-health-check' );
			case 'Mailgun':
				return __( 'Copy the DKIM TXT record from the "Domain Verification & DNS" section of your Mailgun domain.', 'ecom-email-health-check' );
			case 'Brevo':
				return __( 'Brevo shows either one DKIM TXT record or two CNAME records for your domain. Copy them from Brevo, because the values are generated for your account.', 'ecom-email-health-check' );
			case 'SendGrid':
				return __( 'SendGrid gives you CNAME records (or TXT records if Automated Security is off) when you authenticate your domain. Copy them from SendGrid, because the values are specific to your account.', 'ecom-email-health-check' );
			case 'Postmark':
				return __( 'Postmark issues a unique DKIM TXT record for each domain you add. Copy it from your Postmark domain settings.', 'ecom-email-health-check' );
			default:
				/* translators: %s: mail provider name */
				return sprintf( __( 'Copy the DKIM records from your %s dashboard (its domain authentication or verification section), because the values are generated for your account.', 'ecom-email-health-check' ), $name );
		}
	}
}
