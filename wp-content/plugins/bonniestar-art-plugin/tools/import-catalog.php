<?php
/**
 * Explicit local catalogue import: wp eval-file FILE MANIFEST IMAGE_DIRECTORY.
 *
 * Never loaded by the plugin. Source images remain outside the repository.
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() ) {
	return;
}
if ( ! isset( $args[0], $args[1] ) || ! is_readable( $args[0] ) || ! is_dir( $args[1] ) ) {
	\WP_CLI::error( 'Provide a readable JSON manifest and local image directory.' );
}

$catalog_rows = json_decode( preg_replace( '/^\xEF\xBB\xBF/', '', file_get_contents( $args[0] ) ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Explicit local CLI input.
if ( ! is_array( $catalog_rows ) || ! $catalog_rows ) {
	\WP_CLI::error( 'Invalid or empty catalogue manifest.' );
}
// Validate all sources before writing; only explicitly named local JPEGs are read.
foreach ( $catalog_rows as $catalog_row ) {
	if ( empty( $catalog_row['titulo'] ) || empty( $catalog_row['principal'] ) || ! isset( $catalog_row['medidas'], $catalog_row['detalles'] ) || ! is_array( $catalog_row['detalles'] ) ) {
		\WP_CLI::error( 'Incomplete catalogue row.' );
	}
	foreach ( array_merge( array( $catalog_row['principal'] ), $catalog_row['detalles'] ) as $catalog_file ) {
		if ( ! is_string( $catalog_file ) || ! preg_match( '/^obra_[a-z0-9_-]+\.jpg$/i', $catalog_file ) || ! is_readable( $args[1] . '/' . $catalog_file ) ) {
			\WP_CLI::error( 'Missing or invalid catalogue image.' );
		}
	}
}
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Import an image once, keeping its source basename for resumable runs.
 *
 * @param string $directory Read-only source directory.
 * @param string $filename  Validated basename.
 * @param int    $post_id   Parent artwork.
 * @param string $title     Accessible image description.
 * @return int
 */
function import_catalog_image( $directory, $filename, $post_id, $title ) {
	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_bonniestar_art_catalog_source', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Explicit CLI import.
			'meta_value'     => $filename, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'lang'           => '',
		)
	);
	if ( $existing ) {
		return (int) $existing[0];
	}
	$temp = wp_tempnam( $filename );
	if ( ! $temp || ! copy( $directory . '/' . $filename, $temp ) ) {
		\WP_CLI::error( 'Could not stage catalogue image.' );
	}
	$id = media_handle_sideload(
		array(
			'name'     => $filename,
			'tmp_name' => $temp,
		),
		$post_id,
		$title
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $temp );
		\WP_CLI::error( $id->get_error_message() );
	}
	update_post_meta( $id, '_bonniestar_art_catalog_source', $filename );
	update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $title ) );
	return $id;
}

foreach ( $catalog_rows as $catalog_row ) {
	$catalog_slug = sanitize_title( $catalog_row['titulo'] );
	$catalog_post = get_page_by_path( $catalog_slug, OBJECT, ARTWORK_TYPE );
	if ( $catalog_post && get_post_meta( $catalog_post->ID, '_bonniestar_art_catalog_source', true ) !== $catalog_row['principal'] ) {
		\WP_CLI::error( 'Existing artwork slug collision: ' . $catalog_slug );
	}
	$catalog_id = $catalog_post ? $catalog_post->ID : wp_insert_post(
		array(
			'post_type'   => ARTWORK_TYPE,
			'post_status' => 'draft',
			'post_title'  => sanitize_text_field( $catalog_row['titulo'] ),
			'post_name'   => $catalog_slug,
		),
		true
	);
	if ( is_wp_error( $catalog_id ) ) {
		\WP_CLI::error( $catalog_id->get_error_message() );
	}
	update_post_meta( $catalog_id, '_bonniestar_art_catalog_source', $catalog_row['principal'] );
	if ( ! $catalog_post ) {
		update_post_meta( $catalog_id, '_bonniestar_art_catalog_pending', '1' );
	}
	if ( ! $catalog_post && function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $catalog_id, 'es' );
	}
	if ( ! get_post_meta( $catalog_id, DIMENSIONS_META, true ) ) {
		update_post_meta( $catalog_id, DIMENSIONS_META, sanitize_text_field( $catalog_row['medidas'] ) );
	}
	$catalog_main = import_catalog_image( $args[1], $catalog_row['principal'], $catalog_id, $catalog_row['titulo'] );
	if ( ! has_post_thumbnail( $catalog_id ) ) {
		set_post_thumbnail( $catalog_id, $catalog_main );
	}
	$catalog_gallery = array();
	foreach ( $catalog_row['detalles'] as $catalog_index => $catalog_filename ) {
		$catalog_gallery[] = import_catalog_image( $args[1], $catalog_filename, $catalog_id, $catalog_row['titulo'] . ' — ' . ( $catalog_index + 1 ) );
	}
	update_post_meta( $catalog_id, GALLERY_META, array_values( array_unique( array_merge( sanitize_artwork_gallery( get_post_meta( $catalog_id, GALLERY_META, true ) ), $catalog_gallery ) ) ) );
	if ( get_post_meta( $catalog_id, '_bonniestar_art_catalog_pending', true ) ) {
		wp_update_post(
			array(
				'ID'          => $catalog_id,
				'post_status' => 'publish',
			)
		);
		delete_post_meta( $catalog_id, '_bonniestar_art_catalog_pending' );
	}
	\WP_CLI::log( 'Imported artwork ' . $catalog_id . ': ' . $catalog_row['titulo'] );
}
\WP_CLI::success( 'Catalogue imported; existing artwork fields preserved.' );
