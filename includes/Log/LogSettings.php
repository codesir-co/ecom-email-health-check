<?php
/**
 * The email log settings: whether it records, and how long it keeps entries.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Log;

defined( 'ABSPATH' ) || exit;

class LogSettings {

	const OPTION = 'ecehc_log_settings';

	/** Retention choices in days. 7 is the free maximum; the ecehc_log_retention_days filter can raise it. */
	const RETENTION_CHOICES = array( 1, 3, 7 );

	const DEFAULT_RETENTION = 7;

	/**
	 * @return array{enabled: bool, retention_days: int}
	 */
	public static function get(): array {
		$saved = get_option( self::OPTION );
		$saved = is_array( $saved ) ? $saved : array();

		$days = isset( $saved['retention_days'] ) ? (int) $saved['retention_days'] : self::DEFAULT_RETENTION;

		return array(
			'enabled'        => ! isset( $saved['enabled'] ) || (bool) $saved['enabled'],
			'retention_days' => in_array( $days, self::RETENTION_CHOICES, true ) ? $days : self::DEFAULT_RETENTION,
		);
	}

	public static function is_enabled(): bool {
		return self::get()['enabled'];
	}

	public static function retention_days(): int {
		return self::get()['retention_days'];
	}

	/**
	 * Saves the settings, accepting only known values.
	 */
	public static function save( bool $enabled, int $retention_days ): void {
		update_option(
			self::OPTION,
			array(
				'enabled'        => $enabled,
				'retention_days' => in_array( $retention_days, self::RETENTION_CHOICES, true ) ? $retention_days : self::DEFAULT_RETENTION,
			)
		);
	}
}
