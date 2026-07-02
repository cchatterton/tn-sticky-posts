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

    public function validate_announcement(string $text, string $url, string $raw_text = ''): array
    {
        $errors = array();
        $sanitised_text = $this->sanitize_text($text);
        $sanitised_url = $this->sanitize_url($url);
        $source_text = '' !== $raw_text ? wp_unslash($raw_text) : $text;

        if (preg_match('/[\r\n]/', $source_text)) {
            $errors[] = 'line_breaks_not_allowed';
        }

        if (preg_match('/<\/?(p|div|img|script|iframe|h[1-6]|ul|ol|li|button|form|br)\b/i', $source_text)) {
            $errors[] = 'unsupported_html';
        }

        if ('' === $sanitised_text) {
            if ('' !== $sanitised_url) {
                $errors[] = 'url_without_token';
            }

            return $this->result(empty($errors), $errors, $sanitised_text, $sanitised_url);
        }

        $open_count = substr_count($sanitised_text, '%click%');
        $close_count = substr_count($sanitised_text, '%/click%');

        if (0 === $open_count && 0 === $close_count) {
            if ('' !== $sanitised_url) {
                $errors[] = 'url_without_token';
            }

            return $this->result(empty($errors), $errors, $sanitised_text, $sanitised_url);
        }

        if (1 !== $open_count || 1 !== $close_count) {
            $errors[] = 'invalid_token_syntax';
        }

        $open_pos = strpos($sanitised_text, '%click%');
        $close_pos = strpos($sanitised_text, '%/click%');

        if (false === $open_pos || false === $close_pos || $close_pos <= $open_pos) {
            $errors[] = 'invalid_token_syntax';
        } else {
            $linked_text = substr($sanitised_text, $open_pos + strlen('%click%'), $close_pos - ($open_pos + strlen('%click%')));

            if ('' === trim(wp_strip_all_tags($linked_text))) {
                $errors[] = 'empty_link_text';
            }
        }

        if ('' === $sanitised_url) {
            $errors[] = 'missing_url';
        } elseif (!$this->is_valid_destination_url($sanitised_url)) {
            $errors[] = 'invalid_url';
        }

        return $this->result(empty(array_unique($errors)), array_values(array_unique($errors)), $sanitised_text, $sanitised_url);
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
            'url_without_token'       => __('A destination URL requires a click token in the announcement text.', 'tn-sticky-posts'),
            'invalid_token_syntax'    => __('The click token syntax is invalid. Use one %click%...%/click% pair.', 'tn-sticky-posts'),
            'empty_link_text'         => __('The linked words inside the click token cannot be empty.', 'tn-sticky-posts'),
            'missing_url'             => __('A destination URL is required when a click token is used.', 'tn-sticky-posts'),
            'invalid_url'             => __('The destination URL is invalid.', 'tn-sticky-posts'),
        );

        $messages = apply_filters('sticky_announcements_validation_errors', $messages);

        return $messages[$error] ?? __('The announcement could not be validated.', 'tn-sticky-posts');
    }

    private function result(bool $valid, array $errors, string $text, string $url): array
    {
        return array(
            'valid'  => $valid,
            'errors' => $errors,
            'text'   => $text,
            'url'    => $url,
        );
    }

    private function is_root_relative_url(string $url): bool
    {
        return str_starts_with($url, '/') && !str_starts_with($url, '//') && !preg_match('/[\r\n]/', $url);
    }
}
