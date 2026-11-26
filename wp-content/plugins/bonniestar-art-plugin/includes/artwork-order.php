<?php
/**
 * Native artwork ordering, shared across translations and Query blocks.
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get published physical artworks once, preferring the default-language record.
 *
 * @return array
 */
function ordered_artworks() {
	$posts     = get_posts(
		array(
			'post_type'               => ARTWORK_TYPE,
			'post_status'             => 'publish',
			'posts_per_page'          => -1,
			'orderby'                 => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
				'ID'         => 'ASC',
			),
			'lang'                    => '',
			'suppress_filters'        => false,
			'bonniestar_art_ordering' => true,
		)
	);
	$published = array_fill_keys( wp_list_pluck( $posts, 'ID' ), true );
	$default   = function_exists( 'pll_default_language' ) ? pll_default_language() : '';
	$result    = array();
	foreach ( $posts as $post ) {
		$translations = artwork_order_translations( $post->ID );
		$candidates   = array_values( array_filter( $translations, static fn( $id ) => isset( $published[ $id ] ) ) );
		$preferred    = $default && function_exists( 'pll_get_post' ) ? pll_get_post( $post->ID, $default ) : 0;
		$canonical    = isset( $published[ $preferred ] ) ? $preferred : min( $candidates );
		if ( $post->ID === $canonical ) {
			$result[] = $post;
		}
	}
	return $result;
}

/**
 * Get all records belonging to one physical artwork, including drafts.
 *
 * @param int $id Artwork ID.
 * @return array
 */
function artwork_order_translations( $id ) {
	$ids = function_exists( 'pll_get_post_translations' ) ? array_values( pll_get_post_translations( $id ) ) : array();
	return array_values( array_unique( array_merge( array( $id ), array_map( 'absint', $ids ) ) ) );
}

/**
 * Detect stale lists, changed rankings and changed translation groups.
 *
 * @param array $posts Canonical artwork records.
 * @return string
 */
function artwork_order_state( $posts ) {
	$state = array();
	foreach ( $posts as $post ) {
		foreach ( artwork_order_translations( $post->ID ) as $id ) {
			$state[ $id ] = (int) get_post_field( 'menu_order', $id );
		}
	}
	return wp_hash( wp_json_encode( $state ) );
}

/**
 * Register a dedicated list so pagination and admin search cannot hide artworks.
 *
 * @return void
 */
