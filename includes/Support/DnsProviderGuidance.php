<?php
/**
 * Short notes on where DNS records are edited for a detected DNS provider, and whether the
 * DNS provider and the web host are the same company.
 *
 * Notes are general and never invent record values. A docs link is included only where the
 * page was checked to exist (2026-11-18). No promotional links.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class DnsProviderGuidance {

	/**
	 * Official "add a DNS record" page per provider slug. Providers missing here have no link.
	 *
	 * @return array<string, string>
	 */
	private static function docs(): array {
		return array(
			'cloudflare'    => 'https://developers.cloudflare.com/dns/manage-dns-records/how-to/create-dns-records/',
			'godaddy'       => 'https://www.godaddy.com/en/help/add-a-txt-record-19232',
			'namecheap'     => 'https://www.namecheap.com/support/knowledgebase/article.aspx/317/2237/how-do-i-add-txtspfdkimdmarc-records-for-my-domain/',
			'squarespace'   => 'https://support.squarespace.com/hc/en-us/articles/360002101888-Edit-your-domain-s-DNS-records',
			'route-53'      => 'https://docs.aws.amazon.com/Route53/latest/DeveloperGuide/resource-record-sets-creating.html',
			'wix'           => 'https://support.wix.com/en/article/adding-or-updating-txt-records-in-your-wix-account',
			'wordpress-com' => 'https://wordpress.com/support/domains/custom-dns/',
			'digitalocean'  => 'https://docs.digitalocean.com/products/networking/dns/how-to/manage-records/',
		);
	}

	/**
	 * Guidance for a DNS provider slug, or null for one without a note (for example one added with a filter).
	 *
	 * @param string $slug Slug from DnsProviderDetector.
	 * @return array{note: string, docs: string}|null
	 */
	public static function for_provider( string $slug ): ?array {
		switch ( $slug ) {
			case 'cloudflare':
				$note = __( 'Your DNS records are edited in your Cloudflare dashboard, under the DNS section for your domain. Changes are not made in WordPress.', 'ecom-email-health-check' );
				break;
			case 'godaddy':
				$note = __( 'Your DNS records are edited in your GoDaddy account, in the DNS settings of your domain. Changes are not made in WordPress.', 'ecom-email-health-check' );
				break;
			case 'namecheap':
				$note = __( 'Your DNS records are edited in your Namecheap account, under Advanced DNS for your domain. Changes are not made in WordPress.', 'ecom-email-health-check' );
				break;
			case 'squarespace':
				$note = __( 'Your DNS records are edited in your Squarespace account, in the DNS settings of your domain. Changes are not made in WordPress.', 'ecom-email-health-check' );
				break;
			case 'route-53':
				$note = __( 'Your DNS records are edited in the hosted zone for your domain in the Amazon Route 53 console. Changes are not made in WordPress.', 'ecom-email-health-check' );
				break;
			case 'wix':
				$note = __( 'Your DNS records are edited in your Wix account, in the DNS settings of your domain. Changes are not made in WordPress.', 'ecom-email-health-check' );
				break;
			case 'wordpress-com':
				$note = __( 'Your DNS records are edited in the domain settings of your WordPress.com account. Changes are not made in your site\'s WordPress admin.', 'ecom-email-health-check' );
				break;
			case 'hostinger':
				$note = __( 'Your DNS records are edited in the DNS settings of your domain in your Hostinger account. Changes are not made in WordPress.', 'ecom-email-health-check' );
				break;
			case 'bluehost':
				$note = __( 'Your DNS records are edited in the DNS settings of your domain in your Bluehost account. Changes are not made in WordPress.', 'ecom-email-health-check' );
				break;
			case 'digitalocean':
				$note = __( 'Your DNS records are edited in the Networking section of your DigitalOcean account, under your domain. Changes are not made in WordPress.', 'ecom-email-health-check' );
				break;
			default:
				return null;
		}

		return array(
			'note' => $note,
			'docs' => self::docs()[ $slug ] ?? '',
		);
	}

	/**
	 * Whether the DNS provider and the web host are one company, so one combined note is shown.
	 * Compared by slug, or by name ignoring case for providers added with filters. Only a host
	 * detected with high confidence counts: a "best guess" host is never presented as a confirmed
	 * combined claim.
	 *
	 * @param array{slug: string, name: string}|null $dns  Detected DNS provider.
	 * @param array{slug: string, name: string, confidence?: string}|null $host Detected web host.
	 */
	public static function is_same_company( ?array $dns, ?array $host ): bool {
		if ( null === $dns || null === $host || ! isset( $dns['slug'], $dns['name'], $host['slug'], $host['name'], $host['confidence'] ) || 'high' !== $host['confidence'] ) {
			return false;
		}

		return $dns['slug'] === $host['slug'] || strtolower( $dns['name'] ) === strtolower( $host['name'] );
	}
}
