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

$bonniestar_art_home_url = function_exists( 'pll_home_url' ) ? pll_home_url() : home_url( '/' );
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
			<!-- wp:polylang/navigation-language-switcher {"layout":"horizontal","show_labels":"","show_flags":true,"hide_current":true,"hide_if_no_translation":false,"flag_width":"22px","className":"bonniestar-art-language-switcher bonniestar-art-language-switcher-mobile"} /-->
		<!-- /wp:navigation -->
		<!-- wp:group {"className":"bonniestar-art-header-contact","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<div class="wp-block-group bonniestar-art-header-contact">
		<!-- wp:social-links {"className":"is-style-logos-only bonniestar-art-social","layout":{"type":"flex","flexWrap":"nowrap"}} -->
		<ul class="wp-block-social-links is-style-logos-only bonniestar-art-social">
			<!-- wp:social-link {"url":"https://www.instagram.com/bonnie.star.art/","service":"instagram"} /-->
		</ul>
		<!-- /wp:social-links -->
		<!-- wp:html -->
		<div class="bonniestar-art-email">
			<button type="button" class="bonniestar-art-icon-button bonniestar-art-email-trigger" popovertarget="bonniestar-art-email-popover" aria-label="<?php esc_attr_e( 'Correo electrónico', 'bonniestar-art-theme' ); ?>">
				<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="m3 6 9 7 9-7"/></svg>
			</button>
			<div id="bonniestar-art-email-popover" class="bonniestar-art-email-popover" popover aria-label="<?php esc_attr_e( 'Correo electrónico', 'bonniestar-art-theme' ); ?>">
				<a href="mailto:liebybonnie@gmail.com" class="bonniestar-art-email-address">liebybonnie@gmail.com</a>
				<button type="button" class="bonniestar-art-icon-button bonniestar-art-email-copy" aria-label="<?php esc_attr_e( 'Copiar correo', 'bonniestar-art-theme' ); ?>" hidden>
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="8" y="8" width="12" height="13" rx="1.5"/><path d="M16 8V3H3v13h5"/></svg>
				</button>
				<span class="bonniestar-art-email-status" role="status" aria-live="polite"></span>
			</div>
		</div>
		<!-- /wp:html -->
		</div>
		<!-- /wp:group -->
		<!-- wp:polylang/language-switcher {"layout":"horizontal","show_labels":"","show_flags":true,"hide_current":true,"hide_if_no_translation":false,"flag_width":"22px","className":"bonniestar-art-language-switcher bonniestar-art-language-switcher-desktop"} /-->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
