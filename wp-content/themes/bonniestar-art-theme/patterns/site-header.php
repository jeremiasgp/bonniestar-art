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

$bonniestar_art_home_url     = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
$bonniestar_art_header_links = array(
	array(
		'label' => __( 'Obras', 'bonniestar-art-theme' ),
		'url'   => $bonniestar_art_home_url,
		'kind'  => 'custom',
	),
	array(
		'label' => __( 'About', 'bonniestar-art-theme' ),
		'url'   => $bonniestar_art_home_url . '#about',
		'kind'  => 'custom',
	),
	array(
		'label' => __( 'Contacto', 'bonniestar-art-theme' ),
		'url'   => $bonniestar_art_home_url . '#contacto',
		'kind'  => 'custom',
	),
);
?>
<!-- wp:group {"align":"wide","className":"bonniestar-art-header","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group alignwide bonniestar-art-header">
	<!-- wp:group {"className":"bonniestar-art-brand","layout":{"type":"flex","flexWrap":"nowrap"}} -->
	<div class="wp-block-group bonniestar-art-brand">
		<!-- wp:paragraph {"className":"bonniestar-art-wordmark"} -->
		<p class="bonniestar-art-wordmark"><a href="<?php echo esc_url( $bonniestar_art_home_url ); ?>" class="brand-logo" aria-label="<?php esc_attr_e( 'Bonnie Star — Home', 'bonniestar-art-theme' ); ?>">Bonnie Star</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
	<!-- wp:group {"className":"bonniestar-art-header-actions","layout":{"type":"flex","flexWrap":"nowrap"}} -->
	<div class="wp-block-group bonniestar-art-header-actions">
		<!-- wp:navigation {"overlayMenu":"mobile","overlayBackgroundColor":"surface","overlayTextColor":"ink","className":"bonniestar-art-navigation","layout":{"type":"flex","justifyContent":"right"}} -->
			<?php foreach ( $bonniestar_art_header_links as $bonniestar_art_header_link ) : ?>
				<!-- wp:navigation-link <?php echo wp_json_encode( $bonniestar_art_header_link ); ?> /-->
			<?php endforeach; ?>
			<!-- wp:polylang/navigation-language-switcher {"layout":"horizontal","show_labels":"","show_flags":true,"hide_current":true,"hide_if_no_translation":false,"flag_width":"22px","className":"bonniestar-art-language-switcher bonniestar-art-language-switcher-mobile"} /-->
		<!-- /wp:navigation -->
		<!-- wp:social-links {"className":"is-style-logos-only bonniestar-art-social","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<ul class="wp-block-social-links is-style-logos-only bonniestar-art-social">
			<!-- wp:social-link {"url":"https://www.instagram.com/bonnie.star.art/","service":"instagram"} /-->
		</ul>
		<!-- /wp:social-links -->
		<!-- wp:polylang/language-switcher {"layout":"horizontal","show_labels":"","show_flags":true,"hide_current":true,"hide_if_no_translation":false,"flag_width":"22px","className":"bonniestar-art-language-switcher bonniestar-art-language-switcher-desktop"} /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
