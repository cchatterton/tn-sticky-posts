<?php
/**
 * Preserve editorial announcement data by default.
 *
 * Define TNSP_REMOVE_DATA_ON_UNINSTALL as true before uninstall to delete stored
 * announcement metadata intentionally. Native sticky-post settings are never changed.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

if (!defined('TNSP_REMOVE_DATA_ON_UNINSTALL') || true !== TNSP_REMOVE_DATA_ON_UNINSTALL) {
    return;
}

global $wpdb;

$wpdb->delete($wpdb->postmeta, array('meta_key' => '_sticky_announcement_text'), array('%s'));
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_sticky_announcement_click_label'), array('%s'));
$wpdb->delete($wpdb->postmeta, array('meta_key' => '_sticky_announcement_url'), array('%s'));
