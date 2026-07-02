<?php

namespace Techn\StickyPosts;

if (!defined('ABSPATH')) {
    exit;
}

final class Validator
{
    public function allowed_html(): array
    {
        $allowed_html = array(
            'strong' => array(),
            'em'     => array(),
            'b'      => array(),
            'i'      => array(),
            'span'   => array(),
        );

        return apply_filters('sticky_announcements_allowed_html', $allowed_html);
    }

    public function sanitize_text(string $text): string
    {
        $text = str_replace(array("\r", "\n", "\t"), ' ', wp_unslash($text));
        $text = preg_replace('/\s+/', ' ', $text);
        $text = wp_kses((string) $text, $this->allowed_html());
        $text = trim(strip_shortcodes($text));

        return (string) $text;
    }

    public function sanitize_url(string $url): string
    {
        $url = trim(str_replace(array("\r", "\n", "\t"), '', wp_unslash($url)));

        if ('' === $url) {
            return '';
        }

        if ($this->is_root_relative_url($url)) {
            return esc_url_raw($url);
        }

        return esc_url_raw($url);
    }

    public function sanitize_label(string $label): string
    {
        $label = str_replace(array("\r", "\n", "\t"), ' ', wp_unslash($label));
        $label = preg_replace('/\s+/', ' ', $label);
        $label = wp_kses((string) $label, $this->allowed_html());

        return trim($label);
    }

    public function validate_announcement(string $text, string $label, string $url, string $raw_text = ''): array
    {
        $errors = array();
        $sanitised_text = $this->sanitize_text($text);
        $sanitised_label = $this->sanitize_label($label);
        $sanitised_url = $this->sanitize_url($url);
        $source_text = '' !== $raw_text ? wp_unslash($raw_text) : $text;

        if (preg_match('/[\r\n]/', $source_text)) {
            $errors[] = 'line_breaks_not_allowed';
        }

        if (preg_match('/<\/?(p|div|img|script|iframe|h[1-6]|ul|ol|li|button|form|br)\b/i', $source_text)) {
            $errors[] = 'unsupported_html';
        }

        if ('' === $sanitised_text) {
            if ('' !== $sanitised_label || '' !== $sanitised_url) {
                $errors[] = 'url_without_token';
            }

            return $this->result(empty($errors), $errors, $sanitised_text, $sanitised_label, $sanitised_url);
        }

        $token_count = substr_count($sanitised_text, '%click%');
        $legacy_close_count = substr_count($sanitised_text, '%/click%');

        if ($legacy_close_count > 0 || $token_count > 1) {
            $errors[] = 'invalid_token_syntax';
        }

        if (0 === $token_count) {
            if ('' !== $sanitised_label || '' !== $sanitised_url) {
                $errors[] = 'url_without_token';
            }

            return $this->result(empty($errors), $errors, $sanitised_text, $sanitised_label, $sanitised_url);
        }

        if ('' === trim(wp_strip_all_tags($sanitised_label))) {
            $errors[] = 'empty_link_text';
        }

        if ('' === $sanitised_url) {
            $errors[] = 'missing_url';
        } elseif (!$this->is_valid_destination_url($sanitised_url)) {
            $errors[] = 'invalid_url';
        }

        return $this->result(empty(array_unique($errors)), array_values(array_unique($errors)), $sanitised_text, $sanitised_label, $sanitised_url);
    }

    public function is_valid_destination_url(string $url): bool
    {
        if ('' === $url) {
            return false;
        }

        if ($this->is_root_relative_url($url)) {
            return true;
        }

        return (bool) wp_http_validate_url($url);
    }

    public function error_message(string $error): string
    {
        $messages = array(
            'line_breaks_not_allowed' => __('Line breaks are not supported in announcement text.', 'tn-sticky-posts'),
            'unsupported_html'        => __('Unsupported HTML was entered.', 'tn-sticky-posts'),
            'url_without_token'       => __('A click label or destination URL requires a %click% token in the announcement text.', 'tn-sticky-posts'),
            'invalid_token_syntax'    => __('The click token syntax is invalid. Use one %click% placeholder.', 'tn-sticky-posts'),
            'empty_link_text'         => __('The click label cannot be empty when a click token is used.', 'tn-sticky-posts'),
            'missing_url'             => __('A destination URL is required when a click token is used.', 'tn-sticky-posts'),
            'invalid_url'             => __('The destination URL is invalid.', 'tn-sticky-posts'),
        );

        $messages = apply_filters('sticky_announcements_validation_errors', $messages);

        return $messages[$error] ?? __('The announcement could not be validated.', 'tn-sticky-posts');
    }

    private function result(bool $valid, array $errors, string $text, string $label, string $url): array
    {
        return array(
            'valid'       => $valid,
            'errors'      => $errors,
            'text'        => $text,
            'click_label' => $label,
            'url'         => $url,
        );
    }

    private function is_root_relative_url(string $url): bool
    {
        return str_starts_with($url, '/') && !str_starts_with($url, '//') && !preg_match('/[\r\n]/', $url);
    }
}