function artwork_order_menu() {
	add_submenu_page( 'edit.php?post_type=' . ARTWORK_TYPE, __( 'Ordenar obras', 'bonniestar-art-plugin' ), __( 'Ordenar obras', 'bonniestar-art-plugin' ), 'edit_others_posts', 'bonniestar-art-order', __NAMESPACE__ . '\\artwork_order_page' );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\artwork_order_menu' );

/**
 * Load the dependency-free ordering interface only on its own admin page.
 *
 * @param string $hook Current admin page.
 * @return void
 */
function artwork_order_assets( $hook ) {
	if ( get_plugin_page_hookname( 'bonniestar-art-order', 'edit.php?post_type=' . ARTWORK_TYPE ) !== $hook ) {
		return;
	}
	$base = plugins_url( 'assets/', dirname( __DIR__ ) . '/bonniestar-art-plugin.php' );
	wp_enqueue_style( 'bonniestar-art-order', $base . 'artwork-order.css', array(), (string) filemtime( dirname( __DIR__ ) . '/assets/artwork-order.css' ) );
	wp_enqueue_script( 'bonniestar-art-order', $base . 'artwork-order.js', array(), (string) filemtime( dirname( __DIR__ ) . '/assets/artwork-order.js' ), true );
	wp_localize_script(
		'bonniestar-art-order',
		'bonniestarArtworkOrder',
		array(
			'url'     => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'bonniestar_art_save_order' ),
			'changed' => __( 'Orden pendiente de guardar.', 'bonniestar-art-plugin' ),
			'saving'  => __( 'Guardando orden…', 'bonniestar-art-plugin' ),
			'saved'   => __( 'Orden guardado. La grilla ya usa este orden.', 'bonniestar-art-plugin' ),
			'error'   => __( 'No se pudo guardar. El orden sigue pendiente; volvé a intentar.', 'bonniestar-art-plugin' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\artwork_order_assets' );

/**
 * Render the sortable list with real buttons for keyboard access.
 *
 * @return void
 */
function artwork_order_page() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		return;
	}
	$artworks = ordered_artworks();
	?>
	<div class="wrap artwork-order" data-state="<?php echo esc_attr( artwork_order_state( $artworks ) ); ?>">
		<h1><?php esc_html_e( 'Ordenar obras', 'bonniestar-art-plugin' ); ?></h1>
		<p id="artwork-order-help"><?php esc_html_e( 'Arrastrá las obras publicadas desde el control de la izquierda y guardá el orden. También podés usar las flechas del teclado sobre ese control, o los botones Subir y Bajar. El orden se comparte entre idiomas.', 'bonniestar-art-plugin' ); ?></p>
		<noscript><p><?php esc_html_e( 'Activá JavaScript para ordenar las obras.', 'bonniestar-art-plugin' ); ?></p></noscript>
		<div class="artwork-order-toolbar">
			<button type="button" class="button button-primary artwork-order-save" disabled><?php esc_html_e( 'Guardar orden', 'bonniestar-art-plugin' ); ?></button>
			<p class="artwork-order-status" role="status" aria-live="polite"></p>
		</div>
		<ol class="artwork-order-list" aria-describedby="artwork-order-help">
			<?php foreach ( $artworks as $artwork ) : ?>
				<li class="artwork-order-item" data-id="<?php echo esc_attr( $artwork->ID ); ?>">
					<?php /* translators: %s: Artwork title. */ ?>
					<button type="button" class="button artwork-order-handle" aria-label="<?php echo esc_attr( sprintf( __( 'Mover %s', 'bonniestar-art-plugin' ), $artwork->post_title ) ); ?>"><span aria-hidden="true">↕</span></button>
					<?php
					echo get_the_post_thumbnail(
						$artwork,
						'thumbnail',
						array(
							'loading' => 'lazy',
							'alt'     => '',
						)
					);
					?>
					<span class="artwork-order-title"><?php echo esc_html( $artwork->post_title ); ?></span>
					<?php /* translators: %s: Artwork title. */ ?>
					<button type="button" class="button" data-move="-1" aria-label="<?php echo esc_attr( sprintf( __( 'Subir %s', 'bonniestar-art-plugin' ), $artwork->post_title ) ); ?>"><span aria-hidden="true">↑</span></button>
					<?php /* translators: %s: Artwork title. */ ?>
					<button type="button" class="button" data-move="1" aria-label="<?php echo esc_attr( sprintf( __( 'Bajar %s', 'bonniestar-art-plugin' ), $artwork->post_title ) ); ?>"><span aria-hidden="true">↓</span></button>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php if ( ! $artworks ) : ?>
			<p><?php esc_html_e( 'Todavía no hay obras publicadas.', 'bonniestar-art-plugin' ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Validate a complete order before changing any native menu_order values.
 *
 * @param mixed $ids   Submitted ordered integer IDs.
 * @param mixed $state Snapshot token.
 * @param mixed $nonce Request nonce.
 * @return array|\WP_Error
 */
function save_artwork_order( $ids, $state, $nonce ) {
	if ( ! current_user_can( 'edit_others_posts' ) || ! is_string( $nonce ) || ! wp_verify_nonce( $nonce, 'bonniestar_art_save_order' ) ) {
		return new \WP_Error( 'forbidden', __( 'No tenés permiso para guardar este orden. Recargá la página e intentá nuevamente.', 'bonniestar-art-plugin' ), array( 'status' => 403 ) );
	}
	if ( ! is_array( $ids ) || ! array_is_list( $ids ) || ! $ids || count( array_unique( $ids, SORT_REGULAR ) ) !== count( $ids ) ) {
		return new \WP_Error( 'invalid_order', __( 'El orden enviado no es válido.', 'bonniestar-art-plugin' ), array( 'status' => 400 ) );
	}
	foreach ( $ids as $id ) {
		if ( ! is_int( $id ) || $id < 1 ) {
			return new \WP_Error( 'invalid_order', __( 'El orden enviado no es válido.', 'bonniestar-art-plugin' ), array( 'status' => 400 ) );
		}
	}
	$posts = ordered_artworks();
	if ( ! is_string( $state ) || ! hash_equals( artwork_order_state( $posts ), $state ) ) {
		return new \WP_Error( 'stale_order', __( 'La lista cambió desde que la abriste. Recargá la página para ordenar la versión actual.', 'bonniestar-art-plugin' ), array( 'status' => 409 ) );
	}
	$expected = wp_list_pluck( $posts, 'ID' );
	$sorted   = $ids;
	sort( $expected, SORT_NUMERIC );
	sort( $sorted, SORT_NUMERIC );
	if ( $expected !== $sorted ) {
		return new \WP_Error( 'invalid_order', __( 'La lista debe incluir todas las obras publicadas una sola vez.', 'bonniestar-art-plugin' ), array( 'status' => 400 ) );
	}
	$updates = array();
	foreach ( $ids as $position => $id ) {
		foreach ( artwork_order_translations( $id ) as $translation_id ) {
			if ( ARTWORK_TYPE !== get_post_type( $translation_id ) || ! current_user_can( 'edit_post', $translation_id ) ) {
				return new \WP_Error( 'forbidden', __( 'No tenés permiso para ordenar todas las traducciones de estas obras.', 'bonniestar-art-plugin' ), array( 'status' => 403 ) );
			}
			$updates[ $translation_id ] = $position + 1;
		}
	}
	$original = array();
	foreach ( $updates as $id => $rank ) {
		$original[ $id ] = (int) get_post_field( 'menu_order', $id );
		$result          = wp_update_post(
			array(
				'ID'         => $id,
				'menu_order' => $rank,
			),
			true
		);
		if ( is_wp_error( $result ) ) {
			foreach ( $original as $restore_id => $restore_rank ) {
				wp_update_post(
					array(
						'ID'         => $restore_id,
						'menu_order' => $restore_rank,
					)
				);
			}
			return new \WP_Error( 'save_failed', __( 'No se pudo guardar el orden. Recargá la página para revisar el estado actual.', 'bonniestar-art-plugin' ), array( 'status' => 500 ) );
		}
	}
	return array( 'state' => artwork_order_state( ordered_artworks() ) );
}

/**
 * Authenticated AJAX entry point; the service verifies nonce and capabilities.
 *
 * @return void
 */
function artwork_order_ajax() {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce and authorization are verified together by save_artwork_order().
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The JSON schema, integer IDs and all permissions are strictly validated by save_artwork_order().
	$raw_order = isset( $_POST['order'] ) && is_string( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : '';
	$order     = strlen( $raw_order ) <= 1048576 ? json_decode( $raw_order, true ) : null;
	$state     = isset( $_POST['state'] ) && is_string( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '';
	$nonce     = isset( $_POST['nonce'] ) && is_string( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	$result = save_artwork_order( $order, $state, $nonce );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ), $result->get_error_data()['status'] );
	}
	wp_send_json_success( $result );
}
add_action( 'wp_ajax_bonniestar_art_save_order', __NAMESPACE__ . '\\artwork_order_ajax' );

/**
 * Break ties consistently when a Query block explicitly requests manual order.
 *
 * @param array $query Query arguments.
 * @return array
 */
function artwork_order_query( $query ) {
	if ( ARTWORK_TYPE === ( $query['post_type'] ?? '' ) && 'menu_order' === ( $query['orderby'] ?? '' ) ) {
		$query['orderby'] = array(
			'menu_order' => 'ASC',
			'title'      => 'ASC',
			'ID'         => 'ASC',
		);
	}
	return $query;
}
add_filter( 'query_loop_block_query_vars', __NAMESPACE__ . '\\artwork_order_query', 20 );

/**
 * Append new artworks, or copy the established rank of a published translation.
 *
 * @param string   $new_status New post status.
 * @param string   $old_status Previous post status.
 * @param \WP_Post $post       Published post.
 * @return void
 */
function artwork_order_on_publish( $new_status, $old_status, $post ) {
	if ( 'publish' !== $new_status || 'publish' === $old_status || ARTWORK_TYPE !== $post->post_type || $post->menu_order ) {
		return;
	}
	foreach ( artwork_order_translations( $post->ID ) as $id ) {
		if ( $id !== $post->ID && 'publish' === get_post_status( $id ) ) {
			wp_update_post(
				array(
					'ID'         => $post->ID,
					'menu_order' => (int) get_post_field( 'menu_order', $id ),
				)
			);
			return;
		}
	}
	$last = get_posts(
		array(
			'post_type'      => ARTWORK_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'orderby'        => 'menu_order',
			'order'          => 'DESC',
			'lang'           => '',
		)
	);
	$rank = $last ? (int) $last[0]->menu_order + 1 : 1;
	wp_update_post(
		array(
			'ID'         => $post->ID,
			'menu_order' => $rank,
		)
	);
}
add_action( 'transition_post_status', __NAMESPACE__ . '\\artwork_order_on_publish', 20, 3 );
