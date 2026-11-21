<?php
/**
 * Minimal concrete users of Aegis\Framework\InlineAssets\AssetsTrait.
 *
 * Named (not anonymous) because the trait derives the asset type from the
 * class name: a name containing "Style" means css, otherwise js.
 */

declare( strict_types=1 );

namespace AegisTests\Framework\Fixtures;

use Aegis\Framework\InlineAssets\AssetsTrait;

class FakeScripts {
	use AssetsTrait;

	public function enqueue(): void {}

	/** Exposes the private trait methods for tests. */
	public function assets( string $html, bool $load_all ): array {
		return $this->get_assets( $html, $load_all );
	}

	public function inline( ?string $html, bool $load_all ): string {
		return $this->get_inline_assets( $html, $load_all );
	}

	/** Method used as an array callback. */
	public function callback_method( string $html, bool $load_all ): string {
		return 'method:' . $html;
	}
}

class FakeStyles {
	use AssetsTrait;

	public function enqueue(): void {}
}
