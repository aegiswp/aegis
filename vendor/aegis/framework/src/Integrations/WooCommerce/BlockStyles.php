<?php
/**
 * WooCommerce block styles integration.
 *
 * @package    Aegis\Framework\Integrations\WooCommerce
 * @since      1.0.0
 */

declare( strict_types=1 );

namespace Aegis\Framework\Integrations\WooCommerce;

use Aegis\Container\Interfaces\Conditional;
use Aegis\Framework\Companion;
use Aegis\Framework\Companions;
use Aegis\Framework\InlineAssets\Styleable;
use Aegis\Framework\InlineAssets\Styles;
use Aegis\Framework\Integrations\Detect;

class BlockStyles implements Conditional, Styleable {

	public static function condition(): bool {
		$active = Companions::get()->integration_active( Companion::INTEGRATION_WOOCOMMERCE );

		if ( null !== $active ) {
			return $active;
		}

		return Detect::woocommerce();
	}

	public function styles( Styles $styles ): void {
		$styles->add_file(
			'plugins/woocommerce/woocommerce.css',
			[
				'woocommerce',
				'wc-block',
				'wp-block-woocommerce',
				'product-results-count',
				'catalog-sorting',
				'woocommerce-ordering',
				'woocommerce-result-count',
			]
		);

		$styles->add_file(
			'plugins/woocommerce/woocommerce-breadcrumbs.css',
			[
				'woocommerce-breadcrumb',
				'wc-block-breadcrumbs',
				'wp-block-woocommerce-breadcrumbs',
			]
		);
	}
}
