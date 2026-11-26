<?php
/**
 * Title: Artwork portfolio
 * Slug: bonniestar-art-theme/artist-home
 * Categories: featured, gallery
 * Description: A live masonry portfolio of Obras.
 *
 * @package BonniestarArtTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"tagName":"section","align":"wide","className":"bonniestar-art-portfolio","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignwide bonniestar-art-portfolio" id="obras">
	<!-- wp:heading {"level":1,"className":"screen-reader-text"} -->
	<h1 class="wp-block-heading screen-reader-text"><?php esc_html_e( 'Obras', 'bonniestar-art-theme' ); ?></h1>
	<!-- /wp:heading -->
	<!-- wp:query {"queryId":1,"query":{"perPage":18,"pages":0,"offset":0,"postType":"bonniestar_art_obra","order":"asc","orderBy":"menu_order","inherit":false},"align":"wide","className":"bonniestar-art-portfolio-query"} -->
	<div class="wp-block-query alignwide bonniestar-art-portfolio-query">
		<!-- wp:post-template {"className":"bonniestar-art-masonry"} -->
			<!-- wp:group {"tagName":"article","className":"bonniestar-art-card","layout":{"type":"default"}} -->
			<article class="wp-block-group bonniestar-art-card">
				<!-- wp:post-featured-image {"isLink":true,"sizeSlug":"large","className":"is-artwork-lightbox"} /-->
			</article>
			<!-- /wp:group -->
		<!-- /wp:post-template -->
		<!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
			<!-- wp:query-pagination-previous /-->
			<!-- wp:query-pagination-numbers /-->
			<!-- wp:query-pagination-next /-->
		<!-- /wp:query-pagination -->
		<!-- wp:query-no-results -->
			<!-- wp:paragraph -->
			<p><?php esc_html_e( 'Próximamente, nuevas obras.', 'bonniestar-art-theme' ); ?></p>
			<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->
	</div>
	<!-- /wp:query -->
</section>
<!-- /wp:group -->
