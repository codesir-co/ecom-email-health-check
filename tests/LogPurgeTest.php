<?php
// EmailLog::purge() and max_rows(): age delete, row cap trim, minimum clamp, stats cache clearing (#147).

use CodeSir\EmailHealthCheck\Log\EmailLog;

if ( ! function_exists( 'esc_sql' ) ) {
	function esc_sql( $text ) {
		return $text;
	}
}

// Records every query; prepare() substitutes %s/%d so the SQL can be asserted as text.
class Ecehc_Purge_Wpdb {
	public $prefix  = 'wp_';
	public $queries = array();
	public $cutoff  = null;

	public function prepare( $sql, ...$args ) {
		$args = isset( $args[0] ) && is_array( $args[0] ) ? $args[0] : $args;
		$i    = 0;
		return preg_replace_callback(
			'/%[sd]/',
			static function ( $m ) use ( &$i, $args ) {
				$value = $args[ $i++ ];
				return '%d' === $m[0] ? (string) (int) $value : "'" . $value . "'";
			},
			$sql
		);
	}

	public function query( $sql ) {
		$this->queries[] = $sql;
		return 1;
	}

	public function get_var( $sql ) {
		$this->queries[] = $sql;
		return $this->cutoff;
	}
}

$ecehc_purge = static function ( $cutoff ) {
	$wpdb         = new Ecehc_Purge_Wpdb();
	$wpdb->cutoff = $cutoff;
	$GLOBALS['wpdb'] = $wpdb;
	$GLOBALS['ecehc_test_deleted_transients'] = array();
	EmailLog::purge();
	unset( $GLOBALS['wpdb'] );
	return $wpdb->queries;
};

// max_rows().
ecehc_assert_same( 5000, EmailLog::max_rows(), 'max_rows defaults to 5000' );
add_filter( 'ecehc_log_max_rows', static function () {
	return 5;
} );
ecehc_assert_same( 100, EmailLog::max_rows(), 'max_rows never goes below 100' );
$GLOBALS['ecehc_test_hooks']['ecehc_log_max_rows'] = array();
add_filter( 'ecehc_log_max_rows', static function () {
	return 12000;
} );
ecehc_assert_same( 12000, EmailLog::max_rows(), 'max_rows filter can raise the cap' );

// purge() with a fixed retention and cap.
$GLOBALS['ecehc_test_hooks']['ecehc_log_max_rows'] = array();
add_filter( 'ecehc_log_retention_days', static function () {
	return 3;
} );
add_filter( 'ecehc_log_max_rows', static function () {
	return 250;
} );

$before  = time();
$queries = $ecehc_purge( 42 );
$after   = time();

ecehc_assert_same( 3, count( $queries ), 'purge with a cutoff runs age delete, cutoff lookup and trim' );
ecehc_assert_true( 1 === preg_match( "/^DELETE FROM wp_ecehc_email_log WHERE created_at < '([\d-]+ [\d:]+)'$/", $queries[0], $m ), 'age delete targets created_at' );
$cut_time = strtotime( $m[1] . ' UTC' );
ecehc_assert_true( $cut_time >= $before - 3 * DAY_IN_SECONDS && $cut_time <= $after - 3 * DAY_IN_SECONDS, 'age cutoff is now minus the retention days' );
ecehc_assert_same( 'SELECT id FROM wp_ecehc_email_log ORDER BY id DESC LIMIT 1 OFFSET 250', $queries[1], 'cutoff lookup uses OFFSET = max_rows' );
ecehc_assert_same( 'DELETE FROM wp_ecehc_email_log WHERE id <= 42', $queries[2], 'trim deletes id <= cutoff' );
ecehc_assert_true( in_array( 'ecehc_log_stats_' . DAY_IN_SECONDS, $GLOBALS['ecehc_test_deleted_transients'], true ) && in_array( 'ecehc_log_stats_' . 7 * DAY_IN_SECONDS, $GLOBALS['ecehc_test_deleted_transients'], true ), 'purge clears the stats cache' );

$queries = $ecehc_purge( null );
ecehc_assert_same( 2, count( $queries ), 'no trim delete when there is no row past the cap' );
ecehc_assert_true( 0 === strpos( $queries[1], 'SELECT id' ), 'second query is only the cutoff lookup' );

// Clamp applies inside purge() too.
$GLOBALS['ecehc_test_hooks']['ecehc_log_max_rows'] = array();
add_filter( 'ecehc_log_max_rows', static function () {
	return 1;
} );
$queries = $ecehc_purge( 7 );
ecehc_assert_same( 'SELECT id FROM wp_ecehc_email_log ORDER BY id DESC LIMIT 1 OFFSET 100', $queries[1], 'purge OFFSET is clamped to 100' );
