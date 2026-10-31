<?php
/**
 * A polite, dismissible request for a WordPress.org review.
 *
 * Shown only on this plugin's own screens, only to administrators, and only
 * after the plugin has helped (a test email went through or a report came back
 * with no failures or warnings), at least a day after activation. It never offers an incentive,
 * can be dismissed for good, and "Maybe later" works at most twice.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

use CodeSir\EmailHealthCheck\Checks\Result;

defined( 'ABSPATH' ) || exit;

class ReviewPrompt {

	const OPTION_INSTALLED = 'ecehc_installed_at';
	const OPTION_PASSED    = 'ecehc_report_passed';
	const USER_META        = 'ecehc_review_prompt';
	const REVIEW_URL       = 'https://wordpress.org/support/plugin/ecom-email-health-check/reviews/#new-post';

	/** How long "Maybe later" hides the request. */
	const LATER_DAYS = 30;

	/** After this many "Maybe later" clicks the request never comes back. */
	const MAX_LATER = 2;

	/**
	 * When the plugin was activated (or first seen, for sites updated to this version).
	 */
	public static function installed_at(): int {
		$installed = (int) get_option( self::OPTION_INSTALLED );

		if ( $installed <= 0 ) {
			$installed = time();
			// update_option also repairs an existing empty or corrupt value.
			update_option( self::OPTION_INSTALLED, $installed, false );
		}

		return $installed;
	}

	/**
	 * @return array{done: bool, later: int, until: int}
	 */
	private static function state( int $user_id ): array {
		$state = get_user_meta( $user_id, self::USER_META, true );
		$state = is_array( $state ) ? $state : array();

		return array(
			'done'  => ! empty( $state['done'] ),
			'later' => isset( $state['later'] ) ? (int) $state['later'] : 0,
			'until' => isset( $state['until'] ) ? (int) $state['until'] : 0,
		);
	}

	/**
	 * Remember the first time a full Health Report had no failing and no warning check.
	 *
	 * Call it only where an administrator completes a report. A no-op for any
	 * other result set, for junk input and once the time is already stored.
	 *
	 * @param array $results Results from CheckRunner::run_all().
	 */
	public static function note_report( array $results ): void {
		if ( empty( $results ) || (int) get_option( self::OPTION_PASSED ) > 0 ) {
			return;
		}

		foreach ( $results as $result ) {
			if ( ! is_array( $result ) || ! isset( $result['status'] ) || ! is_string( $result['status'] ) ) {
				return;
			}

			if ( in_array( $result['status'], array( Result::FAIL, Result::WARNING ), true ) ) {
				return;
			}
		}

		update_option( self::OPTION_PASSED, time(), false );
	}

	/**
	 * Whether to show the request to the current user now: only after the plugin
	 * helped (the last test email went through, or a report once had no failures
	 * or warnings), and at least a day after activation.
	 */
	public static function should_show(): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$state = self::state( get_current_user_id() );

		if ( $state['done'] || $state['later'] >= self::MAX_LATER || time() < $state['until'] ) {
			return false;
		}

		if ( time() - self::installed_at() < DAY_IN_SECONDS ) {
			return false;
		}

		$last_test = TestEmailHandler::get_last_result();

		return ( null !== $last_test && $last_test['success'] ) || (int) get_option( self::OPTION_PASSED ) > 0;
	}

	/**
	 * "later" hides it for a while (twice at most); "done" hides it for good.
	 */
	public static function dismiss( int $user_id, string $action ): void {
		$state = self::state( $user_id );

		if ( 'done' === $action ) {
			$state['done'] = true;
		} else {
			++$state['later'];
			$state['until'] = time() + self::LATER_DAYS * DAY_IN_SECONDS;
		}

		update_user_meta( $user_id, self::USER_META, $state );
	}
}
