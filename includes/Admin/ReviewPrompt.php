<?php
/**
 * A polite, dismissible request for a WordPress.org review.
 *
 * Shown only on this plugin's own screens, only to administrators, and only
 * after the plugin has had time to prove useful. It never offers an incentive,
 * can be dismissed for good, and "Maybe later" works at most twice.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

defined( 'ABSPATH' ) || exit;

class ReviewPrompt {

	const OPTION_INSTALLED = 'ecehc_installed_at';
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
	 * Whether to show the request to the current user now: a day after
	 * activation if a test email already went through, otherwise after a week.
	 */
	public static function should_show(): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$state = self::state( get_current_user_id() );

		if ( $state['done'] || $state['later'] >= self::MAX_LATER || time() < $state['until'] ) {
			return false;
		}

		$age        = time() - self::installed_at();
		$last_test  = TestEmailHandler::get_last_result();
		$test_works = null !== $last_test && $last_test['success'];

		return ( $test_works && $age >= DAY_IN_SECONDS ) || $age >= 7 * DAY_IN_SECONDS;
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
