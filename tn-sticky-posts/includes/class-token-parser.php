<?php

namespace Techn\StickyPosts;

if (!defined('ABSPATH')) {
    exit;
}

final class Token_Parser
{
    private Validator $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    public function render_announcement_text(string $text, string $url, array $link_attributes = array()): string
    {
        $validation = $this->validator->validate_announcement($text, $url);

        if (!$validation['valid'] || '' === $validation['text']) {
            return '';
        }

        $safe_text = $validation['text'];

        if (!str_contains($safe_text, '%click%')) {
            $rendered = '<span class="sticky-announcements__text">' . wp_kses($safe_text, $this->validator->allowed_html()) . '</span>';

            return apply_filters('sticky_announcements_rendered_text', $rendered, $safe_text, $validation['url']);
        }

        $open_pos = strpos($safe_text, '%click%');
        $close_pos = strpos($safe_text, '%/click%');

        if (false === $open_pos || false === $close_pos || $close_pos <= $open_pos) {
            return '';
        }

        $before = substr($safe_text, 0, $open_pos);
        $linked = substr($safe_text, $open_pos + strlen('%click%'), $close_pos - ($open_pos + strlen('%click%')));
        $after = substr($safe_text, $close_pos + strlen('%/click%'));

        $attributes = $this->build_link_attributes($validation['url'], $link_attributes);
        $link = '<a ' . $attributes . '>' . wp_kses($linked, $this->validator->allowed_html()) . '</a>';

        $rendered = sprintf(
            '<span class="sticky-announcements__text">%s%s%s</span>',
            wp_kses($before, $this->validator->allowed_html()),
            $link,
            wp_kses($after, $this->validator->allowed_html())
        );

        return apply_filters('sticky_announcements_rendered_text', $rendered, $safe_text, $validation['url']);
    }

    private function build_link_attributes(string $url, array $link_attributes): string
    {
        $attributes = array_merge(
            array(
                'class' => 'sticky-announcements__link',
                'href'  => esc_url($url),
            ),
            $link_attributes
        );

        $attributes['href'] = esc_url($attributes['href']);
        $attributes = apply_filters('sticky_announcements_link_attributes', $attributes, $url);
        $parts = array();

        foreach ($attributes as $name => $value) {
            $name = sanitize_key((string) $name);

            if ('' === $name || null === $value || false === $value) {
                continue;
            }

            $parts[] = sprintf('%s="%s"', esc_attr($name), esc_attr((string) $value));
        }

        return implode(' ', $parts);
    }
}
