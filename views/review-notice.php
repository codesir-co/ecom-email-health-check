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
	<p><strong><?php esc_html_e( 'Is eCommerce Email Health Check useful to you?', 'ecom-email-health-check' ); ?></strong> <?php esc_html_e( 'A short review on WordPress.org helps other store owners find it.', 'ecom-email-health-check' ); ?></p>
	<form method="post">
		<?php wp_nonce_field( ReviewPromptHandler::NONCE_ACTION, ReviewPromptHandler::NONCE_FIELD ); ?>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( ReviewPrompt::REVIEW_URL ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Leave a review', 'ecom-email-health-check' ); ?></a>
			<button type="submit" class="button" name="ecehc_review_action" value="later"><?php esc_html_e( 'Maybe later', 'ecom-email-health-check' ); ?></button>
			<button type="submit" class="button-link" name="ecehc_review_action" value="done"><?php esc_html_e( 'I already did, or no thanks', 'ecom-email-health-check' ); ?></button>
		</p>
	</form>
</div>
