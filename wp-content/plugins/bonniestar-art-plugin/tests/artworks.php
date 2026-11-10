<?php
/**
 * Artwork integration checks. Temporary drafts are removed in finally.
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin\Tests;

use const Bonniestar_Art_Plugin\ARTWORK_TYPE;
use const Bonniestar_Art_Plugin\TECHNIQUE_META;
use const Bonniestar_Art_Plugin\DIMENSIONS_META;
use const Bonniestar_Art_Plugin\GALLERY_META;

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$assert = static function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new \RuntimeException( esc_html( $message ) );
	}
};

$fixture_ids = array();
$user_id     = get_current_user_id();
$old_post    = $GLOBALS['post'] ?? null;
// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Preserve input while explicitly testing nonce failures.
$old_input = $_POST;
$image_id  = 0;
$downsize  = static function ( $result, $id ) use ( &$image_id ) {
	return $image_id === $id ? array( home_url( '/temporary-artwork-test.jpg' ), 1600, 1000, false ) : $result;
};
$failure   = null;

try {
	$artwork_type = get_post_type_object( ARTWORK_TYPE );
	$assert( $artwork_type && 'Obras' === $artwork_type->labels->name && $artwork_type->show_in_rest, 'Artwork registration failed.' );
	$assert( use_block_editor_for_post_type( ARTWORK_TYPE ), 'The native block editor must be available.' );
	$assert( ! array_intersect( array( 'category', 'post_tag' ), get_object_taxonomies( ARTWORK_TYPE ) ), 'Artworks must not have classification taxonomies.' );
	$assert( function_exists( 'pll_is_translated_post_type' ) && pll_is_translated_post_type( ARTWORK_TYPE ), 'Polylang must manage artwork translations.' );
	$admin = get_users(
		array(
			'role'   => 'administrator',
			'number' => 1,
			'fields' => 'ID',
		)
	);
	$assert( ! empty( $admin ), 'An existing administrator is required.' );
	wp_set_current_user( $admin[0] );
	foreach ( array( 'es', 'en' ) as $lang ) {
		$fixture_id = wp_insert_post(
			array(
				'post_type'    => ARTWORK_TYPE,
				'post_status'  => 'draft',
				'post_title'   => 'Temporary artwork integration test ' . $lang,
				'post_content' => 'Body ' . $lang,
			),
			true
		);
		$assert( ! is_wp_error( $fixture_id ), 'Could not create a temporary draft.' );
		$fixture_ids[ $lang ] = $fixture_id;
		pll_set_post_language( $fixture_id, $lang );
	}
	pll_save_post_translations( $fixture_ids );
	$image_id = wp_insert_attachment(
		array(
			'post_title'     => 'Temporary media record',
			'post_mime_type' => 'image/jpeg',
			'post_status'    => 'inherit',
		),
		false,
		0,
		true
	);
	$assert( ! is_wp_error( $image_id ), 'Could not create a temporary attachment record.' );
	$fixture_ids['image'] = $image_id;
	update_post_meta( $image_id, '_wp_attached_file', 'artwork-test-' . wp_generate_uuid4() . '.jpg' );
	add_filter( 'image_downsize', $downsize, 10, 3 );
	$sanitized = \Bonniestar_Art_Plugin\sanitize_artwork_gallery( array( $image_id, (string) $image_id, -$image_id, '1.5', array(), $fixture_ids['es'] ) );
	$assert( array( $image_id ) === $sanitized, 'Gallery validation must reject duplicates, negative IDs, malformed IDs and non-images.' );
	$_POST = array(
		'artwork_fields_nonce' => wp_create_nonce( 'save_artwork_fields' ),
		'artwork_technique'    => 'acrilico',
		'artwork_dimensions'   => '<b>80 × 60 cm</b>',
		'artwork_gallery'      => (string) $image_id,
	);
	wp_update_post(
		array(
			'ID'         => $fixture_ids['es'],
			'post_title' => 'Temporary artwork saved',
		)
	);
	$assert( 'acrilico' === get_post_meta( $fixture_ids['es'], TECHNIQUE_META, true ), 'Technique was not saved.' );
	$assert( '80 × 60 cm' === get_post_meta( $fixture_ids['es'], DIMENSIONS_META, true ), 'Dimensions must be sanitized.' );
	$assert( array( $image_id ) === get_post_meta( $fixture_ids['es'], GALLERY_META, true ), 'Gallery IDs were not saved.' );
	$assert( 'acrilico' === get_post_meta( $fixture_ids['en'], TECHNIQUE_META, true ), 'Technique must synchronize to the translation.' );
	$assert( array( $image_id ) === get_post_meta( $fixture_ids['en'], GALLERY_META, true ), 'Gallery selection must synchronize to the translation.' );
	$assert( 'Body en' === get_post_field( 'post_content', $fixture_ids['en'] ), 'Content must remain independent per language.' );
	$_POST['artwork_fields_nonce'] = 'invalid';
	$_POST['artwork_technique']    = 'mixta';
	\Bonniestar_Art_Plugin\save_artwork_fields( $fixture_ids['es'] );
	$assert( 'acrilico' === get_post_meta( $fixture_ids['es'], TECHNIQUE_META, true ), 'An invalid nonce must not save.' );
	wp_set_current_user( 0 );
	$_POST['artwork_fields_nonce'] = wp_create_nonce( 'save_artwork_fields' );
	\Bonniestar_Art_Plugin\save_artwork_fields( $fixture_ids['es'] );
	$assert( 'acrilico' === get_post_meta( $fixture_ids['es'], TECHNIQUE_META, true ), 'A valid nonce must not bypass authorization.' );
	$rest_error = apply_filters( 'rest_authentication_errors', null );
	$assert( is_wp_error( $rest_error ) && 401 === $rest_error->get_error_data()['status'], 'Anonymous REST must be blocked.' );
	wp_set_current_user( $admin[0] );
	$assert( null === apply_filters( 'rest_authentication_errors', null ), 'Authenticated editor requests must remain available.' );
	$previous_error = new \WP_Error( 'test_denied', 'Test' );
	$assert( \Bonniestar_Art_Plugin\restrict_public_rest( $previous_error ) === $previous_error, 'Existing authentication errors must be preserved.' );
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Set up shortcode rendering context in this CLI test only.
	$GLOBALS['post'] = get_post( $fixture_ids['es'] );
	$details         = \Bonniestar_Art_Plugin\artwork_details_shortcode();
	$gallery         = \Bonniestar_Art_Plugin\artwork_gallery_shortcode();
	$assert( str_contains( $details, '80 × 60 cm' ), 'Public artwork facts must render.' );
	$assert( str_contains( $gallery, 'data-gallery-lightbox' ) && str_contains( $gallery, 'data-gallery-lightbox-src' ), 'The public gallery must integrate with the full-size lightbox.' );
	$assert( '' === \Bonniestar_Art_Plugin\sanitize_artwork_technique( 'invalid' ), 'Unknown techniques must be rejected.' );
	$assert( array( $image_id ) === \Bonniestar_Art_Plugin\artwork_translate_gallery( array( $image_id ), GALLERY_META, 'fr' ), 'Images without translated attachments must remain available.' );
} catch ( \Throwable $error ) {
	$failure = $error->getMessage();
} finally {
	$_POST = $old_input;
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the rendering context modified by this test.
	$GLOBALS['post'] = $old_post;
	wp_set_current_user( $user_id );
	remove_filter( 'image_downsize', $downsize );
	foreach ( $fixture_ids as $key => $fixture_id ) {
		if ( 'image' === $key ) {
			wp_delete_attachment( $fixture_id, true );
		} else {
			wp_delete_post( $fixture_id, true );
		}
	}
}
if ( $failure ) {
	\WP_CLI::error( $failure );
}
\WP_CLI::success( 'Artwork registration, editor, validation, authorized saving, Polylang synchronization, rendering and REST access verified; temporary fixtures removed.' );
