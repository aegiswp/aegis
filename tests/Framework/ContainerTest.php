<?php
/**
 * Characterization tests for Aegis\Container\Container.
 */

declare( strict_types=1 );

namespace AegisTests\Framework;

use Aegis\Container\Container;
use Aegis\Container\Exceptions\ContainerException;
use AegisTests\Framework\Fixtures\AbstractThing;
use AegisTests\Framework\Fixtures\Branch;
use AegisTests\Framework\Fixtures\Contract;
use AegisTests\Framework\Fixtures\Counting;
use AegisTests\Framework\Fixtures\DependsOnDisabled;
use AegisTests\Framework\Fixtures\DependsOnPrimitiveThing;
use AegisTests\Framework\Fixtures\Disabled;
use AegisTests\Framework\Fixtures\Enabled;
use AegisTests\Framework\Fixtures\InstanceCondition;
use AegisTests\Framework\Fixtures\Leaf;
use AegisTests\Framework\Fixtures\Pair;
use AegisTests\Framework\Fixtures\Tree;
use AegisTests\Framework\Fixtures\WithDefaults;
use AegisTests\Framework\Fixtures\WithInterface;
use AegisTests\Framework\Fixtures\WithOptionalInterface;
use AegisTests\Framework\Fixtures\WithPrimitive;
use AegisTests\Framework\Fixtures\WithUnion;
use AegisTests\Framework\Fixtures\WithUntyped;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use TypeError;

final class ContainerTest extends TestCase {

	private Container $container;

	protected function setUp(): void {
		$this->container       = new Container();
		Counting::$constructed = 0;
	}

	public function test_make_creates_instance_and_registers_it_for_get_and_has(): void {
		$this->assertFalse( $this->container->has( Leaf::class ) );

		$leaf = $this->container->make( Leaf::class );

		$this->assertInstanceOf( Leaf::class, $leaf );
		$this->assertTrue( $this->container->has( Leaf::class ) );
		$this->assertSame( $leaf, $this->container->get( Leaf::class ) );
	}

	public function test_make_is_a_singleton_per_class(): void {
		$first  = $this->container->make( Counting::class );
		$second = $this->container->make( Counting::class );

		$this->assertSame( $first, $second );
		$this->assertSame( 1, Counting::$constructed );
	}

	public function test_containers_do_not_share_instances(): void {
		$other = new Container();

		$this->assertNotSame( $this->container->make( Leaf::class ), $other->make( Leaf::class ) );
	}

	public function test_make_autowires_nested_class_dependencies_and_shares_them(): void {
		$tree = $this->container->make( Tree::class );

		$this->assertInstanceOf( Tree::class, $tree );
		$this->assertInstanceOf( Branch::class, $tree->branch );
		$this->assertInstanceOf( Leaf::class, $tree->leaf );
		// Dependencies come from the same container, so the Leaf is shared.
		$this->assertSame( $tree->leaf, $tree->branch->leaf );
		$this->assertTrue( $this->container->has( Branch::class ) );
		$this->assertTrue( $this->container->has( Leaf::class ) );
	}

	public function test_explicit_args_bypass_autowiring(): void {
		$pair = $this->container->make( Pair::class, 'x', 'y' );

		$this->assertSame( 'x', $pair->a );
		$this->assertSame( 'y', $pair->b );
	}

	public function test_args_are_ignored_once_the_singleton_exists(): void {
		$first  = $this->container->make( WithPrimitive::class, 'first' );
		$second = $this->container->make( WithPrimitive::class, 'second' );

		$this->assertSame( $first, $second );
		$this->assertSame( 'first', $second->name );
	}

	public function test_primitive_params_with_defaults_use_the_default(): void {
		$object = $this->container->make( WithDefaults::class );

		$this->assertSame( 5, $object->size );
		$this->assertNull( $object->label );
	}

