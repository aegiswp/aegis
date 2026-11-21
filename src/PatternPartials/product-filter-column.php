<?php
/**
 * Pattern partial: WooCommerce filter sidebar column (price, colour, availability, rating, active filters).
 *
 * Output is byte-identical to the markup the listed patterns used to inline.
 * Used by: patterns/template/archive-product.php and the product-archive-sidebar partial.
 *
 * @package Aegis
 * @since   1.1.0
 *
 * @var array<string, mixed> $args {heading: string} Already-escaped heading text.
 */

declare( strict_types=1 );
?>
		<!-- wp:column {"width":"25%"} -->
		<div class="wp-block-column" style="flex-basis:25%">
			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|md"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group">
				<!-- wp:heading {"level":4,"fontSize":"16"} -->
				<h4 class="wp-block-heading has-16-font-size"><?php echo $args['heading']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the calling pattern. ?></h4>
				<!-- /wp:heading -->

				<!-- wp:woocommerce/filter-wrapper {"filterType":"price-filter","heading":"<?php echo esc_attr__( 'Price', 'aegis' ); ?>"} -->
				<div class="wp-block-woocommerce-filter-wrapper">
					<!-- wp:heading {"level":5,"fontSize":"14"} -->
					<h5 class="wp-block-heading has-14-font-size"><?php echo esc_html__( 'Price', 'aegis' ); ?></h5>
					<!-- /wp:heading -->

					<!-- wp:woocommerce/price-filter {"heading":"","lock":{"remove":true}} -->
					<div class="wp-block-woocommerce-price-filter is-loading" data-showinputfields="true" data-showfilterbutton="false" data-heading="" data-heading-level="3">
						<span aria-hidden="true" class="wc-block-product-categories__placeholder"></span>
					</div>
					<!-- /wp:woocommerce/price-filter -->
				</div>
				<!-- /wp:woocommerce/filter-wrapper -->

				<!-- wp:woocommerce/filter-wrapper {"filterType":"attribute-filter","heading":"<?php echo esc_attr__( 'Color', 'aegis' ); ?>"} -->
				<div class="wp-block-woocommerce-filter-wrapper">
					<!-- wp:heading {"level":5,"fontSize":"14"} -->
					<h5 class="wp-block-heading has-14-font-size"><?php echo esc_html__( 'Color', 'aegis' ); ?></h5>
					<!-- /wp:heading -->

					<!-- wp:woocommerce/attribute-filter {"attributeId":0,"heading":"","lock":{"remove":true},"showCounts":true,"headingLevel":3,"queryType":"or"} -->
<div class="wp-block-woocommerce-attribute-filter is-loading"></div>
<!-- /wp:woocommerce/attribute-filter -->
				</div>
				<!-- /wp:woocommerce/filter-wrapper -->

				<!-- wp:woocommerce/filter-wrapper {"filterType":"stock-filter","heading":"<?php echo esc_attr__( 'Availability', 'aegis' ); ?>"} -->
				<div class="wp-block-woocommerce-filter-wrapper">
					<!-- wp:heading {"level":5,"fontSize":"14"} -->
					<h5 class="wp-block-heading has-14-font-size"><?php echo esc_html__( 'Availability', 'aegis' ); ?></h5>
					<!-- /wp:heading -->

					<!-- wp:woocommerce/stock-filter {"heading":"","lock":{"remove":true},"showCounts":true,"headingLevel":3} -->
<div class="wp-block-woocommerce-stock-filter is-loading"></div>
<!-- /wp:woocommerce/stock-filter -->
				</div>
				<!-- /wp:woocommerce/filter-wrapper -->

				<!-- wp:woocommerce/filter-wrapper {"filterType":"rating-filter","heading":"<?php echo esc_attr__( 'Rating', 'aegis' ); ?>"} -->
				<div class="wp-block-woocommerce-filter-wrapper">
					<!-- wp:heading {"level":5,"fontSize":"14"} -->
					<h5 class="wp-block-heading has-14-font-size"><?php echo esc_html__( 'Rating', 'aegis' ); ?></h5>
					<!-- /wp:heading -->

					<!-- wp:woocommerce/rating-filter {"heading":"","lock":{"remove":true},"showCounts":true,"headingLevel":3} -->
<div class="wp-block-woocommerce-rating-filter is-loading"></div>
<!-- /wp:woocommerce/rating-filter -->
				</div>
				<!-- /wp:woocommerce/filter-wrapper -->

				<!-- wp:woocommerce/filter-wrapper {"filterType":"active-filters","heading":"<?php echo esc_attr__( 'Active Filters', 'aegis' ); ?>"} -->
				<div class="wp-block-woocommerce-filter-wrapper">
					<!-- wp:heading {"level":5,"fontSize":"14"} -->
					<h5 class="wp-block-heading has-14-font-size"><?php echo esc_html__( 'Active Filters', 'aegis' ); ?></h5>
					<!-- /wp:heading -->

					<!-- wp:woocommerce/active-filters {"heading":"","lock":{"remove":true},"headingLevel":3,"displayStyle":"list"} -->
<div class="wp-block-woocommerce-active-filters is-loading"><span aria-hidden="true" class="wc-block-active-filters__placeholder"></span></div>
<!-- /wp:woocommerce/active-filters -->
				</div>
				<!-- /wp:woocommerce/filter-wrapper -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
