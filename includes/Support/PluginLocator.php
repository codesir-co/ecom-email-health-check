<?php
/**
 * Maps a PHP file to the plugin or mu-plugin it belongs to.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class PluginLocator {

	/** @var array<string, array{key: string, name: string}|null> */
	private static $cache = array();

	/**
	 * The plugin a file belongs to, or null for files outside the plugins and
	 * mu-plugins directories (themes, core).
	 *
	 * @param string $file Absolute file path.
	 * @return array{key: string, name: string}|null Folder (or file) slug and display name.
	 */
	public static function locate( string $file ): ?array {
		$file = wp_normalize_path( $file );

		if ( array_key_exists( $file, self::$cache ) ) {
			return self::$cache[ $file ];
		}

		$plugins_dir = self::dir_prefix( $file, WP_PLUGIN_DIR );
		$mu_dir      = self::dir_prefix( $file, WPMU_PLUGIN_DIR );
		$found       = null;

		if ( null !== $plugins_dir ) {
			$segment = explode( '/', substr( $file, strlen( $plugins_dir ) ) )[0];

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$found = array(
				'key'  => $segment,
				'name' => $segment,
			);

			foreach ( get_plugins() as $plugin_file => $data ) {
				$matches = false !== strpos( $plugin_file, '/' )
					? strtok( $plugin_file, '/' ) === $segment
					: $plugin_file === $segment;

				if ( $matches && ! empty( $data['Name'] ) ) {
					$found['name'] = $data['Name'];
					break;
				}
			}
		} elseif ( null !== $mu_dir ) {
			$segment = explode( '/', substr( $file, strlen( $mu_dir ) ) )[0];

			if ( ! function_exists( 'get_mu_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$mu_plugins = get_mu_plugins();
			$found      = array(
				'key'  => $segment,
				'name' => ! empty( $mu_plugins[ $segment ]['Name'] ) ? $mu_plugins[ $segment ]['Name'] : $segment,
			);
		}

		self::$cache[ $file ] = $found;

		return $found;
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
