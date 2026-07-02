<?php

namespace Techn\StickyPosts;

if (!defined('ABSPATH')) {
    exit;
}

final class Admin_Page
{
    private Validator $validator;
    private Assets $assets;

    public function __construct(Validator $validator, Assets $assets)
    {
        $this->validator = $validator;
        $this->assets = $assets;
    }

    public static function manage_capability(): string
    {
        return (string) apply_filters('sticky_announcements_manage_capability', 'edit_others_posts');
    }

    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_menu'));
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'edit.php',
            __('Sticky Posts', 'tn-sticky-posts'),
            __('Sticky', 'tn-sticky-posts'),
            self::manage_capability(),
            'sticky-announcements',
            array($this, 'render')
        );
    }

    public function render(): void
    {
        if (!current_user_can(self::manage_capability())) {
            wp_die(esc_html__('You do not have permission to manage sticky announcements.', 'tn-sticky-posts'));
        }

        $posts = $this->get_sticky_posts();
        $summary = $this->summary($posts);

        echo '<div class="wrap tnsp-admin-wrap">';
        echo '<h1>' . esc_html__('Sticky Posts', 'tn-sticky-posts') . '</h1>';
        $this->render_notices();
        echo '<p>' . esc_html__('Posts appear here when they are marked as sticky in the normal post editor. Add announcement content to include a post in the announcement strip.', 'tn-sticky-posts') . '</p>';
        $this->render_summary($summary);
        $this->render_search();
        $this->render_table($posts);
        echo '</div>';
    }

    private function get_sticky_posts(): array
    {
        $sticky_ids = array_map('absint', (array) get_option('sticky_posts', array()));

        if (empty($sticky_ids)) {
            return array();
        }

        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $args = array(
            'post_type'              => 'post',
            'post_status'            => 'any',
            'post__in'               => $sticky_ids,
            'posts_per_page'         => -1,
            'orderby'                => 'modified',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
        );

        if ('' !== $search) {
            $args['s'] = $search;
        }

        return get_posts($args);
    }

    private function summary(array $posts): array
    {
        $summary = array(
            'total'       => count($posts),
            'active'      => 0,
            'unannounced' => 0,
            'unpublished' => 0,
        );

        foreach ($posts as $post) {
            $status = $this->row_status($post);

            if ('active' === $status['key']) {
                $summary['active']++;
            }

            if ('not_announced' === $status['key']) {
                $summary['unannounced']++;
            }

            if ('publish' !== $post->post_status) {
                $summary['unpublished']++;
            }
        }

        return $summary;
    }

    private function render_summary(array $summary): void
    {
        echo '<ul class="tnsp-summary" aria-label="' . esc_attr__('Sticky announcement status summary', 'tn-sticky-posts') . '">';
        echo '<li><strong>' . esc_html((string) $summary['total']) . '</strong> ' . esc_html__('Total sticky posts', 'tn-sticky-posts') . '</li>';
        echo '<li><strong>' . esc_html((string) $summary['active']) . '</strong> ' . esc_html__('Active announcements', 'tn-sticky-posts') . '</li>';
        echo '<li><strong>' . esc_html((string) $summary['unannounced']) . '</strong> ' . esc_html__('Not announced', 'tn-sticky-posts') . '</li>';
        echo '<li><strong>' . esc_html((string) $summary['unpublished']) . '</strong> ' . esc_html__('Unpublished sticky posts', 'tn-sticky-posts') . '</li>';
        echo '</ul>';
    }

    private function render_search(): void
    {
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        echo '<form method="get" class="tnsp-search">';
        echo '<input type="hidden" name="post_type" value="post">';
        echo '<input type="hidden" name="page" value="sticky-announcements">';
        echo '<label class="screen-reader-text" for="tnsp-search-input">' . esc_html__('Search sticky posts', 'tn-sticky-posts') . '</label>';
        echo '<input id="tnsp-search-input" type="search" name="s" value="' . esc_attr($search) . '" placeholder="' . esc_attr__('Search sticky posts', 'tn-sticky-posts') . '">';
        submit_button(__('Search', 'tn-sticky-posts'), 'secondary', '', false);
        echo '</form>';
    }

    private function render_table(array $posts): void
    {
        if (empty($posts)) {
            echo '<p>' . esc_html__('No sticky posts found.', 'tn-sticky-posts') . '</p>';
            return;
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" id="tnsp-bulk-form">';
        wp_nonce_field('tnsp_bulk_action');
        echo '<input type="hidden" name="action" value="tnsp_bulk_action">';
        echo '</form>';
        echo '<div class="tablenav top"><div class="alignleft actions bulkactions">';
        echo '<label for="tnsp-bulk-action" class="screen-reader-text">' . esc_html__('Select bulk action', 'tn-sticky-posts') . '</label>';
        echo '<select id="tnsp-bulk-action" name="tnsp_bulk_action" form="tnsp-bulk-form">';
        echo '<option value="">' . esc_html__('Bulk actions', 'tn-sticky-posts') . '</option>';
        echo '<option value="clear">' . esc_html__('Clear announcement', 'tn-sticky-posts') . '</option>';
        echo '<option value="unstick">' . esc_html__('Remove sticky flag', 'tn-sticky-posts') . '</option>';
        echo '</select> ';
        echo '<button type="submit" class="button action" form="tnsp-bulk-form">' . esc_html__('Apply', 'tn-sticky-posts') . '</button>';
        echo '</div></div>';

        echo '<table class="widefat fixed striped tnsp-table">';
        echo '<thead><tr>';
        echo '<td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">' . esc_html__('Select all sticky posts', 'tn-sticky-posts') . '</label><input id="cb-select-all-1" type="checkbox"></td>';
        echo '<th scope="col">' . esc_html__('Status', 'tn-sticky-posts') . '</th>';
        echo '<th scope="col">' . esc_html__('Post', 'tn-sticky-posts') . '</th>';
        echo '<th scope="col">' . esc_html__('Announcement', 'tn-sticky-posts') . '</th>';
        echo '<th scope="col">' . esc_html__('Click label', 'tn-sticky-posts') . '</th>';
        echo '<th scope="col">' . esc_html__('Click URL', 'tn-sticky-posts') . '</th>';
        echo '<th scope="col">' . esc_html__('Post status', 'tn-sticky-posts') . '</th>';
        echo '<th scope="col">' . esc_html__('Last modified', 'tn-sticky-posts') . '</th>';
        echo '<th scope="col">' . esc_html__('Actions', 'tn-sticky-posts') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($posts as $post) {
            $this->render_row($post);
        }

        echo '</tbody></table>';
    }

    private function render_row(\WP_Post $post): void
    {
        $text = (string) get_post_meta($post->ID, Meta::TEXT_KEY, true);
        $label = (string) get_post_meta($post->ID, Meta::LABEL_KEY, true);
        $url = (string) get_post_meta($post->ID, Meta::URL_KEY, true);
        $status = $this->row_status($post);
        $save_form = 'tnsp-save-' . $post->ID;
        $clear_form = 'tnsp-clear-' . $post->ID;
        $unstick_form = 'tnsp-unstick-' . $post->ID;

        echo '<tr>';
        echo '<th scope="row" class="check-column"><input type="checkbox" name="tnsp_post_ids[]" value="' . esc_attr((string) $post->ID) . '" form="tnsp-bulk-form"></th>';
        echo '<td><span class="tnsp-status tnsp-status--' . esc_attr($status['key']) . '">' . esc_html($status['label']) . '</span>';

        if (!empty($status['messages'])) {
            echo '<ul class="tnsp-row-errors">';
            foreach ($status['messages'] as $message) {
                echo '<li>' . esc_html($message) . '</li>';
            }
            echo '</ul>';
        }

        echo '</td>';
        echo '<td>' . $this->post_column($post) . '</td>';
        echo '<td><label class="screen-reader-text" for="tnsp-text-' . esc_attr((string) $post->ID) . '">' . esc_html__('Announcement', 'tn-sticky-posts') . '</label>';
        echo '<input id="tnsp-text-' . esc_attr((string) $post->ID) . '" class="regular-text tnsp-announcement-input" type="text" name="tnsp_announcement_text" value="' . esc_attr($text) . '" placeholder="' . esc_attr__('Registrations are open for our %click%.', 'tn-sticky-posts') . '" form="' . esc_attr($save_form) . '"></td>';
        echo '<td><label class="screen-reader-text" for="tnsp-label-' . esc_attr((string) $post->ID) . '">' . esc_html__('Click label', 'tn-sticky-posts') . '</label>';
        echo '<input id="tnsp-label-' . esc_attr((string) $post->ID) . '" class="regular-text" type="text" name="tnsp_click_label" value="' . esc_attr($label) . '" placeholder="' . esc_attr__('annual conference', 'tn-sticky-posts') . '" form="' . esc_attr($save_form) . '"></td>';
        echo '<td><label class="screen-reader-text" for="tnsp-url-' . esc_attr((string) $post->ID) . '">' . esc_html__('Click URL', 'tn-sticky-posts') . '</label>';
        echo '<input id="tnsp-url-' . esc_attr((string) $post->ID) . '" class="regular-text" type="text" inputmode="url" name="tnsp_announcement_url" value="' . esc_attr($url) . '" placeholder="' . esc_attr__('https://example.com/event/', 'tn-sticky-posts') . '" form="' . esc_attr($save_form) . '"></td>';
        $post_status = get_post_status_object($post->post_status);
        echo '<td>' . esc_html($post_status ? $post_status->label : $post->post_status) . '</td>';
        echo '<td>' . esc_html(get_the_modified_date('', $post)) . '</td>';
        echo '<td class="tnsp-actions">';
        echo '<form id="' . esc_attr($save_form) . '" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('tnsp_save_announcement_' . $post->ID);
        echo '<input type="hidden" name="action" value="tnsp_save_announcement"><input type="hidden" name="post_id" value="' . esc_attr((string) $post->ID) . '">';
        submit_button(__('Save', 'tn-sticky-posts'), 'primary small', '', false);
        echo '</form>';
        echo '<form id="' . esc_attr($clear_form) . '" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('tnsp_clear_announcement_' . $post->ID);
        echo '<input type="hidden" name="action" value="tnsp_clear_announcement"><input type="hidden" name="post_id" value="' . esc_attr((string) $post->ID) . '">';
        submit_button(__('Clear announcement', 'tn-sticky-posts'), 'secondary small', '', false);
        echo '</form>';
        echo '<form id="' . esc_attr($unstick_form) . '" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('tnsp_remove_sticky_' . $post->ID);
        echo '<input type="hidden" name="action" value="tnsp_remove_sticky"><input type="hidden" name="post_id" value="' . esc_attr((string) $post->ID) . '">';
        submit_button(__('Remove sticky', 'tn-sticky-posts'), 'delete small', '', false);
        echo '</form>';
        echo '</td></tr>';
    }

    private function post_column(\WP_Post $post): string
    {
        $title = get_the_title($post);
        $title = '' !== $title ? $title : __('(no title)', 'tn-sticky-posts');
        $output = '<strong><a href="' . esc_url(get_edit_post_link($post->ID)) . '">' . esc_html($title) . '</a></strong>';
        $output .= '<div class="row-actions">';
        $output .= '<span class="edit"><a href="' . esc_url(get_edit_post_link($post->ID)) . '">' . esc_html__('Edit', 'tn-sticky-posts') . '</a></span>';

        if ('publish' === $post->post_status) {
            $output .= ' | <span class="view"><a href="' . esc_url(get_permalink($post)) . '">' . esc_html__('View', 'tn-sticky-posts') . '</a></span>';
        }

        $author = get_the_author_meta('display_name', (int) $post->post_author);
        if ('' !== $author) {
            $output .= '<br><span class="tnsp-author">' . esc_html(sprintf(__('Author: %s', 'tn-sticky-posts'), $author)) . '</span>';
        }

        $output .= '</div>';

        return $output;
    }

    private function row_status(\WP_Post $post): array
    {
        $text = (string) get_post_meta($post->ID, Meta::TEXT_KEY, true);
        $label = (string) get_post_meta($post->ID, Meta::LABEL_KEY, true);
        $url = (string) get_post_meta($post->ID, Meta::URL_KEY, true);
        $validation = $this->validator->validate_announcement($text, $label, $url);

        if ('' === trim(wp_strip_all_tags($text)) && '' === $label && '' === $url) {
            return array(
                'key'      => 'not_announced',
                'label'    => __('Not announced', 'tn-sticky-posts'),
                'messages' => array(),
            );
        }

        $messages = array();

        if ('publish' !== $post->post_status) {
            $messages[] = __('The post is not published.', 'tn-sticky-posts');
        }

        foreach ($validation['errors'] as $error) {
            $messages[] = $this->validator->error_message($error);
        }

        if (!empty($messages)) {
            return array(
                'key'      => 'needs_attention',
                'label'    => __('Needs attention', 'tn-sticky-posts'),
                'messages' => $messages,
            );
        }

        return array(
            'key'      => 'active',
            'label'    => __('Active announcement', 'tn-sticky-posts'),
            'messages' => array(),
        );
    }

    private function render_notices(): void
    {
        $notice = isset($_GET['tnsp_notice']) ? sanitize_key((string) wp_unslash($_GET['tnsp_notice'])) : '';
        $errors = isset($_GET['tnsp_errors']) ? array_filter(array_map('sanitize_key', explode(',', (string) wp_unslash($_GET['tnsp_errors'])))) : array();
        $count = isset($_GET['tnsp_count']) ? absint(wp_unslash($_GET['tnsp_count'])) : 0;

        if ('' === $notice) {
            return;
        }

        $type = 'success';
        $message = '';

        switch ($notice) {
            case 'saved':
                $message = __('Announcement saved.', 'tn-sticky-posts');
                break;
            case 'cleared':
                $message = __('Announcement cleared.', 'tn-sticky-posts');
                break;
            case 'unstuck':
                $message = __('Sticky flag removed.', 'tn-sticky-posts');
                break;
            case 'validation_failed':
                $type = 'warning';
                $message = __('Announcement saved, but it needs attention before it can appear in the shortcode.', 'tn-sticky-posts');
                break;
            case 'bulk_done':
                $message = sprintf(
                    /* translators: %d: number of posts changed. */
                    _n('%d sticky post updated.', '%d sticky posts updated.', $count, 'tn-sticky-posts'),
                    $count
                );
                break;
            case 'bulk_no_selection':
                $type = 'warning';
                $message = __('Choose a bulk action and at least one sticky post.', 'tn-sticky-posts');
                break;
            case 'insufficient_permissions':
                $type = 'error';
                $message = __('You do not have permission to manage sticky announcements.', 'tn-sticky-posts');
                break;
            case 'post_missing':
                $type = 'error';
                $message = __('The post no longer exists.', 'tn-sticky-posts');
                break;
            case 'not_sticky':
                $type = 'error';
                $message = __('The post is no longer sticky.', 'tn-sticky-posts');
                break;
            case 'invalid_post_type':
                $type = 'error';
                $message = __('Only standard posts can be managed here.', 'tn-sticky-posts');
                break;
        }

        if ('' === $message) {
            return;
        }

        echo '<div class="notice notice-' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($message) . '</p>';

        if (!empty($errors)) {
            echo '<ul>';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($this->validator->error_message($error)) . '</li>';
            }
            echo '</ul>';
        }

        echo '</div>';
    }
}
