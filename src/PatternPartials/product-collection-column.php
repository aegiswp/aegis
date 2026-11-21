<?php
/**
 * Pattern partial: WooCommerce product collection column with pagination and no-results message.
 *
 * Output is byte-identical to the markup the listed patterns used to inline.
 * Used by: patterns/template/archive-product.php and the product-archive-sidebar partial.
 *
 * @package Aegis
 * @since   1.1.0
 *
 * @var array<string, mixed> $args {query_id: int, empty_title: string, search?: bool} empty_title is already escaped.
 */

declare( strict_types=1 );

$aegis_is_search = ! empty( $args['search'] );

?>
		<!-- wp:column {"width":"75%"} -->
		<div class="wp-block-column" style="flex-basis:75%">
			<!-- wp:woocommerce/product-collection {"queryId":<?php echo (int) $args['query_id']; ?>,"query":{"perPage":12,"pages":0,"offset":0,"postType":"product","order":"<?php echo $aegis_is_search ? 'desc' : 'asc'; ?>","orderBy":"<?php echo $aegis_is_search ? 'relevance' : 'title'; ?>","search":"","exclude":[],"inherit":true,"taxQuery":{},"isProductCollectionBlock":true,"featured":false,"woocommerceOnSale":false,"woocommerceStockStatus":["instock","outofstock","onbackorder"],"woocommerceAttributes":[],"woocommerceHandPickedProducts":[],"filterable":true,"relatedBy":{"categories":true,"tags":true}},"tagName":"div","displayLayout":{"type":"flex","columns":3,"shrinkColumns":true},"dimensions":{"widthType":"fill"},"queryContextIncludes":["collection"]} -->
			<div class="wp-block-woocommerce-product-collection">
				<!-- wp:woocommerce/product-template -->
				<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|sm"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
				<div class="wp-block-group" style="padding-bottom:var(--wp--preset--spacing--sm)">
					<!-- wp:woocommerce/product-image {"showSaleBadge":false,"imageSizing":"thumbnail","isDescendentOfQueryLoop":true} -->
					<!-- wp:woocommerce/product-sale-badge {"align":"right"} /-->
					<!-- /wp:woocommerce/product-image -->

					<!-- wp:post-title {"textAlign":"center","level":3,"isLink":true,"style":{"spacing":{"margin":{"bottom":"0","top":"0"}},"typography":{"lineHeight":"1.4","fontSize":"16px"}},"__woocommerceNamespace":"woocommerce/product-collection/product-title"} /-->

					<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"textAlign":"center","style":{"typography":{"fontSize":"16px"}}} /-->

					<!-- wp:woocommerce/product-button {"textAlign":"center","isDescendentOfQueryLoop":true,"fontSize":"14"} /-->
				</div>
				<!-- /wp:group -->
				<!-- /wp:woocommerce/product-template -->

				<!-- wp:query-pagination {"style":{"spacing":{"margin":{"top":"var:preset|spacing|md","bottom":"var:preset|spacing|sm"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
				<!-- wp:query-pagination-previous /-->

				<!-- wp:query-pagination-numbers /-->

				<!-- wp:query-pagination-next /-->
				<!-- /wp:query-pagination -->

				<!-- wp:woocommerce/product-collection-no-results -->
				<!-- wp:group {"layout":{"type":"flex","orientation":"vertical","justifyContent":"center","flexWrap":"wrap"}} -->
				<div class="wp-block-group">
					<!-- wp:paragraph {"fontSize":"18"} -->
					<p class="has-18-font-size"><strong><?php echo $args['empty_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the calling pattern. ?></strong></p>
					<!-- /wp:paragraph -->

<?php if ( $aegis_is_search ) : ?>
					<!-- wp:paragraph {"textColor":"neutral-600"} -->
					<p class="has-neutral-600-color has-text-color"><?php echo esc_html__( 'Sorry, we couldn\'t find any products matching your search. Try a different keyword or browse our store.', 'aegis' ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
					<div class="wp-block-buttons">
						<!-- wp:button {"className":"is-style-outline","fontSize":"14"} -->
<div class="wp-block-button has-custom-font-size has-14-font-size is-style-outline"><a class="wp-block-button__link has-14-font-size has-custom-font-size wp-element-button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( 'Browse All Products', 'aegis' ); ?></a></div>
<!-- /wp:button -->
					</div>
					<!-- /wp:buttons -->
<?php else : ?>
					<!-- wp:paragraph -->
					<p><?php echo esc_html__( 'You can try', 'aegis' ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wc-link-clear-any-filters"><?php echo esc_html__( 'clearing any filters', 'aegis' ); ?></a> <?php echo esc_html__( 'or head to our', 'aegis' ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="wc-link-stores-home"><?php echo esc_html__( 'store\'s home', 'aegis' ); ?></a></p>
					<!-- /wp:paragraph -->
<?php endif; ?>
				</div>
				<!-- /wp:group -->
				<!-- /wp:woocommerce/product-collection-no-results -->
			</div>
			<!-- /wp:woocommerce/product-collection -->
		</div>
		<!-- /wp:column -->
