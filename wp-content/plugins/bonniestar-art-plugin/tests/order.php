<?php
/**
 * Local-only ordering integration checks. All fixture records are removed.
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin\Tests;

use function Bonniestar_Art_Plugin\ordered_artworks;
use function Bonniestar_Art_Plugin\artwork_order_state;
use function Bonniestar_Art_Plugin\save_artwork_order;
use const Bonniestar_Art_Plugin\ARTWORK_TYPE;

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() ) {
	exit;
}

$fixture_ids = array();
$old_user    = get_current_user_id();
$failure     = null;
$assert      = static function ( $value, $message ) {
	if ( ! $value ) {
		throw new \RuntimeException( esc_html( $message ) );
	}
};
// Scope the ordering service's real WP_Query to fixtures, without touching user data.
$rendering_pattern = false;
$scope             = static function ( $query ) use ( &$fixture_ids, &$rendering_pattern ) {
	if ( ARTWORK_TYPE === $query->get( 'post_type' ) && ( $query->get( 'bonniestar_art_ordering' ) || $rendering_pattern ) ) {
		$query->set( 'post__in', array_values( $fixture_ids ) );
	}
};
$deny_translation  = static function ( $caps, $cap, $user_id, $args ) use ( &$fixture_ids ) {
	return 'edit_post' === $cap && ( $args[0] ?? 0 ) === ( $fixture_ids['en'] ?? 0 ) ? array( 'do_not_allow' ) : $caps;
};

try {
	$admin = get_users(
		array(
			'role'   => 'administrator',
			'fields' => 'ID',
			'number' => 1,
		)
	);
	$assert( $admin && function_exists( 'pll_save_post_translations' ), 'An administrator and Polylang with es/en/fr are required.' );
	wp_set_current_user( $admin[0] );
	foreach ( array(
		'a'  => 'es',
		'b'  => 'es',
		'en' => 'en',
	) as $key => $language ) {
		$fixture_id = wp_insert_post(
			array(
				'post_type'   => ARTWORK_TYPE,
				'post_title'  => 'Temporary ordering ' . $key,
				'post_status' => 'draft',
			),
			true
		);
		$assert( ! is_wp_error( $fixture_id ), 'Could not create ordering fixture.' );
		$fixture_ids[ $key ] = $fixture_id;
		pll_set_post_language( $fixture_id, $language );
		if ( 'en' !== $key ) {
			wp_update_post(
				array(
					'ID'          => $fixture_id,
					'post_status' => 'publish',
				)
			);
		}
	}
	pll_save_post_translations(
		array(
			'es' => $fixture_ids['a'],
			'en' => $fixture_ids['en'],
		)
	);
	add_action( 'pre_get_posts', $scope );
	$ids   = wp_list_pluck( ordered_artworks(), 'ID' );
	$state = artwork_order_state( ordered_artworks() );
	$nonce = wp_create_nonce( 'bonniestar_art_save_order' );
	$assert( 2 === count( $ids ) && ! in_array( $fixture_ids['en'], $ids, true ), 'The manager must list each published physical artwork only once.' );
	$result = save_artwork_order( array_reverse( $ids ), $state, 'invalid' );
	$assert( is_wp_error( $result ) && 403 === $result->get_error_data()['status'], 'An invalid nonce must be rejected.' );
	wp_set_current_user( 0 );
	$result = save_artwork_order( $ids, $state, wp_create_nonce( 'bonniestar_art_save_order' ) );
	$assert( is_wp_error( $result ) && 403 === $result->get_error_data()['status'], 'A nonce must not bypass authorization.' );
	wp_set_current_user( $admin[0] );
	foreach ( array( array( $ids[0], $ids[0] ), array( $ids[0] ), array( (string) $ids[0], $ids[1] ), array( $ids[0], -1 ), array( $ids[0], $fixture_ids['en'] ) ) as $invalid ) {
		$result = save_artwork_order( $invalid, $state, $nonce );
		$assert( is_wp_error( $result ) && 400 === $result->get_error_data()['status'], 'Incomplete, duplicate, malformed or noncanonical orders must fail.' );
	}
	$assert( artwork_order_state( ordered_artworks() ) === $state, 'Validation failures must not change any ranks.' );
	$result = save_artwork_order( array_reverse( $ids ), $state, $nonce );
	$assert( ! is_wp_error( $result ), 'A complete authorized order must save.' );
	$assert( array_reverse( $ids ) === wp_list_pluck( ordered_artworks(), 'ID' ), 'The saved order must persist in WordPress.' );
	$assert( get_post_field( 'menu_order', $fixture_ids['a'] ) === get_post_field( 'menu_order', $fixture_ids['en'] ), 'Draft translations must share their artwork rank.' );
	$assert( 'draft' === get_post_status( $fixture_ids['en'] ), 'Saving order must not publish translations.' );
	$stale = save_artwork_order( $ids, $state, $nonce );
	$assert( is_wp_error( $stale ) && 409 === $stale->get_error_data()['status'], 'A stale list must not overwrite newer rankings.' );
	$query = apply_filters(
		'query_loop_block_query_vars',
		array(
			'post_type' => ARTWORK_TYPE,
			'orderby'   => 'menu_order',
		),
		new \WP_Block( array( 'blockName' => 'core/post-template' ) ),
		1
	);
	$front = get_posts(
		array_merge(
			$query,
			array(
				'post__in'       => $ids,
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'lang'           => '',
			)
		)
	);
	$assert( array_reverse( $ids ) === $front, 'The actual frontend Query must use the saved native rank.' );
	$pattern           = \WP_Block_Patterns_Registry::get_instance()->get_registered( 'bonniestar-art-theme/artist-home' );
	$rendering_pattern = true;
	$html              = do_blocks( $pattern['content'] );
	$rendering_pattern = false;
	$tags              = new \WP_HTML_Tag_Processor( $html );
	$rendered          = array();
	while ( $tags->next_tag( 'LI' ) ) {
		if ( preg_match( '/\bpost-([0-9]+)\b/', (string) $tags->get_attribute( 'class' ), $match ) ) {
			$rendered[] = (int) $match[1];
		}
	}
	$assert( array_reverse( $ids ) === $rendered, 'The real homepage pattern must render the saved artwork order.' );
	add_filter( 'map_meta_cap', $deny_translation, 10, 4 );
	$current = artwork_order_state( ordered_artworks() );
	$result  = save_artwork_order( $ids, $current, $nonce );
	$assert( is_wp_error( $result ) && 403 === $result->get_error_data()['status'], 'Every translation requires its own edit capability.' );
	$assert( artwork_order_state( ordered_artworks() ) === $current, 'A denied translation must prevent all writes.' );
	remove_filter( 'map_meta_cap', $deny_translation, 10 );
} catch ( \Throwable $error ) {
	$failure = $error->getMessage();
} finally {
	remove_action( 'pre_get_posts', $scope );
	remove_filter( 'map_meta_cap', $deny_translation, 10 );
	wp_set_current_user( $old_user );
	foreach ( $fixture_ids as $fixture_id ) {
		wp_delete_post( $fixture_id, true );
	}
}
if ( $failure ) {
	\WP_CLI::error( $failure );
}
\WP_CLI::success( 'Ordering, persistence, translation ranks, Query output, permissions, nonce and stale/invalid requests verified; fixtures removed.' );
