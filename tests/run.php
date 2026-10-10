<?php
/**
 * Runs every tests/*Test.php file. Exit status 1 if any assertion failed.
 */

require __DIR__ . '/bootstrap.php';

// Each file runs in its own function scope, and the globals tests may fake are reset afterwards.
$run_file = static function ( $file ) {
	require $file;
};

foreach ( glob( __DIR__ . '/*Test.php' ) as $file ) {
	echo basename( $file ) . "\n";
	try {
		$run_file( $file );
	} finally {
		unset( $GLOBALS['wp_filter'] );
		$GLOBALS['ecehc_test_options'] = array();
	}
}

$failures = $GLOBALS['ecehc_test_failures'];
echo "\n" . $GLOBALS['ecehc_test_count'] . ' assertions, ' . count( $failures ) . " failed\n";

foreach ( $failures as $failure ) {
	echo '  FAIL: ' . $failure . "\n";
}

exit( $failures ? 1 : 0 );
