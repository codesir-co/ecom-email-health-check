<?php
/**
 * Plain-text summary of the email setup, for pasting into a support ticket.
 *
 * It contains versions, check results, the detected mail provider and recent
 * log counts. It never contains passwords, keys, message contents or full
 * email addresses (addresses inside messages are partly hidden) and the site
 * and content directory paths in messages are replaced by placeholders.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

use CodeSir\EmailHealthCheck\Log\EmailLog;
use CodeSir\EmailHealthCheck\Log\EmailLogger;

defined( 'ABSPATH' ) || exit;

class SupportReport {

	/**
	 * Collects the values and returns the report text.
	 *
	 * @param array<string, array{label: string, status: string, message: string}> $results Results from CheckRunner::run_all().
	 */
	public static function build( array $results ): string {
		$provider = MailProvider::detect();

		$data = array(
			'plugin_version'   => ECEHC_VERSION,
			'wp_version'       => get_bloginfo( 'version' ),
			'php_version'      => PHP_VERSION,
			'wc_version'       => defined( 'WC_VERSION' ) ? WC_VERSION : null,
			'mail_domain'      => Domain::mail_domain(),
			'site_domain'      => Domain::site_domain(),
			'provider'         => $provider ? $provider['name'] : null,
			'handling_plugins' => SmtpDetector::plugin_names(),
			'log_stats'        => null,
		);

		if ( EmailLogger::is_enabled() ) {
			$data['log_stats'] = array();
			foreach ( array(
				'last 24 hours' => DAY_IN_SECONDS,
				'last 7 days'   => 7 * DAY_IN_SECONDS,
			) as $label => $seconds ) {
				$stats                       = EmailLog::stats( $seconds );
				$data['log_stats'][ $label ] = array(
					'sent'   => (int) $stats['sent'],
					'failed' => (int) $stats['failed'],
					'rate'   => (int) EmailLog::failure_rate( $stats ),
				);
			}
		}

		return self::format( $data, $results );
	}

	/**
	 * Builds the text from already collected values. Pure: it reads nothing from the site.
	 *
	 * Only the keys below are used, so nothing else passed in can reach the text.
	 * $data keys: plugin_version, wp_version, php_version, wc_version (string or null),
	 * mail_domain, site_domain, provider (string or null), handling_plugins (string list),
	 * log_stats (null, or label => array( sent, failed, rate )), generated (optional, default now).
	 *
	 * @param array<string, mixed>                                                 $data    Collected values.
	 * @param array<string, array{label: string, status: string, message: string}> $results Results from CheckRunner::run_all().
	 */
	public static function format( array $data, array $results ): string {
		$generated = isset( $data['generated'] ) ? (string) $data['generated'] : gmdate( 'Y-m-d H:i' );
		$plugins   = isset( $data['handling_plugins'] ) ? (array) $data['handling_plugins'] : array();
		$provider  = isset( $data['provider'] ) ? (string) $data['provider'] : '';

		$lines   = array();
		$lines[] = 'Email Health Check report';
		$lines[] = 'Generated: ' . $generated . ' UTC';
		$lines[] = '';
		$lines[] = sprintf(
			'Plugin %1$s | WordPress %2$s | PHP %3$s | WooCommerce %4$s',
			$data['plugin_version'] ?? '',
			$data['wp_version'] ?? '',
			$data['php_version'] ?? '',
			isset( $data['wc_version'] ) ? $data['wc_version'] : 'not active'
		);
		$lines[] = 'Mail domain: ' . ( $data['mail_domain'] ?? '' ) . ' | Site domain: ' . ( $data['site_domain'] ?? '' );
		$lines[] = 'Mail provider: ' . ( '' !== $provider ? $provider : 'not detected' );
		$lines[] = 'Outgoing email handled by: ' . ( $plugins ? implode( ', ', $plugins ) : 'no plugin detected (plain PHP mail?)' );
		$lines[] = '';
		$lines[] = 'Checks:';

		foreach ( $results as $result ) {
			$lines[] = sprintf(
				'- [%1$s] %2$s: %3$s',
				strtoupper( $result['status'] ),
				$result['label'],
				self::redact_paths( EmailLogger::mask_addresses( (string) preg_replace( '/\s+/', ' ', $result['message'] ) ) )
			);
		}

		if ( ! empty( $data['log_stats'] ) ) {
			$lines[] = '';
			foreach ( $data['log_stats'] as $label => $stats ) {
				$lines[] = sprintf(
					'Email log, %1$s: %2$d accepted, %3$d failed (%4$d%% failure rate)',
					$label,
					$stats['sent'],
					$stats['failed'],
					$stats['rate']
				);
			}
		}

		return implode( "\n", $lines ) . "\n";
	}

	/**
	 * Replaces the site root and the content directory in a text with short placeholders.
	 *
	 * Only these two known directories are replaced; any other path in a message stays as it is.
	 */
	public static function redact_paths( string $text ): string {
		$dirs = array();
		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$dirs['[content]'] = (string) WP_CONTENT_DIR;
		}
		if ( defined( 'ABSPATH' ) ) {
			$dirs['[site]'] = (string) ABSPATH;
		}

		// The most specific (longest) directory first, so the content directory is not half-replaced by the site root.
		$candidates = array();
		foreach ( $dirs as $placeholder => $dir ) {
			$variants = array( $dir );
			$real     = realpath( $dir );
			if ( false !== $real ) {
				$variants[] = $real;
			}
			foreach ( $variants as $variant ) {
				$base = rtrim( wp_normalize_path( $variant ), '/' );
				if ( '' !== $base ) {
					$candidates[ $base ] = $placeholder;
					// The same path as Windows prints it, with backslashes.
					$candidates[ str_replace( '/', '\\', $base ) ] = $placeholder;
				}
			}
		}
		uksort(
			$candidates,
			static function ( $a, $b ) {
				return strlen( $b ) <=> strlen( $a );
			}
		);

		foreach ( $candidates as $base => $placeholder ) {
			$text = str_replace( $base, $placeholder, $text );
		}

		return $text;
	}
}
