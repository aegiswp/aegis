<?php
/**
 * Named classes used as container targets. Auto-wiring is driven by constructor
 * type hints, which anonymous classes cannot be referenced by.
 */

declare( strict_types=1 );

namespace AegisTests\Framework\Fixtures;

interface Contract {}

abstract class AbstractThing {}

class Leaf {}

class Branch {
	public function __construct( public Leaf $leaf ) {}
}

class Tree {
	public function __construct( public Branch $branch, public Leaf $leaf ) {}
}

class WithDefaults {
	public function __construct( public int $size = 5, public ?string $label = null ) {}
}

class WithPrimitive {
	public function __construct( public string $name ) {}
}

class WithUntyped {
	public function __construct( public $anything = 'dflt' ) {}
}

class WithInterface {
	public function __construct( public Contract $contract ) {}
}

class WithOptionalInterface {
	public function __construct( public ?Contract $contract = null ) {}
}

class WithUnion {
	public function __construct( public Leaf|Branch $either ) {}
}

class DependsOnDisabled {
	public function __construct( public Disabled $disabled ) {}
}

class DependsOnPrimitiveThing {
	public function __construct( public WithPrimitive $inner ) {}
}

class Disabled {
	public static function condition(): bool {
		return false;
	}
}

class Enabled {
	public static function condition(): bool {
		return true;
	}
}

class InstanceCondition {
	// Non-static: the container ignores it.
	public function condition(): bool {
		return false;
	}
}

class Counting {
	public static int $constructed = 0;

	public function __construct() {
		++self::$constructed;
	}
}

class Pair {
	public function __construct( public $a, public $b ) {}
}
