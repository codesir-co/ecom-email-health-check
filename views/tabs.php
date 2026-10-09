<?php
/**
 * Tab navigation shared by the plugin screens. Expects $active_tab.
 *
 * @package CodeSir\EmailHealthCheck
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ecehc_tabs = array(
	'report' => __( 'Health Report', 'ecom-email-health-check' ),
	'log'    => __( 'Email Log', 'ecom-email-health-check' ),
);
?>
<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Email Health Check sections', 'ecom-email-health-check' ); ?>">
	<?php foreach ( $ecehc_tabs as $ecehc_tab_id => $ecehc_tab_label ) : ?>
		<a href="<?php echo esc_url( add_query_arg( array( 'page' => \CodeSir\EmailHealthCheck\Admin\AdminPage::MENU_SLUG, 'tab' => $ecehc_tab_id ), admin_url( 'admin.php' ) ) ); ?>" class="nav-tab<?php echo $ecehc_tab_id === $active_tab ? ' nav-tab-active' : ''; ?>"<?php echo $ecehc_tab_id === $active_tab ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $ecehc_tab_label ); ?></a>
	<?php endforeach; ?>
</nav>
<?php include ECEHC_PLUGIN_PATH . 'views/review-notice.php'; ?>
