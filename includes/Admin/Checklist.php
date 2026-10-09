<?php
/**
 * A short first-run checklist at the top of the Health Report.
 *
 * Only sites where the plugin was freshly activated get it (a flag set on
 * activation), it can be hidden for good, and it disappears once every item
 * is done. The items tick themselves from real state.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

use CodeSir\EmailHealthCheck\Log\EmailLogger;

defined( 'ABSPATH' ) || exit;

class Checklist {

	const OPTION_FLAG   = 'ecehc_show_checklist';
	const META_HIDDEN   = 'ecehc_checklist_hidden';
	const META_SAW_LOG  = 'ecehc_checklist_saw_log';

	/** Called on activation; sites that merely update the plugin never get the checklist. */
	public static function flag_fresh_install(): void {
		add_option( self::OPTION_FLAG, 1, '', false );
	}

	/** Remember that this user has opened the Email Log tab. */
	public static function mark_log_seen(): void {
		$user_id = get_current_user_id();

		if ( $user_id && ! get_user_meta( $user_id, self::META_SAW_LOG, true ) ) {
			update_user_meta( $user_id, self::META_SAW_LOG, 1 );
		}
	}

	public static function hide( int $user_id ): void {
		update_user_meta( $user_id, self::META_HIDDEN, 1 );
	}

	/**
	 * The checklist items for the current user, or an empty array when nothing
	 * should be shown (not a fresh install, hidden, or everything done).
	 *
	 * @param array<string, array{label: string, status: string}> $results Results from CheckRunner::run_all(), keyed by check id.
	 * @return array<int, array{label: string, done: bool, url: string}>
	 */
	public static function items( array $results ): array {
		$user_id = get_current_user_id();

		if ( ! get_option( self::OPTION_FLAG ) || ! current_user_can( 'manage_options' ) || get_user_meta( $user_id, self::META_HIDDEN, true ) ) {
			return array();
		}

		$last_test = TestEmailHandler::get_last_result();
		$items     = array(
			array(
				'label' => __( 'Send a test email with the button on this page', 'ecom-email-health-check' ),
				'done'  => null !== $last_test && $last_test['success'],
				'url'   => '#ecehc-test-email',
			),
		);

		$failing = '';
		$fail_id = '';
		foreach ( $results as $id => $result ) {
			if ( 'fail' === $result['status'] ) {
				$failing = $result['label'];
				$fail_id = (string) $id;
				break;
			}
		}

		$items[] = array(
			'label' => '' === $failing
				? __( 'Fix the failing checks in the report', 'ecom-email-health-check' )
				: sprintf(
					/* translators: %s: name of a check, e.g. SPF Record Validation */
					__( 'Fix the top failing check: %s', 'ecom-email-health-check' ),
					$failing
				),
			'done'  => '' === $failing,
			'url'   => '' === $failing ? '' : '#ecehc-check-' . $fail_id,
		);

		if ( EmailLogger::is_enabled() ) {
			$items[] = array(
				'label' => __( 'Take a look at the Email Log', 'ecom-email-health-check' ),
				'done'  => (bool) get_user_meta( $user_id, self::META_SAW_LOG, true ),
				'url'   => add_query_arg(
					array(
						'page' => AdminPage::MENU_SLUG,
						'tab'  => 'log',
					),
					admin_url( 'admin.php' )
				),
			);
		}

		foreach ( $items as $item ) {
			if ( ! $item['done'] ) {
				return $items;
			}
		}

		return array();
	}
}
