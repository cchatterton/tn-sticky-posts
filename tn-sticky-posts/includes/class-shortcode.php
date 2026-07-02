<?php

namespace Techn\StickyPosts;

if (!defined('ABSPATH')) {
    exit;
}

final class Shortcode
{
    private Validator $validator;
    private Token_Parser $token_parser;
    private Assets $assets;

    public function __construct(Validator $validator, Token_Parser $token_parser, Assets $assets)
    {
        $this->validator = $validator;
        $this->token_parser = $token_parser;
        $this->assets = $assets;
    }

    public function init(): void
    {
        add_shortcode('sticky_announcements', array($this, 'render'));
    }

    public function render(array|string $atts = array()): string
    {
        $atts = shortcode_atts(
            apply_filters(
                'sticky_announcements_default_shortcode_atts',
                array(
                    'limit'       => '0',
                    'speed'       => '5000',
                    'transition'  => 'slide',
                    'order'       => 'modified',
                    'class'       => '',
                    'target'      => '_self',
                    'pause_hover' => 'true',
                )
            ),
            is_array($atts) ? $atts : array(),
            'sticky_announcements'
        );

        $atts = $this->normalise_atts($atts);
        $posts = $this->get_posts($atts);
        $items = $this->build_items($posts, $atts);

        if (empty($items)) {
            return '';
        }

        $this->assets->enqueue_frontend_assets();

        $classes = array_merge(
            array(
                'sticky-announcements',
                'sticky-announcements--' . $atts['transition'],
            ),
            $atts['class']
        );

        $output = '<div class="' . esc_attr(implode(' ', array_unique(array_filter($classes)))) . '" data-sticky-announcements data-speed="' . esc_attr((string) $atts['speed']) . '" data-pause-hover="' . esc_attr($atts['pause_hover'] ? 'true' : 'false') . '" aria-label="' . esc_attr__('Announcements', 'tn-sticky-posts') . '">';
        $output .= '<div class="sticky-announcements__viewport"><div class="sticky-announcements__items">';

        foreach ($items as $index => $item) {
            $active_class = 0 === $index ? ' is-active' : '';
            $output .= '<div class="sticky-announcements__item' . esc_attr($active_class) . '" data-announcement-item>';
            $output .= $item;
            $output .= '</div>';
        }

        $output .= '</div></div></div>';

        return apply_filters('sticky_announcements_shortcode_output', $output, $items, $atts);
    }

    private function normalise_atts(array $atts): array
    {
        $limit = absint($atts['limit']);
        $speed = max(2000, absint($atts['speed']));
        $transition = in_array($atts['transition'], array('slide', 'fade', 'none'), true) ? $atts['transition'] : 'slide';
        $order = in_array($atts['order'], array('modified', 'date', 'title', 'random'), true) ? $atts['order'] : 'modified';
        $target = in_array($atts['target'], array('_self', '_blank'), true) ? $atts['target'] : '_self';
        $pause_hover = in_array((string) $atts['pause_hover'], array('true', '1'), true);
        $classes = preg_split('/\s+/', (string) $atts['class']);
        $classes = array_values(array_filter(array_map('sanitize_html_class', (array) $classes)));

        return array(
            'limit'       => $limit,
            'speed'       => $speed,
            'transition'  => $transition,
            'order'       => $order,
            'class'       => $classes,
            'target'      => $target,
            'pause_hover' => $pause_hover,
        );
    }

    private function get_posts(array $atts): array
    {
        $sticky_ids = array_map('absint', (array) get_option('sticky_posts', array()));

        if (empty($sticky_ids)) {
            return array();
        }

        $args = array(
            'post_type'              => 'post',
            'post_status'            => 'publish',
            'post__in'               => $sticky_ids,
            'posts_per_page'         => $atts['limit'] > 0 ? $atts['limit'] : -1,
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_term_cache' => false,
        );

        if ('random' === $atts['order']) {
            $args['orderby'] = 'rand';
        } elseif ('title' === $atts['order']) {
            $args['orderby'] = 'title';
            $args['order'] = 'ASC';
        } elseif ('date' === $atts['order']) {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } else {
            $args['orderby'] = 'modified';
            $args['order'] = 'DESC';
        }

        $args = apply_filters('sticky_announcements_query_args', $args, $atts, $sticky_ids);

        return get_posts($args);
    }

    private function build_items(array $posts, array $atts): array
    {
        $items = array();
        $link_attributes = array(
            'target' => $atts['target'],
        );

        if ('_blank' === $atts['target']) {
            $link_attributes['rel'] = 'noopener noreferrer';
        }

        foreach ($posts as $post) {
            if (!$post instanceof \WP_Post || !is_sticky($post->ID)) {
                continue;
            }

            $text = (string) get_post_meta($post->ID, Meta::TEXT_KEY, true);
            $url = (string) get_post_meta($post->ID, Meta::URL_KEY, true);
            $validation = $this->validator->validate_announcement($text, $url);

            if (!$validation['valid'] || '' === $validation['text']) {
                continue;
            }

            $rendered = $this->token_parser->render_announcement_text($validation['text'], $validation['url'], $link_attributes);

            if ('' !== $rendered) {
                $items[] = $rendered;
            }
        }

        return $items;
    }
}
