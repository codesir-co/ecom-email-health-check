<?php
$ecehc_bp_path = dirname( __DIR__ ) . '/.wordpress-org/blueprints/blueprint.json';
$ecehc_bp_raw  = is_readable( $ecehc_bp_path ) ? file_get_contents( $ecehc_bp_path ) : '';
$ecehc_bp      = json_decode( $ecehc_bp_raw, true );

ecehc_assert_true( is_array( $ecehc_bp ), 'blueprint parses as JSON' );
$ecehc_bp = is_array( $ecehc_bp ) ? $ecehc_bp : array();

ecehc_assert_same( 'https://playground.wordpress.net/blueprint-schema.json', isset( $ecehc_bp['$schema'] ) ? $ecehc_bp['$schema'] : '', 'blueprint declares the Playground schema' );
ecehc_assert_same( true, isset( $ecehc_bp['login'] ) ? $ecehc_bp['login'] : null, 'blueprint logs in as admin' );

$ecehc_menu_slug = '';
if ( preg_match( "/const MENU_SLUG = '([^']+)'/", (string) file_get_contents( dirname( __DIR__ ) . '/includes/Admin/AdminPage.php' ), $ecehc_m ) ) {
	$ecehc_menu_slug = $ecehc_m[1];
}
ecehc_assert_true( '' !== $ecehc_menu_slug, 'menu slug found in AdminPage' );
ecehc_assert_same( '/wp-admin/admin.php?page=' . $ecehc_menu_slug, isset( $ecehc_bp['landingPage'] ) ? $ecehc_bp['landingPage'] : '', 'landing page is the plugin admin page' );

$ecehc_install = null;
foreach ( isset( $ecehc_bp['steps'] ) ? $ecehc_bp['steps'] : array() as $ecehc_step ) {
	if ( isset( $ecehc_step['step'] ) && 'installPlugin' === $ecehc_step['step'] ) {
		$ecehc_install = $ecehc_step;
	}
}
ecehc_assert_true( null !== $ecehc_install, 'blueprint has an installPlugin step' );
ecehc_assert_same( 'ecom-email-health-check', isset( $ecehc_install['pluginData']['slug'] ) ? $ecehc_install['pluginData']['slug'] : '', 'installs the plugin slug' );
ecehc_assert_same( 'wordpress.org/plugins', isset( $ecehc_install['pluginData']['resource'] ) ? $ecehc_install['pluginData']['resource'] : '', 'installs from wordpress.org' );
ecehc_assert_same( true, isset( $ecehc_install['options']['activate'] ) ? $ecehc_install['options']['activate'] : null, 'activates the plugin' );

// No other external host: only the schema URL may appear as a URL.
preg_match_all( '#https?://([^/"\s]+)#i', $ecehc_bp_raw, $ecehc_hosts );
ecehc_assert_same( array( 'playground.wordpress.net' ), array_values( array_unique( $ecehc_hosts[1] ) ), 'no external host other than the schema' );
ecehc_assert_same( false, (bool) preg_match( '/"resource"\s*:\s*"(url|git:directory)"/', $ecehc_bp_raw ), 'no url or git resources' );
