<?php
/**
 * Companion answers for a site without the companion plugin.
 *
 * @package Aegis\Framework
 */

declare( strict_types=1 );

namespace Aegis\Framework;

/**
 * Used when no plugin supplies a {@see Companion}: features default to on,
 * nothing is delegated, and detection falls back to the framework's own checks.
 */
final class NullCompanion implements Companion {

	/**
	 * Whether a third-party plugin is active, as the companion detects it.
	 *
	 * @param string $integration One of the INTEGRATION_* constants.
	 *
	 * @return bool|null Null when the companion does not detect that plugin.
	 */
	public function integration_active( string $integration ): ?bool {
		return null;
	}

	/**
	 * Whether the integration's setting is on in the dashboard.
	 *
	 * @param string $setting_key Integration setting key.
	 */
	public function is_integration_enabled( string $setting_key ): bool {
		return true;
	}

	/**
	 * Whether a block feature is on in the dashboard.
	 *
	 * @param string $block Block feature key.
	 */
	public function is_block_enabled( string $block ): bool {
		return true;
	}

	/**
	 * Whether the SEO plugin outputs a schema type instead of the framework.
	 *
	 * @param string $schema_type For example `faq`.
	 */
	public function is_schema_delegated_to_seo( string $schema_type ): bool {
		return false;
	}

	/**
	 * Whether embed facades are on.
	 */
	public function is_embed_facade_enabled(): bool {
		return true;
	}

	/**
	 * Whether the companion strips emoji scripts and styles itself.
	 */
	public function owns_emoji_removal(): bool {
		return false;
	}

	/**
	 * Whether the companion implements Co-Authors Plus support itself.
	 */
	public function owns_co_authors_plus(): bool {
		return false;
	}

	/**
	 * Whether a block's visibility settings hide it from the current visitor.
	 *
	 * @param array<string, mixed> $visibility Visibility settings.
	 *
	 * @return bool|null True to hide, false to show; null when the companion has no evaluator.
	 */
	public function should_hide_for_visibility( array $visibility ): ?bool {
		return null;
	}

	/**
	 * Whether a conditionals extra (such as `visibility`, `lockdown`) is on.
	 *
	 * @param string $group Settings group.
	 * @param string $key   Setting key.
	 */
	public function is_conditionals_extra_enabled( string $group, string $key ): bool {
		return true;
	}

	/**
	 * Conditional logic settings for the editor script data.
	 *
	 * @return array<string, mixed>
	 */
	public function conditionals_editor_settings(): array {
		return array();
	}

	/**
	 * Timezone options for the editor.
	 *
	 * @return array<int|string, mixed>
	 */
	public function conditionals_timezone_choices(): array {
		return array();
	}

	/**
	 * Whether the current user may manage conditionals.
	 */
	public function current_user_can_manage_conditionals(): bool {
		return true;
	}

	/**
	 * WP Fusion tags and lists for the editor.
	 *
	 * @return array{tags: array, lists: array}|null Null when WP Fusion support is not available.
	 */
	public function wp_fusion_editor_data(): ?array {
		return null;
	}
}
