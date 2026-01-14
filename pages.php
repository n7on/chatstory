<?php
/**
 * Admin Pages
 *
 * Handles admin menu registration and page rendering.
 */

namespace ChatStory;

/**
 * Register admin menu
 */
function register_admin_menu() {
    add_menu_page(
        __('ChatStory', 'chatstory'),
        __('ChatStory', 'chatstory'),
        'manage_options',
        'chatstory',
        __NAMESPACE__ . '\\render_chats_page',
        'dashicons-format-chat',
        26
    );

    add_submenu_page(
        'chatstory',
        __('Chats', 'chatstory'),
        __('Chats', 'chatstory'),
        'manage_options',
        'chatstory',
        __NAMESPACE__ . '\\render_chats_page'
    );

    add_submenu_page(
        'chatstory',
        __('Characters', 'chatstory'),
        __('Characters', 'chatstory'),
        'manage_options',
        'chatstory-characters',
        __NAMESPACE__ . '\\render_characters_page'
    );
}

/**
 * Render chats admin page
 */
function render_chats_page() {
    include plugin_dir_path(CHATSTORY_PLUGIN_FILE) . 'views/admin-chats.php';
}

/**
 * Render characters admin page
 */
function render_characters_page() {
    include plugin_dir_path(CHATSTORY_PLUGIN_FILE) . 'views/admin-characters.php';
}

/**
 * Handle preview template redirect
 */
function handle_preview() {
    if (!isset($_GET['chatstory_preview']) || !isset($_GET['chat_id'])) {
        return;
    }

    $chat_id = intval($_GET['chat_id']);
    $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';

    if (!wp_verify_nonce($nonce, 'chatstory_preview_' . $chat_id)) {
        wp_die(__('Invalid preview link', 'chatstory'));
    }

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have permission to preview chats', 'chatstory'));
    }

    // Load the preview template
    include plugin_dir_path(CHATSTORY_PLUGIN_FILE) . 'views/preview-template.php';
    exit();
}
