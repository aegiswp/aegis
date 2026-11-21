<?php
/**
 * GravityForms Integration Component
 *
 * Provides support for integrating Gravity Forms plugin compatibility in the Aegis Framework.
 *
 * Responsibilities:
 * - Checks for Gravity Forms plugin presence and conditionally registers styles
 *
 * @package    Aegis\Framework\Integrations
 * @since      1.0.0
 * @author     Atmostfear Entertainment
 * @link       https://github.com/aegiswp/theme
 */

declare( strict_types=1 );

namespace Aegis\Framework\Integrations;

use Aegis\Container\Interfaces\Conditional;
use Aegis\Framework\Companion;
use Aegis\Framework\Companions;
use Aegis\Framework\InlineAssets\Styleable;
use Aegis\Framework\InlineAssets\Styles;
use function add_filter;
use function class_exists;
use function defined;
use function function_exists;

class GravityForms implements Conditional, Styleable {

	/**
	 * Condition.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function condition(): bool {
		$active = Companions::get()->integration_active( Companion::INTEGRATION_GRAVITY_FORMS );

		if ( null !== $active ) {
			return $active;
		}

		return class_exists( 'GFForms' )
			|| class_exists( 'GFAPI' )
			|| defined( 'GF_MIN_WP_VERSION' )
			|| function_exists( 'gravity_form' );
	}

	/**
	 * Register styles.
	 *
	 * @since 1.0.0
	 *
	 * @param Styles $styles Styles instance.
	 *
	 * @return void
	 */
	public function styles( Styles $styles ): void {
		$styles->add_file(
			'plugins/gravity-forms.css',
			[
				'gform_wrapper',
				'gravity-theme',
				'gform',
				'gform_body',
				'gform-body',
				'gfield',
			]
		);
	}

	/**
	 * Remove the default Gravity Forms styles.
	 *
	 * @since 1.0.0
	 *
	 * @hook  init
	 * @hook  gform_disable_form_theme_css
	 *
	 * @param bool $disabled Whether form theme CSS is disabled.
	 *
	 * @return bool
	 */
	public function remove_default_styles( bool $disabled = false ): bool {
		add_filter( 'gform_disable_form_theme_css', '__return_true' );

		return true;
	}
}
