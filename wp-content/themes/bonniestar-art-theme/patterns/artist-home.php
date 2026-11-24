<?php
/**
 * Title: Artwork portfolio and About
 * Slug: bonniestar-art-theme/artist-home
 * Categories: featured, gallery
 * Description: A live masonry portfolio of Obras, artist introduction and contact.
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
	<!-- wp:query {"queryId":1,"query":{"perPage":18,"pages":0,"offset":0,"postType":"bonniestar_art_obra","order":"asc","orderBy":"title","inherit":false},"align":"wide","className":"bonniestar-art-portfolio-query"} -->
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
<!-- wp:group {"tagName":"section","align":"wide","className":"bonniestar-art-section bonniestar-art-about","layout":{"type":"constrained","justifyContent":"left"}} -->
<section class="wp-block-group alignwide bonniestar-art-section bonniestar-art-about" id="about">
	<!-- wp:paragraph {"className":"bonniestar-art-eyebrow"} -->
	<p class="bonniestar-art-eyebrow"><?php esc_html_e( 'Sobre la artista', 'bonniestar-art-theme' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'About Bonnie Star', 'bonniestar-art-theme' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"fontSize":"large"} -->
	<p class="has-large-font-size"><?php esc_html_e( 'Pintura al óleo y acrílico. Surrealismo con toques clásicos.', 'bonniestar-art-theme' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Para conocer más sobre la artista y su obra, visitá su Instagram.', 'bonniestar-art-theme' ); ?> <a href="https://www.instagram.com/bonnie.star.art/">@bonnie.star.art</a></p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","align":"wide","className":"bonniestar-art-section bonniestar-art-contact","layout":{"type":"constrained","justifyContent":"left"}} -->
<section class="wp-block-group alignwide bonniestar-art-section bonniestar-art-contact" id="contacto">
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Contacto', 'bonniestar-art-theme' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p><a href="mailto:liebybonnie@gmail.com">liebybonnie@gmail.com</a></p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
