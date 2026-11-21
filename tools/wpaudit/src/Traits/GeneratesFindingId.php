<?php
/**
 * Trait for generating unique finding IDs.
 *
 * @package WPAudit
 */

declare( strict_types=1 );

namespace WPAudit\Traits;

/**
 * Provides a method to generate unique finding IDs for analyzers.
 */
trait GeneratesFindingId {
	/**
	 * Generate a unique finding ID.
	 *
	 * @param string $rule_id  Rule identifier.
	 * @param string $file     File path.
	 * @param int    $line     Line number.
	 * @return string Unique finding ID.
	 */
	private function generate_finding_id( string $rule_id, string $file, int $line ): string {
		return sprintf( '%s-%s-%d', $rule_id, md5( $file ), $line );
	}

	/**
	 * Severity of a rule as declared in the analyzer's $rules array.
	 *
	 * Call sites with a deliberate escalation or downgrade pass their own value instead.
	 *
	 * @param string $rule_id Rule identifier.
	 * @return string Severity constant value.
	 */
	private function rule_severity( string $rule_id ): string {
		return $this->rules[ $rule_id ]['severity'];
	}
}
