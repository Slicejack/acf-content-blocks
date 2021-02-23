<?php
/**
 * Plugin Name: ACF Content Blocks
 * Plugin URI: https://github.com/hovhann3ss/acf-content-blocks
 * Description: ACF Content Blocks
 * Version: 0.7.0
 * Author: Agilo
 * Author URI: https://agilo.co/
 * License: GNU General Public License v3.0
 * License URI: https://github.com/hovhann3ss/acf-content-blocks/blob/master/LICENSE
 * Text Domain: acf-content-blocks
 * Domain Path: /languages
 *
 * @package ACF_Content_Blocks
 */

// Require plugin class.
require_once trailingslashit( __DIR__ ) . 'classes/class-plugin.php';
// Require utils class.
require_once trailingslashit( __DIR__ ) . 'classes/class-utils.php';
// Require API class.
require_once trailingslashit( __DIR__ ) . 'classes/class-api.php';
// Require Blocks List class.
require_once trailingslashit( __DIR__ ) . 'classes/class-blocks-list.php';
// Require Block Details List class.
require_once trailingslashit( __DIR__ ) . 'classes/class-block-details-list.php';
// Require Blocks Locator class.
require_once trailingslashit( __DIR__ ) . 'classes/class-blocks-locator-page.php';
// Require API functions.
require_once trailingslashit( __DIR__ ) . 'api.php';


\ACF_Content_Blocks\Plugin::get_instance();
