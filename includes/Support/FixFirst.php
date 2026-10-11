<?php
/**
 * Picks the one check to fix first from a set of results.
 *
 * Failures come before warnings, and a check that was "Not checked" never comes
 * before a real failure or warning (it is never picked at all). Within the same
 * severity the checks that stop email from arriving at all rank ahead of the
 * ones that only hurt deliverability. Checks added by other code rank last, in
 * the order they ran. There is no score: this only chooses one check.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

defined( 'ABSPATH' ) || exit;

class FixFirst {

	const STATE_FIX       = 'fix';
	const STATE_OK        = 'ok';
	const STATE_UNCHECKED = 'unchecked';

	/**
	 * Check ids from the most to the least important, for checks of the same severity.
	 * An independent heuristic from the ordering in WooDiagnosis (feature/118); the two are not meant to agree.
	 */
	const PRIORITY = array(
		'basic_functionality',
		'smtp_configured',
		'from_address',
		'spf_record',
		'dkim_record',
		'dmarc_record',
		'ip_blacklist',
		'wc_email_setup',
		'pending_orders',
	);

	/**
	 * @param array<string, array{label: string, status: string, message: string, guidance?: array|null}> $results Results from CheckRunner::run_all().
	 * @return array{state: string, id: string, label: string, status: string, step: string, has_guidance: bool, not_checked: int}
	 *         state is "fix" (id, label, status, step and has_guidance describe the check to fix),
	 *         "ok" (every check that ran passed) or "unchecked" (nothing passed and nothing failed).
	 *         not_checked counts the checks that could not reach a conclusion.
	 */
	public static function pick( array $results ): array {
		$summary = array(
			'state'        => self::STATE_UNCHECKED,
			'id'           => '',
			'label'        => '',
			'status'       => '',
			'step'         => '',
			'has_guidance' => false,
			'not_checked'  => 0,
		);

		$best      = null;
		$best_rank = null;
		$passed    = 0;
		$position  = 0;

		foreach ( $results as $id => $result ) {
			$status = is_array( $result ) && isset( $result['status'] ) ? (string) $result['status'] : 'unknown';
			++$position;

			if ( 'pass' === $status ) {
				++$passed;
				continue;
			}

			if ( 'fail' !== $status && 'warning' !== $status ) {
				++$summary['not_checked'];
				continue;
			}

			$priority = array_search( (string) $id, self::PRIORITY, true );
			$rank     = array(
				'fail' === $status ? 0 : 1,
				false === $priority ? count( self::PRIORITY ) : $priority,
				$position,
			);

			if ( null === $best_rank || $rank < $best_rank ) {
				$best_rank = $rank;
				$best      = (string) $id;
			}
		}

		if ( null === $best ) {
			$summary['state'] = $passed > 0 ? self::STATE_OK : self::STATE_UNCHECKED;
			return $summary;
		}

		$result = $results[ $best ];

		$summary['state']        = self::STATE_FIX;
		$summary['id']           = $best;
		$summary['label']        = (string) ( $result['label'] ?? $best );
		$summary['status']       = (string) $result['status'];
		$summary['step']         = self::next_step( $best, (string) ( $result['message'] ?? '' ) );
		$summary['has_guidance'] = ! empty( $result['guidance'] );

		return $summary;
	}

	/**
	 * One plain-language next step. Checks added by other code have no step of
	 * ours, so their own message is used.
	 */
	public static function next_step( string $id, string $message ): string {
		switch ( $id ) {
			case 'basic_functionality':
				return __( 'Make sure your site can send email at all: ask your host, or set up an SMTP plugin, then send a test email.', 'ecom-email-health-check' );
			case 'smtp_configured':
				return __( 'Connect an SMTP plugin or a transactional email service, so your email is sent with authentication instead of plain PHP mail.', 'ecom-email-health-check' );
			case 'from_address':
				return __( 'Change the From address to an address at your own domain.', 'ecom-email-health-check' );
			case 'spf_record':
				return __( 'Add an SPF record for your sending domain in your DNS settings.', 'ecom-email-health-check' );
			case 'dkim_record':
				return __( 'Turn on DKIM signing with your mail provider and add the DNS record it gives you.', 'ecom-email-health-check' );
			case 'dmarc_record':
				return __( 'Add a DMARC record for your sending domain in your DNS settings.', 'ecom-email-health-check' );
			case 'ip_blacklist':
				return __( 'Ask your host to move you to a clean sending address, or send through an SMTP service instead.', 'ecom-email-health-check' );
			case 'wc_email_setup':
				return __( 'Open WooCommerce > Settings > Emails and fix what this check lists.', 'ecom-email-health-check' );
			case 'pending_orders':
				return __( 'Look at your payment gateway: orders stuck in "Pending payment" or "Failed" send no order emails.', 'ecom-email-health-check' );
		}

		return $message;
	}

	/**
	 * The headline as plain text, for the screen reader announcement.
	 */
	public static function headline( array $summary ): string {
		if ( self::STATE_FIX === $summary['state'] ) {
			return sprintf(
				/* translators: 1: name of a check, 2: what to do next */
				__( 'Fix this first: %1$s. %2$s', 'ecom-email-health-check' ),
				$summary['label'],
				$summary['step']
			);
		}

		if ( self::STATE_OK === $summary['state'] ) {
			return __( 'Nothing to fix: every check that ran passed.', 'ecom-email-health-check' );
		}

		return __( 'No problem was found, but the checks could not reach a conclusion.', 'ecom-email-health-check' );
	}
}
