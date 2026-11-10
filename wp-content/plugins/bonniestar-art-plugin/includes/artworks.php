<?php
/**
 * Artwork content model and reusable public rendering.
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ARTWORK_TYPE    = 'bonniestar_art_obra';
const TECHNIQUE_META  = '_bonniestar_art_technique';
const DIMENSIONS_META = '_bonniestar_art_dimensions';
const GALLERY_META    = '_bonniestar_art_gallery';

/**
 * Technique keys remain unchanged across languages.
 *
 * @return array
 */
function artwork_techniques() {
	return array(
		'oleo'     => __( 'Óleo', 'bonniestar-art-plugin' ),
		'acrilico' => __( 'Acrílico', 'bonniestar-art-plugin' ),
		'mixta'    => __( 'Mixta', 'bonniestar-art-plugin' ),
	);
}

/**
 * Register the post type and private, revision-enabled metadata.
 *
 * @return void
 */
function register_artworks() {
	register_post_type(
		ARTWORK_TYPE,
		array(
			'labels'       => array(
				'name'               => __( 'Obras', 'bonniestar-art-plugin' ),
				'singular_name'      => __( 'Obra', 'bonniestar-art-plugin' ),
				'add_new'            => __( 'Añadir obra', 'bonniestar-art-plugin' ),
				'add_new_item'       => __( 'Añadir nueva obra', 'bonniestar-art-plugin' ),
				'edit_item'          => __( 'Editar obra', 'bonniestar-art-plugin' ),
				'new_item'           => __( 'Nueva obra', 'bonniestar-art-plugin' ),
				'view_item'          => __( 'Ver obra', 'bonniestar-art-plugin' ),
				'view_items'         => __( 'Ver obras', 'bonniestar-art-plugin' ),
				'search_items'       => __( 'Buscar obras', 'bonniestar-art-plugin' ),
				'not_found'          => __( 'No se encontraron obras.', 'bonniestar-art-plugin' ),
				'not_found_in_trash' => __( 'No hay obras en la papelera.', 'bonniestar-art-plugin' ),
				'all_items'          => __( 'Todas las obras', 'bonniestar-art-plugin' ),
				'featured_image'     => __( 'Imagen destacada de la obra', 'bonniestar-art-plugin' ),
				'set_featured_image' => __( 'Establecer imagen destacada', 'bonniestar-art-plugin' ),
				'item_updated'       => __( 'Obra actualizada.', 'bonniestar-art-plugin' ),
				'item_published'     => __( 'Obra publicada.', 'bonniestar-art-plugin' ),
			),
			'public'       => true,
			'show_in_rest' => true, // Required by the authenticated native block editor.
			'rest_base'    => 'obras',
			'has_archive'  => 'obras',
			'rewrite'      => array(
				'slug'       => 'obras',
				'with_front' => false,
			),
			'menu_icon'    => 'dashicons-art',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'taxonomies'   => array(),
		)
	);
	foreach ( array(
		TECHNIQUE_META  => 'string',
		DIMENSIONS_META => 'string',
		GALLERY_META    => 'array',
	) as $key => $type ) {
		register_post_meta(
			ARTWORK_TYPE,
			$key,
			array(
				'type'              => $type,
				'single'            => true,
				'default'           => 'array' === $type ? array() : '',
				'show_in_rest'      => false,
				'revisions_enabled' => true,
				'sanitize_callback' => GALLERY_META === $key ? __NAMESPACE__ . '\\sanitize_artwork_gallery' : ( TECHNIQUE_META === $key ? __NAMESPACE__ . '\\sanitize_artwork_technique' : 'sanitize_text_field' ),
			)
		);
	}
}
add_action( 'init', __NAMESPACE__ . '\\register_artworks' );

/**
 * Validate a technique against the fixed option list.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function sanitize_artwork_technique( $value ) {
	return is_string( $value ) && isset( artwork_techniques()[ $value ] ) ? $value : '';
}

/**
 * Keep ordered, unique IDs of existing image attachments.
 *
 * @param mixed $value Array of IDs or a comma-separated form value.
 * @return array
 */
function sanitize_artwork_gallery( $value ) {
	$values = is_string( $value ) ? explode( ',', $value ) : $value;
	$result = array();
	if ( ! is_array( $values ) ) {
		return $result;
	}
	foreach ( $values as $id ) {
		if ( ! is_int( $id ) && ! ( is_string( $id ) && ctype_digit( trim( $id ) ) ) ) {
			continue;
		}
		$id = (int) $id;
		if ( $id > 0 && 'attachment' === get_post_type( $id ) && wp_attachment_is_image( $id ) && ! in_array( $id, $result, true ) ) {
			$result[] = $id;
		}
	}
	return $result;
}

/**
 * Translate public field labels using Polylang when available.
 *
 * @param string $label Already gettext-translated label.
 * @return string
 */
function artwork_label( $label ) {
	return function_exists( 'pll__' ) ? pll__( $label ) : $label;
}

/**
 * Render artwork facts in a native Shortcode block or another theme.
 *
 * @return string
 */
function artwork_details_shortcode() {
	$id = get_the_ID();
	if ( ARTWORK_TYPE !== get_post_type( $id ) ) {
		return '';
	}
	$technique  = get_post_meta( $id, TECHNIQUE_META, true );
	$dimensions = get_post_meta( $id, DIMENSIONS_META, true );
	$choices    = artwork_techniques();
	$fields     = array();
	if ( isset( $choices[ $technique ] ) ) {
		$fields[ artwork_label( __( 'Técnica', 'bonniestar-art-plugin' ) ) ] = artwork_label( $choices[ $technique ] );
	}
	if ( '' !== $dimensions ) {
		$fields[ artwork_label( __( 'Medidas', 'bonniestar-art-plugin' ) ) ] = $dimensions;
	}
	if ( ! $fields ) {
		return '';
	}
	$output = '<dl class="artwork-details">';
	foreach ( $fields as $label => $value ) {
		$output .= '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
	}
	return $output . '</dl>';
}
add_shortcode( 'bonniestar_art_obra_details', __NAMESPACE__ . '\\artwork_details_shortcode' );

/**
 * Render gallery images using media APIs and the existing lightbox.
 *
 * @return string
 */
function artwork_gallery_shortcode() {
	$id = get_the_ID();
	if ( ARTWORK_TYPE !== get_post_type( $id ) ) {
		return '';
	}
	$images = sanitize_artwork_gallery( get_post_meta( $id, GALLERY_META, true ) );
	if ( ! $images ) {
		return '';
	}
	$output = '<div class="artwork-gallery" data-gallery-lightbox role="group" aria-label="' . esc_attr( artwork_label( __( 'Galería de la obra', 'bonniestar-art-plugin' ) ) ) . '">';
	foreach ( $images as $image_id ) {
		$output .= '<figure class="wp-block-image">' . wp_get_attachment_image( $image_id, 'large' );
		$caption = wp_get_attachment_caption( $image_id );
		if ( $caption ) {
			$output .= '<figcaption>' . esc_html( $caption ) . '</figcaption>';
		}
		$output .= '</figure>';
	}
	return $output . '</div>';
}
add_shortcode( 'bonniestar_art_obra_gallery', __NAMESPACE__ . '\\artwork_gallery_shortcode' );
