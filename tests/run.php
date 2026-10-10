<?php
/**
 * Runs every tests/*Test.php file. Exit status 1 if any assertion failed.
 */

require __DIR__ . '/bootstrap.php';

foreach ( glob( __DIR__ . '/*Test.php' ) as $file ) {
	echo basename( $file ) . "\n";
	require $file;
}

$failures = $GLOBALS['ecehc_test_failures'];
echo "\n" . $GLOBALS['ecehc_test_count'] . ' assertions, ' . count( $failures ) . " failed\n";

foreach ( $failures as $failure ) {
	echo '  FAIL: ' . $failure . "\n";
}

exit( $failures ? 1 : 0 );
