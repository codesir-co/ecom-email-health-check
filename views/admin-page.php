<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Email Health Check', 'ecom-email-health-check' ); ?></h1>
    <p class="description">
		<?php esc_html_e( 'Run a quick diagnostic to check the health of your store\'s email delivery system.', 'ecom-email-health-check' ); ?>
    </p>

    <?php $ecehc_active_tab = 'report'; include ECEHC_PLUGIN_PATH . 'views/tabs.php'; ?>
    <div id="ecehc-checklist-slot"><?php include ECEHC_PLUGIN_PATH . 'views/checklist.php'; ?></div>

    <div class="ecehc-grid">
        <div class="ecehc-main-col">
            <?php if ( null === $results ) : ?>
                <?php
                $ecehc_sync_url = add_query_arg(
                    array(
                        'page'       => \CodeSir\EmailHealthCheck\Admin\AdminPage::MENU_SLUG,
                        'ecehc_sync' => 1,
                    ),
                    admin_url( 'admin.php' )
                );
                // The checks run in the background (DNS lookups can be slow), so the page opens at once.
                ?>
                <div id="ecehc-main-report" class="card ecehc-report-loading"
                     data-ecehc-ajax="1"
                     data-nonce="<?php echo esc_attr( wp_create_nonce( \CodeSir\EmailHealthCheck\Admin\ReportLoader::NONCE_ACTION ) ); ?>"
                     data-error="<?php esc_attr_e( 'The checks could not be loaded.', 'ecom-email-health-check' ); ?>"
                     data-retry="<?php esc_attr_e( 'Run them again on the server', 'ecom-email-health-check' ); ?>"
                     data-done="<?php esc_attr_e( 'The email checks have finished.', 'ecom-email-health-check' ); ?>"
                     data-sync-url="<?php echo esc_url( $ecehc_sync_url ); ?>">
                    <h2><?php esc_html_e( 'Email Health Report', 'ecom-email-health-check' ); ?></h2>
                    <p class="ecehc-report-status" role="status" aria-live="polite">
                        <span class="spinner is-active" aria-hidden="true"></span>
                        <?php esc_html_e( 'Running the checks. DNS lookups can take a few seconds.', 'ecom-email-health-check' ); ?>
                    </p>
                    <noscript>
                        <p><a href="<?php echo esc_url( $ecehc_sync_url ); ?>"><?php esc_html_e( 'Run the checks without JavaScript', 'ecom-email-health-check' ); ?></a></p>
                    </noscript>
                </div>
            <?php else : ?>
                <?php include ECEHC_PLUGIN_PATH . 'views/report-card.php'; ?>
            <?php endif; ?>
        </div>

        <div class="ecehc-sidebar-col">
            <div class="card ecehc-card-padded" id="ecehc-test-email">
                <h2 class="ecehc-card-title"><?php esc_html_e( 'Test Your Email Sending', 'ecom-email-health-check' ); ?></h2>
                <p>
					<?php esc_html_e( 'Send a test email through your real mail setup to confirm that it works. Use your own address, or any inbox you want to test (for example a Gmail or Outlook address).', 'ecom-email-health-check' ); ?>
                </p>
                <form method="post" class="ecehc-test-form">
					<?php wp_nonce_field( 'ecehc_send_test_email' ); ?>
                    <p>
                        <label for="ecehc-test-to"><?php esc_html_e( 'Send to', 'ecom-email-health-check' ); ?></label><br>
                        <input type="email" id="ecehc-test-to" name="ecehc_test_to" class="regular-text" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" required>
                    </p>
                    <p>
                        <label for="ecehc-test-subject"><?php esc_html_e( 'Subject (optional)', 'ecom-email-health-check' ); ?></label><br>
                        <input type="text" id="ecehc-test-subject" name="ecehc_test_subject" class="regular-text" maxlength="150" placeholder="<?php echo esc_attr__( 'Email Health Check: Test Email', 'ecom-email-health-check' ); ?>">
                    </p>
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