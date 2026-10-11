<?php
/**
 * "WooCommerce emails not arriving?" panel. Shown only when WooCommerce is active.
 * Expects $results (see CheckRunner::run_all()). Included by report-card.php.
 *
 * @package CodeSir\EmailHealthCheck
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ecehc_diagnosis = \CodeSir\EmailHealthCheck\Support\WooDiagnosis::for_site( $results );
$ecehc_diag_url  = '';
$ecehc_diag_text = '';
$ecehc_diag_open = '';

if ( 'check' === $ecehc_diagnosis['link'] && '' !== $ecehc_diagnosis['check_id'] ) {
	$ecehc_diag_url  = '#ecehc-check-' . $ecehc_diagnosis['check_id'];
	$ecehc_diag_text = __( 'Go to this check', 'ecom-email-health-check' );
	$ecehc_diag_open = 'ecehc-check-' . $ecehc_diagnosis['check_id'];
} elseif ( 'wc_settings' === $ecehc_diagnosis['link'] && current_user_can( 'manage_woocommerce' ) ) {
	$ecehc_diag_url  = admin_url( 'admin.php?page=wc-settings&tab=email' );
	$ecehc_diag_text = __( 'Open WooCommerce email settings', 'ecom-email-health-check' );
} elseif ( 'log_failed' === $ecehc_diagnosis['link'] ) {
	$ecehc_diag_url  = add_query_arg(
		array(
			'page'   => \CodeSir\EmailHealthCheck\Admin\AdminPage::MENU_SLUG,
			'tab'    => 'log',
			'status' => 'failed',
		),
		admin_url( 'admin.php' )
	);
	$ecehc_diag_text = __( 'Open the failed emails in the Email Log', 'ecom-email-health-check' );
}
?>
<div id="ecehc-woo-diagnosis" class="ecehc-woo-diagnosis" role="region" aria-labelledby="ecehc-woo-diagnosis-title">
	<h3 id="ecehc-woo-diagnosis-title" class="ecehc-woo-diagnosis-title"><?php esc_html_e( 'WooCommerce emails not arriving?', 'ecom-email-health-check' ); ?></h3>
	<p><strong><?php echo esc_html( $ecehc_diagnosis['headline'] ); ?></strong></p>
	<p><?php echo esc_html( $ecehc_diagnosis['step'] ); ?></p>
	<?php if ( '' !== $ecehc_diag_url ) : ?>
		<p><a href="<?php echo esc_url( $ecehc_diag_url ); ?>"<?php echo '' !== $ecehc_diag_open ? ' data-ecehc-open="' . esc_attr( $ecehc_diag_open ) . '"' : ''; ?>><?php echo esc_html( $ecehc_diag_text ); ?></a></p>
	<?php endif; ?>
	<p class="description"><?php echo esc_html( $ecehc_diagnosis['log_note'] ); ?></p>
</div>
