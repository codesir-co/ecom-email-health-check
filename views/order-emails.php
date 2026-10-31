<?php
/**
 * "Emails for this order" panel.
 *
 * Expects: $rows, $retention_days, $log_url.
 *
 * @package CodeSir\EmailHealthCheck
 */

use CodeSir\EmailHealthCheck\Log\EmailLog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php if ( ! $rows ) : ?>
	<p>
		<?php
		echo esc_html(
			sprintf(
				/* translators: %d: number of days log entries are kept */
				_n(
					'No emails are logged for this order. Only emails sent after this feature was added are recorded, and entries are kept for %d day.',
					'No emails are logged for this order. Only emails sent after this feature was added are recorded, and entries are kept for %d days.',
					$retention_days,
					'ecom-email-health-check'
				),
				$retention_days
			)
		);
		?>
	</p>
<?php else : ?>
	<table class="widefat striped">
		<caption class="screen-reader-text"><?php esc_html_e( 'Emails logged for this order, newest first', 'ecom-email-health-check' ); ?></caption>
		<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'Time', 'ecom-email-health-check' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Email', 'ecom-email-health-check' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Recipient', 'ecom-email-health-check' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Status', 'ecom-email-health-check' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $rows as $ecehc_row ) : ?>
			<tr>
				<td><?php echo esc_html( get_date_from_gmt( $ecehc_row['created_at'], 'Y-m-d H:i:s' ) ); ?></td>
				<td><?php echo esc_html( '' !== $ecehc_row['email_type_label'] ? $ecehc_row['email_type_label'] : '-' ); ?></td>
				<td><?php echo esc_html( '' !== $ecehc_row['recipient'] ? $ecehc_row['recipient'] : '-' ); ?></td>
				<td>
					<?php if ( EmailLog::STATUS_FAILED === $ecehc_row['status'] ) : ?>
						<strong><span aria-hidden="true">&#10006;</span> <?php esc_html_e( 'Failed', 'ecom-email-health-check' ); ?></strong>
						<?php if ( '' !== $ecehc_row['error'] ) : ?>
							<br><?php echo esc_html( $ecehc_row['error'] ); ?>
						<?php endif; ?>
					<?php else : ?>
						<strong><span aria-hidden="true">&#10004;</span> <?php esc_html_e( 'Accepted', 'ecom-email-health-check' ); ?></strong>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php if ( count( $rows ) >= EmailLog::ORDER_LIMIT ) : ?>
	<p class="description">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %d: number of emails shown at most */
				__( 'The newest %d emails are shown.', 'ecom-email-health-check' ),
				EmailLog::ORDER_LIMIT
			)
		);
		?>
	</p>
	<?php endif; ?>
<?php endif; ?>
<p><a href="<?php echo esc_url( $log_url ); ?>"><?php esc_html_e( 'View in Email Log', 'ecom-email-health-check' ); ?></a></p>
