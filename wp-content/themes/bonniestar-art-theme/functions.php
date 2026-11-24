<?php
/**
 * Bonniestar Art Theme asset registration.
 *
 * @package BonniestarArtTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Share the stylesheet between the frontend and block editor.
 *
 * @return void
 */
function bonniestar_art_theme_enqueue_styles() {
	wp_enqueue_style(
		'bonniestar-art-theme',
		get_theme_file_uri( 'style.css' ),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'enqueue_block_assets', 'bonniestar_art_theme_enqueue_styles' );

/**
 * Keep all configured languages available in the header's native selector.
 *
 * @param array $settings Polylang switcher settings.
 * @return array
 */
function bonniestar_art_theme_language_switcher_settings( $settings ) {
	$classes = explode( ' ', $settings['className'] ?? '' );
	if ( in_array( 'bonniestar-art-language-switcher', $classes, true ) ) {
		$settings['hide_if_empty'] = false;
	}
	return $settings;
}
add_filter( 'pll_language_switcher_settings', 'bonniestar_art_theme_language_switcher_settings' );
