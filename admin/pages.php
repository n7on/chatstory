<?php
/**
 * Admin Pages
 *
 * Handles admin menu registration and page rendering.
 */

namespace ChatStory\Admin;

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

