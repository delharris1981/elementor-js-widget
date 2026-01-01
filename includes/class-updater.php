<?php

declare(strict_types=1);

namespace CustomElementorJSWidget;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * GitHub Update Checker
 * 
 * Handles checking for updates and auto-activation.
 */
class Updater
{

    private string $plugin_file;
    private string $repo;
    private string $slug;

    public function __construct(string $plugin_file, string $repo)
    {
        $this->plugin_file = $plugin_file;
        $this->repo = $repo; // Format: username/repo
        $this->slug = plugin_basename($plugin_file);

        $this->init_hooks();
    }

    private function init_hooks(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
    }

    public function check_update($transient)
    {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = $this->get_remote_info();
        if (!$remote) {
            return $transient;
        }

        $current_version = get_plugin_data($this->plugin_file)['Version'];

        if (version_compare($current_version, $remote->tag_name, '<')) {
            $res = new \stdClass();
            $res->slug = $this->slug;
            $res->plugin = $this->slug;
            $res->new_version = $remote->tag_name;
            $res->package = $remote->zipball_url;
            $res->url = "https://github.com/{$this->repo}";

            $transient->response[$this->slug] = $res;
        }

        return $transient;
    }

    public function plugin_popup($result, $action, $args)
    {
        if ('plugin_information' !== $action || $this->slug !== ($args->slug ?? '')) {
            return $result;
        }

        $remote = $this->get_remote_info();
        if (!$remote) {
            return $result;
        }

        $res = new \stdClass();
        $res->name = 'Custom JS Widget';
        $res->slug = $this->slug;
        $res->version = $remote->tag_name;
        $res->author = '<a href="https://github.com/' . explode('/', $this->repo)[0] . '">Custom Developer</a>';
        $res->homepage = "https://github.com/{$this->repo}";
        $res->download_link = $remote->zipball_url;
        $res->sections = [
            'description' => 'A premium widget for Elementor that allows adding raw JavaScript to pages.',
            'changelog' => $remote->body ?? 'No changelog provided.',
        ];

        return $res;
    }

    public function after_install($response, $hook_extra, $result)
    {
        global $wp_firstname; // Unused but just to be safe

        // Auto-activate plugin after update
        if (isset($hook_extra['plugin']) && $hook_extra['plugin'] === $this->slug) {
            activate_plugin($this->slug);
        }

        return $result;
    }

    private function get_remote_info()
    {
        $url = "https://api.github.com/repos/{$this->repo}/releases/latest";
        $response = wp_remote_get($url, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
            ],
        ]);

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response));
    }
}
