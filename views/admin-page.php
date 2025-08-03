<div class="wrap">
    <h1><?php _e( 'WooCommerce Email Health Check', 'ecehc' ); ?></h1>
    <p class="description">
		<?php _e( 'Run a quick diagnostic to check the health of your store\'s email delivery system.', 'ecehc' ); ?>
    </p>

    <style>
        /* General Layout */
        .ecehc-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }

        /* Full-width container for the main report */
        .ecehc-main-col {
            flex: 2; /* 2/3 width */
            min-width: 0;
        }
        #ecehc-main-report.card {
            max-width: 100% !important; /* Force override of default WP card max-width */
            padding: 20px;
        }

        /* Sidebar container for the two smaller cards */
        .ecehc-sidebar-col {
            flex: 1; /* 1/3 width */
            min-width: 300px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Styles for the individual cards */
        .ecehc-card-padded {
            padding: 20px;
        }
        #ecehc-cta-card.card {
            border-left: 4px solid #1e87f0;
        }

        /* Full-width button style */
        .ecehc-full-width-button {
            display: block;
            text-align: center;
            width: 100%;
        }

        /* Responsive behavior */
        @media (max-width: 900px) {
            .ecehc-grid {
                flex-direction: column;
            }
            .ecehc-sidebar-col {
                min-width: unset;
            }
        }
    </style>

    <hr>

    <div class="ecehc-grid">
        <div class="ecehc-main-col">
            <div id="ecehc-main-report" class="card">
                <h2><?php _e( 'Email Health Report', 'ecehc' ); ?></h2>
                <p>
					<?php _e( 'The following checks will help you identify common issues that prevent emails from being delivered successfully.', 'ecehc' ); ?>
                </p>

                <table class="widefat fixed" cellspacing="0">
                    <thead>
                    <tr>
                        <th class="manage-column column-columnname" scope="col"><?php _e( 'Check', 'ecehc' ); ?></th>
                        <th class="manage-column column-columnname" scope="col"><?php _e( 'Status', 'ecehc' ); ?></th>
                        <th class="manage-column column-columnname" scope="col"><?php _e( 'Message', 'ecehc' ); ?></th>
                    </tr>
                    </thead>
                    <tbody>
					<?php foreach ( $results as $result ) : ?>
                        <tr>
                            <td class="column-columnname"><strong><?php echo esc_html( $result['label'] ); ?></strong></td>
                            <td class="column-columnname">
								<?php if ( $result['status'] ) : ?>
                                    <span style="color: green; font-weight: bold;">&#10004; <?php _e( 'Pass', 'ecehc' ); ?></span>
								<?php else : ?>
                                    <span style="color: red; font-weight: bold;">&#10006; <?php _e( 'Fail', 'ecehc' ); ?></span>
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
            <div id="ecehc-cta-card" class="card ecehc-card-padded">
                <h2 style="margin-top: 0;"><?php _e( 'Found Issues?', 'ecehc' ); ?></h2>
                <p>
					<?php _e( 'Your email health report shows potential issues that can cause your emails to end up in spam. Don\'t lose crucial order confirmations. Our managed service automatically handles all the technical details for you.', 'ecehc' ); ?>
                </p>
                <a href="https://your-saas-domain.com/?utm_source=plugin&utm_medium=banner" class="button button-primary button-hero ecehc-full-width-button" target="_blank">
					<?php _e( 'Fix All These Issues Now', 'ecehc' ); ?>
                </a>
            </div>

            <div class="card ecehc-card-padded">
                <h2 style="margin-top: 0;"><?php _e( 'Test Your Email Sending', 'ecehc' ); ?></h2>
                <p>
					<?php _e( 'Click the button below to send a test email to your admin email address (', 'ecehc' ); ?>
                    <code><?php echo esc_html( get_option('admin_email') ); ?></code>
					<?php _e( ') to confirm basic functionality.', 'ecehc' ); ?>
                </p>
                <form method="post">
					<?php wp_nonce_field( 'ecehc_send_test_email' ); ?>
                    <input type="submit" name="ecehc_send_test_email" class="button button-secondary" value="<?php _e( 'Send Test Email', 'ecehc' ); ?>">
                </form>
            </div>
        </div>
    </div>
</div>