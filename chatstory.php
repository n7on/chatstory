<?php
/**
 * Plugin Name: ChatStory
 * Description: Tell your company story through recorded team chat conversations. Create characters and chat messages to showcase your team dynamics.
 * Version: 1.0.0
 * Author: Anton Lindström
 * Text Domain: chatstory
 *
 * ARCHITECTURE: This plugin uses a functional, layered architecture with namespaces.
 * Read docs/ARCHITECTURE.md for complete documentation.
 *
 * Quick Overview:
 * - core/        = Plugin foundation (hooks, database, assets)
 * - admin/       = Admin UI integration (pages)
 * - frontend/    = Public-facing integration (shortcodes, permalinks)
 * - data/        = Business logic (CRUD functions, NO WordPress coupling)
 * - api/         = External interfaces (REST API, MCP abilities)
 * - views/       = Templates only (presentation)
 * - assets/      = Static files (CSS, JS)
 *
 * Key Rule: api/ and root files call data/ functions. data/ NEVER calls WordPress hooks.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CHATSTORY_PLUGIN_FILE', __FILE__);
define('CHATSTORY_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Load Composer dependencies
require_once CHATSTORY_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize MCP Adapter if available
if (class_exists('WP\\MCP\\Core\\McpAdapter')) {
    add_action('plugins_loaded', function() {
        WP\MCP\Core\McpAdapter::instance();
    }, 5);
}

// Load core components
require_once CHATSTORY_PLUGIN_DIR . 'core/database.php';
require_once CHATSTORY_PLUGIN_DIR . 'core/assets.php';

// Load admin components
require_once CHATSTORY_PLUGIN_DIR . 'admin/pages.php';

// Load frontend components
require_once CHATSTORY_PLUGIN_DIR . 'frontend/shortcodes.php';
require_once CHATSTORY_PLUGIN_DIR . 'frontend/permalinks.php';

// Load data layer
require_once CHATSTORY_PLUGIN_DIR . 'data/characters.php';
require_once CHATSTORY_PLUGIN_DIR . 'data/chats.php';
require_once CHATSTORY_PLUGIN_DIR . 'data/messages.php';

// Load API layer
require_once CHATSTORY_PLUGIN_DIR . 'api/rest-api.php';

// Load MCP abilities if MCP adapter is available
if (class_exists('WP\\MCP\\Core\\McpAdapter')) {
    require_once CHATSTORY_PLUGIN_DIR . 'api/mcp-abilities.php';
}

// Register all WordPress hooks
require_once CHATSTORY_PLUGIN_DIR . 'core/hooks.php';

// Load text domain for translations
add_action('plugins_loaded', function() {
    load_plugin_textdomain(
        'chatstory',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
});
