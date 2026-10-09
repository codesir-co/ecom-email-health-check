<?php
/**
 * Works out which plugin, theme or WordPress itself called wp_mail().
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Log;

use CodeSir\EmailHealthCheck\Support\PluginLocator;

defined( 'ABSPATH' ) || exit;

class SourceDetector {

	/**
	 * Looks at a short backtrace from inside the wp_mail filter. Frames that are
	 * only plumbing (this plugin, the pluggable wp_mail(), the hook system) are
	 * skipped; the first remaining frame decides the source.
	 *
	 * @return array{key: string, label: string}
	 */
	public static function detect(): array {
		$core = static function ( string $file ): string {
			// Real paths, because WP-CLI run with a relative --path gives an ABSPATH like "/site/./".
			return wp_normalize_path( (string) ( realpath( ABSPATH . WPINC . '/' . $file ) ?: ABSPATH . WPINC . '/' . $file ) );
		};

		$skip = array(
			wp_normalize_path( ECEHC_PLUGIN_PATH . 'includes/Log/' ),
			$core( 'pluggable.php' ),
			$core( 'class-wp-hook.php' ),
			$core( 'plugin.php' ),
		);

		$frames = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 40 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace

		foreach ( $frames as $frame ) {
			if ( empty( $frame['file'] ) ) {
				continue;
			}

			$file = wp_normalize_path( $frame['file'] );

			foreach ( $skip as $prefix ) {
				if ( 0 === strpos( $file, $prefix ) ) {
					continue 2;
				}
			}

			return self::classify( $file );
		}

		return array(
			'key'   => 'unknown',
			'label' => __( 'Unknown', 'ecom-email-health-check' ),
		);
	}

	/**
	 * @return array{key: string, label: string}
	 */
	private static function classify( string $file ): array {
		$plugin = PluginLocator::locate( $file );

		if ( $plugin ) {
			return array(
				'key'   => $plugin['key'],
				'label' => $plugin['name'],
			);
		}

		$theme_root = trailingslashit( wp_normalize_path( get_theme_root() ) );
		if ( 0 === strpos( $file, $theme_root ) ) {
			return array(
				'key'   => 'theme',
				'label' => __( 'Theme', 'ecom-email-health-check' ),
			);
		}

		$abspath = wp_normalize_path( ABSPATH );
		if ( 0 === strpos( $file, $abspath ) ) {
			return array(
				'key'   => 'wordpress',
				'label' => __( 'WordPress', 'ecom-email-health-check' ),
			);
		}

		return array(
			'key'   => 'unknown',
			'label' => __( 'Unknown', 'ecom-email-health-check' ),
		);
	}
}
