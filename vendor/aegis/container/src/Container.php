<?php
/**
 * Simple Auto-Wiring Dependency Injection Container
 *
 * This file defines a PSR-11 compliant dependency injection container that supports
 * auto-wiring of dependencies. It is designed to be lightweight and easy to use,
 * providing a simple way to manage object creation and dependency resolution.
 *
 * Responsibilities:
 * - Implements Psr\Container\ContainerInterface for interoperability.
 * - Provides `get`, `has`, and `make` methods for service location and creation.
 * - Automatically resolves and injects dependencies using reflection.
 * - Supports conditional loading of services.
 *
 * @package    Aegis\Container
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/theme
 */

declare( strict_types=1 );

namespace Aegis\Container;

use Aegis\Container\Exceptions\ContainerException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use function is_object;

/**
 * A simple, auto-wiring dependency injection container that implements the PSR-11 standard.
 * This container is responsible for instantiating and managing the lifecycle of objects,
 * automatically resolving their dependencies through reflection.
 *
 * @package Aegis\Container
 * @since 1.0.0
 */
class Container implements ContainerInterface {

	/**
	 * Holds the singleton instances of resolved services, keyed by their class name.
	 *
	 * @var array<string, mixed>
	 */
	private array $instances = [];

	/**
	 * Retrieves a service from the container by its identifier (class name).
	 * This method adheres to the PSR-11 `get` standard.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The unique identifier for the service (fully qualified class name).
	 *
	 * @return mixed The resolved service instance.
	 * @throws ContainerException If the service is not found in the container.
	 */
	public function get( string $id ) {
		if ( ! $this->has( $id ) ) {
			throw new ContainerException( "Class {$id} not found in container." );
		}

		return $this->instances[ $id ];
	}

	/**
	 * Checks if a service identifier is registered in the container.
	 * This method adheres to the PSR-11 `has` standard.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The unique identifier for the service (fully qualified class name).
	 *
	 * @return bool True if the service is registered, false otherwise.
	 */
	public function has( string $id ): bool {
		return isset( $this->instances[ $id ] );
	}

	/**
	 * Creates and returns a new instance of a class, or returns an existing singleton instance.
	 * This method handles the core auto-wiring logic, resolving dependencies recursively.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id   The fully qualified class name to instantiate.
	 * @param mixed  ...$args Optional arguments to pass to the class constructor, bypassing auto-wiring for those specific parameters.
	 *
	 * @return mixed|null The instantiated object, or null if instantiation fails.
	 */
	public function make( string $id, ...$args ) {
		// If the service is not yet registered, add it to the instances array.
		if ( ! $this->has( $id ) ) {
			$this->instances[ $id ] = null;
		}

		// Return the existing instance if it is already a resolved object.
		if ( is_object( $this->instances[ $id ] ) ) {
			return $this->instances[ $id ];
		}

		// Use reflection to inspect the class and its dependencies.
		try {
			$reflector = new ReflectionClass( $id );
		} catch ( ReflectionException ) {
			return null;
		}

		// The class must be instantiable (e.g., not an abstract class or interface).
		if ( ! $reflector->isInstantiable() ) {
			return null;
		}

		// Check for a static `condition` method to conditionally load the service.
		$condition = true;
		if ( $reflector->hasMethod( 'condition' ) ) {
			$method = $reflector->getMethod( 'condition' );

			if ( $method->isStatic() ) {
				try {
					$condition = (bool) $method->invoke( null );
				} catch ( ReflectionException ) {
					return null;
				}
			}
		}

		// If the condition is not met, do not instantiate the service.
		if ( ! $condition ) {
			return null;
		}

		$constructor = $reflector->getConstructor();

		// Instantiate the class, resolving dependencies as needed.
		try {
			if ( $args ) {
				// Use provided arguments for instantiation.
				$instance = $reflector->newInstanceArgs( $args );
			} elseif ( $constructor ) {
				// Resolve constructor parameters automatically.
				$parameters   = $constructor->getParameters();
				$dependencies = $this->resolve_parameters( $parameters );
				$instance     = $reflector->newInstanceArgs( $dependencies );
			} else {
				// No constructor, create a simple instance.
				$instance = $reflector->newInstance();
			}
		} catch ( ReflectionException | ContainerException ) {
			return null;
		}

		if ( ! is_object( $instance ) ) {
			return null;
		}

		// Store the newly created instance for reuse.
		$this->instances[ $id ] = $instance;

		return $instance;
	}

	/**
	 * Resolves the dependencies for a class constructor by inspecting its parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param ReflectionParameter[] $parameters An array of reflection parameters from the constructor.
	 *
	 * @return array An array of resolved dependency instances.
	 * @throws ContainerException If a non-optional, non-built-in dependency cannot be resolved.
	 */
	private function resolve_parameters( array $parameters ): array {
		$dependencies = [];

		foreach ( $parameters as $parameter ) {
			if ( ! $parameter instanceof ReflectionParameter ) {
				continue;
			}

			$type = $parameter->getType();

			// Handle built-in types (string, int, etc.) and parameters without a type hint.
			if ( ! $type || $type->isBuiltin() ) {
				if ( $parameter->isDefaultValueAvailable() ) {
					// If a default value is available, use it.
					$dependencies[] = $parameter->getDefaultValue();
				} else {
					// Auto-resolution of primitive types is not supported.
					$type_name  = $type ? $type->getName() : 'mixed';
					$class_name = $parameter->getDeclaringClass()->name;

					throw new ContainerException( "Cannot auto-resolve primitive type: '{$type_name}' for {$class_name}." );
				}
			} else {
				// For object type hints, recursively resolve the dependency.
				$resolved = $this->make( $type->getName() );

				if ( $resolved ) {
					$dependencies[] = $resolved;
				}
			}
		}

		return $dependencies;
	}
}
