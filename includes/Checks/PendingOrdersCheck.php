<?php
/**
 * Flags a pile of unpaid orders, which look like a missing-email problem.
 *
 * WooCommerce sends no order emails for orders in "Pending payment", so a
 * failing payment gateway can be mistaken for an email delivery problem. Only
 * order counts are read, never customer data.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

defined( 'ABSPATH' ) || exit;

class PendingOrdersCheck implements CheckInterface {

	/** How far back orders are looked at. */
	const WINDOW = 7 * DAY_IN_SECONDS;

	/** Orders younger than this are ignored: customers are still checking out. */
	const GRACE = HOUR_IN_SECONDS;

	public function get_id(): string {
		return 'pending_orders';
	}

	public function get_label(): string {
		return __( 'Unpaid Orders', 'ecom-email-health-check' );
	}

	public function run(): Result {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return Result::unknown( __( 'WooCommerce orders could not be read.', 'ecom-email-health-check' ) );
		}

		$now = time();

		try {
			$range   = ( $now - self::WINDOW ) . '...' . ( $now - self::GRACE );
			$pending = $this->count_orders( array( 'pending', 'failed' ), $range );
			$paid    = $this->count_orders( array( 'processing', 'completed', 'on-hold' ), $range );
		} catch ( \Throwable $e ) {
			return Result::unknown( __( 'WooCommerce orders could not be read.', 'ecom-email-health-check' ) );
		}

		/**
		 * Filters how many old unpaid orders (and at least as many as orders that went through) trigger the warning.
		 *
		 * @param int $minimum Minimum number of unpaid orders. Default 5.
		 */
		$minimum = max( 1, (int) apply_filters( 'ecehc_pending_orders_minimum', 5 ) );

		if ( $pending >= $minimum && $pending >= $paid ) {
			return Result::warning(
				sprintf(
					/* translators: 1: number of unpaid or failed orders, 2: number of orders that went through */
					__( 'In the last 7 days, %1$d orders are still "Pending payment" or "Failed" against %2$d that went through (processing, completed or on hold). WooCommerce sends no order emails for unpaid orders, so customers may look like they are missing emails when the real cause is a payment problem. Check your payment gateway before changing your email setup.', 'ecom-email-health-check' ),
					$pending,
					$paid
				)
			);
		}

		return new Result(
			true,
			sprintf(
				/* translators: 1: number of unpaid or failed orders, 2: number of orders that went through */
				__( 'In the last 7 days, %1$d orders are "Pending payment" or "Failed" and %2$d went through (processing, completed or on hold). WooCommerce sends no order emails for unpaid orders, so a few are normal (abandoned checkouts).', 'ecom-email-health-check' ),
				$pending,
				$paid
			)
		);
	}

	/**
	 * Counts orders in the given statuses, with HPOS and legacy storage alike.
	 *
	 * @param string[] $statuses     Statuses without the "wc-" prefix.
	 * @param string   $date_created WooCommerce date query, e.g. "123...456".
	 */
	private function count_orders( array $statuses, string $date_created ): int {
		$result = wc_get_orders(
			array(
				'status'       => $statuses,
				'type'         => 'shop_order',
				'date_created' => $date_created,
				'limit'        => 1,
				'paginate'     => true,
				'return'       => 'ids',
			)
		);

		return is_object( $result ) && isset( $result->total ) ? (int) $result->total : 0;
	}
}
