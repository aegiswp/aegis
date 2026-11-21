<?php
/**
 * The questions the framework asks of a companion plugin.
 *
 * @package Aegis\Framework
 */

declare( strict_types=1 );

namespace Aegis\Framework;

/**
 * What the framework needs to know from a companion plugin (Aegis Dash).
 *
 * The framework never names plugin classes. A plugin supplies an implementation
 * through the `aegis_framework_companion` filter (see {@see Companions}); with
 * none, {@see NullCompanion} answers every question as "no plugin installed".
 *
 * Methods returning `?bool` or `?array` use null for "the companion has no
 * opinion", and the framework then applies its own native check.
 */
interface Companion {

	public const INTEGRATION_AFFILIATEWP      = 'affiliatewp';
	public const INTEGRATION_CODE_BLOCK_PRO   = 'code-block-pro';
	public const INTEGRATION_EDD              = 'easy-digital-downloads';
	public const INTEGRATION_FLUENT_BOOKING   = 'fluent-booking';
	public const INTEGRATION_FLUENT_FORMS     = 'fluent-forms';
	public const INTEGRATION_GRAVITY_FORMS    = 'gravity-forms';
	public const INTEGRATION_LEARNDASH        = 'learndash';
	public const INTEGRATION_LIFTERLMS        = 'lifterlms';
	public const INTEGRATION_META_BOX         = 'meta-box';
	public const INTEGRATION_NINJA_FORMS      = 'ninja-forms';
	public const INTEGRATION_SENSEI_LMS       = 'sensei-lms';
	public const INTEGRATION_SYNTAX_HIGHLIGHT = 'syntax-highlighting';
	public const INTEGRATION_WOOCOMMERCE      = 'woocommerce';

	/**
	 * Whether a third-party plugin is active, as the companion detects it.
	 *
	 * @param string $integration One of the INTEGRATION_* constants.
	 *
	 * @return bool|null Null when the companion does not detect that plugin.
	 */
	public function integration_active( string $integration ): ?bool;

	/**
	 * Whether the integration's setting is on in the dashboard.
	 *
	 * @param string $setting_key Integration setting key.
	 */
	public function is_integration_enabled( string $setting_key ): bool;

	/**
	 * Whether a block feature is on in the dashboard.
	 *
	 * @param string $block Block feature key.
	 */
	public function is_block_enabled( string $block ): bool;

	/**
	 * Whether the SEO plugin outputs a schema type instead of the framework.
	 *
	 * @param string $schema_type For example `faq`.
	 */
	public function is_schema_delegated_to_seo( string $schema_type ): bool;

	/**
	 * Whether embed facades are on.
	 */
	public function is_embed_facade_enabled(): bool;

	/**
	 * Whether the companion strips emoji scripts and styles itself.
	 */
	public function owns_emoji_removal(): bool;

	/**
	 * Whether the companion implements Co-Authors Plus support itself.
	 */
	public function owns_co_authors_plus(): bool;

	/**
	 * Whether a block's visibility settings hide it from the current visitor.
	 *
	 * @param array<string, mixed> $visibility Visibility settings.
	 *
	 * @return bool|null True to hide, false to show; null when the companion has no evaluator.
	 */
	public function should_hide_for_visibility( array $visibility ): ?bool;

	/**
	 * Whether a conditionals extra (such as `visibility`, `lockdown`) is on.
	 *
	 * @param string $group Settings group.
	 * @param string $key   Setting key.
	 */
	public function is_conditionals_extra_enabled( string $group, string $key ): bool;

	/**
	 * Conditional logic settings for the editor script data.
	 *
	 * @return array<string, mixed>
	 */
	public function conditionals_editor_settings(): array;

	/**
	 * Timezone options for the editor.
	 *
	 * @return array<int|string, mixed>
	 */
	public function conditionals_timezone_choices(): array;

	/**
	 * Whether the current user may manage conditionals.
	 */
	public function current_user_can_manage_conditionals(): bool;

	/**
	 * WP Fusion tags and lists for the editor.
	 *
	 * @return array{tags: array, lists: array}|null Null when WP Fusion support is not available.
	 */
	public function wp_fusion_editor_data(): ?array;
}
