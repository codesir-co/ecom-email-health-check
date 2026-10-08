<?php
/**
 * Outcome of a diagnostic check.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

defined( 'ABSPATH' ) || exit;

class Result {

	const PASS    = 'pass';
	const FAIL    = 'fail';
	const UNKNOWN = 'unknown';

	/** @var string */
	private $status;

	/** @var string */
	private $message;

	public function __construct( bool $passed, string $message ) {
		$this->status  = $passed ? self::PASS : self::FAIL;
		$this->message = $message;
	}

	/**
	 * A check that could not determine a result (not a pass and not a failure).
	 */
	public static function unknown( string $message ): Result {
		$result         = new self( false, $message );
		$result->status = self::UNKNOWN;
		return $result;
	}

	public function passed(): bool {
		return self::PASS === $this->status;
	}

	public function get_status(): string {
		return $this->status;
	}

	public function get_message(): string {
		return $this->message;
	}
}
