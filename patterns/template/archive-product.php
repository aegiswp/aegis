<?php
/**
 * Title: Archive Product
 * Slug: archive-product
 * Categories: template
 * Template Types: archive-product
 * Inserter: false
 */
?>

<!-- wp:template-part {"slug":"header","tagName":"header","className":"site-header"} /-->

<!-- wp:group {"tagName":"main","anchor":"main","metadata":{"name":"Main"},"className":"site-main","style":{"spacing":{"padding":{"top":"var:preset|spacing|sm","bottom":"var:preset|spacing|lg"}}},"layout":{"type":"constrained"}} -->
<main id="main" class="wp-block-group site-main" style="padding-top:var(--wp--preset--spacing--sm);padding-bottom:var(--wp--preset--spacing--lg)">
	<!-- wp:woocommerce/breadcrumbs /-->

	<!-- wp:woocommerce/store-notices /-->

	<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|sm"}}},"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
	<div class="wp-block-group alignwide" style="margin-bottom:var(--wp--preset--spacing--sm)">
		<!-- wp:query-title {"type":"archive","showPrefix":false} /-->

		<!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:woocommerce/product-results-count /-->
			<!-- wp:woocommerce/catalog-sorting /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|md"}}}} -->
	<div class="wp-block-columns alignwide">
<?php aegis_pattern_partial( 'product-filter-column', array( 'heading' => esc_html__( 'Filter Products', 'aegis' ) ) ); ?>


<?php
aegis_pattern_partial(
	'product-collection-column',
	array(
		'query_id'    => 1,
		'empty_title' => esc_html__( 'No results found', 'aegis' ),
	)
);
?>

	</div>
	<!-- /wp:columns -->
</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","tagName":"footer","className":"site-footer"} /-->