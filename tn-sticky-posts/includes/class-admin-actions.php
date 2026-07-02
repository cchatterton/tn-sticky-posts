<?php

namespace Techn\StickyPosts;

if (!defined('ABSPATH')) {
    exit;
}

final class Admin_Actions
{
    private Validator $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    public function init(): void
    {
        add_action('admin_post_tnsp_save_announcement', array($this, 'save_announcement'));
        add_action('admin_post_tnsp_clear_announcement', array($this, 'clear_announcement'));
        add_action('admin_post_tnsp_remove_sticky', array($this, 'remove_sticky'));
        add_action('admin_post_tnsp_bulk_action', array($this, 'bulk_action'));
    }

    public function save_announcement(): void
    {
        $post_id = $this->request_post_id();
        check_admin_referer('tnsp_save_announcement_' . $post_id);
        $post = $this->validate_post_request($post_id);

        $raw_text = isset($_POST['tnsp_announcement_text']) ? (string) wp_unslash($_POST['tnsp_announcement_text']) : '';
        $raw_label = isset($_POST['tnsp_click_label']) ? (string) wp_unslash($_POST['tnsp_click_label']) : '';
        $raw_url = isset($_POST['tnsp_announcement_url']) ? (string) wp_unslash($_POST['tnsp_announcement_url']) : '';
        $validation = $this->validator->validate_announcement($raw_text, $raw_label, $raw_url, $raw_text);

        update_post_meta($post->ID, Meta::TEXT_KEY, $validation['text']);
        update_post_meta($post->ID, Meta::LABEL_KEY, $validation['click_label']);
        update_post_meta($post->ID, Meta::URL_KEY, $validation['url']);

        do_action('sticky_announcements_saved', $post->ID, $validation['text'], $validation['click_label'], $validation['url'], $validation);

        if ($validation['valid']) {
            $this->redirect('saved');
        }

        $this->redirect('validation_failed', $validation['errors']);
    }

    public function clear_announcement(): void
    {
        $post_id = $this->request_post_id();
        check_admin_referer('tnsp_clear_announcement_' . $post_id);
        $post = $this->validate_post_request($post_id);

        delete_post_meta($post->ID, Meta::TEXT_KEY);
        delete_post_meta($post->ID, Meta::LABEL_KEY);
        delete_post_meta($post->ID, Meta::URL_KEY);

        do_action('sticky_announcements_cleared', $post->ID);

        $this->redirect('cleared');
    }

    public function remove_sticky(): void
    {
        $post_id = $this->request_post_id();
        check_admin_referer('tnsp_remove_sticky_' . $post_id);
        $post = $this->validate_post_request($post_id);

        unstick_post($post->ID);

        do_action('sticky_announcements_unstuck', $post->ID);

        $this->redirect('unstuck');
    }

    public function bulk_action(): void
    {
        check_admin_referer('tnsp_bulk_action');
        $this->require_capability();

        $bulk_action = isset($_POST['tnsp_bulk_action']) ? sanitize_key((string) wp_unslash($_POST['tnsp_bulk_action'])) : '';
        $post_ids = isset($_POST['tnsp_post_ids']) && is_array($_POST['tnsp_post_ids'])
            ? array_map('absint', wp_unslash($_POST['tnsp_post_ids']))
            : array();

        if (!in_array($bulk_action, array('clear', 'unstick'), true) || empty($post_ids)) {
            $this->redirect('bulk_no_selection');
        }

        $changed = 0;

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);

            if (!$post || 'post' !== $post->post_type || !is_sticky($post_id) || !current_user_can(Admin_Page::manage_capability(), $post_id)) {
                continue;
            }

            if ('clear' === $bulk_action) {
                delete_post_meta($post_id, Meta::TEXT_KEY);
                delete_post_meta($post_id, Meta::LABEL_KEY);
                delete_post_meta($post_id, Meta::URL_KEY);
                do_action('sticky_announcements_cleared', $post_id);
                $changed++;
            }

            if ('unstick' === $bulk_action) {
                unstick_post($post_id);
                do_action('sticky_announcements_unstuck', $post_id);
                $changed++;
            }
        }

        $this->redirect('bulk_done', array(), $changed);
    }

    private function validate_post_request(int $post_id): \WP_Post
    {
        $this->require_capability($post_id);
        $post = get_post($post_id);

        if (!$post) {
            $this->redirect('post_missing');
        }

        if ('post' !== $post->post_type) {
            $this->redirect('invalid_post_type');
        }

        if (!is_sticky($post_id)) {
            $this->redirect('not_sticky');
        }

        return $post;
    }

    private function request_post_id(): int
    {
        return isset($_REQUEST['post_id']) ? absint(wp_unslash($_REQUEST['post_id'])) : 0;
    }

    private function require_capability(int $post_id = 0): void
    {
        if (!current_user_can(Admin_Page::manage_capability(), $post_id)) {
            $this->redirect('insufficient_permissions');
        }
    }

    private function redirect(string $notice, array $errors = array(), int $count = 0): void
    {
        $args = array(
            'page'        => 'sticky-announcements',
            'tnsp_notice' => $notice,
        );

        if (!empty($errors)) {
            $args['tnsp_errors'] = implode(',', array_map('sanitize_key', $errors));
        }

        if ($count > 0) {
            $args['tnsp_count'] = $count;
        }

        wp_safe_redirect(add_query_arg($args, admin_url('edit.php')));
        exit;
    }
}
