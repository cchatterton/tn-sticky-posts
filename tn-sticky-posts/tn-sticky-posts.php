<?php
/**
 * Plugin Name: TN Sticky Posts
 * Plugin URI: https://github.com/cchatterton/tn-sticky-posts/releases/latest
 * Description: Centrally manages announcement content for native WordPress sticky posts.
 * Version: 1.0.2
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Update URI: https://github.com/cchatterton/tn-sticky-posts
 * Author: Techn
 * Author URI: https://techn.com.au
 * Text Domain: tn-sticky-posts
 */

if (!defined('ABSPATH')) {
    exit;
}

define('TNSP_VERSION', '1.0.2');
define('TNSP_PLUGIN_FILE', __FILE__);
define('TNSP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TNSP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TNSP_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once TNSP_PLUGIN_DIR . 'includes/class-validator.php';
require_once TNSP_PLUGIN_DIR . 'includes/class-token-parser.php';
require_once TNSP_PLUGIN_DIR . 'includes/class-meta.php';
require_once TNSP_PLUGIN_DIR . 'includes/class-assets.php';
require_once TNSP_PLUGIN_DIR . 'includes/class-admin-actions.php';
require_once TNSP_PLUGIN_DIR . 'includes/class-admin-page.php';
require_once TNSP_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once TNSP_PLUGIN_DIR . 'includes/class-github-updater.php';
require_once TNSP_PLUGIN_DIR . 'includes/class-plugin.php';

add_action('plugins_loaded', static function (): void {
    Techn\StickyPosts\Plugin::instance()->init();
});
