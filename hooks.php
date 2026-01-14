<?php
/**
 * WordPress Hooks Registration
 *
 * Central file showing ALL WordPress hooks used by the plugin.
 * This is the routing table - see everything the plugin does at a glance.
 */

namespace ChatStory;

// === ACTIVATION ===
register_activation_hook(CHATSTORY_PLUGIN_FILE, __NAMESPACE__ . '\\create_tables');

// === REST API ===
add_action('rest_api_init', 'ChatStory\\Api\\register_routes');

// === MCP (Model Context Protocol) ===
if (class_exists('WP\\MCP\\Core\\McpAdapter')) {
    add_action('wp_abilities_api_init', 'ChatStory\\Api\\register_mcp_abilities');
}

// === ADMIN ===
add_action('admin_menu', __NAMESPACE__ . '\\register_admin_menu');
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_assets');

// === FRONTEND ===
add_action('wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_frontend_assets');
add_action('template_redirect', __NAMESPACE__ . '\\handle_preview');
add_shortcode('chatstory', __NAMESPACE__ . '\\render_shortcode');
