<?php
/**
 * "Emails for this order" panel on the WooCommerce order screen.
 *
 * Read-only: it lists what the email log recorded for the order. Only shown to
 * administrators, and only while the email log is on.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Admin;

use CodeSir\EmailHealthCheck\Log\EmailLog;
use CodeSir\EmailHealthCheck\Log\EmailLogger;
use CodeSir\EmailHealthCheck\Log\LogSettings;

defined( 'ABSPATH' ) || exit;

class OrderEmailsPanel {

	const ID = 'ecehc_order_emails';

	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
	}

	/**
	 * Screen ids of the order edit screen: HPOS and legacy post storage.
	 *
	 * @return string[]
	 */
	public static function screens(): array {
		$screens = array( 'shop_order' );

		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screens[] = (string) wc_get_page_screen_id( 'shop-order' );
		}

		return array_values( array_unique( array_filter( $screens ) ) );
	}

	public function add_meta_box(): void {
		if ( ! function_exists( 'wc_get_order' ) || ! current_user_can( 'manage_options' ) || ! EmailLogger::is_enabled() ) {
			return;
		}

		add_meta_box(
			self::ID,
			__( 'Emails for this order', 'ecom-email-health-check' ),
			array( $this, 'render' ),
			self::screens(),
			'normal',
			'default'
		);
	}

	/**
	 * @param mixed $object WC_Order (HPOS) or WP_Post (legacy storage).
	 */
	public function render( $object ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$order_id = 0;
		if ( is_object( $object ) && method_exists( $object, 'get_id' ) ) {
			$order_id = (int) $object->get_id();
		} elseif ( is_object( $object ) && isset( $object->ID ) ) {
			$order_id = (int) $object->ID;
		}

		$rows = EmailLog::for_order( $order_id );

		/** This filter is documented in includes/Log/EmailLog.php */
		$retention_days = max( 1, (int) apply_filters( 'ecehc_log_retention_days', LogSettings::retention_days() ) );
		$log_url        = add_query_arg(
			array(
				'page' => AdminPage::MENU_SLUG,
				'tab'  => 'log',
			),
			admin_url( 'admin.php' )
		);

		include ECEHC_PLUGIN_PATH . 'views/order-emails.php';
	}
}
