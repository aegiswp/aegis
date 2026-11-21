<?php
/**
 * Pattern partial: WooCommerce product archive template with a filter sidebar (category, tag, search results).
 *
 * Output is byte-identical to the markup the listed patterns used to inline.
 * Used by: patterns/template/taxonomy-product_cat.php, taxonomy-product_tag.php, search-product.php.
 *
 * @package Aegis
 * @since   1.1.0
 *
 * @var array<string, mixed> $args {query_id: int, search?: bool}
 */

declare( strict_types=1 );

$aegis_is_search = ! empty( $args['search'] );

?>
<!-- wp:template-part {"slug":"header","tagName":"header","className":"site-header"} /-->

<!-- wp:group {"tagName":"main","anchor":"main","className":"site-main","metadata":{"name":"Main"},"style":{"spacing":{"padding":{"top":"var:preset|spacing|sm","bottom":"var:preset|spacing|lg"}}},"layout":{"type":"constrained"}} -->
<main id="main" class="wp-block-group site-main" style="padding-top:var(--wp--preset--spacing--sm);padding-bottom:var(--wp--preset--spacing--lg)">
	<!-- wp:woocommerce/breadcrumbs /-->

	<!-- wp:woocommerce/store-notices /-->

	<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|sm"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
	<div class="wp-block-group alignwide" style="margin-bottom:var(--wp--preset--spacing--sm)">
<?php if ( $aegis_is_search ) : ?>
		<!-- wp:query-title {"type":"search","showPrefix":true} /-->
<?php else : ?>
		<!-- wp:query-title {"type":"archive","showPrefix":false} /-->

		<!-- wp:term-description {"textColor":"neutral-600","fontSize":"16"} /-->
<?php endif; ?>
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|sm"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group alignwide" style="margin-bottom:var(--wp--preset--spacing--sm)">
		<!-- wp:woocommerce/product-results-count /-->
		<!-- wp:woocommerce/catalog-sorting /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|md"}}}} -->
	<div class="wp-block-columns alignwide">
<?php
aegis_pattern_partial(
	'product-filter-column',
	array( 'heading' => $aegis_is_search ? esc_html__( 'Refine Results', 'aegis' ) : esc_html__( 'Filter Products', 'aegis' ) )
);
?>


<?php
aegis_pattern_partial(
	'product-collection-column',
	array(
		'query_id'    => $args['query_id'],
		'search'      => $aegis_is_search,
		'empty_title' => esc_html__( 'No products found', 'aegis' ),
	)
);
?>

	</div>
	<!-- /wp:columns -->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer","className":"site-footer"} /-->
