<?php
/**
 * Read-only WordPress integration checks; run with WP-CLI eval-file.
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin\Tests;

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit;
}

$assert = static function ( $condition, $message ) {
	if ( ! $condition ) {
		\WP_CLI::error( $message );
	}
};

// Keep test media entirely in the nonpersistent object cache of this CLI request.
$test_id = 2147483600;
$assert( ! get_post( $test_id ), 'The fixture ID must not belong to a real post.' );
$fixture = new \WP_Post(
	(object) array(
		'ID'             => $test_id,
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'post_mime_type' => 'image/jpeg',
		'filter'         => 'raw',
	)
);
wp_cache_add( $test_id, $fixture, 'posts' );
$full_url = home_url( '/lightbox-test-full.jpg' );
$full     = static function ( $result, $id, $size ) use ( $test_id, $full_url ) {
	return $test_id === $id && 'full' === $size ? array( $full_url, 2400, 1600, false ) : $result;
};
add_filter( 'image_downsize', $full, 10, 3 );

$block = array(
	'blockName'    => 'core/image',
	'attrs'        => array(
		'id'       => $test_id,
		'lightbox' => array( 'enabled' => true ),
	),
	'innerBlocks'  => array(),
	'innerHTML'    => '<figure class="wp-block-image"><img src="' . esc_url( home_url( '/thumbnail.jpg' ) ) . '" alt="Test" /></figure>',
	'innerContent' => array( '<figure class="wp-block-image"><img src="' . esc_url( home_url( '/thumbnail.jpg' ) ) . '" alt="Test" /></figure>' ),
);

$rendered = render_block( $block );
$assert( str_contains( $rendered, 'data-gallery-lightbox-src="' . esc_url( $full_url ) . '"' ), 'The block must expose the full media source.' );
$assert( ! str_contains( $rendered, 'data-wp-interactive="core/image"' ), 'Core must not create a competing overlay.' );
$assert( true === $block['attrs']['lightbox']['enabled'], 'Rendering must not change saved content.' );

$gallery = array(
	'blockName'    => 'core/gallery',
	'attrs'        => array( 'lightbox' => array( 'enabled' => true ) ),
	'innerBlocks'  => array( $block, $block ),
	'innerHTML'    => '<figure class="wp-block-gallery has-nested-images"></figure>',
	'innerContent' => array( '<figure class="wp-block-gallery has-nested-images">', null, null, '</figure>' ),
);

$gallery_html = render_block( $gallery );
$assert( 2 === substr_count( $gallery_html, 'data-gallery-lightbox-src=' ), 'Nested gallery images must retain full sources.' );
$assert( ! str_contains( $gallery_html, 'data-wp-interactive="core/image"' ), 'Gallery settings must not restore core overlays.' );

$attrs = wp_get_attachment_image( $test_id, 'full' );
$assert( str_contains( $attrs, 'data-gallery-lightbox-src=' ), 'Generated/classic images must expose the full source.' );

\Bonniestar_Art_Plugin\enqueue_lightbox();
$script = wp_scripts()->registered['wp-gallery-lightbox'];
$assert( 'defer' === $script->extra['strategy'] && empty( $script->extra['group'] ), 'Script must be deferred in the head.' );
$assert( str_contains( $script->src, '/plugins/bonniestar-art-plugin/assets/lightbox.js' ), 'Asset URL must point to this plugin.' );
$assert( wp_style_is( 'wp-gallery-lightbox', 'enqueued' ), 'Portable CSS must be enqueued.' );

add_filter( 'bonniestar_art_lightbox_enabled', '__return_false' );
$unchanged = \Bonniestar_Art_Plugin\replace_core_lightbox( $block );
$assert( true === $unchanged['attrs']['lightbox']['enabled'], 'Opting out must leave core settings unchanged.' );
$assert( ! \Bonniestar_Art_Plugin\lightbox_enabled(), 'The enable filter must disable the integration.' );
remove_filter( 'bonniestar_art_lightbox_enabled', '__return_false' );
remove_filter( 'image_downsize', $full );
wp_cache_delete( $test_id, 'posts' );
\WP_CLI::success( 'Full-resolution sources, classic images, core replacement, asset strategy and opt-out verified without database writes.' );
