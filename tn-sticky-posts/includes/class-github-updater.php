<?php

namespace Techn\StickyPosts;

if (!defined('ABSPATH')) {
    exit;
}

final class GitHub_Updater
{
    private const OWNER = 'cchatterton';
    private const REPO = 'tn-sticky-posts';
    private const SLUG = 'tn-sticky-posts';
    private const ASSET_NAME = 'tn-sticky-posts.zip';
    private const RELEASE_TRANSIENT = 'tnsp_github_latest_release';
    private const ERROR_TRANSIENT = 'tnsp_github_latest_release_error';

    public function init(): void
    {
        if (!is_admin()) {
            return;
        }

        add_filter('pre_set_site_transient_update_plugins', array($this, 'add_update_data'));
        add_filter('site_transient_update_plugins', array($this, 'add_update_data'));
        add_filter('plugins_api', array($this, 'plugin_details'), 10, 3);
        add_filter('plugin_row_meta', array($this, 'row_meta'), 10, 2);
        add_action('admin_init', array($this, 'handle_manual_update_check'));
        add_action('admin_notices', array($this, 'manual_update_check_notice'));
        add_action('network_admin_notices', array($this, 'manual_update_check_notice'));
        add_action('upgrader_process_complete', array($this, 'clear_cache_after_upgrade'), 10, 2);
    }

    public function add_update_data($transient)
    {
        if (!is_object($transient)) {
            $transient = new \stdClass();
        }

        $transient->response = isset($transient->response) && is_array($transient->response) ? $transient->response : array();
        $transient->no_update = isset($transient->no_update) && is_array($transient->no_update) ? $transient->no_update : array();

        $plugin_file = TNSP_PLUGIN_BASENAME;
        $release = $this->latest_release();

        if (!$release) {
            unset($transient->response[$plugin_file], $transient->no_update[$plugin_file]);
            return $transient;
        }

        $version = $this->release_version($release);
        $package = $this->asset_url($release);

        if ($version && $package && version_compare($version, TNSP_VERSION, '>')) {
            $transient->response[$plugin_file] = (object) array(
                'id'           => $this->repo_url(),
                'slug'         => self::SLUG,
                'plugin'       => $plugin_file,
                'new_version'  => $version,
                'url'          => $this->repo_url(),
                'package'      => $package,
                'requires'     => '6.0',
                'requires_php' => '8.1',
            );
            unset($transient->no_update[$plugin_file]);
            return $transient;
        }

        unset($transient->response[$plugin_file], $transient->no_update[$plugin_file]);
        return $transient;
    }

    public function plugin_details($result, string $action, object $args)
    {
        if ('plugin_information' !== $action || empty($args->slug) || self::SLUG !== $args->slug) {
            return $result;
        }

        $release = $this->latest_release();
        if (!$release) {
            return $result;
        }

        return (object) array(
            'name'          => 'TN Sticky Posts',
            'slug'          => self::SLUG,
            'version'       => $this->release_version($release) ?: TNSP_VERSION,
            'author'        => 'Techn',
            'homepage'      => $this->repo_url(),
            'download_link' => $this->asset_url($release),
            'requires'      => '6.0',
            'requires_php'  => '8.1',
            'sections'      => array(
                'description' => __('Centrally manages announcement content for native WordPress sticky posts.', 'tn-sticky-posts'),
                'changelog'   => wp_kses_post((string) ($release['body'] ?? '')),
            ),
        );
    }

    public function row_meta(array $links, string $file): array
    {
        if (TNSP_PLUGIN_BASENAME !== $file) {
            return $links;
        }

        $links[] = '<a href="' . esc_url($this->repo_url()) . '">' . esc_html__('GitHub', 'tn-sticky-posts') . '</a>';
        $links[] = '<a href="' . esc_url($this->check_updates_url()) . '">' . esc_html__('Check for updates', 'tn-sticky-posts') . '</a>';

        return $links;
    }

    public function handle_manual_update_check(): void
    {
        if (empty($_GET['tnsp_check_updates'])) {
            return;
        }

        if (!current_user_can('update_plugins')) {
            wp_die(esc_html__('You do not have permission to check for plugin updates.', 'tn-sticky-posts'));
        }

        check_admin_referer('tnsp_check_updates');
        delete_site_transient(self::RELEASE_TRANSIENT);
        delete_site_transient(self::ERROR_TRANSIENT);
        delete_site_transient('update_plugins');

        if (!function_exists('wp_update_plugins')) {
            require_once ABSPATH . 'wp-includes/update.php';
        }

        if (function_exists('wp_clean_plugins_cache')) {
            wp_clean_plugins_cache(true);
        }

        $_GET['force-check'] = '1';
        wp_update_plugins();

        $transient = get_site_transient('update_plugins');
        $transient = $this->add_update_data(is_object($transient) ? $transient : new \stdClass());
        set_site_transient('update_plugins', $transient);

        $notice = isset($transient->response[TNSP_PLUGIN_BASENAME]) ? 'update_available' : 'no_update';
        if (get_site_transient(self::ERROR_TRANSIENT)) {
            $notice = 'lookup_failed';
        }

        wp_safe_redirect(add_query_arg('tnsp_checked_updates', $notice, $this->plugins_page_url()));
        exit;
    }

