<?php
/**
 * "WooCommerce emails are not arriving": the most likely cause, worked out from
 * results the plugin already has.
 *
 * It only combines the check results and the counts in the email log. It makes
 * no request of its own, reads no order or customer data and stores nothing. The
 * wording says "most likely" on purpose: the checks cannot see inside a mailbox,
 * so this is a pointer to where to look first, not a verdict.
 *
 * Its order of causes is an independent heuristic from FixFirst::PRIORITY (feature/117);
 * the two are not meant to agree.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Support;

use CodeSir\EmailHealthCheck\Log\EmailLog;
use CodeSir\EmailHealthCheck\Log\EmailLogger;

defined( 'ABSPATH' ) || exit;

class WooDiagnosis {

	const LOG_ON          = 'on';
	const LOG_OFF         = 'off';
	const LOG_UNSUPPORTED = 'unsupported';

	/** The log window the diagnosis looks at. */
	const LOG_WINDOW = 7 * DAY_IN_SECONDS;

	/**
	 * Gathers the log counts (from the existing log statistics) and diagnoses.
	 *
	 * @param array<string, array{status: string}> $results Results from CheckRunner::run_all().
	 * @return array{cause: string, headline: string, step: string, link: string, check_id: string, log: string, log_note: string}
	 */
	public static function for_site( array $results ): array {
		$log = null;

		if ( EmailLogger::is_enabled() ) {
			$stats = EmailLog::stats( self::LOG_WINDOW );
			$log   = array(
				'wc_sent'   => 0,
				'wc_failed' => 0,
			);
			// This sum counts WooCommerce emails only because EmailLog::stats() fills 'types' for WooCommerce email types alone.
			foreach ( $stats['types'] as $type ) {
				$log['wc_sent']   += (int) $type['sent'];
				$log['wc_failed'] += (int) $type['failed'];
			}
		}

		return self::diagnose( $results, $log, EmailLogger::is_supported() );
	}

	/**
	 * @param array<string, array{status: string}> $results   Results from CheckRunner::run_all(), keyed by check id.
	 * @param array{wc_sent: int, wc_failed: int}|null $log   WooCommerce email counts from the last 7 days, or null when the log is off.
	 * @param bool                                 $supported Whether this site can log email at all.
	 * @return array{cause: string, headline: string, step: string, link: string, check_id: string, log: string, log_note: string}
	 *         link is "check" (the row of check_id), "wc_settings" or "log_failed".
	 */
	public static function diagnose( array $results, ?array $log, bool $supported = true ): array {
		$status = static function ( string $id ) use ( $results ): string {
			return isset( $results[ $id ]['status'] ) ? (string) $results[ $id ]['status'] : '';
		};
		$bad    = static function ( string $id ) use ( $status ): bool {
			return in_array( $status( $id ), array( 'fail', 'warning' ), true );
		};

		$log_state = null !== $log ? self::LOG_ON : ( $supported ? self::LOG_OFF : self::LOG_UNSUPPORTED );

		$diagnosis = array(
			'cause'    => 'none',
			'headline' => __( 'None of the checks points to a clear cause.', 'ecom-email-health-check' ),
			'step'     => __( 'Send a test email to an address you can read, and send a test copy of a WooCommerce email from WooCommerce > Settings > Emails. If neither arrives, ask your host or mail service. If they do arrive, a missing email may come from one order or one customer address.', 'ecom-email-health-check' ),
			'link'     => 'none',
			'check_id' => '',
			'log'      => $log_state,
			'log_note' => self::log_note( $log_state ),
		);

		$cause = null;

		if ( 'fail' === $status( 'wc_email_setup' ) ) {
			$cause = array(
				'woo_setup_broken',
				__( 'Most likely, a WooCommerce email setting is wrong: an email that should go out has no valid recipient.', 'ecom-email-health-check' ),
				__( 'Open WooCommerce > Settings > Emails and fix the setting the check below describes.', 'ecom-email-health-check' ),
				'wc_settings',
				'wc_email_setup',
			);
		} elseif ( null !== $log && $log['wc_failed'] > 0 ) {
			$cause = array(
				'log_failures',
				sprintf(
					/* translators: %d: number of failed WooCommerce emails */
					_n(
						'Most likely, your mail system refused a WooCommerce email: %d failed in the last 7 days.',
						'Most likely, your mail system refused some WooCommerce emails: %d failed in the last 7 days.',
						$log['wc_failed'],
						'ecom-email-health-check'
					),
					$log['wc_failed']
				),
				__( 'Open the Email Log, filtered to failed emails, and read the error. It usually says what to change (a wrong password, a blocked sender address and so on).', 'ecom-email-health-check' ),
				'log_failed',
				'',
			);
		} elseif ( 'warning' === $status( 'wc_email_setup' ) ) {
			$cause = array(
				'woo_setup_warning',
				__( 'Most likely, some WooCommerce emails are turned off, or a WooCommerce email setting needs attention.', 'ecom-email-health-check' ),
				__( 'Open WooCommerce > Settings > Emails and check the emails the check below lists. Turned-off emails are fine if you meant to turn them off.', 'ecom-email-health-check' ),
				'wc_settings',
				'wc_email_setup',
			);
		} elseif ( 'warning' === $status( 'pending_orders' ) ) {
			$cause = array(
				'unpaid_orders',
				__( 'Most likely, this is not an email problem: many orders are stuck as unpaid, and WooCommerce sends no order emails for unpaid orders.', 'ecom-email-health-check' ),
				__( 'Look at your payment gateway and its logs first.', 'ecom-email-health-check' ),
				'check',
				'pending_orders',
			);
		} elseif ( $bad( 'smtp_configured' ) ) {
			$cause = array(
				'plain_php_mail',
				__( 'Most likely, your site sends through your host\'s plain PHP mail, which is often filtered as spam or silently dropped.', 'ecom-email-health-check' ),
				__( 'Connect an SMTP plugin or a transactional email service, then send a test email.', 'ecom-email-health-check' ),
				'check',
				'smtp_configured',
			);
		} elseif ( 'fail' === $status( 'from_address' ) ) {
			$cause = array(
				'from_address',
				__( 'Most likely, the From address is the problem: it is at a public mailbox domain such as Gmail, so receivers may reject or filter mail your site sends as that address.', 'ecom-email-health-check' ),
				__( 'Use a From address at your own domain (WooCommerce > Settings > Emails).', 'ecom-email-health-check' ),
				'check',
				'from_address',
			);
		} else {
			foreach ( array( 'spf_record', 'dkim_record', 'dmarc_record' ) as $id ) {
				if ( 'fail' === $status( $id ) ) {
					$cause = array(
						'dns_records',
						__( 'Most likely, your emails are going to spam or being rejected because the sending domain is missing a DNS record that receivers look for.', 'ecom-email-health-check' ),
						__( 'Add the missing record described below in your DNS settings.', 'ecom-email-health-check' ),
						'check',
						$id,
					);
					break;
				}
			}
		}

		if ( null === $cause && 'fail' === $status( 'ip_blacklist' ) ) {
			$cause = array(
				'blacklist',
				__( 'Most likely, your server\'s address is on an email blacklist, so receivers refuse its mail.', 'ecom-email-health-check' ),
				__( 'Ask your host for a clean sending address, or send through an SMTP service instead.', 'ecom-email-health-check' ),
				'check',
				'ip_blacklist',
			);
		}

		if ( null === $cause && 'warning' === $status( 'from_address' ) ) {
			$cause = array(
				'from_domain_differs',
				__( 'Possibly, the From address is at a different domain than your site. That is fine if you control that domain and your mail service is set up for it; if not, receivers may filter your mail.', 'ecom-email-health-check' ),
				__( 'Check that your mail service is set up for the domain of your From address, or use an address at your site\'s own domain.', 'ecom-email-health-check' ),
				'check',
				'from_address',
			);
		}

		if ( null === $cause && null !== $log && 0 === $log['wc_sent'] + $log['wc_failed'] ) {
			$cause = array(
				'no_woo_mail_logged',
				__( 'No WooCommerce emails were logged in the last 7 days. If you expected some, WooCommerce may not be sending them, or the log was only turned on recently.', 'ecom-email-health-check' ),
				__( 'Send a test copy of a WooCommerce email from WooCommerce > Settings > Emails and see whether it shows up in the Email Log.', 'ecom-email-health-check' ),
				'wc_settings',
				'',
			);
		}

		if ( null !== $cause ) {
			list( $diagnosis['cause'], $diagnosis['headline'], $diagnosis['step'], $diagnosis['link'], $diagnosis['check_id'] ) = $cause;
		}

		return $diagnosis;
	}

	private static function log_note( string $state ): string {
		if ( self::LOG_OFF === $state ) {
			return __( 'The email log is off, so recent failed emails could not be taken into account. Turn it on in the Email Log tab to include them.', 'ecom-email-health-check' );
		}

		if ( self::LOG_UNSUPPORTED === $state ) {
			return __( 'The email log is not available on this site, so recent failed emails could not be taken into account.', 'ecom-email-health-check' );
		}

		return __( 'This is based on the checks below and the last 7 days of the Email Log. "Accepted" in the log means your mail system took the email, not that it reached the inbox.', 'ecom-email-health-check' );
	}
}
