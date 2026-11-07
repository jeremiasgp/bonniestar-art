<?php
/**
 * Title: Artist site header
 * Slug: bonniestar-art-theme/site-header
 * Inserter: no
 *
 * @package BonniestarArtTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bonniestar_art_header_links = array(
	array(
		'label' => __( 'Obras', 'bonniestar-art-theme' ),
		'url'   => home_url( '/#obras' ),
		'kind'  => 'custom',
	),
	array(
		'label' => __( 'La mirada', 'bonniestar-art-theme' ),
		'url'   => home_url( '/#mirada' ),
		'kind'  => 'custom',
	),
	array(
		'label' => __( 'Contacto', 'bonniestar-art-theme' ),
		'url'   => home_url( '/#contacto' ),
		'kind'  => 'custom',
	),
);
?>
<!-- wp:group {"align":"wide","className":"bonniestar-art-header","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide bonniestar-art-header">
	<!-- wp:group {"className":"bonniestar-art-brand","layout":{"type":"flex","flexWrap":"nowrap"}} -->
	<div class="wp-block-group bonniestar-art-brand">
		<!-- wp:site-logo {"width":56,"shouldSyncIcon":false} /-->
		<!-- wp:site-title {"level":0,"fontSize":"large"} /-->
	</div>
	<!-- /wp:group -->
	<!-- wp:group {"className":"bonniestar-art-header-actions","layout":{"type":"flex","flexWrap":"nowrap"}} -->
	<div class="wp-block-group bonniestar-art-header-actions">
		<!-- wp:navigation {"overlayMenu":"mobile","className":"bonniestar-art-navigation","layout":{"type":"flex","justifyContent":"right"}} -->
			<?php foreach ( $bonniestar_art_header_links as $bonniestar_art_header_link ) : ?>
				<!-- wp:navigation-link <?php echo wp_json_encode( $bonniestar_art_header_link ); ?> /-->
			<?php endforeach; ?>
		<!-- /wp:navigation -->
		<!-- wp:social-links {"className":"is-style-logos-only bonniestar-art-social","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<ul class="wp-block-social-links is-style-logos-only bonniestar-art-social">
			<!-- wp:social-link {"service":"instagram"} /-->
		</ul>
		<!-- /wp:social-links -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
