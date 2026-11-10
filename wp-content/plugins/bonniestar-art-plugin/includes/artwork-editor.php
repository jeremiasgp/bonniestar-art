<?php
/**
 * Native editor metabox and media-library gallery selection.
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add only the fields specific to artworks.
 *
 * @return void
 */
function artwork_meta_boxes() {
	add_meta_box( 'artwork-fields', __( 'Datos de la obra', 'bonniestar-art-plugin' ), __NAMESPACE__ . '\\artwork_meta_box', ARTWORK_TYPE, 'normal', 'high', array( '__block_editor_compatible_meta_box' => true ) );
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\artwork_meta_boxes' );

/**
 * Render escaped fields and the accessible gallery controls.
 *
 * @param \WP_Post $post Edited artwork.
 * @return void
 */
function artwork_meta_box( $post ) {
	wp_nonce_field( 'save_artwork_fields', 'artwork_fields_nonce' );
	$technique = get_post_meta( $post->ID, TECHNIQUE_META, true );
	$gallery   = sanitize_artwork_gallery( get_post_meta( $post->ID, GALLERY_META, true ) );
	?>
	<p><label for="artwork-technique"><strong><?php esc_html_e( 'Técnica', 'bonniestar-art-plugin' ); ?></strong></label></p>
	<select id="artwork-technique" name="artwork_technique">
		<option value=""><?php esc_html_e( 'Seleccionar técnica', 'bonniestar-art-plugin' ); ?></option>
		<?php foreach ( artwork_techniques() as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $technique, $key ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
	<p><label for="artwork-dimensions"><strong><?php esc_html_e( 'Medidas', 'bonniestar-art-plugin' ); ?></strong></label></p>
	<input class="widefat" id="artwork-dimensions" name="artwork_dimensions" type="text" value="<?php echo esc_attr( get_post_meta( $post->ID, DIMENSIONS_META, true ) ); ?>" aria-describedby="artwork-dimensions-help" />
	<p id="artwork-dimensions-help"><?php esc_html_e( 'Incluir las unidades. Por ejemplo: 80 × 60 cm.', 'bonniestar-art-plugin' ); ?></p>
	<div class="artwork-gallery-editor">
		<p><strong><?php esc_html_e( 'Galería de imágenes', 'bonniestar-art-plugin' ); ?></strong></p>
		<p><?php esc_html_e( 'Seleccioná imágenes de la biblioteca y ordenalas con los botones. Los cambios se guardan al guardar la obra.', 'bonniestar-art-plugin' ); ?></p>
		<input type="hidden" name="artwork_gallery" value="<?php echo esc_attr( implode( ',', $gallery ) ); ?>" />
		<button type="button" class="button artwork-gallery-select"><?php esc_html_e( 'Seleccionar imágenes', 'bonniestar-art-plugin' ); ?></button>
		<ol class="artwork-gallery-items">
			<?php foreach ( $gallery as $image_id ) : ?>
				<li data-id="<?php echo esc_attr( $image_id ); ?>">
					<?php echo wp_get_attachment_image( $image_id, 'thumbnail' ); ?>
					<span><?php echo esc_html( get_the_title( $image_id ) ); ?></span>
					<button type="button" class="button" data-action="up"><?php esc_html_e( 'Subir', 'bonniestar-art-plugin' ); ?></button>
					<button type="button" class="button" data-action="down"><?php esc_html_e( 'Bajar', 'bonniestar-art-plugin' ); ?></button>
					<button type="button" class="button" data-action="remove"><?php esc_html_e( 'Quitar', 'bonniestar-art-plugin' ); ?></button>
				</li>
			<?php endforeach; ?>
		</ol>
		<p class="artwork-gallery-status" role="status" aria-live="polite"></p>
	</div>
	<?php
}

/**
 * Save only deliberate edits with both authorization and nonce validation.
 *
 * @param int $post_id Artwork ID.
 * @return void
 */
function save_artwork_fields( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ARTWORK_TYPE !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['artwork_fields_nonce'] ) || ! is_string( $_POST['artwork_fields_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['artwork_fields_nonce'] ) ), 'save_artwork_fields' ) ) {
		return;
	}
	if ( isset( $_POST['artwork_technique'] ) && is_string( $_POST['artwork_technique'] ) ) {
		update_post_meta( $post_id, TECHNIQUE_META, sanitize_artwork_technique( sanitize_text_field( wp_unslash( $_POST['artwork_technique'] ) ) ) );
	}
	if ( isset( $_POST['artwork_dimensions'] ) && is_string( $_POST['artwork_dimensions'] ) ) {
		update_post_meta( $post_id, DIMENSIONS_META, sanitize_text_field( wp_unslash( $_POST['artwork_dimensions'] ) ) );
	}
	if ( isset( $_POST['artwork_gallery'] ) && is_string( $_POST['artwork_gallery'] ) ) {
		$images = sanitize_artwork_gallery( sanitize_text_field( wp_unslash( $_POST['artwork_gallery'] ) ) );
		$images = array_values( array_filter( $images, static fn( $id ) => current_user_can( 'read_post', $id ) ) );
		update_post_meta( $post_id, GALLERY_META, $images );
	}
}
add_action( 'save_post_' . ARTWORK_TYPE, __NAMESPACE__ . '\\save_artwork_fields' );

/**
 * Load the media picker and its controls only on artwork edit screens.
 *
 * @return void
 */
function enqueue_artwork_editor() {
	$screen = get_current_screen();
	if ( ! $screen || ARTWORK_TYPE !== $screen->post_type || 'post' !== $screen->base ) {
		return;
	}
	wp_enqueue_media();
	$base = plugins_url( 'assets/', dirname( __DIR__ ) . '/bonniestar-art-plugin.php' );
	wp_enqueue_script( 'artwork-editor', $base . 'artwork-editor.js', array( 'media-views' ), VERSION, true );
	wp_enqueue_style( 'artwork-editor', $base . 'artwork-editor.css', array(), VERSION );
	wp_localize_script(
		'artwork-editor',
		'artworkEditorLabels',
		array(
			'title'  => __( 'Galería de la obra', 'bonniestar-art-plugin' ),
			'select' => __( 'Usar estas imágenes', 'bonniestar-art-plugin' ),
			'up'     => __( 'Subir', 'bonniestar-art-plugin' ),
			'down'   => __( 'Bajar', 'bonniestar-art-plugin' ),
			'remove' => __( 'Quitar', 'bonniestar-art-plugin' ),
			'saved'  => __( 'Selección actualizada. Guardá la obra para conservar los cambios.', 'bonniestar-art-plugin' ),
			'error'  => __( 'No se pudieron cargar las imágenes. Volvé a abrir la biblioteca para intentarlo de nuevo.', 'bonniestar-art-plugin' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_artwork_editor' );
