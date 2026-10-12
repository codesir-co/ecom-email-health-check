<?php
/**
 * Public functions for add-ons. Documented in docs/extending.md.
 *
 * @package CodeSir\EmailHealthCheck
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'ecehc_run_checks' ) ) {
	/**
	 * Runs the full set of diagnostic checks, exactly like the Health Report does.
	 *
	 * The `ecehc_checks` filter applies and `ecehc_checks_completed` fires. Nothing is
	 * stored, echoed or sent, and no admin context is needed (WP-Cron and WP-CLI work).
	 * It makes DNS lookups and can take several seconds: schedule it, do not call it on
	 * page loads.
	 *
	 * @return array<string, array{label: string, status: string, message: string, guidance: array|null}>
	 *         One entry per check, keyed by check id.
	 */
	function ecehc_run_checks(): array {
		return ( new \CodeSir\EmailHealthCheck\Checks\CheckRunner() )->run_all();
	}
}
