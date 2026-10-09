<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="wrap">
    <h1><?php esc_html_e( 'eCommerce Email Health Check', 'ecom-email-health-check' ); ?></h1>
    <p class="description">
		<?php esc_html_e( 'Run a quick diagnostic to check the health of your store\'s email delivery system.', 'ecom-email-health-check' ); ?>
    </p>

    <?php $active_tab = 'report'; include ECEHC_PLUGIN_PATH . 'views/tabs.php'; ?>
    <?php include ECEHC_PLUGIN_PATH . 'views/checklist.php'; ?>

    <div class="ecehc-grid">
        <div class="ecehc-main-col">
            <div id="ecehc-main-report" class="card">
                <h2><?php esc_html_e( 'Email Health Report', 'ecom-email-health-check' ); ?></h2>
                <p>
					<?php esc_html_e( 'The following checks will help you identify common issues that prevent emails from being delivered successfully.', 'ecom-email-health-check' ); ?>
                </p>

                <table class="widefat fixed" cellspacing="0">
                    <thead>
                    <tr>
                        <th class="manage-column column-columnname" scope="col"><?php esc_html_e( 'Check', 'ecom-email-health-check' ); ?></th>
                        <th class="manage-column column-columnname" scope="col"><?php esc_html_e( 'Status', 'ecom-email-health-check' ); ?></th>
                        <th class="manage-column column-columnname" scope="col"><?php esc_html_e( 'Message', 'ecom-email-health-check' ); ?></th>
                    </tr>
                    </thead>
                    <tbody>
					<?php foreach ( $results as $ecehc_check_id => $result ) : ?>
                        <tr id="ecehc-check-<?php echo esc_attr( (string) $ecehc_check_id ); ?>">
                            <td class="column-columnname"><strong><?php echo esc_html( $result['label'] ); ?></strong></td>
                            <td class="column-columnname">
								<?php if ( 'pass' === $result['status'] ) : ?>
                                    <span class="ecehc-status ecehc-status-pass">&#10004; <?php esc_html_e( 'Pass', 'ecom-email-health-check' ); ?></span>
								<?php elseif ( 'warning' === $result['status'] ) : ?>
                                    <span class="ecehc-status ecehc-status-warning">&#9888; <?php esc_html_e( 'Warning', 'ecom-email-health-check' ); ?></span>
								<?php elseif ( 'unknown' === $result['status'] ) : ?>
                                    <span class="ecehc-status ecehc-status-unknown">&#63; <?php esc_html_e( 'Not checked', 'ecom-email-health-check' ); ?></span>
								<?php else : ?>
                                    <span class="ecehc-status ecehc-status-fail">&#10006; <?php esc_html_e( 'Fail', 'ecom-email-health-check' ); ?></span>
								<?php endif; ?>
                            </td>
                            <td class="column-columnname">
								<?php echo esc_html( $result['message'] ); ?>
								<?php if ( ! empty( $result['guidance'] ) ) : ?>
                                    <details class="ecehc-guidance">
                                        <summary><?php esc_html_e( 'How to fix this', 'ecom-email-health-check' ); ?></summary>
                                        <p><?php echo esc_html( $result['guidance']['text'] ); ?></p>
										<?php foreach ( $result['guidance']['records'] as $record ) : ?>
                                            <div class="ecehc-record">
                                                <span class="ecehc-record-meta"><?php echo esc_html( $record['type'] ); ?> &middot; <?php echo esc_html( $record['host'] ); ?></span>
                                                <code><?php echo esc_html( $record['value'] ); ?></code>
                                                <button type="button" class="button button-small ecehc-copy" data-ecehc-copy="<?php echo esc_attr( $record['value'] ); ?>" data-copied="<?php esc_attr_e( 'Copied', 'ecom-email-health-check' ); ?>"><?php esc_html_e( 'Copy', 'ecom-email-health-check' ); ?></button>
                                            </div>
										<?php endforeach; ?>
										<?php if ( ! empty( $result['guidance']['docs'] ) ) : ?>
                                            <p><a href="<?php echo esc_url( $result['guidance']['docs'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Official setup guide', 'ecom-email-health-check' ); ?></a></p>
										<?php endif; ?>
                                    </details>
								<?php endif; ?>
                            </td>
                        </tr>
					<?php endforeach; ?>
                    </tbody>
                </table>

                <form method="post" class="ecehc-recheck-form">
					<?php wp_nonce_field( 'ecehc_recheck', '_ecehc_recheck_nonce' ); ?>
                    <input type="submit" name="ecehc_recheck" class="button button-secondary" value="<?php esc_attr_e( 'Re-check', 'ecom-email-health-check' ); ?>">
                    <span class="description"><?php esc_html_e( 'The mail service detection and blacklist lookups are saved for up to 12 hours. Re-check to refresh them.', 'ecom-email-health-check' ); ?></span>
                </form>

                <?php $ecehc_report = \CodeSir\EmailHealthCheck\Support\SupportReport::build( $results ); ?>
                <div class="ecehc-support-report">
                    <button type="button" class="button ecehc-copy" data-ecehc-copy="<?php echo esc_attr( $ecehc_report ); ?>" data-copied="<?php esc_attr_e( 'Copied', 'ecom-email-health-check' ); ?>"><?php esc_html_e( 'Copy support report', 'ecom-email-health-check' ); ?></button>
                    <span class="description"><?php esc_html_e( 'A plain-text summary to paste into a support ticket. It has your site and mail domains, versions, check results, the plugin that handles your email and your server IP if it is blacklisted, but no passwords, message contents or full email addresses.', 'ecom-email-health-check' ); ?></span>
                    <details>
                        <summary><?php esc_html_e( 'Preview the report', 'ecom-email-health-check' ); ?></summary>
                        <label class="screen-reader-text" for="ecehc-support-report-text"><?php esc_html_e( 'Support report', 'ecom-email-health-check' ); ?></label>
                        <textarea id="ecehc-support-report-text" class="large-text code" rows="14" readonly><?php echo esc_textarea( $ecehc_report ); ?></textarea>
                    </details>
                </div>
            </div>
        </div>

        <div class="ecehc-sidebar-col">
            <div class="card ecehc-card-padded" id="ecehc-test-email">
                <h2 class="ecehc-card-title"><?php esc_html_e( 'Test Your Email Sending', 'ecom-email-health-check' ); ?></h2>
                <p>
					<?php esc_html_e( 'Click the button below to send a test email to your admin email address (', 'ecom-email-health-check' ); ?>
                    <code><?php echo esc_html( get_option('admin_email') ); ?></code>
					<?php esc_html_e( ') to confirm basic functionality.', 'ecom-email-health-check' ); ?>
                </p>
                <form method="post">
					<?php wp_nonce_field( 'ecehc_send_test_email' ); ?>
                    <input type="submit" name="ecehc_send_test_email" class="button button-secondary" value="<?php esc_attr_e( 'Send Test Email', 'ecom-email-health-check' ); ?>">
                </form>
            </div>

            <?php if ( class_exists( 'WooCommerce' ) && current_user_can( 'manage_woocommerce' ) ) : ?>
                <div class="card ecehc-card-padded">
                    <h2 class="ecehc-card-title"><?php esc_html_e( 'Test Your WooCommerce Emails', 'ecom-email-health-check' ); ?></h2>
                    <p>
						<?php esc_html_e( 'Recent WooCommerce versions can preview each email and send you a test copy, using your real From address and mail setup. Open it, pick an email and use its preview to send yourself a test.', 'ecom-email-health-check' ); ?>
                    </p>
                    <p><a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=email' ) ); ?>"><?php esc_html_e( 'Open WooCommerce email settings', 'ecom-email-health-check' ); ?></a></p>
                    <?php if ( \CodeSir\EmailHealthCheck\Log\EmailLogger::is_enabled() ) : ?>
                        <p class="description"><?php esc_html_e( 'The test email then shows up in the Email Log tab as a WooCommerce email.', 'ecom-email-health-check' ); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>