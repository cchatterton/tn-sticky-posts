<?php

namespace Techn\StickyPosts;

if (!defined('ABSPATH')) {
    exit;
}

final class Meta
{
    public const TEXT_KEY = '_sticky_announcement_text';
    public const URL_KEY = '_sticky_announcement_url';

    private Validator $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    public function init(): void
    {
        add_action('init', array($this, 'register_meta'));
    }

    public function register_meta(): void
    {
        register_post_meta(
            'post',
            self::TEXT_KEY,
            array(
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => array($this->validator, 'sanitize_text'),
                'auth_callback'     => array($this, 'can_edit_meta'),
                'show_in_rest'      => false,
            )
        );

        register_post_meta(
            'post',
            self::URL_KEY,
            array(
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => array($this->validator, 'sanitize_url'),
                'auth_callback'     => array($this, 'can_edit_meta'),
                'show_in_rest'      => false,
            )
        );
    }

    public function can_edit_meta(bool $allowed, string $meta_key, int $post_id): bool
    {
        unset($allowed, $meta_key);

        return current_user_can(Admin_Page::manage_capability(), $post_id);
    }
}
