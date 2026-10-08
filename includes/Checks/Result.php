<?php
/**
 * Outcome of a diagnostic check.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

defined( 'ABSPATH' ) || exit;

class Result {

	/** @var bool */
	private $passed;

	/** @var string */
	private $message;

	public function __construct( bool $passed, string $message ) {
		$this->passed  = $passed;
		$this->message = $message;
	}

	public function passed(): bool {
		return $this->passed;
	}

	public function get_message(): string {
		return $this->message;
	}
}
