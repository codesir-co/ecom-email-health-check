<?php
use CodeSir\EmailHealthCheck\Checks\CheckInterface;
use CodeSir\EmailHealthCheck\Checks\Result;

require_once ECEHC_PLUGIN_PATH . 'includes/functions.php';

if ( ! function_exists( 'network_home_url' ) ) {
	function network_home_url() {
		return 'https://shop.example.com';
	}
}

class Ecehc_Api_Fake_Check implements CheckInterface {
	public function get_id(): string {
		return 'api_fake';
	}

	public function get_label(): string {
		return 'API fake';
	}

	public function run(): Result {
		return new Result( true, 'fine' );
	}
}

ecehc_assert_true( function_exists( 'ecehc_run_checks' ), 'ecehc_run_checks() is defined' );

add_filter(
	'ecehc_checks',
	static function () {
		return array( new Ecehc_Api_Fake_Check() );
	}
);

$received = null;
add_action(
	'ecehc_checks_completed',
	static function ( $results ) use ( &$received ) {
		$received = $results;
	}
);

ob_start();
$results = ecehc_run_checks();
$output  = ob_get_clean();

ecehc_assert_same( '', $output, 'ecehc_run_checks() echoes nothing' );
ecehc_assert_same( array( 'api_fake' ), array_keys( $results ), 'ecehc_checks filter applies' );
ecehc_assert_same( 'pass', $results['api_fake']['status'], 'returns the check status' );
ecehc_assert_same( $received, $results, 'returns the array ecehc_checks_completed received' );
