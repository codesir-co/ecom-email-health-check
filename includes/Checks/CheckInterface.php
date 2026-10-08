<?php
/**
 * Contract for a single diagnostic check.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

defined( 'ABSPATH' ) || exit;

interface CheckInterface {

	/** Unique machine-readable id. */
	public function get_id(): string;

	/** Human-readable, translated label. */
	public function get_label(): string;

	/** Execute the check exactly once. */
	public function run(): Result;
}
