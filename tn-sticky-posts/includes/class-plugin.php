<?php

namespace Techn\StickyPosts;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    private static ?self $instance = null;

    private Validator $validator;
    private Token_Parser $token_parser;
    private Meta $meta;
    private Assets $assets;
    private Admin_Actions $admin_actions;
    private Admin_Page $admin_page;
    private Shortcode $shortcode;
    private GitHub_Updater $github_updater;

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->validator = new Validator();
        $this->token_parser = new Token_Parser($this->validator);
        $this->meta = new Meta($this->validator);
        $this->assets = new Assets();
        $this->admin_actions = new Admin_Actions($this->validator);
        $this->admin_page = new Admin_Page($this->validator, $this->assets);
        $this->shortcode = new Shortcode($this->validator, $this->token_parser, $this->assets);
        $this->github_updater = new GitHub_Updater();
    }

    public function init(): void
    {
        load_plugin_textdomain('tn-sticky-posts', false, dirname(TNSP_PLUGIN_BASENAME) . '/languages');

        $this->meta->init();
        $this->assets->init();
        $this->admin_actions->init();
        $this->admin_page->init();
        $this->shortcode->init();
        $this->github_updater->init();
    }
}
