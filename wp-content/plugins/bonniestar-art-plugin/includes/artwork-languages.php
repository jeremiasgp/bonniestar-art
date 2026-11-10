<?php
/**
 * Optional Polylang integration, without making the content depend on it.
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include artworks in language management.
 *
 * @param array $types Translatable post types.
 * @return array
 */
function artwork_language_types( $types ) {
	$types[ ARTWORK_TYPE ] = ARTWORK_TYPE;
	return $types;
}
add_filter( 'pll_get_post_types', __NAMESPACE__ . '\\artwork_language_types' );

/**
 * Share physical artwork data, but keep title/content independent.
 *
 * @param array $keys Meta keys to copy/synchronize.
 * @param bool  $sync Whether this is synchronization.
 * @param int   $from Source post.
 * @return array
 */
function artwork_language_meta( $keys, $sync, $from ) {
	if ( ARTWORK_TYPE === get_post_type( $from ) ) {
		$keys = array_merge( $keys, array( TECHNIQUE_META, DIMENSIONS_META, GALLERY_META, '_thumbnail_id' ) );
	}
	return array_values( array_unique( $keys ) );
}
add_filter( 'pll_copy_post_metas', __NAMESPACE__ . '\\artwork_language_meta', 10, 3 );

/**
 * Use translated attachment records when available, keeping shared files.
 *
 * @param mixed  $value Meta value.
 * @param string $key   Meta key.
 * @param string $lang  Destination language.
 * @return mixed
 */
function artwork_translate_gallery( $value, $key, $lang ) {
	if ( GALLERY_META !== $key || ! is_array( $value ) || ! function_exists( 'pll_get_post' ) ) {
		return $value;
	}
	return array_values(
		array_unique(
			array_map(
				static function ( $id ) use ( $lang ) {
					$translated = pll_get_post( $id, $lang );
					return $translated ? $translated : $id;
				},
				$value
			)
		)
	);
}
add_filter( 'pll_translate_post_meta', __NAMESPACE__ . '\\artwork_translate_gallery', 10, 3 );

/**
 * Make public field labels editable in Languages > Translations.
 *
 * @return void
 */
function register_artwork_strings() {
	if ( function_exists( 'pll_register_string' ) ) {
		$labels = array_merge( artwork_techniques(), array( __( 'Técnica', 'bonniestar-art-plugin' ), __( 'Medidas', 'bonniestar-art-plugin' ), __( 'Galería de la obra', 'bonniestar-art-plugin' ) ) );
		foreach ( $labels as $label ) {
			pll_register_string( 'artwork-' . sanitize_title( $label ), $label, 'Obras' );
		}
	}
}
add_action( 'admin_init', __NAMESPACE__ . '\\register_artwork_strings' );
