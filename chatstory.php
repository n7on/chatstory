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

        // AJAX handlers for characters
        add_action("wp_ajax_chatstory_save_character", [
            $this,
            "ajax_save_character",
        ]);
        add_action("wp_ajax_chatstory_delete_character", [
            $this,
            "ajax_delete_character",
        ]);
        add_action("wp_ajax_chatstory_get_characters", [
            $this,
            "ajax_get_characters",
        ]);
        add_action("wp_ajax_chatstory_import_characters", [
            $this,
            "ajax_import_characters",
        ]);

        // AJAX handlers for chats
        add_action("wp_ajax_chatstory_save_chat", [$this, "ajax_save_chat"]);
        add_action("wp_ajax_chatstory_delete_chat", [
            $this,
            "ajax_delete_chat",
        ]);
        add_action("wp_ajax_chatstory_get_chats", [$this, "ajax_get_chats"]);
        add_action("wp_ajax_chatstory_get_chat", [$this, "ajax_get_chat"]);

        // AJAX handlers for messages
        add_action("wp_ajax_chatstory_save_message", [
            $this,
            "ajax_save_message",
        ]);
        add_action("wp_ajax_chatstory_delete_message", [
            $this,
            "ajax_delete_message",
        ]);

        // AJAX handlers for reactions
        add_action("wp_ajax_chatstory_save_reaction", [
            $this,
            "ajax_save_reaction",
        ]);
        add_action("wp_ajax_chatstory_delete_reaction", [
            $this,
            "ajax_delete_reaction",
        ]);

        // AJAX handlers for typing events
        add_action("wp_ajax_chatstory_save_typing", [
            $this,
            "ajax_save_typing",
        ]);
        add_action("wp_ajax_chatstory_delete_typing", [
            $this,
            "ajax_delete_typing",
        ]);

        // AJAX handlers for presence events
        add_action("wp_ajax_chatstory_save_presence", [
            $this,
            "ajax_save_presence",
        ]);
        add_action("wp_ajax_chatstory_delete_presence", [
            $this,
            "ajax_delete_presence",
        ]);

        add_action("wp_ajax_chatstory_import_json", [
            $this,
            "ajax_import_json",
        ]);
        add_action("wp_ajax_chatstory_get_preview_url", [
            $this,
            "ajax_get_preview_url",
        ]);

        // Frontend AJAX
        add_action("wp_ajax_chatstory_get_chat_frontend", [
            $this,
            "ajax_get_chat_frontend",
        ]);
        add_action("wp_ajax_nopriv_chatstory_get_chat_frontend", [
            $this,
            "ajax_get_chat_frontend",
        ]);

        add_shortcode("chatstory", [$this, "render_chat"]);
    }
    /**
     * @return void
     */
    public function activate()
    {
        $this->create_tables();
        $this->upgrade_database();
    }

    public function upgrade_database()
    {
        global $wpdb;

        // Check if old messages table exists
        $old_table = $wpdb->prefix . "chatstory_messages";
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$old_table}'");

        if ($table_exists) {
            // Drop old messages table
            $wpdb->query("DROP TABLE IF EXISTS {$old_table}");
            error_log(
                "ChatStory: Dropped old messages table, migrating to events",
            );
        }

        // Check if old personalities table exists and rename to characters
        $old_personalities_table = $wpdb->prefix . "chatstory_personalities";
        $personalities_table_exists = $wpdb->get_var(
            "SHOW TABLES LIKE '{$old_personalities_table}'",
        );

        if ($personalities_table_exists) {
            // Rename table from personalities to characters
            $wpdb->query(
                "RENAME TABLE {$old_personalities_table} TO {$this->table_characters}",
            );
            error_log(
                "ChatStory: Renamed personalities table to characters table",
            );

            // Check if personality_traits column exists and rename it
            $column_exists = $wpdb->get_results(
                "SHOW COLUMNS FROM {$this->table_characters} LIKE 'personality_traits'",
            );

            if (!empty($column_exists)) {
                $wpdb->query(
                    "ALTER TABLE {$this->table_characters}
                    CHANGE COLUMN personality_traits character_traits text DEFAULT ''",
                );
                error_log(
                    "ChatStory: Renamed personality_traits column to character_traits",
                );
            }
        }

        // Check if characters table exists and add slug column if missing
        $characters_table_exists = $wpdb->get_var(
            "SHOW TABLES LIKE '{$this->table_characters}'",
        );

        if ($characters_table_exists) {
            $slug_column_exists = $wpdb->get_results(
                "SHOW COLUMNS FROM {$this->table_characters} LIKE 'slug'",
            );

            if (empty($slug_column_exists)) {
                // Add slug column
                $wpdb->query(
                    "ALTER TABLE {$this->table_characters}
                    ADD COLUMN slug varchar(255) NOT NULL DEFAULT '' AFTER name",
                );

                // Generate slugs for existing characters
                $characters = $wpdb->get_results(
                    "SELECT id, name FROM {$this->table_characters}",
                );

                foreach ($characters as $character) {
                    $slug = $this->generate_unique_slug(
                        $character->name,
                        $character->id,
                    );
                    $wpdb->update(
                        $this->table_characters,
                        ["slug" => $slug],
                        ["id" => $character->id],
                    );
                }

                // Add unique index after populating slugs
                $wpdb->query(
                    "ALTER TABLE {$this->table_characters}
                    ADD UNIQUE KEY slug (slug)",
                );

                error_log("ChatStory: Added slug column to characters table");
            }
        }

        // Check if events table has personality_id column and rename to character_id
        $events_table_exists = $wpdb->get_var(
            "SHOW TABLES LIKE '{$this->table_messages}'",
        );

        if ($events_table_exists) {
            $column_exists = $wpdb->get_results(
                "SHOW COLUMNS FROM {$this->table_messages} LIKE 'personality_id'",
            );

            if (!empty($column_exists)) {
                $wpdb->query(
                    "ALTER TABLE {$this->table_messages}
                    CHANGE COLUMN personality_id character_id bigint(20) UNSIGNED NULL",
                );
                error_log(
                    "ChatStory: Renamed personality_id column to character_id in events table",
                );
            }
        } else {
            // Create tables will handle this
            $this->create_tables();
            error_log("ChatStory: Created new events table");
        }
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
            plugin_dir_url(__FILE__) . "includes/chatstory-admin.css",
            [],
            "1.0.0",
        );
        wp_enqueue_script(
            "chatstory-admin",
            plugin_dir_url(__FILE__) . "includes/chatstory-admin.js",
            ["jquery"],
            "1.0.0",
            true,
        );
        wp_localize_script("chatstory-admin", "ChatStoryAjax", [
            "ajax_url" => admin_url("admin-ajax.php"),
            "nonce" => wp_create_nonce("chatstory_nonce"),
            "home_url" => home_url("/"),
        ]);
    }

    public function frontend_assets()
    {
        wp_enqueue_style(
            "chatstory-frontend",
            plugin_dir_url(__FILE__) . "includes/chatstory-frontend.css",
            [],
            "1.0.0",
        );
        wp_enqueue_script(
            "chatstory-frontend",
            plugin_dir_url(__FILE__) . "includes/chatstory-frontend.js",
            ["jquery"],
            "1.0.0",
            true,
        );
        wp_localize_script("chatstory-frontend", "ChatStoryAjax", [
            "ajax_url" => admin_url("admin-ajax.php"),
        ]);
    }

    // Character AJAX handlers
    public function ajax_save_character()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
        $name = isset($_POST["name"])
            ? sanitize_text_field($_POST["name"])
            : "";
        $slug = isset($_POST["slug"])
            ? sanitize_title($_POST["slug"])
            : "";
        $avatar = isset($_POST["avatar"])
            ? sanitize_text_field($_POST["avatar"])
            : "";
        $role = isset($_POST["role"])
            ? sanitize_text_field($_POST["role"])
            : "";
        $character_traits = isset($_POST["character_traits"])
            ? sanitize_textarea_field($_POST["character_traits"])
            : "";

        if (empty($name)) {
            wp_send_json_error(["message" => "Name is required"]);
        }

        // Auto-generate slug from name if not provided
        if (empty($slug)) {
            $slug = $this->generate_unique_slug($name, $id);
        } else {
            // Ensure custom slug is unique
            $slug = $this->generate_unique_slug($slug, $id);
        }

        $data = [
            "name" => $name,
            "slug" => $slug,
            "avatar" => $avatar,
            "role" => $role,
            "character_traits" => $character_traits,
        ];

        if ($id > 0) {
            $wpdb->update($this->table_characters, $data, ["id" => $id]);
        } else {
            $wpdb->insert($this->table_characters, $data);
            $id = $wpdb->insert_id;
        }

        wp_send_json_success(["id" => $id, "slug" => $slug]);
    }

    public function ajax_delete_character()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

        if ($id === 0) {
            wp_send_json_error(["message" => "Invalid ID"]);
        }

        $wpdb->delete($this->table_characters, ["id" => $id]);
        wp_send_json_success();
    }

    public function ajax_get_characters()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        global $wpdb;
        $characters = $wpdb->get_results(
            "SELECT * FROM {$this->table_characters} ORDER BY name ASC",
        );
        wp_send_json_success($characters);
    }

    public function ajax_import_characters()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        $json = isset($_POST["json"]) ? stripslashes($_POST["json"]) : "";
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(["message" => "Invalid JSON"]);
        }

        if (!is_array($data)) {
            wp_send_json_error([
                "message" => "JSON must be an array of characters",
            ]);
        }

        global $wpdb;
        $imported = 0;
        $skipped = 0;

        foreach ($data as $character) {
            $name = sanitize_text_field($character["name"] ?? "");
            $slug = isset($character["slug"])
                ? sanitize_title($character["slug"])
                : "";
            $avatar = sanitize_text_field($character["avatar"] ?? "");
            $role = sanitize_text_field($character["role"] ?? "");
            $character_traits = sanitize_textarea_field(
                $character["character_traits"] ?? "",
            );

            if (empty($name)) {
                $skipped++;
                continue;
            }

            // Auto-generate slug if not provided
            if (empty($slug)) {
                $slug = $this->generate_unique_slug($name, 0);
            } else {
                $slug = $this->generate_unique_slug($slug, 0);
            }

            $wpdb->insert($this->table_characters, [
                "name" => $name,
                "slug" => $slug,
                "avatar" => $avatar,
                "role" => $role,
                "character_traits" => $character_traits,
            ]);

            $imported++;
        }

        wp_send_json_success([
            "imported" => $imported,
            "skipped" => $skipped,
        ]);
    }

    // Chat AJAX handlers
    public function ajax_save_chat()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
        $title = isset($_POST["title"])
            ? sanitize_text_field($_POST["title"])
            : "";
        $description = isset($_POST["description"])
            ? sanitize_textarea_field($_POST["description"])
            : "";

        if (empty($title)) {
            wp_send_json_error(["message" => "Title is required"]);
        }

        $data = [
            "title" => $title,
            "description" => $description,
        ];

        if ($id > 0) {
            $wpdb->update($this->table_chats, $data, ["id" => $id]);
        } else {
            $wpdb->insert($this->table_chats, $data);
            $id = $wpdb->insert_id;
        }

        wp_send_json_success(["id" => $id]);
    }

    public function ajax_delete_chat()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

        if ($id === 0) {
            wp_send_json_error(["message" => "Invalid ID"]);
        }

        $wpdb->delete($this->table_chats, ["id" => $id]);
        $wpdb->delete($this->table_messages, ["chat_id" => $id]);
        wp_send_json_success();
    }

    public function ajax_get_chats()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        global $wpdb;
        $chats = $wpdb->get_results(
            "SELECT * FROM {$this->table_chats} ORDER BY created_at DESC",
        );
        wp_send_json_success($chats);
    }

    public function ajax_get_chat()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

        if ($id === 0) {
            wp_send_json_error(["message" => "Invalid ID"]);
        }

        $chat = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_chats} WHERE id = %d",
                $id,
            ),
        );

        // Get events and decode event_data JSON
        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                        e.event_data, e.created_at,
                        p.name, p.avatar, p.role
                FROM {$this->table_messages} e
                LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
                WHERE e.chat_id = %d AND e.event_type = 'message'
                ORDER BY e.start_time ASC",
                $id,
            ),
        );

        // Get reaction events
        $reactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                        e.event_data, e.target_event_id, e.created_at,
                        p.name, p.avatar, p.role
                FROM {$this->table_messages} e
                LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
                WHERE e.chat_id = %d AND e.event_type = 'reaction'
                ORDER BY e.start_time ASC",
                $id,
            ),
        );

        // Get typing events
        $typing_events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                        e.event_data, e.target_event_id, e.created_at,
                        p.name, p.avatar, p.role
                FROM {$this->table_messages} e
                LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
                WHERE e.chat_id = %d AND e.event_type = 'typing'
                ORDER BY e.start_time ASC",
                $id,
            ),
        );

        // Get presence events (join/leave)
        $presence_events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                        e.event_data, e.created_at,
                        p.name, p.avatar, p.role
                FROM {$this->table_messages} e
                LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
                WHERE e.chat_id = %d AND e.event_type = 'presence'
                ORDER BY e.start_time ASC",
                $id,
            ),
        );

        // Transform events into message format for admin UI
        $messages = array_map(function ($event) {
            $data = json_decode($event->event_data, true);
            return (object) [
                "id" => $event->id,
                "chat_id" => $event->chat_id,
                "character_id" => $event->character_id,
                "message" => $data["text"] ?? "",
                "timestamp" => $data["timestamp"] ?? "",
                "start_time" => $event->start_time,
                "name" => $event->name,
                "avatar" => $event->avatar,
                "role" => $event->role,
                "created_at" => $event->created_at,
                "reactions" => [],
                "typing_event" => null,
            ];
        }, $events);

        // Transform reactions and attach to messages
        $reaction_objects = array_map(function ($reaction) {
            $data = json_decode($reaction->event_data, true);
            return (object) [
                "id" => $reaction->id,
                "target_event_id" => $reaction->target_event_id,
                "character_id" => $reaction->character_id,
                "reaction" => $data["reaction"] ?? "👍",
                "start_time" => $reaction->start_time,
                "name" => $reaction->name,
            ];
        }, $reactions);

        // Transform typing events
        $typing_objects = array_map(function ($typing) {
            $data = json_decode($typing->event_data, true);
            return (object) [
                "id" => $typing->id,
                "target_event_id" => $typing->target_event_id,
                "character_id" => $typing->character_id,
                "duration" => $data["duration"] ?? 3,
                "start_time" => $typing->start_time,
                "name" => $typing->name,
            ];
        }, $typing_events);

        // Attach reactions to their messages
        foreach ($messages as &$message) {
            foreach ($reaction_objects as $reaction) {
                if ($reaction->target_event_id == $message->id) {
                    $message->reactions[] = $reaction;
                }
            }
        }

        // Attach typing events to their messages
        foreach ($messages as &$message) {
            foreach ($typing_objects as $typing) {
                if ($typing->target_event_id == $message->id) {
                    $message->typing_event = $typing;
                    break; // Only one typing event per message
                }
            }
        }

        // Transform presence events (join/leave)
        $presence_objects = array_map(function ($presence) {
            $data = json_decode($presence->event_data, true);
            return (object) [
                "id" => $presence->id,
                "character_id" => $presence->character_id,
                "action" => $data["action"] ?? "join",
                "start_time" => $presence->start_time,
                "name" => $presence->name,
                "avatar" => $presence->avatar,
                "role" => $presence->role,
            ];
        }, $presence_events);

        wp_send_json_success([
            "chat" => $chat,
            "messages" => $messages,
            "presence_events" => $presence_objects,
        ]);
    }

    // Event AJAX handlers
    public function ajax_save_message()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
        $chat_id = isset($_POST["chat_id"]) ? intval($_POST["chat_id"]) : 0;
        $character_id = isset($_POST["character_id"])
            ? intval($_POST["character_id"])
            : 0;
        $message = isset($_POST["message"])
            ? sanitize_textarea_field($_POST["message"])
            : "";
        $start_time = isset($_POST["start_time"])
            ? floatval($_POST["start_time"])
            : 0;
        $timestamp = isset($_POST["timestamp"])
            ? sanitize_text_field($_POST["timestamp"])
            : "";

        if ($chat_id === 0 || $character_id === 0 || empty($message)) {
            wp_send_json_error(["message" => "Missing required fields"]);
        }

        // Prepare event data as JSON
        $event_data = json_encode([
            "text" => $message,
            "timestamp" => $timestamp,
        ]);

        $data = [
            "chat_id" => $chat_id,
            "character_id" => $character_id,
            "event_type" => "message",
            "start_time" => $start_time,
            "event_data" => $event_data,
        ];

        if ($id > 0) {
            $wpdb->update($this->table_messages, $data, ["id" => $id]);

            // Recalculate typing event start_time if it exists
            $typing_event = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, event_data FROM {$this->table_messages}
                WHERE event_type = 'typing' AND target_event_id = %d",
                    $id,
                ),
            );

            if ($typing_event) {
                $typing_data = json_decode($typing_event->event_data, true);
                $duration = $typing_data["duration"] ?? 3;
                $typing_start = max(0, $start_time - $duration);

                $wpdb->update(
                    $this->table_messages,
                    [
                        "start_time" => $typing_start,
                    ],
                    ["id" => $typing_event->id],
                );
            }
        } else {
            $wpdb->insert($this->table_messages, $data);
            $id = $wpdb->insert_id;
        }

        wp_send_json_success(["id" => $id]);
    }

    public function ajax_delete_message()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

        if ($id === 0) {
            wp_send_json_error(["message" => "Invalid ID"]);
        }

        // Delete the message
        $wpdb->delete($this->table_messages, ["id" => $id]);

        // Also delete any associated typing events and reactions
        $wpdb->delete($this->table_messages, ["target_event_id" => $id]);

        wp_send_json_success();
    }

    public function ajax_save_reaction()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
        $chat_id = isset($_POST["chat_id"]) ? intval($_POST["chat_id"]) : 0;
        $character_id = isset($_POST["character_id"])
            ? intval($_POST["character_id"])
            : 0;
        $target_event_id = isset($_POST["target_event_id"])
            ? intval($_POST["target_event_id"])
            : 0;
        $reaction_type = isset($_POST["reaction_type"])
            ? sanitize_text_field($_POST["reaction_type"])
            : "";
        $start_time = isset($_POST["start_time"])
            ? floatval($_POST["start_time"])
            : 0;

        if (
            $chat_id === 0 ||
            $character_id === 0 ||
            $target_event_id === 0 ||
            empty($reaction_type)
        ) {
            wp_send_json_error(["message" => "Missing required fields"]);
        }

        // Prepare event data as JSON
        $event_data = json_encode([
            "reaction" => $reaction_type,
        ]);

        $data = [
            "chat_id" => $chat_id,
            "character_id" => $character_id,
            "event_type" => "reaction",
            "start_time" => $start_time,
            "event_data" => $event_data,
            "target_event_id" => $target_event_id,
        ];

        if ($id > 0) {
            $wpdb->update($this->table_messages, $data, ["id" => $id]);
        } else {
            $wpdb->insert($this->table_messages, $data);
            $id = $wpdb->insert_id;
        }

        wp_send_json_success(["id" => $id]);
    }

    public function ajax_delete_reaction()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

        if ($id === 0) {
            wp_send_json_error(["message" => "Invalid ID"]);
        }

        // Delete the reaction event
        $wpdb->delete($this->table_messages, ["id" => $id]);
        wp_send_json_success();
    }

    public function ajax_save_typing()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
        $chat_id = isset($_POST["chat_id"]) ? intval($_POST["chat_id"]) : 0;
        $character_id = isset($_POST["character_id"])
            ? intval($_POST["character_id"])
            : 0;
        $target_event_id = isset($_POST["target_event_id"])
            ? intval($_POST["target_event_id"])
            : 0;
        $duration = isset($_POST["duration"])
            ? floatval($_POST["duration"])
            : 3;
        $start_time = isset($_POST["start_time"])
            ? floatval($_POST["start_time"])
            : 0;

        if ($chat_id === 0 || $character_id === 0 || $target_event_id === 0) {
            wp_send_json_error(["message" => "Missing required fields"]);
        }

        // Prepare event data as JSON
        $event_data = json_encode([
            "duration" => $duration,
        ]);

        $data = [
            "chat_id" => $chat_id,
            "character_id" => $character_id,
            "event_type" => "typing",
            "start_time" => $start_time,
            "event_data" => $event_data,
            "target_event_id" => $target_event_id,
        ];

        if ($id > 0) {
            $wpdb->update($this->table_messages, $data, ["id" => $id]);
        } else {
            $wpdb->insert($this->table_messages, $data);
            $id = $wpdb->insert_id;
        }

        wp_send_json_success(["id" => $id]);
    }

    public function ajax_delete_typing()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

        if ($id === 0) {
            wp_send_json_error(["message" => "Invalid ID"]);
        }

        // Delete the typing event
        $wpdb->delete($this->table_messages, ["id" => $id]);
        wp_send_json_success();
    }

    public function ajax_save_presence()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
        $chat_id = isset($_POST["chat_id"]) ? intval($_POST["chat_id"]) : 0;
        $character_id = isset($_POST["character_id"])
            ? intval($_POST["character_id"])
            : 0;
        $presence_action = isset($_POST["presence_action"])
            ? sanitize_text_field($_POST["presence_action"])
            : "join";
        $start_time = isset($_POST["start_time"])
            ? floatval($_POST["start_time"])
            : 0;

        if ($chat_id === 0 || $character_id === 0) {
            wp_send_json_error(["message" => "Missing required fields"]);
        }

        // Prepare event data as JSON
        $event_data = json_encode([
            "action" => $presence_action,
        ]);

        $data = [
            "chat_id" => $chat_id,
            "character_id" => $character_id,
            "event_type" => "presence",
            "start_time" => $start_time,
            "event_data" => $event_data,
        ];

        if ($id > 0) {
            $wpdb->update($this->table_messages, $data, ["id" => $id]);
        } else {
            $wpdb->insert($this->table_messages, $data);
            $id = $wpdb->insert_id;
        }

        wp_send_json_success(["id" => $id]);
    }

    public function ajax_delete_presence()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        global $wpdb;
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

        if ($id === 0) {
            wp_send_json_error(["message" => "Invalid ID"]);
        }

        // Delete the presence event
        $wpdb->delete($this->table_messages, ["id" => $id]);
        wp_send_json_success();
    }

    public function ajax_import_json()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        $json = isset($_POST["json"]) ? stripslashes($_POST["json"]) : "";
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(["message" => "Invalid JSON"]);
        }

        global $wpdb;

        // Import chat with events (messages and reactions)
        if (isset($data["chat"])) {
            $chat_data = [
                "title" => sanitize_text_field($data["chat"]["title"] ?? ""),
                "description" => sanitize_textarea_field(
                    $data["chat"]["description"] ?? "",
                ),
            ];
            $wpdb->insert($this->table_chats, $chat_data);
            $chat_id = $wpdb->insert_id;

            // Import message events
            if (isset($data["messages"]) && is_array($data["messages"])) {
                foreach ($data["messages"] as $index => $message) {
                    // Look up character by slug
                    $character_slug = sanitize_title(
                        $message["character"] ?? "",
                    );
                    $character = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT id FROM {$this->table_characters} WHERE slug = %s",
                            $character_slug,
                        ),
                    );

                    if (!$character) {
                        // Skip message if character not found
                        continue;
                    }

                    $character_id = $character->id;

                    $event_data = json_encode([
                        "text" => sanitize_textarea_field(
                            $message["message"] ?? "",
                        ),
                        "timestamp" => sanitize_text_field(
                            $message["timestamp"] ?? "",
                        ),
                    ]);

                    $wpdb->insert($this->table_messages, [
                        "chat_id" => $chat_id,
                        "character_id" => $character_id,
                        "event_type" => "message",
                        "start_time" => floatval(
                            $message["start_time"] ?? $index * 2,
                        ),
                        "event_data" => $event_data,
                    ]);

                    $message_db_id = $wpdb->insert_id;

                    // Auto-create typing event 3 seconds before message (unless disabled)
                    $typing_start = max(
                        0,
                        floatval($message["start_time"] ?? 0) - 3,
                    );
                    $typing_data = json_encode(["duration" => 3]);

                    $wpdb->insert($this->table_messages, [
                        "chat_id" => $chat_id,
                        "character_id" => $character_id,
                        "event_type" => "typing",
                        "start_time" => $typing_start,
                        "event_data" => $typing_data,
                        "target_event_id" => $message_db_id,
                    ]);

                    // Import reactions nested within this message
                    if (
                        isset($message["reactions"]) &&
                        is_array($message["reactions"])
                    ) {
                        foreach ($message["reactions"] as $reaction) {
                            // Look up character by slug for reaction
                            $reaction_character_slug = sanitize_title(
                                $reaction["character"] ?? "",
                            );
                            $reaction_character = $wpdb->get_row(
                                $wpdb->prepare(
                                    "SELECT id FROM {$this->table_characters} WHERE slug = %s",
                                    $reaction_character_slug,
                                ),
                            );

                            if (!$reaction_character) {
                                // Skip reaction if character not found
                                continue;
                            }

                            $reaction_data = json_encode([
                                "reaction" => sanitize_text_field(
                                    $reaction["reaction"] ?? "👍",
                                ),
                            ]);

                            $wpdb->insert($this->table_messages, [
                                "chat_id" => $chat_id,
                                "character_id" => $reaction_character->id,
                                "event_type" => "reaction",
                                "start_time" => floatval(
                                    $reaction["start_time"] ?? 0,
                                ),
                                "event_data" => $reaction_data,
                                "target_event_id" => $message_db_id,
                            ]);
                        }
                    }
                }
            }

            wp_send_json_success(["chat_id" => $chat_id]);
        }

        wp_send_json_error(["message" => "Invalid data structure"]);
    }

    public function ajax_get_chat_frontend()
    {
        $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

        if ($id === 0) {
            wp_send_json_error(["message" => "Invalid ID"]);
        }

        global $wpdb;
        $chat = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_chats} WHERE id = %d",
                $id,
            ),
        );

        // Get message events
        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                        e.event_data, e.created_at,
                        p.name, p.avatar, p.role
                FROM {$this->table_messages} e
                LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
                WHERE e.chat_id = %d AND e.event_type = 'message'
                ORDER BY e.start_time ASC",
                $id,
            ),
        );

        // Get reaction events
        $reactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                        e.event_data, e.target_event_id, e.created_at,
                        p.name, p.avatar, p.role
                FROM {$this->table_messages} e
                LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
                WHERE e.chat_id = %d AND e.event_type = 'reaction'
                ORDER BY e.start_time ASC",
                $id,
            ),
        );

        // Get typing events
        $typing_events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                        e.event_data, e.target_event_id, e.created_at,
                        p.name, p.avatar, p.role
                FROM {$this->table_messages} e
                LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
                WHERE e.chat_id = %d AND e.event_type = 'typing'
                ORDER BY e.start_time ASC",
                $id,
            ),
        );

        // Get presence events
        $presence_events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                        e.event_data, e.created_at,
                        p.name, p.avatar, p.role
                FROM {$this->table_messages} e
                LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
                WHERE e.chat_id = %d AND e.event_type = 'presence'
                ORDER BY e.start_time ASC",
                $id,
            ),
        );

        // Transform events into message format for frontend
        $messages = array_map(function ($event) {
            $data = json_decode($event->event_data, true);
            return (object) [
                "id" => $event->id,
                "chat_id" => $event->chat_id,
                "character_id" => $event->character_id,
                "message" => $data["text"] ?? "",
                "timestamp" => $data["timestamp"] ?? "",
                "start_time" => $event->start_time,
                "name" => $event->name,
                "avatar" => $event->avatar,
                "role" => $event->role,
            ];
        }, $events);

        // Transform reactions
        $reaction_objects = array_map(function ($reaction) {
            $data = json_decode($reaction->event_data, true);
            return (object) [
                "id" => $reaction->id,
                "target_event_id" => $reaction->target_event_id,
                "character_id" => $reaction->character_id,
                "reaction" => $data["reaction"] ?? "👍",
                "start_time" => $reaction->start_time,
                "name" => $reaction->name,
            ];
        }, $reactions);

        // Transform typing events
        $typing_objects = [];
        if ($typing_events) {
            $typing_objects = array_map(function ($typing) {
                $data = json_decode($typing->event_data, true);
                return (object) [
                    "id" => $typing->id,
                    "character_id" => $typing->character_id,
                    "start_time" => $typing->start_time,
                    "duration" => $data["duration"] ?? 3,
                    "name" => $typing->name,
                ];
            }, $typing_events);
        }

        // Transform presence events
        $presence_objects = [];
        if ($presence_events) {
            $presence_objects = array_map(function ($presence) {
                $data = json_decode($presence->event_data, true);
                return (object) [
                    "id" => $presence->id,
                    "character_id" => $presence->character_id,
                    "action" => $data["action"] ?? "join",
                    "start_time" => $presence->start_time,
                    "name" => $presence->name,
                    "avatar" => $presence->avatar,
                    "role" => $presence->role,
                ];
            }, $presence_events);
        }

        wp_send_json_success([
            "chat" => $chat,
            "messages" => $messages,
            "reactions" => $reaction_objects,
            "typing_events" => $typing_objects,
            "presence_events" => $presence_objects,
        ]);
    }

    public function ajax_get_preview_url()
    {
        check_ajax_referer("chatstory_nonce", "nonce");

        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => "Unauthorized"]);
        }

        $chat_id = isset($_POST["chat_id"]) ? intval($_POST["chat_id"]) : 0;

        if ($chat_id === 0) {
            wp_send_json_error(["message" => "Invalid chat ID"]);
        }

        $preview_url = $this->get_preview_url($chat_id);
        wp_send_json_success(["url" => $preview_url]);
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
}

ChatStory::instance();
