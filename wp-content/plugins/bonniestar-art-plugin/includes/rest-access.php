<?php
/**
 * Keep REST available to authenticated editing, without a public API channel.
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preserve authentication errors and the endpoint's own capability checks.
 *
 * @param mixed $result Existing authentication result.
 * @return mixed
 */
function restrict_public_rest( $result ) {
	if ( is_wp_error( $result ) || is_user_logged_in() ) {
		return $result;
	}
	return new \WP_Error( 'rest_authentication_required', __( 'Se requiere autenticación para acceder a la API.', 'bonniestar-art-plugin' ), array( 'status' => 401 ) );
}
add_filter( 'rest_authentication_errors', __NAMESPACE__ . '\\restrict_public_rest', 200 );
