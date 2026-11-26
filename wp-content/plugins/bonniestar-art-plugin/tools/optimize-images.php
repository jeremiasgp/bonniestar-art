<?php
/**
 * Local-only WebP preparation. Run through npm run media:optimize for backups.
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
$apply = isset( $args[0] ) && 'apply' === $args[0];
if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
	\WP_CLI::error( 'This WordPress image editor does not support WebP.' );
}
$attachments = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => array( 'image/jpeg', 'image/png' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'lang'           => '',
	)
);
$attachments = array_values( array_filter( $attachments, static fn( $id ) => ! get_post_meta( $id, '_bonniestar_art_webp_original', true ) ) );
$report      = array(
	'applied'     => $apply,
	'attachments' => count( $attachments ),
	'converted'   => 0,
	'before'      => 0,
	'after'       => 0,
	'full_before' => 0,
	'full_after'  => 0,
	'files'       => array(),
);
$uploads     = wp_get_upload_dir();
foreach ( $attachments as $attachment_id ) {
	$metadata = wp_get_attachment_metadata( $attachment_id );
	$full     = get_attached_file( $attachment_id );
	if ( ! $metadata || ! $full || ! is_readable( $full ) || ! str_starts_with( wp_normalize_path( $full ), wp_normalize_path( $uploads['basedir'] ) . '/' ) ) {
		\WP_CLI::error( 'Missing or invalid attachment: ' . $attachment_id );
	}
	$original = array(
		'file'     => get_post_meta( $attachment_id, '_wp_attached_file', true ),
		'metadata' => $metadata,
		'mime'     => get_post_mime_type( $attachment_id ),
	);
	$files    = array( 'full' => $full );
	foreach ( $metadata['sizes'] ?? array() as $size => $details ) {
		$files[ $size ] = dirname( $full ) . '/' . wp_basename( $details['file'] );
	}
	$created = array();
	$updated = $metadata;
	try {
		foreach ( $files as $size => $source ) {
			if ( ! is_readable( $source ) ) {
				throw new \RuntimeException( 'Missing image size for attachment ' . $attachment_id );
			}
			$before            = wp_filesize( $source );
			$report['before'] += $before;
			if ( 'full' === $size ) {
				$report['full_before'] += $before;
			}
			if ( ! $apply ) {
				continue;
			}
			$destination = preg_replace( '/\.[^.]+$/', '.webp', $source );
			if ( file_exists( $destination ) ) {
				throw new \RuntimeException( 'Refusing to overwrite an existing WebP: ' . wp_basename( $destination ) );
			}
			$editor = wp_get_image_editor( $source );
			if ( is_wp_error( $editor ) ) {
				throw new \RuntimeException( esc_html( $editor->get_error_message() ) );
			}
			$editor->set_quality( 'full' === $size ? 85 : 80 );
			$saved = $editor->save( $destination, 'image/webp' );
			if ( is_wp_error( $saved ) ) {
				throw new \RuntimeException( esc_html( $saved->get_error_message() ) );
			}
			$created[] = $destination;
			$after     = wp_filesize( $destination );
			// A format change must actually reduce transfer size.
			if ( $after >= $before ) {
				wp_delete_file( $destination );
				$after = $before;
			} elseif ( 'full' === $size ) {
				$updated['file']     = _wp_relative_upload_path( $destination );
				$updated['filesize'] = $after;
				if ( empty( $updated['original_image'] ) ) {
					$updated['original_image'] = wp_basename( $source );
				}
			} else {
				$updated['sizes'][ $size ]['file']      = wp_basename( $destination );
				$updated['sizes'][ $size ]['mime-type'] = 'image/webp';
				$updated['sizes'][ $size ]['filesize']  = $after;
			}
			$report['after'] += $after;
			if ( 'full' === $size ) {
				$report['full_after'] += $after;
			}
			$report['files'][] = array(
				'id'     => $attachment_id,
				'size'   => $size,
				'before' => $before,
				'after'  => $after,
			);
		}
		if ( $apply ) {
			update_post_meta( $attachment_id, '_bonniestar_art_webp_original', $original );
			update_attached_file( $attachment_id, $uploads['basedir'] . '/' . $updated['file'] );
			wp_update_attachment_metadata( $attachment_id, $updated );
			$result = wp_update_post(
				array(
					'ID'             => $attachment_id,
					'post_mime_type' => str_ends_with( $updated['file'], '.webp' ) ? 'image/webp' : $original['mime'],
				),
				true
			);
			if ( is_wp_error( $result ) ) {
				update_attached_file( $attachment_id, $full );
				wp_update_attachment_metadata( $attachment_id, $metadata );
				delete_post_meta( $attachment_id, '_bonniestar_art_webp_original' );
				throw new \RuntimeException( esc_html( $result->get_error_message() ) );
			}
			++$report['converted'];
		}
	} catch ( \Throwable $error ) {
		foreach ( $created as $image_path ) {
			wp_delete_file( $image_path );
		}
		\WP_CLI::error( $error->getMessage() );
	}
}
\WP_CLI::line( wp_json_encode( $report ) );
