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
