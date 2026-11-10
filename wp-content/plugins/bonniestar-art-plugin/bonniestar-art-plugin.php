<?php
/**
 * Plugin Name: Bonniestar Art Plugin
 * Description: Artwork content, multilingual integration and an accessible gallery lightbox.
 * Version: 1.2.0
 * Requires at least: 7.1
 * Requires PHP: 8.3
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bonniestar-art-plugin
 *
 * @package BonniestarArtPlugin
 */

namespace Bonniestar_Art_Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const VERSION = '1.2.0';

require_once __DIR__ . '/includes/lightbox.php';
require_once __DIR__ . '/includes/artworks.php';
require_once __DIR__ . '/includes/artwork-editor.php';
require_once __DIR__ . '/includes/artwork-languages.php';
require_once __DIR__ . '/includes/rest-access.php';
