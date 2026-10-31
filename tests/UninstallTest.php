<?php
// Regression test for #152: uninstall.php must delete its options and transients on EVERY site of a network.

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	define( 'WP_UNINSTALL_PLUGIN', 'ecom-email-health-check/ecom-email-health-check.php' );
}

// Stubs record (blog id, call) pairs into $GLOBALS['ecehc_uninstall_calls'].
$GLOBALS['ecehc_uninstall_calls'] = array();
$GLOBALS['ecehc_uninstall_blog']  = 1;
$GLOBALS['ecehc_uninstall_sites'] = array( 1 );
$GLOBALS['ecehc_uninstall_multi'] = false;
$GLOBALS['ecehc_uninstall_stack'] = array();

if ( ! function_exists( 'ecehc_uninstall_record' ) ) {
	function ecehc_uninstall_record( $call ) {
		$GLOBALS['ecehc_uninstall_calls'][] = array( $GLOBALS['ecehc_uninstall_blog'], $call );
	}
	function is_multisite() {
		return $GLOBALS['ecehc_uninstall_multi'];
	}
	function get_sites( $args = array() ) {
		return $GLOBALS['ecehc_uninstall_sites'];
	}
	function get_current_blog_id() {
		return $GLOBALS['ecehc_uninstall_blog'];
	}
	function switch_to_blog( $id ) {
		$GLOBALS['ecehc_uninstall_stack'][] = $GLOBALS['ecehc_uninstall_blog'];
		$GLOBALS['ecehc_uninstall_blog']    = $id;
	}
	function restore_current_blog() {
		$GLOBALS['ecehc_uninstall_blog'] = array_pop( $GLOBALS['ecehc_uninstall_stack'] );
	}
	function delete_option( $name ) {
		ecehc_uninstall_record( 'option:' . $name );
	}
	function delete_transient( $name ) {
		ecehc_uninstall_record( 'transient:' . $name );
	}
	function wp_clear_scheduled_hook( $hook ) {
		ecehc_uninstall_record( 'cron:' . $hook );
	}
	function delete_metadata( $type, $id, $key ) {
		ecehc_uninstall_record( 'meta:' . $key );
	}
}
if ( ! function_exists( 'esc_sql' ) ) {
	function esc_sql( $text ) {
		return $text;
	}
}

class Ecehc_Uninstall_Wpdb {
	public $prefix;

	public function query( $sql ) {
		ecehc_uninstall_record( 'sql:' . $sql );
		return true;
	}
}

// Runs uninstall.php in a function scope; $wpdb is set as a global because the file declares "global $wpdb".
$ecehc_run_uninstall = static function ( array $site_ids, bool $multisite ) {
	$GLOBALS['ecehc_uninstall_calls'] = array();
	$GLOBALS['ecehc_uninstall_sites'] = $site_ids;
	$GLOBALS['ecehc_uninstall_multi'] = $multisite;
	$GLOBALS['ecehc_uninstall_blog']  = 1;
	$GLOBALS['ecehc_uninstall_stack'] = array();
	$GLOBALS['wpdb']                  = new Ecehc_Uninstall_Wpdb();
	// The prefix follows the current blog, like the real $wpdb after switch_to_blog().
	$GLOBALS['wpdb']->prefix = 'wp_';
	( static function () {
		include ECEHC_PLUGIN_PATH . 'uninstall.php';
	} )();
	unset( $GLOBALS['wpdb'] );
	return $GLOBALS['ecehc_uninstall_calls'];
};

$ecehc_count = static function ( array $calls, int $blog, string $call ) {
	return count( array_filter( $calls, static function ( $c ) use ( $blog, $call ) {
		return $c[0] === $blog && $c[1] === $call;
	} ) );
};

$per_site = array(
	'option:ecehc_last_test_email',
	'transient:ecehc_smtp_probe',
	'transient:ecehc_blacklist',
	'transient:ecehc_redirect_to_dashboard',
	'option:ecehc_db_version',
	'option:ecehc_log_settings',
	'option:ecehc_installed_at',
	'option:ecehc_show_checklist',
	'transient:ecehc_plugin_names',
	'transient:ecehc_log_stats_86400',
	'transient:ecehc_log_stats_604800',
	'cron:ecehc_purge_email_log',
	'sql:DROP TABLE IF EXISTS `wp_ecehc_email_log`',
);
$user_meta = array( 'meta:ecehc_review_prompt', 'meta:ecehc_checklist_hidden', 'meta:ecehc_checklist_saw_log' );

// Three-site network.
$calls = $ecehc_run_uninstall( array( 1, 2, 3 ), true );
foreach ( array( 1, 2, 3 ) as $blog ) {
	foreach ( $per_site as $call ) {
		ecehc_assert_same( 1, $ecehc_count( $calls, $blog, $call ), "network: blog $blog runs $call once" );
	}
}
foreach ( $user_meta as $call ) {
	$total = count( array_filter( $calls, static function ( $c ) use ( $call ) {
		return $c[1] === $call;
	} ) );
	ecehc_assert_same( 1, $total, "network: $call once in total" );
}

// Single site.
$calls = $ecehc_run_uninstall( array( 1 ), false );
foreach ( $per_site as $call ) {
	ecehc_assert_same( 1, $ecehc_count( $calls, 1, $call ), "single site: $call once" );
}
foreach ( $user_meta as $call ) {
	ecehc_assert_same( 1, $ecehc_count( $calls, 1, $call ), "single site: $call once" );
}
ecehc_assert_same( count( $per_site ) + count( $user_meta ), count( $calls ), 'single site: no other calls' );
