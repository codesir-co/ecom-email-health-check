<div class="wrap">
    <h1><?php _e( 'WooCommerce Email Health Check', 'wcehc' ); ?></h1>
    <p class="description">
		<?php _e( 'Run a quick diagnostic to check the health of your store\'s email delivery system.', 'wcehc' ); ?>
    </p>

    <style>
        /* General Layout */
        .wcehc-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }

        /* Full-width container for the main report */
        .wcehc-main-col {
            flex: 2; /* 2/3 width */
            min-width: 0;
        }
        #wcehc-main-report.card {
            max-width: 100% !important; /* Force override of default WP card max-width */
            padding: 20px;
        }

        /* Sidebar container for the two smaller cards */
        .wcehc-sidebar-col {
            flex: 1; /* 1/3 width */
            min-width: 300px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Styles for the individual cards */
        .wcehc-card-padded {
            padding: 20px;
        }
        #wcehc-cta-card.card {
            border-left: 4px solid #1e87f0;
        }

        /* Full-width button style */
        .wcehc-full-width-button {
            display: block;
            text-align: center;
            width: 100%;
        }

        /* Responsive behavior */
        @media (max-width: 900px) {
            .wcehc-grid {
                flex-direction: column;
            }
            .wcehc-sidebar-col {
                min-width: unset;
            }
        }
    </style>

    <hr>

    <div class="wcehc-grid">
        <div class="wcehc-main-col">
            <div id="wcehc-main-report" class="card">
                <h2><?php _e( 'Email Health Report', 'wcehc' ); ?></h2>
                <p>
					<?php _e( 'The following checks will help you identify common issues that prevent emails from being delivered successfully.', 'wcehc' ); ?>
                </p>

                <table class="widefat fixed" cellspacing="0">
                    <thead>
                    <tr>
                        <th class="manage-column column-columnname" scope="col"><?php _e( 'Check', 'wcehc' ); ?></th>
                        <th class="manage-column column-columnname" scope="col"><?php _e( 'Status', 'wcehc' ); ?></th>
                        <th class="manage-column column-columnname" scope="col"><?php _e( 'Message', 'wcehc' ); ?></th>
                    </tr>
                    </thead>
                    <tbody>
					<?php foreach ( $results as $result ) : ?>
                        <tr>
                            <td class="column-columnname"><strong><?php echo esc_html( $result['label'] ); ?></strong></td>
                            <td class="column-columnname">
								<?php if ( $result['status'] ) : ?>
                                    <span style="color: green; font-weight: bold;">&#10004; <?php _e( 'Pass', 'wcehc' ); ?></span>
								<?php else : ?>
                                    <span style="color: red; font-weight: bold;">&#10006; <?php _e( 'Fail', 'wcehc' ); ?></span>
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

        <div class="wcehc-sidebar-col">
            <div id="wcehc-cta-card" class="card wcehc-card-padded">
                <h2 style="margin-top: 0;"><?php _e( 'Found Issues?', 'wcehc' ); ?></h2>
                <p>
					<?php _e( 'Your email health report shows potential issues that can cause your emails to end up in spam. Don\'t lose crucial order confirmations. Our managed service automatically handles all the technical details for you.', 'wcehc' ); ?>
                </p>
                <a href="https://your-saas-domain.com/?utm_source=plugin&utm_medium=banner" class="button button-primary button-hero wcehc-full-width-button" target="_blank">
					<?php _e( 'Fix All These Issues Now', 'wcehc' ); ?>
                </a>
            </div>

            <div class="card wcehc-card-padded">
                <h2 style="margin-top: 0;"><?php _e( 'Test Your Email Sending', 'wcehc' ); ?></h2>
                <p>
					<?php _e( 'Click the button below to send a test email to your admin email address (', 'wcehc' ); ?>
                    <code><?php echo esc_html( get_option('admin_email') ); ?></code>
					<?php _e( ') to confirm basic functionality.', 'wcehc' ); ?>
                </p>
                <form method="post">
					<?php wp_nonce_field( 'wcehc_send_test_email' ); ?>
                    <input type="submit" name="wcehc_send_test_email" class="button button-secondary" value="<?php _e( 'Send Test Email', 'wcehc' ); ?>">
                </form>
            </div>
        </div>
    </div>
</div>