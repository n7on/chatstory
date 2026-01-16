<?php
/**
 * WordPress Hooks Registration
 *
 * Central file showing ALL WordPress hooks used by the plugin.
 * This is the routing table - see everything the plugin does at a glance.
 */

namespace ChatStory\Core;

// === ACTIVATION ===
register_activation_hook(CHATSTORY_PLUGIN_FILE, 'ChatStory\\Core\\create_tables');

// === REST API ===
add_action('rest_api_init', 'ChatStory\\Api\\register_routes');

// === MCP (Model Context Protocol) ===
if (class_exists('WP\\MCP\\Core\\McpAdapter')) {
    add_action('wp_abilities_api_init', 'ChatStory\\Api\\register_mcp_abilities');
}

// === ADMIN ===
add_action('admin_menu', 'ChatStory\\Admin\\register_admin_menu');
add_action('admin_enqueue_scripts', 'ChatStory\\Core\\enqueue_admin_assets');

// === CUSTOM POST TYPE ===
add_action('init', 'ChatStory\\Core\\register_chat_post_type');

// === FRONTEND ===
add_action('wp_enqueue_scripts', 'ChatStory\\Core\\enqueue_frontend_assets');
add_shortcode('chatstory', 'ChatStory\\Frontend\\render_shortcode');
add_shortcode('recent_chats', 'ChatStory\\Frontend\\render_recent_chats_shortcode');
add_filter('the_content', 'ChatStory\\Frontend\\inject_chat_content');
