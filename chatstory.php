<?php
/**
 * Plugin Name: ChatStory
 * Description: Tell your company story through recorded team chat conversations. Create characters and chat messages to showcase your team dynamics.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: chatstory
 */

if (!defined("ABSPATH")) {
    exit();
}

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Initialize MCP Adapter if available
if (class_exists('WP\MCP\Core\McpAdapter')) {
    add_action('plugins_loaded', function() {
        WP\MCP\Core\McpAdapter::instance();
    }, 5);
}

class ChatStory
{
    private static $instance = null;
    private $table_characters;
    private $table_chats;
    private $table_messages;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        global $wpdb;
        $this->table_characters = $wpdb->prefix . "chatstory_characters";
        $this->table_chats = $wpdb->prefix . "chatstory_chats";
        $this->table_messages = $wpdb->prefix . "chatstory_events"; // Renamed to events
        register_activation_hook(__FILE__, [$this, "activate"]);
        add_action("plugins_loaded", [$this, "load_textdomain"]);
        add_action("admin_menu", [$this, "add_admin_menu"]);
        add_action("admin_enqueue_scripts", [$this, "admin_assets"]);
        add_action("wp_enqueue_scripts", [$this, "frontend_assets"]);
        add_action("template_redirect", [$this, "handle_preview"]);

        add_shortcode("chatstory", [$this, "render_chat"]);

        // Initialize REST API
        require_once plugin_dir_path(__FILE__) . 'includes/class-chatstory-rest-api.php';
        new ChatStory_REST_API();

        // Load MCP abilities if MCP adapter is available
        if (class_exists('WP\MCP\Core\McpAdapter')) {
            $this->load_mcp_abilities();
        }
    }
    /**
     * Plugin activation - creates database tables
     */
    public function activate()
    {
        $this->create_tables();
    }

    private function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql_characters = "CREATE TABLE IF NOT EXISTS {$this->table_characters} (
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

        $sql_chats = "CREATE TABLE IF NOT EXISTS {$this->table_chats} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_events = "CREATE TABLE IF NOT EXISTS {$this->table_messages} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            chat_id bigint(20) UNSIGNED NOT NULL,
            character_id bigint(20) UNSIGNED NULL,
            event_type varchar(50) NOT NULL DEFAULT 'message',
            start_time decimal(10,2) NOT NULL DEFAULT 0,
            event_data text DEFAULT NULL,
            target_event_id bigint(20) UNSIGNED NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY chat_id (chat_id),
            KEY character_id (character_id),
            KEY event_type (event_type),
            KEY start_time (start_time)
        ) $charset_collate;";

        require_once ABSPATH . "wp-admin/includes/upgrade.php";
        dbDelta($sql_characters);
        dbDelta($sql_chats);
        dbDelta($sql_events);
    }

    private function generate_unique_slug($name, $exclude_id = 0)
    {
        global $wpdb;

        // Generate base slug from name
        $slug = sanitize_title($name);

        // Check if slug exists
        $suffix = 0;
        $original_slug = $slug;

        while (true) {
            $check_slug = $suffix > 0 ? $original_slug . "-" . $suffix : $slug;

            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$this->table_characters} WHERE slug = %s AND id != %d",
                    $check_slug,
                    $exclude_id,
                ),
            );

            if (!$exists) {
                return $check_slug;
            }

            $suffix++;
        }
    }

    public function load_textdomain()
    {
        load_plugin_textdomain(
            "chatstory",
            false,
            dirname(plugin_basename(__FILE__)) . "/languages",
        );
    }

    public function add_admin_menu()
    {
        add_menu_page(
            __("ChatStory", "chatstory"),
            __("ChatStory", "chatstory"),
            "manage_options",
            "chatstory",
            [$this, "admin_page_chats"],
            "dashicons-format-chat",
            26,
        );

        add_submenu_page(
            "chatstory",
            __("Chats", "chatstory"),
            __("Chats", "chatstory"),
            "manage_options",
            "chatstory",
            [$this, "admin_page_chats"],
        );

        add_submenu_page(
            "chatstory",
            __("Characters", "chatstory"),
            __("Characters", "chatstory"),
            "manage_options",
            "chatstory-characters",
            [$this, "admin_page_characters"],
        );
    }

    public function admin_page_chats()
    {
        include plugin_dir_path(__FILE__) . "views/admin-chats.php";
    }

    public function admin_page_characters()
    {
        include plugin_dir_path(__FILE__) . "views/admin-characters.php";
    }

    public function admin_assets($hook)
    {
        if (strpos($hook, "chatstory") === false) {
            return;
        }

        // Enqueue WordPress media uploader
        wp_enqueue_media();

        wp_enqueue_style(
            "chatstory-admin",
            plugin_dir_url(__FILE__) . "assets/css/admin.css",
            [],
            "1.0.0",
        );
        wp_enqueue_script(
            "chatstory-admin",
            plugin_dir_url(__FILE__) . "assets/js/admin.js",
            ["jquery"],
            "1.0.0",
            true,
        );
        wp_localize_script("chatstory-admin", "ChatStoryAjax", [
            "rest_url" => rest_url("chatstory/v1/"),
            "rest_nonce" => wp_create_nonce("wp_rest"),
            "home_url" => home_url("/"),
        ]);
    }

    public function frontend_assets()
    {
        wp_enqueue_style(
            "chatstory-frontend",
            plugin_dir_url(__FILE__) . "assets/css/frontend.css",
            [],
            "1.0.0",
        );
        wp_enqueue_script(
            "chatstory-frontend",
            plugin_dir_url(__FILE__) . "assets/js/frontend.js",
            ["jquery"],
            "1.0.0",
            true,
        );
        wp_localize_script("chatstory-frontend", "ChatStoryAjax", [
            "rest_url" => rest_url("chatstory/v1/"),
        ]);
    }

    public function render_chat($atts)
    {
        $atts = shortcode_atts(["id" => 0], $atts);
        $chat_id = intval($atts["id"]);

        if ($chat_id === 0) {
            return "<p>" . __("No chat ID specified", "chatstory") . "</p>";
        }

        ob_start();
        include plugin_dir_path(__FILE__) . "views/frontend-chat.php";
        return ob_get_clean();
    }

    public function handle_preview()
    {
        if (!isset($_GET["chatstory_preview"]) || !isset($_GET["chat_id"])) {
            return;
        }

        $chat_id = intval($_GET["chat_id"]);
        $nonce = isset($_GET["_wpnonce"]) ? $_GET["_wpnonce"] : "";

        if (!wp_verify_nonce($nonce, "chatstory_preview_" . $chat_id)) {
            wp_die(__("Invalid preview link", "chatstory"));
        }

        if (!current_user_can("manage_options")) {
            wp_die(
                __("You do not have permission to preview chats", "chatstory"),
            );
        }

        // Load the preview template
        include plugin_dir_path(__FILE__) . "views/preview-template.php";
        exit();
    }

    public function get_preview_url($chat_id)
    {
        return add_query_arg(
            [
                "chatstory_preview" => "1",
                "chat_id" => $chat_id,
                "_wpnonce" => wp_create_nonce("chatstory_preview_" . $chat_id),
            ],
            home_url("/"),
        );
    }

    /**
     * Load MCP abilities for AI integration
     */
    private function load_mcp_abilities()
    {
        require_once plugin_dir_path(__FILE__) . 'includes/mcp-abilities-characters.php';
        require_once plugin_dir_path(__FILE__) . 'includes/mcp-abilities-chats.php';
        require_once plugin_dir_path(__FILE__) . 'includes/mcp-abilities-messages.php';
    }
}

ChatStory::instance();
