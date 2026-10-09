<?php
/**
 * First-run checklist. Expects $checklist (see Checklist::items()).
 *
 * @package CodeSir\EmailHealthCheck
 */

use CodeSir\EmailHealthCheck\Admin\ChecklistHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $checklist ) ) {
	return;
}
?>
<div class="card ecehc-checklist">
	<h2><?php esc_html_e( 'Getting started', 'ecom-email-health-check' ); ?></h2>
	<ul>
		<?php foreach ( $checklist as $ecehc_item ) : ?>
			<li<?php echo $ecehc_item['done'] ? ' class="ecehc-done"' : ''; ?>>
				<span aria-hidden="true"><?php echo $ecehc_item['done'] ? '&#10004;' : '&#9744;'; ?></span>
				<?php if ( ! $ecehc_item['done'] && '' !== $ecehc_item['url'] ) : ?>
					<a href="<?php echo esc_url( $ecehc_item['url'] ); ?>"><?php echo esc_html( $ecehc_item['label'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $ecehc_item['label'] ); ?>
				<?php endif; ?>
				<span class="screen-reader-text">
					<?php echo $ecehc_item['done'] ? esc_html__( '(done)', 'ecom-email-health-check' ) : esc_html__( '(to do)', 'ecom-email-health-check' ); ?>
				</span>
			</li>
		<?php endforeach; ?>
	</ul>
	<form method="post">
		<?php wp_nonce_field( ChecklistHandler::NONCE_ACTION, ChecklistHandler::NONCE_FIELD ); ?>
		<button type="submit" class="button-link" name="ecehc_hide_checklist" value="1"><?php esc_html_e( 'Hide this checklist', 'ecom-email-health-check' ); ?></button>
	</form>
</div>
