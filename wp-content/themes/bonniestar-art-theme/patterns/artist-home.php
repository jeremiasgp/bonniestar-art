<?php
/**
 * Title: Artist home and gallery
 * Slug: bonniestar-art-theme/artist-home
 * Categories: featured, gallery
 * Description: A warm artist introduction with an empty gallery ready for original artwork.
 *
 * @package BonniestarArtTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!-- wp:group {"align":"wide","className":"bonniestar-art-hero","layout":{"type":"constrained","justifyContent":"left"}} -->
<div class="wp-block-group alignwide bonniestar-art-hero">
	<!-- wp:paragraph {"className":"bonniestar-art-eyebrow"} -->
	<p class="bonniestar-art-eyebrow"><?php esc_html_e( 'Óleo y acrílico · Hiperrealismo e impresionismo', 'bonniestar-art-theme' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:heading {"level":1,"fontSize":"display"} -->
	<h1 class="wp-block-heading has-display-font-size"><?php esc_html_e( 'La belleza de detenerse a mirar.', 'bonniestar-art-theme' ); ?></h1>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'El detalle, la luz y el color. Dos maneras de acercarse al mundo, unidas por la pintura.', 'bonniestar-art-theme' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#obras"><?php esc_html_e( 'Explorar las obras', 'bonniestar-art-theme' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","align":"wide","className":"bonniestar-art-section","layout":{"type":"constrained","justifyContent":"left"}} -->
<section class="wp-block-group alignwide bonniestar-art-section" id="obras">
	<!-- wp:paragraph {"className":"bonniestar-art-eyebrow"} -->
	<p class="bonniestar-art-eyebrow"><?php esc_html_e( 'La galería', 'bonniestar-art-theme' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Cada obra, una pausa.', 'bonniestar-art-theme' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"className":"bonniestar-art-gallery-note"} -->
	<p class="bonniestar-art-gallery-note"><?php esc_html_e( 'Este espacio espera las primeras obras. La selección de imágenes estará disponible próximamente.', 'bonniestar-art-theme' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:gallery {"columns":3,"imageCrop":false,"linkTo":"none","align":"wide","className":"bonniestar-art-gallery"} -->
	<figure class="wp-block-gallery alignwide has-nested-images columns-3 bonniestar-art-gallery"></figure>
	<!-- /wp:gallery -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","align":"wide","className":"bonniestar-art-section bonniestar-art-statement","layout":{"type":"constrained","justifyContent":"left"}} -->
<section class="wp-block-group alignwide bonniestar-art-section bonniestar-art-statement" id="mirada">
	<!-- wp:paragraph {"className":"bonniestar-art-eyebrow"} -->
	<p class="bonniestar-art-eyebrow"><?php esc_html_e( 'La mirada', 'bonniestar-art-theme' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Entre el detalle y la impresión.', 'bonniestar-art-theme' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'El hiperrealismo invita a descubrir lo que una mirada rápida deja pasar. El impresionismo encuentra en la luz y el gesto otra forma de sentir. Óleo y acrílico dan lugar a ese encuentro.', 'bonniestar-art-theme' ); ?></p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
<!-- wp:group {"tagName":"section","align":"wide","className":"bonniestar-art-section","layout":{"type":"constrained","justifyContent":"left"}} -->
<section class="wp-block-group alignwide bonniestar-art-section" id="contacto">
	<!-- wp:paragraph {"className":"bonniestar-art-eyebrow"} -->
	<p class="bonniestar-art-eyebrow"><?php esc_html_e( 'Contacto', 'bonniestar-art-theme' ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:heading -->
	<h2 class="wp-block-heading"><?php esc_html_e( 'Una conversación alrededor del arte.', 'bonniestar-art-theme' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph -->
	<p><?php esc_html_e( 'Pronto encontrarás aquí los canales de contacto para conversar sobre las obras.', 'bonniestar-art-theme' ); ?></p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
