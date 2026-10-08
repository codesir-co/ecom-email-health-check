<?php
/**
 * Looks for a DKIM public key under common selectors.
 *
 * DKIM selectors can't be discovered from DNS, so only well-known ones are
 * tried; when none is found the result is "not checked" rather than a failure.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

use CodeSir\EmailHealthCheck\Support\Dns;
use CodeSir\EmailHealthCheck\Support\Domain;

defined( 'ABSPATH' ) || exit;

class DkimCheck implements CheckInterface {

	public function get_id(): string {
		return 'dkim_record';
	}

	public function get_label(): string {
		return __( 'DKIM Record', 'ecom-email-health-check' );
	}

	/**
	 * @return string[]
	 */
	private function get_selectors(): array {
		$selectors = array( 'default', 'google', 'selector1', 'selector2', 'k1', 'k2', 'mail', 's1', 's2', 'dkim', 'smtp', 'mandrill', 'mxvault' );

		/**
		 * Filters the DKIM selectors that are tried.
		 *
		 * @param string[] $selectors Selector names.
		 */
		return array_filter( array_map( 'strval', (array) apply_filters( 'ecehc_dkim_selectors', $selectors ) ) );
	}

	public function run(): Result {
		$domain      = Domain::site_domain();
		$lookup_fail = false;

		foreach ( $this->get_selectors() as $selector ) {
			$records = Dns::txt_records( $selector . '._domainkey.' . $domain );

			if ( null === $records ) {
				$lookup_fail = true;
				break;
			}

			foreach ( $records as $record ) {
				if ( preg_match( '/v=DKIM1|(^|;)\s*p=/i', $record ) ) {
					return new Result(
						true,
						/* translators: %s: DKIM selector name */
						sprintf( __( 'A DKIM key was found for selector "%s". This lets receivers verify that your emails were not altered.', 'ecom-email-health-check' ), $selector )
					);
				}
			}
		}

		if ( $lookup_fail ) {
			return Result::unknown( __( 'The DNS lookup was not possible on this server, so DKIM could not be checked. Use an online DKIM checker with your mail provider\'s selector.', 'ecom-email-health-check' ) );
		}

		return Result::unknown( __( 'No DKIM key was found under common selectors. Your mail provider may use a custom selector that cannot be detected automatically. Check your provider\'s DNS instructions or use an online DKIM checker.', 'ecom-email-health-check' ) );
	}
}
