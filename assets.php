<?php
/**
 * Asset Management
 *
 * Handles enqueuing of CSS and JavaScript files.
 */

namespace ChatStory;

/**
 * Enqueue admin assets
 */
function enqueue_admin_assets($hook) {
    // Only load on ChatStory admin pages
    if (strpos($hook, 'chatstory') === false) {
        return;
    }

    // Enqueue WordPress media uploader
    wp_enqueue_media();

    // Enqueue admin CSS
    wp_enqueue_style(
        'chatstory-admin',
        plugin_dir_url(CHATSTORY_PLUGIN_FILE) . 'assets/css/admin.css',
        [],
        '1.0.0'
    );

    // Enqueue admin JavaScript
    wp_enqueue_script(
        'chatstory-admin',
        plugin_dir_url(CHATSTORY_PLUGIN_FILE) . 'assets/js/admin.js',
        ['jquery'],
        '1.0.0',
        true
    );

    // Localize script with REST API data
    wp_localize_script('chatstory-admin', 'ChatStoryAjax', [
        'rest_url' => rest_url('chatstory/v1/'),
        'rest_nonce' => wp_create_nonce('wp_rest'),
        'home_url' => home_url('/'),
    ]);
}

/**
 * Enqueue frontend assets
 */
function enqueue_frontend_assets() {
    // Enqueue frontend CSS
    wp_enqueue_style(
        'chatstory-frontend',
        plugin_dir_url(CHATSTORY_PLUGIN_FILE) . 'assets/css/frontend.css',
        [],
        '1.0.0'
    );

    // Enqueue frontend JavaScript
    wp_enqueue_script(
        'chatstory-frontend',
        plugin_dir_url(CHATSTORY_PLUGIN_FILE) . 'assets/js/frontend.js',
        ['jquery'],
        '1.0.0',
        true
    );

    // Localize script with REST API data
    wp_localize_script('chatstory-frontend', 'ChatStoryAjax', [
        'rest_url' => rest_url('chatstory/v1/'),
    ]);
}
