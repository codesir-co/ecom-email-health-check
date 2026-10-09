<?php
/**
 * Reads WooCommerce's own email settings and flags likely setup problems.
 *
 * Many "customers got no email" cases are configuration, not delivery: a key
 * email that is switched off, an empty new-order recipient, a missing From
 * address, or an outdated theme override of an email template. Nothing is
 * changed; the check only reads.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

defined( 'ABSPATH' ) || exit;

class WooCommerceEmailCheck implements CheckInterface {

	/** WooCommerce email ids that customers and the shop owner expect to receive. */
	const KEY_EMAILS = array( 'new_order', 'customer_processing_order', 'customer_completed_order', 'customer_note' );

	public function get_id(): string {
		return 'wc_email_setup';
	}

	public function get_label(): string {
		return __( 'WooCommerce Email Setup', 'ecom-email-health-check' );
	}

	public function run(): Result {
		if ( ! function_exists( 'WC' ) || ! is_object( WC() ) || ! method_exists( WC(), 'mailer' ) ) {
			return Result::unknown( __( 'WooCommerce\'s email settings could not be read.', 'ecom-email-health-check' ) );
		}

		try {
			$emails = WC()->mailer()->get_emails();
		} catch ( \Throwable $e ) {
			return Result::unknown( __( 'WooCommerce\'s email settings could not be read.', 'ecom-email-health-check' ) );
		}

		$disabled         = array();
		$recipient_broken = false;

		foreach ( (array) $emails as $email ) {
			if ( ! is_object( $email ) || ! isset( $email->id ) || ! in_array( $email->id, self::KEY_EMAILS, true ) ) {
				continue;
			}

			if ( ! $email->is_enabled() ) {
				$disabled[] = wp_strip_all_tags( (string) $email->title );
				continue;
			}

			// Only the new-order email goes to the shop owner, so only it has an editable recipient.
			if ( 'new_order' === $email->id && '' === trim( (string) $email->get_recipient() ) ) {
				$recipient_broken = true;
			}
		}

		$problems = array();

		if ( $recipient_broken ) {
			$problems[] = __( 'The "New order" email is on but has no valid recipient, so nobody receives new order emails. Set one in WooCommerce > Settings > Emails > New order.', 'ecom-email-health-check' );
		}

		if ( $disabled ) {
			$problems[] = sprintf(
				/* translators: %s: comma-separated list of WooCommerce email names */
				__( 'These WooCommerce emails are turned off: %s. That is fine if you did it on purpose; if not, turn them on in WooCommerce > Settings > Emails.', 'ecom-email-health-check' ),
				implode( ', ', $disabled )
			);
		}

		$from = (string) get_option( 'woocommerce_email_from_address' );
		if ( '' === $from || ! is_email( $from ) ) {
			$problems[] = __( 'WooCommerce\'s "From" address is empty or not a valid email address. Set it in WooCommerce > Settings > Emails.', 'ecom-email-health-check' );
		}

		$outdated = $this->outdated_template_overrides();
		if ( $outdated ) {
			$problems[] = sprintf(
				/* translators: %s: comma-separated list of template file names */
				__( 'Your theme overrides WooCommerce email templates that are older than WooCommerce\'s own versions: %s. Outdated overrides can break emails or miss changes. Update them or remove the overrides.', 'ecom-email-health-check' ),
				implode( ', ', $outdated )
			);
		}

		if ( ! $problems ) {
			return new Result( true, __( 'The key WooCommerce emails (new order, processing, completed and customer note) are on, the new order recipient and the From address are set, and no outdated email template overrides were found.', 'ecom-email-health-check' ) );
		}

		$message = implode( ' ', $problems );

		return $recipient_broken ? new Result( false, $message ) : Result::warning( $message );
	}

	/**
	 * Email template files that the theme overrides with an older version than WooCommerce's.
	 *
	 * @return string[] File names, at most ten.
	 */
	private function outdated_template_overrides(): array {
		if ( ! class_exists( '\WC_Admin_Status' ) || ! method_exists( '\WC_Admin_Status', 'scan_template_files' ) || ! method_exists( '\WC_Admin_Status', 'get_file_version' ) ) {
			return array();
		}

		$core_dir = WC()->plugin_path() . '/templates/emails';
		$outdated = array();

		foreach ( \WC_Admin_Status::scan_template_files( $core_dir ) as $file ) {
			if ( '.php' !== substr( $file, -4 ) ) {
				continue;
			}

			$override = locate_template( array( trailingslashit( WC()->template_path() ) . 'emails/' . $file ) );
			if ( ! $override ) {
				continue;
			}

			$core_version     = \WC_Admin_Status::get_file_version( $core_dir . '/' . $file );
			$override_version = \WC_Admin_Status::get_file_version( $override );

			if ( $core_version && '' !== $override_version && version_compare( $override_version, $core_version, '<' ) ) {
				$outdated[] = $file;
			}
		}

		return array_slice( $outdated, 0, 10 );
	}
}
