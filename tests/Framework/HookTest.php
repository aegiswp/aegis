<?php
/**
 * Characterization tests for Aegis\Hooks\Hook.
 */

declare( strict_types=1 );

namespace AegisTests\Framework;

use Aegis\Hooks\Hook;
use AegisTests\Framework\Fixtures\Annotated;
use PHPUnit\Framework\TestCase;

final class HookTest extends TestCase {

	protected function setUp(): void {
		Wp::reset();
	}

	/**
	 * @param string $method Method name to filter on.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function registered_for( string $method ): array {
		return array_values(
			array_filter(
				Wp::$hooks,
				static fn( array $hook ): bool => $hook['callback'][1] === $method
			)
		);
	}

	public function test_default_priority_is_int_10_and_args_count_comes_from_signature(): void {
		$object = new Annotated();
		Hook::annotations( $object );

		$hooks = $this->registered_for( 'no_priority' );

		$this->assertCount( 1, $hooks );
		$this->assertSame( 'init', $hooks[0]['tag'] );
		$this->assertSame( [ $object, 'no_priority' ], $hooks[0]['callback'] );
		$this->assertSame( 10, $hooks[0]['priority'] );
		$this->assertSame( 0, $hooks[0]['accepted_args'] );
	}

	public function test_explicit_priority_is_parsed_and_args_count_matches_parameters(): void {
		Hook::annotations( new Annotated() );

		$hooks = $this->registered_for( 'with_priority' );

		$this->assertCount( 1, $hooks );
		$this->assertSame( 'init', $hooks[0]['tag'] );
		// Pins current behaviour: a parsed priority is a numeric STRING, not an int.
		$this->assertSame( '20', $hooks[0]['priority'] );
		$this->assertSame( 3, $hooks[0]['accepted_args'] );
	}

	public function test_zero_priority_is_kept(): void {
		Hook::annotations( new Annotated() );

		$this->assertSame( '0', $this->registered_for( 'zero_priority' )[0]['priority'] );
	}

	public function test_accepted_args_counts_optional_parameters_too(): void {
		Hook::annotations( new Annotated() );

		$this->assertSame( 3, $this->registered_for( 'optional_params' )[0]['accepted_args'] );
	}

	public function test_multiple_annotations_register_one_hook_each(): void {
		Hook::annotations( new Annotated() );

		$hooks = $this->registered_for( 'multiple' );

		$this->assertCount( 2, $hooks );
		$this->assertSame( [ 'wp_head', '5', 1 ], [ $hooks[0]['tag'], $hooks[0]['priority'], $hooks[0]['accepted_args'] ] );
		$this->assertSame( [ 'wp_footer', '7', 1 ], [ $hooks[1]['tag'], $hooks[1]['priority'], $hooks[1]['accepted_args'] ] );
	}

	public function test_identical_annotations_are_registered_twice(): void {
		Hook::annotations( new Annotated() );

		$this->assertCount( 2, $this->registered_for( 'duplicated' ) );
	}

	public function test_everything_goes_through_add_filter_never_add_action(): void {
		Hook::annotations( new Annotated() );

		$this->assertNotEmpty( Wp::$hooks );
		$this->assertSame( [ 'add_filter' ], array_values( array_unique( array_column( Wp::$hooks, 'via' ) ) ) );
	}

	public function test_constructor_non_public_unannotated_and_undocumented_methods_are_skipped(): void {
		Hook::annotations( new Annotated() );

		$names = array_map( static fn( array $hook ): string => $hook['callback'][1], Wp::$hooks );

		$this->assertNotContains( '__construct', $names );
		$this->assertNotContains( 'not_public', $names );
		$this->assertNotContains( 'not_public_either', $names );
		$this->assertNotContains( 'plain', $names );
		$this->assertNotContains( 'no_docblock', $names );
	}

	public function test_static_methods_are_registered_when_given_an_object(): void {
		$object = new Annotated();
		Hook::annotations( $object );

		$hooks = $this->registered_for( 'static_method' );

		$this->assertCount( 1, $hooks );
		$this->assertSame( [ $object, 'static_method' ], $hooks[0]['callback'] );
		$this->assertSame( 1, $hooks[0]['accepted_args'] );
	}

	public function test_static_methods_are_skipped_when_given_a_class_string(): void {
		Hook::annotations( Annotated::class );

		$this->assertSame( [], $this->registered_for( 'static_method' ) );
	}

	public function test_instance_methods_are_still_registered_for_a_class_string_with_the_string_as_callback_target(): void {
		// Pins current (odd) behaviour: a non-static method is hooked as
		// [ 'Class\Name', 'method' ], which WordPress could not call.
		Hook::annotations( Annotated::class );

		$hooks = $this->registered_for( 'with_priority' );

		$this->assertCount( 1, $hooks );
		$this->assertSame( [ Annotated::class, 'with_priority' ], $hooks[0]['callback'] );
	}

	public function test_unknown_class_string_is_skipped_silently(): void {
		Hook::annotations( 'No\\Such\\Class' );

		$this->assertSame( [], Wp::$hooks );
	}

	public function test_two_spaces_before_priority_yields_an_empty_string_priority(): void {
		// Pins a parsing oddity: the regex accepts any whitespace run but the
		// parser splits on a single space, so the priority becomes ''.
		Hook::annotations( new Annotated() );

		$hooks = $this->registered_for( 'double_space' );

		$this->assertSame( 'init', $hooks[0]['tag'] );
		$this->assertSame( '', $hooks[0]['priority'] );
	}

	public function test_tab_before_priority_is_swallowed_into_the_tag(): void {
		// Pins a parsing oddity: explode( ' ' ) does not split on a tab.
		Hook::annotations( new Annotated() );

		$hooks = $this->registered_for( 'tab_before_priority' );

		$this->assertSame( "init\t30", $hooks[0]['tag'] );
		$this->assertSame( 10, $hooks[0]['priority'] );
	}

	public function test_extra_whitespace_between_the_annotation_and_the_tag_is_tolerated(): void {
		Hook::annotations( new Annotated() );

		$hooks = $this->registered_for( 'wide_gap_before_tag' );

		$this->assertSame( 'spaced_after_tag', $hooks[0]['tag'] );
		$this->assertSame( 10, $hooks[0]['priority'] );
	}
}
