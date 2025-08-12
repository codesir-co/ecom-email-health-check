<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>
<div class="wrap">
    <h1><?php esc_html_e( 'eCommerce Email Health Check', 'ecom-email-health-check' ); ?></h1>
    <p class="description">
		<?php esc_html_e( 'Run a quick diagnostic to check the health of your store\'s email delivery system.', 'ecom-email-health-check' ); ?>
    </p>

    <hr>

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
					<?php foreach ( $results as $result ) : ?>
                        <tr>
                            <td class="column-columnname"><strong><?php echo esc_html( $result['label'] ); ?></strong></td>
                            <td class="column-columnname">
								<?php if ( $result['status'] ) : ?>
                                    <span style="color: green; font-weight: bold;">&#10004; <?php esc_html_e( 'Pass', 'ecom-email-health-check' ); ?></span>
								<?php else : ?>
                                    <span style="color: red; font-weight: bold;">&#10006; <?php esc_html_e( 'Fail', 'ecom-email-health-check' ); ?></span>
								<?php endif; ?>
                            </td>
                            <td class="column-columnname">
								<?php echo esc_html( $result['message'] ); ?>
                            </td>
                        </tr>
					<?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ecehc-sidebar-col">
            <div class="card ecehc-card-padded">
                <h2 style="margin-top: 0;"><?php esc_html_e( 'Test Your Email Sending', 'ecom-email-health-check' ); ?></h2>
                <p>
					<?php esc_html_e( 'Click the button below to send a test email to your admin email address (', 'ecom-email-health-check' ); ?>
                    <code><?php echo esc_html( get_option('admin_email') ); ?></code>
					<?php esc_html_e( ') to confirm basic functionality.', 'ecom-email-health-check' ); ?>
                </p>
                <form method="post">
					<?php wp_nonce_field( 'ecehc_send_test_email' ); ?>
                    <input type="submit" name="ecehc_send_test_email" class="button button-secondary" value="<?php esc_html_e( 'Send Test Email', 'ecom-email-health-check' ); ?>">
                </form>
            </div>
        </div>
    </div>
</div>