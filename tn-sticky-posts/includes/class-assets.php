<?php

namespace Techn\StickyPosts;

if (!defined('ABSPATH')) {
    exit;
}

final class Assets
{
    public const ADMIN_HANDLE = 'tnsp-admin';
    public const ADMIN_SCRIPT_HANDLE = 'tnsp-admin';
    public const FRONTEND_STYLE_HANDLE = 'tnsp-frontend';
    public const FRONTEND_SCRIPT_HANDLE = 'tnsp-frontend';

    public function init(): void
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets(string $hook_suffix): void
    {
        if ('posts_page_sticky-announcements' !== $hook_suffix) {
            return;
        }

        wp_enqueue_style(
            self::ADMIN_HANDLE,
            TNSP_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            TNSP_VERSION
        );

        wp_enqueue_script(
            self::ADMIN_SCRIPT_HANDLE,
            TNSP_PLUGIN_URL . 'assets/js/admin.js',
            array(),
            TNSP_VERSION,
            true
        );
    }

    public function enqueue_frontend_assets(): void
    {
        wp_enqueue_style(
            self::FRONTEND_STYLE_HANDLE,
            TNSP_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            TNSP_VERSION
        );

        wp_enqueue_script(
            self::FRONTEND_SCRIPT_HANDLE,
            TNSP_PLUGIN_URL . 'assets/js/frontend.js',
            array(),
            TNSP_VERSION,
            true
        );
    }
}
