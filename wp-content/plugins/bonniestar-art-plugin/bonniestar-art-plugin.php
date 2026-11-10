<?php
/**
 * Plugin Name: Bonniestar Art Plugin
 * Description: Minimal home for functionality independent of the active theme.
 * Version: 1.1.0
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

const VERSION = '1.1.0';

require_once __DIR__ . '/includes/lightbox.php';
