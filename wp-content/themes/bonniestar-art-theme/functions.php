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
		(string) filemtime( get_theme_file_path( 'style.css' ) )
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

/**
 * Enhance the native email popover with clipboard support and viewport positioning.
 *
 * @return void
 */
function bonniestar_art_theme_enqueue_contact() {
	wp_enqueue_script( 'bonniestar-art-contact', get_theme_file_uri( 'assets/js/contact.js' ), array(), (string) filemtime( get_theme_file_path( 'assets/js/contact.js' ) ), array( 'strategy' => 'defer' ) );
	wp_localize_script(
		'bonniestar-art-contact',
		'bonniestarContact',
		array(
			'copied' => __( 'Copiado', 'bonniestar-art-theme' ),
			'failed' => __( 'No se pudo copiar. Seleccioná el correo para copiarlo manualmente.', 'bonniestar-art-theme' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'bonniestar_art_theme_enqueue_contact' );

/**
 * Supply the local brand icon unless an administrator selected a native site icon.
 *
 * @return void
 */
function bonniestar_art_theme_favicon() {
	if ( has_site_icon() ) {
		return;
	}
	foreach ( array( 32, 192 ) as $size ) {
		printf( '<link rel="icon" href="%s" sizes="%dx%d" type="image/png">' . "\n", esc_url( get_theme_file_uri( 'assets/favicon/icon-' . $size . '.png' ) ), (int) $size, (int) $size );
	}
	printf( '<link rel="apple-touch-icon" href="%s">' . "\n", esc_url( get_theme_file_uri( 'assets/favicon/icon-180.png' ) ) );
}
add_action( 'wp_head', 'bonniestar_art_theme_favicon' );
add_action( 'admin_head', 'bonniestar_art_theme_favicon' );
add_action( 'login_head', 'bonniestar_art_theme_favicon' );
