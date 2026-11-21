<?php
/**
 * Classes carrying `@hook` annotations for Aegis\Hooks\Hook tests.
 */

declare( strict_types=1 );

namespace AegisTests\Framework\Fixtures;


class Annotated {

	/**
	 * @hook init
	 */
	public function no_priority(): void {}

	/**
	 * @hook init 20
	 */
	public function with_priority( $a, $b, $c ): void {}

	/**
	 * Two annotations on one method.
	 *
	 * @hook wp_head 5
	 * @hook wp_footer 7
	 */
	public function multiple( $a ): void {}

	/**
	 * @hook the_content
	 */
	public function optional_params( $a, $b = 1, $c = 2 ): void {}

	/**
	 * @hook init
	 */
	public static function static_method( $a ): void {}

	/**
	 * @hook init
	 */
	public function __construct() {}

	/**
	 * @hook init
	 */
	protected function not_public(): void {}

	/**
	 * @hook init
	 */
	private function not_public_either(): void {}

	/**
	 * No annotation here.
	 */
	public function plain(): void {}

	public function no_docblock(): void {}

	/**
	 * @hook init  30
	 */
	public function double_space(): void {}

	/**
	 * @hook init	30
	 */
	public function tab_before_priority(): void {}

	/**
	 * @hook init 0
	 */
	public function zero_priority(): void {}

	/**
	 * @hook   spaced_after_tag
	 */
	public function wide_gap_before_tag(): void {}

	/**
	 * @hook init 20
	 * @hook init 20
	 */
	public function duplicated(): void {}
}
