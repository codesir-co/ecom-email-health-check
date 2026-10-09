<?php
/**
 * Maps a PHP file to the plugin or mu-plugin it belongs to.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class PluginLocator {

	const NAMES_TRANSIENT = 'ecehc_plugin_names';

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
		$symlinked   = null === $plugins_dir && null === $mu_dir ? self::symlinked_plugin( $file ) : null;

		if ( null !== $symlinked ) {
			$found = array(
				'key'  => $symlinked,
				'name' => self::display_name( $symlinked ),
			);
			self::$cache[ $file ] = $found;

			return $found;
		}

		if ( null !== $plugins_dir ) {
			$segment = explode( '/', substr( $file, strlen( $plugins_dir ) ) )[0];

			$found = array(
				'key'  => $segment,
				'name' => self::display_name( $segment ),
			);
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
	 * Plugin display name for a folder (or single-file) slug, from a map cached
	 * in a transient so get_plugins() does not scan every plugin header on each request.
	 */
	private static function display_name( string $slug ): string {
		static $rebuilt = false;

		$names = get_transient( self::NAMES_TRANSIENT );

		if ( ! is_array( $names ) || ( ! isset( $names[ $slug ] ) && ! $rebuilt ) ) {
			$rebuilt = true;

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$names = array();
			foreach ( get_plugins() as $plugin_file => $data ) {
				$folder = false !== strpos( $plugin_file, '/' ) ? strtok( $plugin_file, '/' ) : $plugin_file;

				if ( ! empty( $data['Name'] ) ) {
					$names[ $folder ] = $data['Name'];
				}
			}

			if ( ! function_exists( 'get_mu_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			foreach ( get_mu_plugins() as $mu_file => $data ) {
				if ( ! empty( $data['Name'] ) ) {
					$names[ $mu_file ] = $data['Name'];
				}
			}

			set_transient( self::NAMES_TRANSIENT, $names, DAY_IN_SECONDS );
		}

		return $names[ $slug ] ?? $slug;
	}

	/**
	 * Folder name of a plugin that is symlinked into the plugins directory, where
	 * PHP reports the symlink's real path. WordPress records these in $wp_plugin_paths.
	 */
	private static function symlinked_plugin( string $file ): ?string {
		global $wp_plugin_paths;

		foreach ( (array) $wp_plugin_paths as $link => $real ) {
			if ( 0 === strpos( $file, trailingslashit( wp_normalize_path( (string) $real ) ) ) ) {
				return basename( (string) $link );
			}
		}

		return null;
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
