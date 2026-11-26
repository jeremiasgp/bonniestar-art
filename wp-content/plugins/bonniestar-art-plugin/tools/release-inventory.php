<?php
/**
 * Read-only local release inventory, never loaded by the plugin.
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
global $wpdb;
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$active = array();
foreach ( get_option( 'active_plugins', array() ) as $plugin_file ) {
	$headers  = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
	$active[] = array(
		'file'    => $plugin_file,
		'version' => $headers['Version'],
	);
}
\WP_CLI::line(
	wp_json_encode(
		array(
			'wordpress'    => get_bloginfo( 'version' ),
			'php'          => PHP_VERSION,
			'table_prefix' => $wpdb->prefix,
			'home'         => get_option( 'home' ),
			'siteurl'      => get_option( 'siteurl' ),
			'blog_public'  => get_option( 'blog_public' ),
			'theme'        => get_stylesheet(),
			'plugins'      => $active,
			'artworks'     => (int) wp_count_posts( ARTWORK_TYPE )->publish,
			'attachments'  => (int) wp_count_posts( 'attachment' )->inherit,
		)
	)
);