	public function test_untyped_param_with_default_uses_the_default(): void {
		$this->assertSame( 'dflt', $this->container->make( WithUntyped::class )->anything );
	}

	public function test_false_static_condition_makes_make_return_null_and_registers_nothing(): void {
		$this->assertNull( $this->container->make( Disabled::class ) );
		$this->assertFalse( $this->container->has( Disabled::class ) );
	}

	public function test_true_static_condition_instantiates(): void {
		$this->assertInstanceOf( Enabled::class, $this->container->make( Enabled::class ) );
	}

	public function test_non_static_condition_method_is_ignored(): void {
		$this->assertInstanceOf( InstanceCondition::class, $this->container->make( InstanceCondition::class ) );
	}

	public function test_unknown_class_interface_and_abstract_return_null(): void {
		$this->assertNull( $this->container->make( 'No\\Such\\Thing' ) );
		$this->assertNull( $this->container->make( Contract::class ) );
		$this->assertNull( $this->container->make( AbstractThing::class ) );

		$this->assertFalse( $this->container->has( 'No\\Such\\Thing' ) );
		$this->assertFalse( $this->container->has( Contract::class ) );
	}

	public function test_primitive_without_default_makes_make_return_null_instead_of_throwing(): void {
		$this->assertNull( $this->container->make( WithPrimitive::class ) );
		$this->assertFalse( $this->container->has( WithPrimitive::class ) );
	}

	public function test_failure_of_a_nested_dependency_is_swallowed_then_leaks_an_argument_count_error(): void {
		// Pins current (odd) behaviour: the inner make() returns null for the
		// unresolvable primitive, the outer resolver omits that argument, and the
		// constructor call then fails with an uncaught ArgumentCountError.
		$this->expectException( \ArgumentCountError::class );

		$this->container->make( DependsOnPrimitiveThing::class );
	}

	public function test_unresolvable_optional_class_dependency_is_silently_omitted_and_default_applies(): void {
		// Pins current behaviour: Contract cannot be resolved, so no argument is
		// passed and the parameter default is used.
		$object = $this->container->make( WithOptionalInterface::class );

		$this->assertInstanceOf( WithOptionalInterface::class, $object );
		$this->assertNull( $object->contract );
	}

	public function test_unresolvable_required_class_dependency_leaks_a_type_error(): void {
		// Pins current (odd) behaviour: the dependency is silently omitted from the
		// argument list, so PHP raises a TypeError that make() does not catch.
		$this->expectException( TypeError::class );

		$this->container->make( WithInterface::class );
	}

	public function test_dependency_with_false_condition_is_omitted_and_leaks_a_type_error(): void {
		$this->expectException( TypeError::class );

		$this->container->make( DependsOnDisabled::class );
	}

	public function test_union_typed_dependency_is_not_supported_and_errors(): void {
		// Pins current behaviour: ReflectionUnionType has no isBuiltin(), so an Error
		// (not a ContainerException) escapes from make().
		$this->expectException( \Error::class );

		$this->container->make( WithUnion::class );
	}

	public function test_get_on_missing_id_throws_container_exception(): void {
		$this->expectException( ContainerException::class );
		$this->expectExceptionMessage( 'Class Missing\\Thing not found in container.' );

		$this->container->get( 'Missing\\Thing' );
	}

	public function test_get_on_missing_id_does_not_throw_not_found_exception(): void {
		// Pins current behaviour: PSR-11 asks for NotFoundExceptionInterface, but
		// get() throws a plain ContainerException.
		try {
			$this->container->get( 'Missing\\Thing' );
			$this->fail( 'Expected an exception.' );
		} catch ( ContainerExceptionInterface $e ) {
			$this->assertSame( ContainerException::class, get_class( $e ) );
			$this->assertNotInstanceOf( NotFoundExceptionInterface::class, $e );
		}
	}

	public function test_get_does_not_autowire(): void {
		$this->expectException( ContainerException::class );

		$this->container->get( Leaf::class );
	}
}
