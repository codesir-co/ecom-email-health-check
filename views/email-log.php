<?php
/**
 * Email Log tab.
 *
 * Expects: $active_tab, $supported, $settings, $notice, $sources, $types, $filters, $result, $paged, $stats24, $stats7.
 *
 * @package CodeSir\EmailHealthCheck
 */

use CodeSir\EmailHealthCheck\Log\EmailLog;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ecehc_base_url = add_query_arg(
	array(
		'page' => \CodeSir\EmailHealthCheck\Admin\AdminPage::MENU_SLUG,
		'tab'  => 'log',
	),
	admin_url( 'admin.php' )
);
?>
<div class="wrap">
	<h1><?php esc_html_e( 'eCommerce Email Health Check', 'ecom-email-health-check' ); ?></h1>

	<?php include ECEHC_PLUGIN_PATH . 'views/tabs.php'; ?>

	<?php if ( 'saved' === $notice ) : ?>
		<div class="notice notice-success inline"><p><?php esc_html_e( 'Settings saved.', 'ecom-email-health-check' ); ?></p></div>
	<?php elseif ( 'cleared' === $notice ) : ?>
		<div class="notice notice-success inline"><p><?php esc_html_e( 'The email log was cleared.', 'ecom-email-health-check' ); ?></p></div>
	<?php endif; ?>

	<?php if ( $supported && ! $settings['enabled'] ) : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'Email logging is turned off, so new emails are not recorded. Existing entries stay until they expire or you clear the log.', 'ecom-email-health-check' ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $supported ) : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'The email log needs WordPress 5.9 or newer, or has been turned off by a filter, so nothing is recorded.', 'ecom-email-health-check' ); ?></p></div>
	<?php else : ?>

		<p class="description ecehc-log-note">
			<?php esc_html_e( '"Accepted" means WordPress handed the email to your mail system without an error. It does not prove the email was delivered. Entries are kept for a short time (7 days by default), and only a partly hidden recipient address is stored, never the message.', 'ecom-email-health-check' ); ?>
		</p>

		<div class="ecehc-stats">
			<?php
			foreach ( array(
				array( __( 'Last 24 hours', 'ecom-email-health-check' ), $stats24 ),
				array( __( 'Last 7 days', 'ecom-email-health-check' ), $stats7 ),
			) as $ecehc_card ) :
				list( $ecehc_period, $ecehc_stats ) = $ecehc_card;
				$ecehc_rate                         = EmailLog::failure_rate( $ecehc_stats );
				?>
				<div class="card ecehc-stat-card">
					<h2><?php echo esc_html( $ecehc_period ); ?></h2>
					<p class="ecehc-stat-numbers">
						<span class="ecehc-status ecehc-status-pass"><?php echo esc_html( number_format_i18n( $ecehc_stats['sent'] ) ); ?></span> <?php esc_html_e( 'accepted', 'ecom-email-health-check' ); ?>
						&middot;
						<span class="ecehc-status ecehc-status-fail"><?php echo esc_html( number_format_i18n( $ecehc_stats['failed'] ) ); ?></span> <?php esc_html_e( 'failed', 'ecom-email-health-check' ); ?>
						&middot;
						<?php
						/* translators: %d: failure rate percentage */
						echo esc_html( sprintf( __( '%d%% failure rate', 'ecom-email-health-check' ), $ecehc_rate ) );
						?>
					</p>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( $stats7['sources'] ) : ?>
			<div class="ecehc-stats">
				<div class="card ecehc-stat-card">
					<h2><?php esc_html_e( 'By source (last 7 days)', 'ecom-email-health-check' ); ?></h2>
					<table class="widefat striped">
						<thead><tr><th><?php esc_html_e( 'Source', 'ecom-email-health-check' ); ?></th><th><?php esc_html_e( 'Accepted', 'ecom-email-health-check' ); ?></th><th><?php esc_html_e( 'Failed', 'ecom-email-health-check' ); ?></th></tr></thead>
						<tbody>
						<?php foreach ( $stats7['sources'] as $ecehc_source_key => $ecehc_row ) : ?>
							<tr>
								<td><a href="<?php echo esc_url( add_query_arg( 'source', $ecehc_source_key, $ecehc_base_url ) ); ?>"><?php echo esc_html( $ecehc_row['label'] ); ?></a></td>
								<td><?php echo esc_html( number_format_i18n( $ecehc_row['sent'] ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( $ecehc_row['failed'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<?php if ( $stats7['types'] ) : ?>
					<div class="card ecehc-stat-card">
						<h2><?php esc_html_e( 'WooCommerce emails (last 7 days)', 'ecom-email-health-check' ); ?></h2>
						<table class="widefat striped">
							<thead><tr><th><?php esc_html_e( 'Email', 'ecom-email-health-check' ); ?></th><th><?php esc_html_e( 'Accepted', 'ecom-email-health-check' ); ?></th><th><?php esc_html_e( 'Failed', 'ecom-email-health-check' ); ?></th></tr></thead>
							<tbody>
							<?php foreach ( $stats7['types'] as $ecehc_type_key => $ecehc_row ) : ?>
								<tr>
									<td><a href="<?php echo esc_url( add_query_arg( 'type', $ecehc_type_key, $ecehc_base_url ) ); ?>"><?php echo esc_html( $ecehc_row['label'] ); ?></a></td>
									<td><?php echo esc_html( number_format_i18n( $ecehc_row['sent'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( $ecehc_row['failed'] ) ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $stats7['last_failure'] ) : ?>
			<p class="ecehc-last-failure">
				<strong><?php esc_html_e( 'Last failure:', 'ecom-email-health-check' ); ?></strong>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: date and time, 2: source name, 3: error message */
						__( '%1$s, %2$s: %3$s', 'ecom-email-health-check' ),
						get_date_from_gmt( $stats7['last_failure']['created_at'], 'Y-m-d H:i' ),
						$stats7['last_failure']['source_label'],
						'' !== $stats7['last_failure']['error'] ? $stats7['last_failure']['error'] : __( 'no error message', 'ecom-email-health-check' )
					)
				);
				?>
			</p>
		<?php endif; ?>

		<form method="get" class="ecehc-log-filters">
			<input type="hidden" name="page" value="<?php echo esc_attr( \CodeSir\EmailHealthCheck\Admin\AdminPage::MENU_SLUG ); ?>">
			<input type="hidden" name="tab" value="log">

			<label class="screen-reader-text" for="ecehc-filter-source"><?php esc_html_e( 'Source', 'ecom-email-health-check' ); ?></label>
			<select name="source" id="ecehc-filter-source">
				<option value=""><?php esc_html_e( 'All sources', 'ecom-email-health-check' ); ?></option>
				<?php foreach ( $sources as $ecehc_key => $ecehc_label ) : ?>
					<option value="<?php echo esc_attr( $ecehc_key ); ?>" <?php selected( $filters['source'], $ecehc_key ); ?>><?php echo esc_html( $ecehc_label ); ?></option>
				<?php endforeach; ?>
			</select>

			<label class="screen-reader-text" for="ecehc-filter-status"><?php esc_html_e( 'Status', 'ecom-email-health-check' ); ?></label>
			<select name="status" id="ecehc-filter-status">
				<option value=""><?php esc_html_e( 'Any status', 'ecom-email-health-check' ); ?></option>
				<option value="<?php echo esc_attr( EmailLog::STATUS_SENT ); ?>" <?php selected( $filters['status'], EmailLog::STATUS_SENT ); ?>><?php esc_html_e( 'Accepted', 'ecom-email-health-check' ); ?></option>
				<option value="<?php echo esc_attr( EmailLog::STATUS_FAILED ); ?>" <?php selected( $filters['status'], EmailLog::STATUS_FAILED ); ?>><?php esc_html_e( 'Failed', 'ecom-email-health-check' ); ?></option>
			</select>

			<?php if ( $types ) : ?>
				<label class="screen-reader-text" for="ecehc-filter-type"><?php esc_html_e( 'WooCommerce email type', 'ecom-email-health-check' ); ?></label>
				<select name="type" id="ecehc-filter-type">
					<option value=""><?php esc_html_e( 'Any WooCommerce email', 'ecom-email-health-check' ); ?></option>
					<?php foreach ( $types as $ecehc_key => $ecehc_label ) : ?>
						<option value="<?php echo esc_attr( $ecehc_key ); ?>" <?php selected( $filters['type'], $ecehc_key ); ?>><?php echo esc_html( $ecehc_label ); ?></option>
					<?php endforeach; ?>
				</select>
			<?php endif; ?>

			<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'ecom-email-health-check' ); ?>">
			<?php if ( array_filter( $filters ) ) : ?>
				<a href="<?php echo esc_url( $ecehc_base_url ); ?>"><?php esc_html_e( 'Reset', 'ecom-email-health-check' ); ?></a>
			<?php endif; ?>
		</form>

		<table class="widefat striped ecehc-log-table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Time', 'ecom-email-health-check' ); ?></th>
				<th><?php esc_html_e( 'Source', 'ecom-email-health-check' ); ?></th>
				<th><?php esc_html_e( 'WooCommerce email', 'ecom-email-health-check' ); ?></th>
				<th><?php esc_html_e( 'Recipient', 'ecom-email-health-check' ); ?></th>
				<th><?php esc_html_e( 'Status', 'ecom-email-health-check' ); ?></th>
				<th><?php esc_html_e( 'Error', 'ecom-email-health-check' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if ( ! $result['rows'] ) : ?>
				<tr>
					<td colspan="6">
						<?php
						echo esc_html(
							array_filter( $filters )
								? __( 'No emails match these filters.', 'ecom-email-health-check' )
								: __( 'No emails have been logged yet. The log starts recording as soon as your site sends its next email.', 'ecom-email-health-check' )
						);
						?>
					</td>
				</tr>
			<?php endif; ?>
			<?php foreach ( $result['rows'] as $ecehc_row ) : ?>
				<tr>
					<td><?php echo esc_html( get_date_from_gmt( $ecehc_row['created_at'], 'Y-m-d H:i:s' ) ); ?></td>
					<td><?php echo esc_html( $ecehc_row['source_label'] ); ?></td>
					<td><?php echo esc_html( '' !== $ecehc_row['email_type_label'] ? $ecehc_row['email_type_label'] : '-' ); ?></td>
					<td><?php echo esc_html( '' !== $ecehc_row['recipient'] ? $ecehc_row['recipient'] : '-' ); ?></td>
					<td>
						<?php if ( EmailLog::STATUS_FAILED === $ecehc_row['status'] ) : ?>
							<span class="ecehc-status ecehc-status-fail">&#10006; <?php esc_html_e( 'Failed', 'ecom-email-health-check' ); ?></span>
						<?php else : ?>
							<span class="ecehc-status ecehc-status-pass">&#10004; <?php esc_html_e( 'Accepted', 'ecom-email-health-check' ); ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $ecehc_row['error'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $result['pages'] > 1 ) : ?>
			<div class="tablenav">
				<div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%', add_query_arg( array_filter( $filters ), $ecehc_base_url ) ),
								'format'    => '',
								'current'   => $paged,
								'total'     => $result['pages'],
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
							)
						)
					);
					?>
				</div>
			</div>
		<?php endif; ?>

		<p class="description">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: number of emails shown at most */
					__( 'The newest %d emails are shown.', 'ecom-email-health-check' ),
					EmailLog::VIEW_LIMIT
				)
			);
			?>
		</p>

		<div class="card ecehc-settings-card">
			<h2><?php esc_html_e( 'Log settings', 'ecom-email-health-check' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( \CodeSir\EmailHealthCheck\Admin\LogSettingsHandler::NONCE_ACTION, \CodeSir\EmailHealthCheck\Admin\LogSettingsHandler::NONCE_FIELD ); ?>
				<p>
					<label>
						<input type="checkbox" name="ecehc_log_enabled" value="1" <?php checked( $settings['enabled'] ); ?>>
						<?php esc_html_e( 'Record the emails this site sends', 'ecom-email-health-check' ); ?>
					</label>
				</p>
				<p>
					<label for="ecehc-log-retention"><?php esc_html_e( 'Keep entries for', 'ecom-email-health-check' ); ?></label>
					<select name="ecehc_log_retention" id="ecehc-log-retention">
						<?php foreach ( \CodeSir\EmailHealthCheck\Log\LogSettings::RETENTION_CHOICES as $ecehc_days ) : ?>
							<option value="<?php echo esc_attr( (string) $ecehc_days ); ?>" <?php selected( $settings['retention_days'], $ecehc_days ); ?>>
								<?php
								echo esc_html(
									sprintf(
										/* translators: %d: number of days */
										_n( '%d day', '%d days', $ecehc_days, 'ecom-email-health-check' ),
										$ecehc_days
									)
								);
								?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p><input type="submit" name="ecehc_save_log_settings" class="button button-primary" value="<?php esc_attr_e( 'Save settings', 'ecom-email-health-check' ); ?>"></p>
			</form>

			<form method="post" class="ecehc-clear-form">
				<?php // A plain hidden field, so the page has no duplicate element IDs. ?>
				<input type="hidden" name="<?php echo esc_attr( \CodeSir\EmailHealthCheck\Admin\LogSettingsHandler::NONCE_FIELD ); ?>" value="<?php echo esc_attr( wp_create_nonce( \CodeSir\EmailHealthCheck\Admin\LogSettingsHandler::NONCE_ACTION ) ); ?>">
				<input type="submit" name="ecehc_clear_log" class="button" value="<?php esc_attr_e( 'Clear log now', 'ecom-email-health-check' ); ?>" data-ecehc-confirm="<?php esc_attr_e( 'Delete all logged emails? This cannot be undone.', 'ecom-email-health-check' ); ?>">
			</form>
		</div>
	<?php endif; ?>
</div>
