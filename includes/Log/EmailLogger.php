<?php
/**
 * Records every email WordPress sends.
 *
 * It listens to wp_mail_succeeded / wp_mail_failed (WordPress 5.9+), with
 * context gathered in the wp_mail filter. "Sent" only means WordPress handed
 * the message off successfully, not that it was delivered. Mail sent through
 * a pre_wp_mail short circuit never reaches these events and is not logged.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Log;

defined( 'ABSPATH' ) || exit;

class EmailLogger {

	const PURGE_HOOK = 'ecehc_purge_email_log';

	/** @var array{hash: string, source: array{key: string, label: string}, wc: array{id: string, label: string}|null}|null Context of the wp_mail() call in progress. */
	private $context = null;

	/** @var array{id: string, label: string}|null WooCommerce email being sent. */
	private $wc_email = null;

	/**
	 * Whether the logger can run on this site.
	 */
	public static function is_supported(): bool {
		/**
		 * Filters whether emails are logged.
		 *
		 * @param bool $enabled Default true on WordPress 5.9+.
		 */
		return (bool) apply_filters( 'ecehc_enable_email_log', version_compare( get_bloginfo( 'version' ), '5.9', '>=' ) );
	}

	public function register(): void {
		add_action( 'init', array( Schema::class, 'maybe_install' ), 1 );
		add_action( self::PURGE_HOOK, array( EmailLog::class, 'purge' ) );
		add_action( 'admin_init', array( $this, 'add_privacy_policy_content' ) );
		add_filter( 'wpmu_drop_tables', array( $this, 'add_drop_table' ) );

		if ( ! self::is_supported() ) {
			return;
		}

		add_action( 'init', array( $this, 'schedule_purge' ) );
		add_filter( 'pre_wp_mail', array( $this, 'forget_context' ), PHP_INT_MAX );

		add_filter( 'woocommerce_mail_callback', array( $this, 'track_wc_email' ), 10, 2 );
		add_action( 'woocommerce_email_sent', array( $this, 'forget_wc_email' ) );
		add_filter( 'wp_mail', array( $this, 'capture_context' ), PHP_INT_MAX );
		add_action( 'wp_mail_succeeded', array( $this, 'log_success' ) );
		add_action( 'wp_mail_failed', array( $this, 'log_failure' ) );
	}

	/**
	 * Drops the log table of a site that is deleted from a multisite network.
	 *
	 * @param mixed $tables Tables to drop.
	 * @return mixed
	 */
	public function add_drop_table( $tables ) {
		if ( is_array( $tables ) ) {
			$tables[] = Schema::table();
		}

		return $tables;
	}

	/**
	 * A pre_wp_mail short circuit ends the send without success/failure events,
	 * so the context gathered for it must not leak into the next event.
	 *
	 * @param mixed $pre Short-circuit value.
	 * @return mixed
	 */
	public function forget_context( $pre ) {
		if ( null !== $pre ) {
			$this->context = null;
		}

		return $pre;
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::PURGE_HOOK );
	}

	public function schedule_purge(): void {
		if ( ! wp_next_scheduled( self::PURGE_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::PURGE_HOOK );
		}
	}

	/**
	 * @param mixed $callback Mail callback.
	 * @param mixed $email    WC_Email being sent.
	 * @return mixed
	 */
	public function track_wc_email( $callback, $email ) {
		if ( is_object( $email ) && isset( $email->id ) ) {
			$this->wc_email = array(
				'id'    => (string) $email->id,
				'label' => isset( $email->title ) ? wp_strip_all_tags( (string) $email->title ) : (string) $email->id,
			);
		}

		return $callback;
	}

	public function forget_wc_email(): void {
		$this->wc_email = null;
	}

	/**
	 * Runs inside wp_mail(), so the backtrace still shows who called it.
	 *
	 * @param mixed $args wp_mail arguments.
	 * @return mixed Unchanged.
	 */
	public function capture_context( $args ) {
		try {
			$source = SourceDetector::detect();

			$this->context = array(
				'hash'   => self::hash( is_array( $args ) ? $args : array() ),
				'source' => $source,
				'wc'     => 'woocommerce' === $source['key'] ? $this->wc_email : null,
			);
		} catch ( \Throwable $e ) {
			$this->context = null;
		}

		// Read once; never let a failed WooCommerce send label a later email.
		$this->wc_email = null;

		return $args;
	}

	/**
	 * @param mixed $mail_data wp_mail_succeeded data.
	 */
	public function log_success( $mail_data ): void {
		$this->log( EmailLog::STATUS_SENT, $mail_data, '' );
	}

	/**
	 * @param mixed $error WP_Error from wp_mail_failed.
	 */
	public function log_failure( $error ): void {
		if ( ! $error instanceof \WP_Error ) {
			return;
		}

		$data = $error->get_error_data();
		$this->log( EmailLog::STATUS_FAILED, is_array( $data ) ? $data : array(), $error->get_error_message() );
	}

	/**
	 * Identifies a mail by its recipient and subject, to match events to the
	 * wp_mail() call that started them.
	 *
	 * @param array<string, mixed> $mail wp_mail arguments or event data.
	 */
	private static function hash( array $mail ): string {
		// wp_mail() turns a "to" string into an array before firing its events, so compare normalised lists.
		$recipients = array();
		foreach ( (array) ( $mail['to'] ?? '' ) as $item ) {
			foreach ( explode( ',', (string) $item ) as $part ) {
				$part = trim( $part );
				if ( '' !== $part ) {
					$recipients[] = $part;
				}
			}
		}

		return md5( wp_json_encode( array( $recipients, (string) ( $mail['subject'] ?? '' ) ) ) );
	}

	/**
	 * @param mixed $mail_data Mail data (to, subject, ...). Only "to" is read.
	 */
	private function log( string $status, $mail_data, string $error ): void {
		try {
			$context = $this->context;

			// Only trust the context if it belongs to this very email.
			if ( ! $context || ! is_array( $mail_data ) || self::hash( $mail_data ) !== $context['hash'] ) {
				$context = null;
			}

			$context   = $context ?: array(
				'source' => array(
					'key'   => 'unknown',
					'label' => __( 'Unknown', 'ecom-email-health-check' ),
				),
				'wc'     => null,
			);
			$recipient = self::mask_recipient( is_array( $mail_data ) && isset( $mail_data['to'] ) ? $mail_data['to'] : '' );

			EmailLog::insert(
				array(
					'status'           => $status,
					'source_key'       => $context['source']['key'],
					'source_label'     => $context['source']['label'],
					'email_type'       => $context['wc'] ? $context['wc']['id'] : '',
					'email_type_label' => $context['wc'] ? $context['wc']['label'] : '',
					'recipient'        => $recipient['masked'],
					'recipient_domain' => $recipient['domain'],
					'error'            => self::truncate( self::mask_addresses( $error ), 500 ),
				)
			);
		} catch ( \Throwable $e ) {
			// Logging must never break sending.
			unset( $e );
		}

		$this->context = null;
	}

	/**
	 * Masks the first recipient (j***@gmail.com) and counts the rest.
	 *
	 * @param mixed $to String or array of recipients, possibly "Name <a@b.com>".
	 * @return array{masked: string, domain: string}
	 */
	public static function mask_recipient( $to ): array {
		$addresses = array();

		foreach ( (array) $to as $item ) {
			foreach ( explode( ',', (string) $item ) as $part ) {
				$part = trim( $part );
				if ( preg_match( '/<([^>]+)>/', $part, $match ) ) {
					$part = trim( $match[1] );
				}
				if ( is_email( $part ) ) {
					$addresses[] = $part;
				}
			}
		}

		if ( ! $addresses ) {
			return array(
				'masked' => '',
				'domain' => '',
			);
		}

		$masked = self::mask_address( $addresses[0] );
		$extra  = count( $addresses ) - 1;
		$domain = strtolower( (string) substr( strrchr( $addresses[0], '@' ), 1 ) );

		return array(
			'masked' => $extra > 0 ? $masked . ' +' . $extra : $masked,
			'domain' => self::truncate( $domain, 190 ),
		);
	}

	/**
	 * j***@example.com; very short local parts are hidden completely.
	 */
	private static function mask_address( string $address ): string {
		$at    = strrpos( $address, '@' );
		$local = false === $at ? $address : substr( $address, 0, $at );
		$host  = false === $at ? '' : substr( $address, $at );

		return ( strlen( $local ) > 2 ? substr( $local, 0, 1 ) : '' ) . '***' . $host;
	}

	/**
	 * Masks every email address inside free text, such as a mailer error message.
	 */
	public static function mask_addresses( string $text ): string {
		return (string) preg_replace_callback(
			'/[^\s<>"\',;:()\[\]]+@[^\s<>"\',;:()\[\]]+/',
			static function ( $match ) {
				return self::mask_address( $match[0] );
			},
			$text
		);
	}

	private static function truncate( string $text, int $length ): string {
		return function_exists( 'mb_substr' ) ? mb_substr( $text, 0, $length ) : substr( $text, 0, $length );
	}

	public function add_privacy_policy_content(): void {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		wp_add_privacy_policy_content(
			'eCommerce Email Health Check',
			wp_kses_post(
				wpautop(
					__( 'This plugin keeps a short log of the emails your site sends (time, which plugin sent it, WooCommerce email type, whether it was accepted or failed, the recipient\'s email domain and a partly hidden address such as j***@example.com). It does not store message contents or subjects, and email addresses, including those in error messages, are partly hidden. Log entries are deleted automatically after a short time (7 days by default).', 'ecom-email-health-check' )
				)
			)
		);
	}
}
