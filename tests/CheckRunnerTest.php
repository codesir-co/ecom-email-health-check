<?php
use CodeSir\EmailHealthCheck\Checks\CheckInterface;
use CodeSir\EmailHealthCheck\Checks\CheckRunner;
use CodeSir\EmailHealthCheck\Checks\Result;

class Ecehc_Fake_Check implements CheckInterface {
	private $id;
	private $result;
	public $runs = 0;

	public function __construct( string $id, Result $result ) {
		$this->id     = $id;
		$this->result = $result;
	}

	public function get_id(): string {
		return $this->id;
	}

	public function get_label(): string {
		return 'Label ' . $this->id;
	}

	public function run(): Result {
		++$this->runs;
		return $this->result;
	}
}

$passing = new Ecehc_Fake_Check( 'one', new Result( true, 'fine' ) );
$failing = new Ecehc_Fake_Check( 'two', new Result( false, 'broken' ) );
$unknown = new Ecehc_Fake_Check( 'three', Result::unknown( 'who knows' ) );

$calls = array();
add_action(
	'ecehc_checks_completed',
	static function ( $results ) use ( &$calls ) {
		$calls[] = $results;
	}
);

$returned = ( new CheckRunner() )->run_checks( array( $passing, $failing, $unknown ), null, 'shop.example.com' );

ecehc_assert_same( 1, count( $calls ), 'ecehc_checks_completed fires once per full run' );
ecehc_assert_same( $returned, $calls[0], 'the action receives the same results that the run returns' );
ecehc_assert_same( array( 'one', 'two', 'three' ), array_keys( $calls[0] ), 'results are keyed by check id, in order' );
ecehc_assert_same( array( 'label', 'status', 'message', 'guidance' ), array_keys( $calls[0]['one'] ), 'each result keeps the documented shape' );
ecehc_assert_same( array( 'pass', 'fail', 'unknown' ), array( $calls[0]['one']['status'], $calls[0]['two']['status'], $calls[0]['three']['status'] ), 'statuses use the documented values' );
ecehc_assert_same( 1, $passing->runs, 'each check still runs exactly once' );

// A duplicate id is skipped and does not stop the action.
$GLOBALS['ecehc_test_doing_it_wrong'] = array();
$calls                                = array();
$returned                             = ( new CheckRunner() )->run_checks( array( $passing, new Ecehc_Fake_Check( 'one', new Result( false, 'x' ) ) ), null, 'shop.example.com' );
ecehc_assert_same( 1, count( $returned ), 'a duplicate check id is skipped' );
ecehc_assert_same( 1, count( $calls ), 'the action still fires after a duplicate id' );

// No checks: the action still fires, with an empty array.
$calls = array();
( new CheckRunner() )->run_checks( array(), null, 'shop.example.com' );
ecehc_assert_same( array( array() ), $calls, 'an empty run fires the action with an empty array' );