    public function manual_update_check_notice(): void
    {
        $notice = isset($_GET['tnsp_checked_updates']) ? sanitize_key((string) wp_unslash($_GET['tnsp_checked_updates'])) : '';

        if ('' === $notice) {
            return;
        }

        if ('update_available' === $notice) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('TN Sticky Posts update check completed. A newer version is available below.', 'tn-sticky-posts') . '</p></div>';
            return;
        }

        if ('lookup_failed' === $notice) {
            $message = __('TN Sticky Posts could not read the latest GitHub release. The site may be blocked from reaching GitHub or rate limited.', 'tn-sticky-posts');
            $error = get_site_transient(self::ERROR_TRANSIENT);

            if (is_array($error) && !empty($error['message'])) {
                $message .= ' ' . sprintf(
                    /* translators: %s: GitHub lookup error message. */
                    __('GitHub returned: %s', 'tn-sticky-posts'),
                    (string) $error['message']
                );
            }

            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
            return;
        }

        if ('no_update' === $notice) {
            echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__('TN Sticky Posts update check completed. No newer GitHub release was found.', 'tn-sticky-posts') . '</p></div>';
        }
    }

    public function clear_cache_after_upgrade($upgrader, array $hook_extra): void
    {
        unset($upgrader);

        if (($hook_extra['type'] ?? '') !== 'plugin' || empty($hook_extra['plugins']) || !in_array(TNSP_PLUGIN_BASENAME, (array) $hook_extra['plugins'], true)) {
            return;
        }

        delete_site_transient(self::RELEASE_TRANSIENT);
        delete_site_transient(self::ERROR_TRANSIENT);
    }

    private function latest_release(): ?array
    {
        if ($this->is_forced_check()) {
            delete_site_transient(self::RELEASE_TRANSIENT);
        }

        $cached = get_site_transient(self::RELEASE_TRANSIENT);
        if (is_array($cached)) {
            return $cached;
        }

        $response = wp_remote_get(
            'https://api.github.com/repos/' . self::OWNER . '/' . self::REPO . '/releases/latest',
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept'     => 'application/vnd.github+json',
                    'User-Agent' => 'TN-Sticky-Posts/' . TNSP_VERSION,
                ),
            )
        );

        if (is_wp_error($response)) {
            set_site_transient(
                self::ERROR_TRANSIENT,
                array(
                    'type'       => 'wp_error',
                    'message'    => $response->get_error_message(),
                    'checked_at' => time(),
                ),
                10 * MINUTE_IN_SECONDS
            );
            delete_site_transient(self::RELEASE_TRANSIENT);
            return null;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            set_site_transient(
                self::ERROR_TRANSIENT,
                array(
                    'type'       => 'http_error',
                    'code'       => $code,
                    'message'    => wp_remote_retrieve_response_message($response),
                    'body'       => substr($body, 0, 500),
                    'checked_at' => time(),
                ),
                10 * MINUTE_IN_SECONDS
            );
            delete_site_transient(self::RELEASE_TRANSIENT);
            return null;
        }

        $release = json_decode($body, true);
        if (!is_array($release) || !$this->release_version($release)) {
            set_site_transient(
                self::ERROR_TRANSIENT,
                array(
                    'type'       => 'json_error',
                    'checked_at' => time(),
                ),
                10 * MINUTE_IN_SECONDS
            );
            delete_site_transient(self::RELEASE_TRANSIENT);
            return null;
        }

        $cache_length = version_compare($this->release_version($release), TNSP_VERSION, '>') ? 6 * HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS;
        set_site_transient(self::RELEASE_TRANSIENT, $release, $cache_length);
        delete_site_transient(self::ERROR_TRANSIENT);

        return $release;
    }

    private function release_version(array $release): string
    {
        return ltrim((string) ($release['tag_name'] ?? ''), 'vV');
    }

    private function asset_url(array $release): string
    {
        foreach ((array) ($release['assets'] ?? array()) as $asset) {
            if (self::ASSET_NAME === ($asset['name'] ?? '') && !empty($asset['browser_download_url'])) {
                return esc_url_raw((string) $asset['browser_download_url']);
            }
        }

        return '';
    }

    private function repo_url(): string
    {
        return 'https://github.com/' . self::OWNER . '/' . self::REPO;
    }

    private function check_updates_url(): string
    {
        return wp_nonce_url(add_query_arg('tnsp_check_updates', '1', $this->plugins_page_url()), 'tnsp_check_updates');
    }

    private function plugins_page_url(): string
    {
        return is_multisite() ? network_admin_url('plugins.php') : admin_url('plugins.php');
    }

    private function is_forced_check(): bool
    {
        if (!current_user_can('update_plugins')) {
            return false;
        }

        $force = isset($_GET['force-check']) || isset($_POST['force-check']);
        $action = sanitize_key((string) ($_REQUEST['action'] ?? ''));

        return $force || in_array($action, array('update-selected', 'upgrade-plugin', 'do-plugin-upgrade'), true);
    }
}
