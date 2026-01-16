<?php
/**
 * Database Schema
 *
 * Defines database tables and handles activation.
 */

namespace ChatStory\Core;

/**
 * Register Custom Post Type for chats
 */
function register_chat_post_type() {
    register_post_type('chatstory', [
        'labels' => [
            'name' => __('Chat Stories', 'chatstory'),
            'singular_name' => __('Chat Story', 'chatstory'),
            'add_new' => __('Add New', 'chatstory'),
            'add_new_item' => __('Add New Chat Story', 'chatstory'),
            'edit_item' => __('Edit Chat Story', 'chatstory'),
            'new_item' => __('New Chat Story', 'chatstory'),
            'view_item' => __('View Chat Story', 'chatstory'),
            'search_items' => __('Search Chat Stories', 'chatstory'),
            'not_found' => __('No chat stories found', 'chatstory'),
            'not_found_in_trash' => __('No chat stories found in trash', 'chatstory'),
        ],
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => false, // Hide from admin UI for now (use custom admin)
        'show_in_menu' => false,
        'show_in_rest' => true,
        'rest_base' => 'chats',
        'has_archive' => true,
        'rewrite' => [
            'slug' => 'chat',
            'with_front' => false,
        ],
        'capability_type' => 'post',
        'supports' => ['title', 'editor', 'custom-fields', 'comments'],
        'taxonomies' => [],
    ]);
}

/**
 * Create database tables on plugin activation
 */
function create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_characters = $wpdb->prefix . 'chatstory_characters';
    $table_events = $wpdb->prefix . 'chatstory_events';

    $sql_characters = "CREATE TABLE IF NOT EXISTS {$table_characters} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        slug varchar(255) NOT NULL,
        avatar varchar(255) DEFAULT '',
        role varchar(255) DEFAULT '',
        character_traits text DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";

    $sql_events = "CREATE TABLE IF NOT EXISTS {$table_events} (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id bigint(20) UNSIGNED NOT NULL,
        character_id bigint(20) UNSIGNED NULL,
        event_type varchar(50) NOT NULL DEFAULT 'message',
        start_time decimal(10,2) NOT NULL DEFAULT 0,
        event_data text DEFAULT NULL,
        target_event_id bigint(20) UNSIGNED NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY character_id (character_id),
        KEY event_type (event_type),
        KEY start_time (start_time)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_characters);
    dbDelta($sql_events);

    // Flush rewrite rules to activate custom post type
    flush_rewrite_rules();
}
