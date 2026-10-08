<?php
/**
 * Runs every registered check once.
 *
 * @package CodeSir\EmailHealthCheck
 */

namespace CodeSir\EmailHealthCheck\Checks;

defined( 'ABSPATH' ) || exit;

class CheckRunner {

	/**
	 * @return CheckInterface[]
	 */
	public function get_checks(): array {
		$checks = array(
			new BasicEmailCheck(),
			new SenderAddressCheck(),
			new SpfCheck(),
		);

		/**
		 * Filters the list of diagnostic checks.
		 *
		 * @param CheckInterface[] $checks Check instances.
		 */
		return array_filter(
			(array) apply_filters( 'ecehc_checks', $checks ),
			static function ( $check ) {
				return $check instanceof CheckInterface;
			}
		);
	}

	/**
	 * @return array<string, array{label: string, status: bool, message: string}>
	 */
	public function run_all(): array {
		$results = array();

		foreach ( $this->get_checks() as $check ) {
			$result                    = $check->run();
			$results[ $check->get_id() ] = array(
				'label'   => $check->get_label(),
				'status'  => $result->passed(),
				'message' => $result->get_message(),
			);
		}

		return $results;
	}
}
