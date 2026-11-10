<?php
/**
 * Theme-independent gallery lightbox integration.
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether to enhance this frontend request.
 *
 * @return bool
 */
function lightbox_enabled() {
	return ! is_admin() && ! is_feed() && ! wp_is_json_request()
		&& (bool) apply_filters( 'bonniestar_art_lightbox_enabled', true );
}

/**
 * Enqueue deferred frontend assets, including for galleries in templates/patterns.
 *
 * @return void
 */
function enqueue_lightbox() {
	if ( ! lightbox_enabled() ) {
		return;
	}

	$base = plugins_url( 'assets/', dirname( __DIR__ ) . '/bonniestar-art-plugin.php' );
	wp_enqueue_style( 'wp-gallery-lightbox', $base . 'lightbox.css', array(), VERSION );
	wp_enqueue_script(
		'wp-gallery-lightbox',
		$base . 'lightbox.js',
		array(),
		VERSION,
		array(
			'strategy'  => 'defer',
			'in_footer' => false,
		)
	);
	wp_localize_script(
		'wp-gallery-lightbox',
		'wpGalleryLightbox',
		array(
			'title'    => __( 'Visor de imágenes', 'bonniestar-art-plugin' ),
			'open'     => __( 'Ampliar imagen', 'bonniestar-art-plugin' ),
			'close'    => __( 'Cerrar', 'bonniestar-art-plugin' ),
			'previous' => __( 'Imagen anterior', 'bonniestar-art-plugin' ),
			'next'     => __( 'Imagen siguiente', 'bonniestar-art-plugin' ),
			'loading'  => __( 'Cargando imagen…', 'bonniestar-art-plugin' ),
			'error'    => __( 'No se pudo cargar la imagen.', 'bonniestar-art-plugin' ),
			'original' => __( 'Abrir imagen original', 'bonniestar-art-plugin' ),
			/* translators: 1: Current image number, 2: Total images. */
			'position' => __( '%1$s de %2$s', 'bonniestar-art-plugin' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_lightbox' );

/**
 * Avoid competing core overlays, without changing saved block attributes.
 *
 * @param array $block Parsed block.
 * @return array
 */
function replace_core_lightbox( $block ) {
	if ( lightbox_enabled() && in_array( $block['blockName'], array( 'core/image', 'core/gallery' ), true ) ) {
		$block['attrs']['lightbox']['enabled'] = false;
	}
	return $block;
}
add_filter( 'render_block_data', __NAMESPACE__ . '\\replace_core_lightbox', 99 );

/**
 * Supply the full-size media URL rather than deriving it from thumbnail names.
 *
 * @param string $content Rendered image block.
 * @param array  $block   Parsed block.
 * @return string
 */
function lightbox_image_source( $content, $block ) {
	if ( ! lightbox_enabled() ) {
		return $content;
	}
	$html = new \WP_HTML_Tag_Processor( $content );
	if ( ! $html->next_tag( 'IMG' ) ) {
		return $content;
	}
	$id = absint( $block['attrs']['id'] ?? 0 );
	if ( ! $id && preg_match( '/\bwp-image-(\d+)\b/', (string) $html->get_attribute( 'class' ), $matches ) ) {
		$id = absint( $matches[1] );
	}
	$full = $id ? wp_get_attachment_image_src( $id, 'full' ) : false;
	if ( $full ) {
		$html->set_attribute( 'data-gallery-lightbox-src', esc_url_raw( $full[0] ) );
	}
	return $html->get_updated_html();
}
add_filter( 'render_block_core/image', __NAMESPACE__ . '\\lightbox_image_source', 10, 2 );

/**
 * Include media metadata on generated images, including classic galleries.
 *
 * @param array    $attributes Image attributes.
 * @param \WP_Post $attachment Attachment post.
 * @return array
 */
function lightbox_attachment_source( $attributes, $attachment ) {
	if ( lightbox_enabled() ) {
		$full = wp_get_attachment_image_src( $attachment->ID, 'full' );
		if ( $full ) {
			$attributes['data-gallery-lightbox-src'] = esc_url_raw( $full[0] );
		}
	}
	return $attributes;
}
add_filter( 'wp_get_attachment_image_attributes', __NAMESPACE__ . '\\lightbox_attachment_source', 10, 2 );
