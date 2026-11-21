<?php
/**
 * A configurable Companion for tests.
 */

declare( strict_types=1 );

namespace AegisTests\Framework\Fixtures;

use Aegis\Framework\Companion;

final class FakeCompanion implements Companion {

	/**
	 * @param array<string, bool|null> $integrations Answers per integration key; missing keys answer null.
	 * @param bool                     $blocks       Answer for is_block_enabled().
	 */
	public function __construct(
		public array $integrations = [],
		public bool $blocks = true
	) {}

	public function integration_active( string $integration ): ?bool {
		return $this->integrations[ $integration ] ?? null;
	}

	public function is_integration_enabled( string $setting_key ): bool {
		return false;
	}

	public function is_block_enabled( string $block ): bool {
		return $this->blocks;
	}

	public function is_schema_delegated_to_seo( string $schema_type ): bool {
		return true;
	}

	public function is_embed_facade_enabled(): bool {
		return false;
	}

	public function owns_emoji_removal(): bool {
		return true;
	}

	public function owns_co_authors_plus(): bool {
		return true;
	}

	public function should_hide_for_visibility( array $visibility ): ?bool {
		return true;
	}

	public function is_conditionals_extra_enabled( string $group, string $key ): bool {
		return false;
	}

	public function conditionals_editor_settings(): array {
		return [ 'fake' => true ];
	}

	public function conditionals_timezone_choices(): array {
		return [ 'UTC' ];
	}

	public function current_user_can_manage_conditionals(): bool {
		return false;
	}

	public function wp_fusion_editor_data(): ?array {
		return [ 'tags' => [ 't' ], 'lists' => [ 'l' ] ];
	}
}
