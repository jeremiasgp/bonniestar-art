<?php
/**
 * Read-only checks for optimized local media and preserved originals.
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin\Tests;

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() ) {
	exit;
}
$media_ids = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'lang'           => '',
	)
);
$checked   = 0;
$webp      = 0;
$uploads   = wp_get_upload_dir();
foreach ( $media_ids as $media_id ) {
	$original = get_post_meta( $media_id, '_bonniestar_art_webp_original', true );
	if ( ! $original ) {
		continue;
	}
	$metadata = wp_get_attachment_metadata( $media_id );
	$full     = get_attached_file( $media_id );
	$previous = $uploads['basedir'] . '/' . $original['file'];
	if ( ! is_readable( $previous ) || ! is_readable( $full ) || $metadata['width'] !== $original['metadata']['width'] || $metadata['height'] !== $original['metadata']['height'] ) {
		\WP_CLI::error( 'Missing original or changed full image dimensions: ' . $media_id );
	}
	$versions = array(
		array(
			'file'   => wp_basename( $full ),
			'width'  => $metadata['width'],
			'height' => $metadata['height'],
		),
	);
	foreach ( $metadata['sizes'] as $size => $details ) {
		if ( $details['width'] !== $original['metadata']['sizes'][ $size ]['width'] || $details['height'] !== $original['metadata']['sizes'][ $size ]['height'] ) {
			\WP_CLI::error( 'Changed responsive image dimensions: ' . $media_id );
		}
		$versions[] = $details;
	}
	foreach ( $versions as $details ) {
		$image_path = dirname( $full ) . '/' . $details['file'];
		$size       = wp_getimagesize( $image_path );
		if ( ! $size || $size[0] !== $details['width'] || $size[1] !== $details['height'] ) {
			\WP_CLI::error( 'Unreadable or mismatched optimized image: ' . $media_id );
		}
		if ( str_ends_with( $image_path, '.webp' ) ) {
			++$webp;
			if ( 'image/webp' !== $size['mime'] ) {
				\WP_CLI::error( 'Incorrect WebP MIME type: ' . $media_id );
			}
		}
		++$checked;
	}
}
if ( ! $checked ) {
	\WP_CLI::error( 'No optimized media found; run npm run media:optimize first.' );
}
\WP_CLI::success( sprintf( '%d image versions readable with unchanged dimensions; %d WebP versions; originals preserved.', $checked, $webp ) );
