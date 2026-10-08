<?php
/**
 * Fired on plugin uninstall. The plugin stores no options; clean up the transient only.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_transient( 'ecehc_redirect_to_dashboard' );
