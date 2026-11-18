<?php
/**
 * Short, plain-language notes on sending email from a detected web host.
 *
 * The notes are deliberately general (hosts change their limits); they point people to
 * their host's own documentation and never invent record values. A docs link is included
 * only where the page was checked to exist. No promotional links.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class HostGuidance {

	/**
	 * Docs link per host slug (official pages, checked 2026-11-18). Hosts missing here have no link.
	 *
	 * @return array<string, string>
	 */
	private static function docs(): array {
		return array(
			'pantheon'      => 'https://docs.pantheon.io/guides/domains/dns',
			'godaddy'       => 'https://www.godaddy.com/en/help/add-a-txt-record-19232',
			'wordpress-com' => 'https://wordpress.com/support/domains/custom-dns/',
		);
	}

	/**
	 * Guidance for a host slug, or null for a host without a note (for example one added with a filter).
	 *
	 * @param string $slug Host slug from HostDetector.
	 * @return array{mail: string, dns: string, docs: string}|null
	 */
	public static function for_host( string $slug ): ?array {
		$mail = '';
		$dns  = '';

		switch ( $slug ) {
			case 'wp-engine':
			case 'kinsta':
			case 'flywheel':
			case 'pantheon':
				$mail = __( 'Managed hosts like this often limit or do not support sending email straight from the server, and mail sent that way is easily marked as spam. Sending through an SMTP plugin with a transactional email service is the dependable option.', 'ecom-email-health-check' );
				$dns  = __( 'Your SPF, DKIM and DMARC records belong with your domain\'s DNS, which is wherever your domain\'s name servers point (your registrar, a DNS service such as Cloudflare, or your host\'s DNS if you use it). They are not edited in WordPress.', 'ecom-email-health-check' );
				break;
			case 'siteground':
			case 'cloudways':
				$mail = __( 'Shared and cloud hosts often limit how much email a site can send directly, and their server addresses are shared with other sites. Sending through an SMTP plugin with a transactional email service is the dependable option; check your host\'s documentation for its limits.', 'ecom-email-health-check' );
				$dns  = __( 'Add the records where your domain\'s DNS is managed: your host\'s DNS zone editor if your domain uses its name servers, otherwise your registrar or DNS service. They are not edited in WordPress.', 'ecom-email-health-check' );
				break;
			case 'godaddy':
				$mail = __( 'GoDaddy hosting may limit or relay email from the server, so mail sent straight from your site can be delayed or marked as spam. Sending through an SMTP plugin with a transactional email service is the dependable option.', 'ecom-email-health-check' );
				$dns  = __( 'If your domain is registered with GoDaddy, edit its DNS records in your GoDaddy domain settings. If it uses other name servers, edit them there instead.', 'ecom-email-health-check' );
				break;
			case 'wordpress-com':
				$mail = __( 'WordPress.com sends your site\'s email from its own servers, so you have less control over how mail is sent. A mail service connected through your plan, or a transactional email service where your plan allows plugins, is the dependable option.', 'ecom-email-health-check' );
				$dns  = __( 'If your domain is managed at WordPress.com, edit its DNS records in your domain settings. If it uses other name servers, edit them there instead.', 'ecom-email-health-check' );
				break;
			default:
				return null;
		}

		return array(
			'mail' => $mail,
			'dns'  => $dns,
			'docs' => self::docs()[ $slug ] ?? '',
		);
	}
}
