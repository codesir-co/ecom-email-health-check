<?php
/**
 * The review request, shown on this plugin's own screens only.
 *
 * @package CodeSir\EmailHealthCheck
 */

use CodeSir\EmailHealthCheck\Admin\ReviewPrompt;
use CodeSir\EmailHealthCheck\Admin\ReviewPromptHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ReviewPrompt::should_show() ) {
	return;
}
?>
<div class="notice notice-info inline ecehc-review-notice">
	<p><strong><?php esc_html_e( 'Is Email Health Check useful to you?', 'ecom-email-health-check' ); ?></strong> <?php esc_html_e( 'A short review on WordPress.org helps other store owners find it.', 'ecom-email-health-check' ); ?></p>
	<form method="post">
		<?php wp_nonce_field( ReviewPromptHandler::NONCE_ACTION, ReviewPromptHandler::NONCE_FIELD ); ?>
		<input type="hidden" name="ecehc_review_tab" value="<?php echo esc_attr( isset( $ecehc_active_tab ) && 'log' === $ecehc_active_tab ? 'log' : 'report' ); ?>">
		<p>
			<a class="button button-primary" href="<?php echo esc_url( ReviewPrompt::REVIEW_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Leave a review', 'ecom-email-health-check' ); ?><span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'ecom-email-health-check' ); ?></span></a>
			<button type="submit" class="button" name="ecehc_review_action" value="later"><?php esc_html_e( 'Maybe later', 'ecom-email-health-check' ); ?></button>
			<button type="submit" class="button-link" name="ecehc_review_action" value="done"><?php esc_html_e( 'I have left a review, or no thanks', 'ecom-email-health-check' ); ?></button>
		</p>
	</form>
</div>
