<?php
/**
 * ChatStory REST API Controller
 *
 * Provides unified REST API endpoints for characters, chats, and messages.
 * Used by both admin UI and MCP integration.
 */

if (!defined('ABSPATH')) {
    exit;
}

class ChatStory_REST_API {
    private $namespace = 'chatstory/v1';
    private $table_characters;
    private $table_chats;
    private $table_messages;

    public function __construct() {
        global $wpdb;
        $this->table_characters = $wpdb->prefix . 'chatstory_characters';
        $this->table_chats = $wpdb->prefix . 'chatstory_chats';
        $this->table_messages = $wpdb->prefix . 'chatstory_events';

        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        // Character routes
        register_rest_route($this->namespace, '/characters', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_characters'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_character'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/characters/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_character'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_character'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_character'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/characters/import', [
            'methods' => 'POST',
            'callback' => [$this, 'import_characters'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Chat routes
        register_rest_route($this->namespace, '/chats', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_chats'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_chat'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/chats/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_chat'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_chat'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_chat'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/chats/(?P<id>\d+)/preview-url', [
            'methods' => 'GET',
            'callback' => [$this, 'get_preview_url'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        register_rest_route($this->namespace, '/chats/import', [
            'methods' => 'POST',
            'callback' => [$this, 'import_chat'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Message routes
        register_rest_route($this->namespace, '/messages/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_message'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_message'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_message'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/chats/(?P<chat_id>\d+)/messages', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'get_messages'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'create_message'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        // Reaction routes
        register_rest_route($this->namespace, '/reactions/(?P<id>\d+)', [
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_reaction'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_reaction'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/messages/(?P<message_id>\d+)/reactions', [
            'methods' => 'POST',
            'callback' => [$this, 'create_reaction'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Typing event routes
        register_rest_route($this->namespace, '/typing/(?P<id>\d+)', [
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_typing'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_typing'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/messages/(?P<message_id>\d+)/typing', [
            'methods' => 'POST',
            'callback' => [$this, 'create_typing'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Presence event routes
        register_rest_route($this->namespace, '/presence/(?P<id>\d+)', [
            [
                'methods' => 'PUT',
                'callback' => [$this, 'update_presence'],
                'permission_callback' => [$this, 'check_permission'],
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'delete_presence'],
                'permission_callback' => [$this, 'check_permission'],
            ],
        ]);

        register_rest_route($this->namespace, '/chats/(?P<chat_id>\d+)/presence', [
            'methods' => 'POST',
            'callback' => [$this, 'create_presence'],
            'permission_callback' => [$this, 'check_permission'],
        ]);

        // Public frontend route (no auth required)
        register_rest_route($this->namespace, '/chats/(?P<id>\d+)/frontend', [
            'methods' => 'GET',
            'callback' => [$this, 'get_chat_frontend'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Check permissions
     */
    public function check_permission($request) {
        return current_user_can('manage_options');
    }

    // ==================== CHARACTER METHODS ====================

    public function get_characters($request) {
        global $wpdb;
        $characters = $wpdb->get_results("SELECT * FROM {$this->table_characters} ORDER BY name ASC");
        return rest_ensure_response($characters);
    }

    public function get_character($request) {
        global $wpdb;
        $id = intval($request['id']);

        $character = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_characters} WHERE id = %d",
            $id
        ));

        if (!$character) {
            return new WP_Error('not_found', 'Character not found', ['status' => 404]);
        }

        return rest_ensure_response($character);
    }

    public function create_character($request) {
        global $wpdb;
        $params = $request->get_json_params();

        $name = sanitize_text_field($params['name'] ?? '');
        if (empty($name)) {
            return new WP_Error('missing_field', 'Name is required', ['status' => 400]);
        }

        $slug = isset($params['slug']) ? sanitize_title($params['slug']) : '';
        if (empty($slug)) {
            $slug = $this->generate_unique_slug($name);
        } else {
            $slug = $this->generate_unique_slug($slug);
        }

        $data = [
            'name' => $name,
            'slug' => $slug,
            'role' => sanitize_text_field($params['role'] ?? ''),
            'avatar' => esc_url_raw($params['avatar'] ?? ''),
            'character_traits' => sanitize_textarea_field($params['character_traits'] ?? ''),
        ];

        $result = $wpdb->insert($this->table_characters, $data);

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create character', ['status' => 500]);
        }

        $data['id'] = $wpdb->insert_id;
        return rest_ensure_response($data);
    }

    public function update_character($request) {
        global $wpdb;
        $id = intval($request['id']);
        $params = $request->get_json_params();

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_characters} WHERE id = %d",
            $id
        ));

        if (!$exists) {
            return new WP_Error('not_found', 'Character not found', ['status' => 404]);
        }

        $data = [];
        if (isset($params['name'])) {
            $data['name'] = sanitize_text_field($params['name']);
        }
        if (isset($params['slug'])) {
            $data['slug'] = $this->generate_unique_slug($params['slug'], $id);
        }
        if (isset($params['role'])) {
            $data['role'] = sanitize_text_field($params['role']);
        }
        if (isset($params['avatar'])) {
            $data['avatar'] = esc_url_raw($params['avatar']);
        }
        if (isset($params['character_traits'])) {
            $data['character_traits'] = sanitize_textarea_field($params['character_traits']);
        }

        if (empty($data)) {
            return new WP_Error('no_data', 'No data to update', ['status' => 400]);
        }

        $wpdb->update($this->table_characters, $data, ['id' => $id]);

        $character = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_characters} WHERE id = %d",
            $id
        ));

        return rest_ensure_response($character);
    }

    public function delete_character($request) {
        global $wpdb;
        $id = intval($request['id']);

        $result = $wpdb->delete($this->table_characters, ['id' => $id]);

        if ($result === 0) {
            return new WP_Error('not_found', 'Character not found', ['status' => 404]);
        }

        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    public function import_characters($request) {
        global $wpdb;
        $params = $request->get_json_params();
        $data = $params['characters'] ?? [];

        if (!is_array($data)) {
            return new WP_Error('invalid_data', 'Characters must be an array', ['status' => 400]);
        }

        $imported = 0;
        $skipped = 0;

        foreach ($data as $character) {
            $name = sanitize_text_field($character['name'] ?? '');
            if (empty($name)) {
                $skipped++;
                continue;
            }

            $slug = isset($character['slug']) ? sanitize_title($character['slug']) : '';
            if (empty($slug)) {
                $slug = $this->generate_unique_slug($name, 0);
            } else {
                $slug = $this->generate_unique_slug($slug, 0);
            }

            $wpdb->insert($this->table_characters, [
                'name' => $name,
                'slug' => $slug,
                'avatar' => esc_url_raw($character['avatar'] ?? ''),
                'role' => sanitize_text_field($character['role'] ?? ''),
                'character_traits' => sanitize_textarea_field($character['character_traits'] ?? ''),
            ]);

            $imported++;
        }

        return rest_ensure_response(['imported' => $imported, 'skipped' => $skipped]);
    }

    // ==================== CHAT METHODS ====================

    public function get_chats($request) {
        global $wpdb;
        $chats = $wpdb->get_results("SELECT * FROM {$this->table_chats} ORDER BY created_at DESC");
        return rest_ensure_response($chats);
    }

    public function get_chat($request) {
        global $wpdb;
        $id = intval($request['id']);

        $chat = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_chats} WHERE id = %d",
            $id
        ));

        if (!$chat) {
            return new WP_Error('not_found', 'Chat not found', ['status' => 404]);
        }

        // Get events and decode event_data JSON
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                    e.event_data, e.created_at,
                    p.name, p.avatar, p.role
            FROM {$this->table_messages} e
            LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
            WHERE e.chat_id = %d AND e.event_type = 'message'
            ORDER BY e.start_time ASC",
            $id
        ));

        // Get reaction events
        $reactions = $wpdb->get_results($wpdb->prepare(
            "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                    e.event_data, e.target_event_id, e.created_at,
                    p.name, p.avatar, p.role
            FROM {$this->table_messages} e
            LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
            WHERE e.chat_id = %d AND e.event_type = 'reaction'
            ORDER BY e.start_time ASC",
            $id
        ));

        // Get typing events
        $typing_events = $wpdb->get_results($wpdb->prepare(
            "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                    e.event_data, e.target_event_id, e.created_at,
                    p.name, p.avatar, p.role
            FROM {$this->table_messages} e
            LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
            WHERE e.chat_id = %d AND e.event_type = 'typing'
            ORDER BY e.start_time ASC",
            $id
        ));

        // Get presence events
        $presence_events = $wpdb->get_results($wpdb->prepare(
            "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                    e.event_data, e.created_at,
                    p.name, p.avatar, p.role
            FROM {$this->table_messages} e
            LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
            WHERE e.chat_id = %d AND e.event_type = 'presence'
            ORDER BY e.start_time ASC",
            $id
        ));

        // Transform events into message format
        $messages = array_map(function ($event) {
            $data = json_decode($event->event_data, true);
            return (object) [
                'id' => $event->id,
                'chat_id' => $event->chat_id,
                'character_id' => $event->character_id,
                'message' => $data['text'] ?? '',
                'timestamp' => $data['timestamp'] ?? '',
                'start_time' => $event->start_time,
                'name' => $event->name,
                'avatar' => $event->avatar,
                'role' => $event->role,
                'created_at' => $event->created_at,
                'reactions' => [],
                'typing_event' => null,
            ];
        }, $events);

        // Transform reactions
        $reaction_objects = array_map(function ($reaction) {
            $data = json_decode($reaction->event_data, true);
            return (object) [
                'id' => $reaction->id,
                'target_event_id' => $reaction->target_event_id,
                'character_id' => $reaction->character_id,
                'reaction' => $data['reaction'] ?? '👍',
                'start_time' => $reaction->start_time,
                'name' => $reaction->name,
            ];
        }, $reactions);

        // Transform typing events
        $typing_objects = array_map(function ($typing) {
            $data = json_decode($typing->event_data, true);
            return (object) [
                'id' => $typing->id,
                'target_event_id' => $typing->target_event_id,
                'character_id' => $typing->character_id,
                'duration' => $data['duration'] ?? 3,
                'start_time' => $typing->start_time,
                'name' => $typing->name,
            ];
        }, $typing_events);

        // Attach reactions to messages
        foreach ($messages as &$message) {
            foreach ($reaction_objects as $reaction) {
                if ($reaction->target_event_id == $message->id) {
                    $message->reactions[] = $reaction;
                }
            }
        }

        // Attach typing events to messages
        foreach ($messages as &$message) {
            foreach ($typing_objects as $typing) {
                if ($typing->target_event_id == $message->id) {
                    $message->typing_event = $typing;
                    break;
                }
            }
        }

        // Transform presence events
        $presence_objects = array_map(function ($presence) {
            $data = json_decode($presence->event_data, true);
            return (object) [
                'id' => $presence->id,
                'character_id' => $presence->character_id,
                'action' => $data['action'] ?? 'join',
                'start_time' => $presence->start_time,
                'name' => $presence->name,
                'avatar' => $presence->avatar,
                'role' => $presence->role,
            ];
        }, $presence_events);

        return rest_ensure_response([
            'chat' => $chat,
            'messages' => $messages,
            'presence_events' => $presence_objects,
        ]);
    }

    public function create_chat($request) {
        global $wpdb;
        $params = $request->get_json_params();

        $title = sanitize_text_field($params['title'] ?? '');
        if (empty($title)) {
            return new WP_Error('missing_field', 'Title is required', ['status' => 400]);
        }

        $data = [
            'title' => $title,
            'description' => sanitize_textarea_field($params['description'] ?? ''),
        ];

        $result = $wpdb->insert($this->table_chats, $data);

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create chat', ['status' => 500]);
        }

        $data['id'] = $wpdb->insert_id;
        return rest_ensure_response($data);
    }

    public function update_chat($request) {
        global $wpdb;
        $id = intval($request['id']);
        $params = $request->get_json_params();

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_chats} WHERE id = %d",
            $id
        ));

        if (!$exists) {
            return new WP_Error('not_found', 'Chat not found', ['status' => 404]);
        }

        $data = [];
        if (isset($params['title'])) {
            $data['title'] = sanitize_text_field($params['title']);
        }
        if (isset($params['description'])) {
            $data['description'] = sanitize_textarea_field($params['description']);
        }

        if (empty($data)) {
            return new WP_Error('no_data', 'No data to update', ['status' => 400]);
        }

        $wpdb->update($this->table_chats, $data, ['id' => $id]);

        $chat = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_chats} WHERE id = %d",
            $id
        ));

        return rest_ensure_response($chat);
    }

    public function delete_chat($request) {
        global $wpdb;
        $id = intval($request['id']);

        $wpdb->delete($this->table_messages, ['chat_id' => $id]);
        $result = $wpdb->delete($this->table_chats, ['id' => $id]);

        if ($result === 0) {
            return new WP_Error('not_found', 'Chat not found', ['status' => 404]);
        }

        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    public function get_preview_url($request) {
        $chat_id = intval($request['id']);

        $preview_url = add_query_arg([
            'chatstory_preview' => '1',
            'chat_id' => $chat_id,
            '_wpnonce' => wp_create_nonce('chatstory_preview_' . $chat_id),
        ], home_url('/'));

        return rest_ensure_response(['url' => $preview_url]);
    }

    public function import_chat($request) {
        global $wpdb;
        $params = $request->get_json_params();

        if (!isset($params['chat'])) {
            return new WP_Error('invalid_data', 'Invalid data structure', ['status' => 400]);
        }

        $chat_data = [
            'title' => sanitize_text_field($params['chat']['title'] ?? ''),
            'description' => sanitize_textarea_field($params['chat']['description'] ?? ''),
        ];

        $wpdb->insert($this->table_chats, $chat_data);
        $chat_id = $wpdb->insert_id;

        // Import messages
        if (isset($params['messages']) && is_array($params['messages'])) {
            foreach ($params['messages'] as $index => $message) {
                $character_slug = sanitize_title($message['character'] ?? '');
                $character = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM {$this->table_characters} WHERE slug = %s",
                    $character_slug
                ));

                if (!$character) {
                    continue;
                }

                $event_data = json_encode([
                    'text' => sanitize_textarea_field($message['message'] ?? ''),
                    'timestamp' => sanitize_text_field($message['timestamp'] ?? ''),
                ]);

                $wpdb->insert($this->table_messages, [
                    'chat_id' => $chat_id,
                    'character_id' => $character->id,
                    'event_type' => 'message',
                    'start_time' => floatval($message['start_time'] ?? $index * 2),
                    'event_data' => $event_data,
                ]);

                $message_db_id = $wpdb->insert_id;

                // Auto-create typing event
                $typing_start = max(0, floatval($message['start_time'] ?? 0) - 3);
                $typing_data = json_encode(['duration' => 3]);

                $wpdb->insert($this->table_messages, [
                    'chat_id' => $chat_id,
                    'character_id' => $character->id,
                    'event_type' => 'typing',
                    'start_time' => $typing_start,
                    'event_data' => $typing_data,
                    'target_event_id' => $message_db_id,
                ]);

                // Import reactions
                if (isset($message['reactions']) && is_array($message['reactions'])) {
                    foreach ($message['reactions'] as $reaction) {
                        $reaction_character_slug = sanitize_title($reaction['character'] ?? '');
                        $reaction_character = $wpdb->get_row($wpdb->prepare(
                            "SELECT id FROM {$this->table_characters} WHERE slug = %s",
                            $reaction_character_slug
                        ));

                        if (!$reaction_character) {
                            continue;
                        }

                        $reaction_data = json_encode([
                            'reaction' => sanitize_text_field($reaction['reaction'] ?? '👍'),
                        ]);

                        $wpdb->insert($this->table_messages, [
                            'chat_id' => $chat_id,
                            'character_id' => $reaction_character->id,
                            'event_type' => 'reaction',
                            'start_time' => floatval($reaction['start_time'] ?? 0),
                            'event_data' => $reaction_data,
                            'target_event_id' => $message_db_id,
                        ]);
                    }
                }
            }
        }

        return rest_ensure_response(['chat_id' => $chat_id]);
    }

    // ==================== MESSAGE METHODS ====================

    public function get_messages($request) {
        global $wpdb;
        $chat_id = intval($request['chat_id']);

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT e.id, e.chat_id, e.character_id, e.start_time, e.event_data,
                    p.name as character_name
            FROM {$this->table_messages} e
            LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
            WHERE e.chat_id = %d AND e.event_type = 'message'
            ORDER BY e.start_time ASC",
            $chat_id
        ));

        foreach ($messages as $message) {
            $data = json_decode($message->event_data, true);
            $message->message = $data['text'] ?? '';
            $message->timestamp = $data['timestamp'] ?? '';
            unset($message->event_data);
        }

        return rest_ensure_response($messages);
    }

    public function get_message($request) {
        global $wpdb;
        $id = intval($request['id']);

        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT e.id, e.chat_id, e.character_id, e.start_time, e.event_data,
                    p.name as character_name
            FROM {$this->table_messages} e
            LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
            WHERE e.id = %d AND e.event_type = 'message'",
            $id
        ));

        if (!$message) {
            return new WP_Error('not_found', 'Message not found', ['status' => 404]);
        }

        $data = json_decode($message->event_data, true);
        $message->message = $data['text'] ?? '';
        $message->timestamp = $data['timestamp'] ?? '';
        unset($message->event_data);

        return rest_ensure_response($message);
    }

    public function create_message($request) {
        global $wpdb;
        $chat_id = intval($request['chat_id']);
        $params = $request->get_json_params();

        $character_id = intval($params['character_id'] ?? 0);
        $message_text = $params['message'] ?? '';

        if ($character_id === 0 || empty($message_text)) {
            return new WP_Error('missing_fields', 'character_id and message are required', ['status' => 400]);
        }

        $event_data = json_encode([
            'text' => sanitize_textarea_field($message_text),
            'timestamp' => sanitize_text_field($params['timestamp'] ?? ''),
        ]);

        $data = [
            'chat_id' => $chat_id,
            'character_id' => $character_id,
            'event_type' => 'message',
            'start_time' => floatval($params['start_time'] ?? 0),
            'event_data' => $event_data,
        ];

        $result = $wpdb->insert($this->table_messages, $data);

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create message', ['status' => 500]);
        }

        $data['id'] = $wpdb->insert_id;
        return rest_ensure_response($data);
    }

    public function update_message($request) {
        global $wpdb;
        $id = intval($request['id']);
        $params = $request->get_json_params();

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_messages} WHERE id = %d AND event_type = 'message'",
            $id
        ));

        if (!$existing) {
            return new WP_Error('not_found', 'Message not found', ['status' => 404]);
        }

        $event_data = json_decode($existing->event_data, true);

        if (isset($params['message'])) {
            $event_data['text'] = sanitize_textarea_field($params['message']);
        }
        if (isset($params['timestamp'])) {
            $event_data['timestamp'] = sanitize_text_field($params['timestamp']);
        }

        $data = ['event_data' => json_encode($event_data)];

        if (isset($params['character_id'])) {
            $data['character_id'] = intval($params['character_id']);
        }
        if (isset($params['start_time'])) {
            $data['start_time'] = floatval($params['start_time']);

            // Update typing event if exists
            $typing_event = $wpdb->get_row($wpdb->prepare(
                "SELECT id, event_data FROM {$this->table_messages}
                WHERE event_type = 'typing' AND target_event_id = %d",
                $id
            ));

            if ($typing_event) {
                $typing_data = json_decode($typing_event->event_data, true);
                $duration = $typing_data['duration'] ?? 3;
                $typing_start = max(0, floatval($params['start_time']) - $duration);

                $wpdb->update(
                    $this->table_messages,
                    ['start_time' => $typing_start],
                    ['id' => $typing_event->id]
                );
            }
        }

        $wpdb->update($this->table_messages, $data, ['id' => $id]);

        return $this->get_message($request);
    }

    public function delete_message($request) {
        global $wpdb;
        $id = intval($request['id']);

        // Delete associated events
        $wpdb->delete($this->table_messages, ['target_event_id' => $id]);

        // Delete message
        $result = $wpdb->delete($this->table_messages, ['id' => $id, 'event_type' => 'message']);

        if ($result === 0) {
            return new WP_Error('not_found', 'Message not found', ['status' => 404]);
        }

        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    // ==================== REACTION METHODS ====================

    public function create_reaction($request) {
        global $wpdb;
        $message_id = intval($request['message_id']);
        $params = $request->get_json_params();

        $chat_id = intval($params['chat_id'] ?? 0);
        $character_id = intval($params['character_id'] ?? 0);
        $reaction_type = sanitize_text_field($params['reaction_type'] ?? '');
        $start_time = floatval($params['start_time'] ?? 0);

        if ($chat_id === 0 || $character_id === 0 || empty($reaction_type)) {
            return new WP_Error('missing_fields', 'Missing required fields', ['status' => 400]);
        }

        $event_data = json_encode(['reaction' => $reaction_type]);

        $data = [
            'chat_id' => $chat_id,
            'character_id' => $character_id,
            'event_type' => 'reaction',
            'start_time' => $start_time,
            'event_data' => $event_data,
            'target_event_id' => $message_id,
        ];

        $result = $wpdb->insert($this->table_messages, $data);

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create reaction', ['status' => 500]);
        }

        $data['id'] = $wpdb->insert_id;
        return rest_ensure_response($data);
    }

    public function update_reaction($request) {
        global $wpdb;
        $id = intval($request['id']);
        $params = $request->get_json_params();

        $data = [];
        if (isset($params['character_id'])) {
            $data['character_id'] = intval($params['character_id']);
        }
        if (isset($params['start_time'])) {
            $data['start_time'] = floatval($params['start_time']);
        }
        if (isset($params['reaction_type'])) {
            $data['event_data'] = json_encode(['reaction' => sanitize_text_field($params['reaction_type'])]);
        }

        if (empty($data)) {
            return new WP_Error('no_data', 'No data to update', ['status' => 400]);
        }

        $result = $wpdb->update($this->table_messages, $data, ['id' => $id, 'event_type' => 'reaction']);

        if ($result === false || $result === 0) {
            return new WP_Error('not_found', 'Reaction not found', ['status' => 404]);
        }

        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    public function delete_reaction($request) {
        global $wpdb;
        $id = intval($request['id']);

        $result = $wpdb->delete($this->table_messages, ['id' => $id, 'event_type' => 'reaction']);

        if ($result === 0) {
            return new WP_Error('not_found', 'Reaction not found', ['status' => 404]);
        }

        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    // ==================== TYPING METHODS ====================

    public function create_typing($request) {
        global $wpdb;
        $message_id = intval($request['message_id']);
        $params = $request->get_json_params();

        $chat_id = intval($params['chat_id'] ?? 0);
        $character_id = intval($params['character_id'] ?? 0);
        $duration = floatval($params['duration'] ?? 3);
        $start_time = floatval($params['start_time'] ?? 0);

        if ($chat_id === 0 || $character_id === 0) {
            return new WP_Error('missing_fields', 'Missing required fields', ['status' => 400]);
        }

        $event_data = json_encode(['duration' => $duration]);

        $data = [
            'chat_id' => $chat_id,
            'character_id' => $character_id,
            'event_type' => 'typing',
            'start_time' => $start_time,
            'event_data' => $event_data,
            'target_event_id' => $message_id,
        ];

        $result = $wpdb->insert($this->table_messages, $data);

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create typing event', ['status' => 500]);
        }

        $data['id'] = $wpdb->insert_id;
        return rest_ensure_response($data);
    }

    public function update_typing($request) {
        global $wpdb;
        $id = intval($request['id']);
        $params = $request->get_json_params();

        $data = [];
        if (isset($params['duration'])) {
            $data['event_data'] = json_encode(['duration' => floatval($params['duration'])]);
        }
        if (isset($params['start_time'])) {
            $data['start_time'] = floatval($params['start_time']);
        }

        if (empty($data)) {
            return new WP_Error('no_data', 'No data to update', ['status' => 400]);
        }

        $result = $wpdb->update($this->table_messages, $data, ['id' => $id, 'event_type' => 'typing']);

        if ($result === false || $result === 0) {
            return new WP_Error('not_found', 'Typing event not found', ['status' => 404]);
        }

        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    public function delete_typing($request) {
        global $wpdb;
        $id = intval($request['id']);

        $result = $wpdb->delete($this->table_messages, ['id' => $id, 'event_type' => 'typing']);

        if ($result === 0) {
            return new WP_Error('not_found', 'Typing event not found', ['status' => 404]);
        }

        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    // ==================== PRESENCE METHODS ====================

    public function create_presence($request) {
        global $wpdb;
        $chat_id = intval($request['chat_id']);
        $params = $request->get_json_params();

        $character_id = intval($params['character_id'] ?? 0);
        $presence_action = sanitize_text_field($params['presence_action'] ?? 'join');
        $start_time = floatval($params['start_time'] ?? 0);

        if ($character_id === 0) {
            return new WP_Error('missing_fields', 'character_id is required', ['status' => 400]);
        }

        $event_data = json_encode(['action' => $presence_action]);

        $data = [
            'chat_id' => $chat_id,
            'character_id' => $character_id,
            'event_type' => 'presence',
            'start_time' => $start_time,
            'event_data' => $event_data,
        ];

        $result = $wpdb->insert($this->table_messages, $data);

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create presence event', ['status' => 500]);
        }

        $data['id'] = $wpdb->insert_id;
        return rest_ensure_response($data);
    }

    public function update_presence($request) {
        global $wpdb;
        $id = intval($request['id']);
        $params = $request->get_json_params();

        $data = [];
        if (isset($params['presence_action'])) {
            $data['event_data'] = json_encode(['action' => sanitize_text_field($params['presence_action'])]);
        }
        if (isset($params['start_time'])) {
            $data['start_time'] = floatval($params['start_time']);
        }

        if (empty($data)) {
            return new WP_Error('no_data', 'No data to update', ['status' => 400]);
        }

        $result = $wpdb->update($this->table_messages, $data, ['id' => $id, 'event_type' => 'presence']);

        if ($result === false || $result === 0) {
            return new WP_Error('not_found', 'Presence event not found', ['status' => 404]);
        }

        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    public function delete_presence($request) {
        global $wpdb;
        $id = intval($request['id']);

        $result = $wpdb->delete($this->table_messages, ['id' => $id, 'event_type' => 'presence']);

        if ($result === 0) {
            return new WP_Error('not_found', 'Presence event not found', ['status' => 404]);
        }

        return rest_ensure_response(['success' => true, 'id' => $id]);
    }

    // ==================== FRONTEND METHOD ====================

    public function get_chat_frontend($request) {
        global $wpdb;
        $id = intval($request['id']);

        $chat = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_chats} WHERE id = %d",
            $id
        ));

        if (!$chat) {
            return new WP_Error('not_found', 'Chat not found', ['status' => 404]);
        }

        // Get message events
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                    e.event_data, e.created_at,
                    p.name, p.avatar, p.role
            FROM {$this->table_messages} e
            LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
            WHERE e.chat_id = %d AND e.event_type = 'message'
            ORDER BY e.start_time ASC",
            $id
        ));

        // Get reactions
        $reactions = $wpdb->get_results($wpdb->prepare(
            "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                    e.event_data, e.target_event_id, e.created_at,
                    p.name, p.avatar, p.role
            FROM {$this->table_messages} e
            LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
            WHERE e.chat_id = %d AND e.event_type = 'reaction'
            ORDER BY e.start_time ASC",
            $id
        ));

        // Get typing events
        $typing_events = $wpdb->get_results($wpdb->prepare(
            "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                    e.event_data, e.target_event_id, e.created_at,
                    p.name, p.avatar, p.role
            FROM {$this->table_messages} e
            LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
            WHERE e.chat_id = %d AND e.event_type = 'typing'
            ORDER BY e.start_time ASC",
            $id
        ));

        // Get presence events
        $presence_events = $wpdb->get_results($wpdb->prepare(
            "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                    e.event_data, e.created_at,
                    p.name, p.avatar, p.role
            FROM {$this->table_messages} e
            LEFT JOIN {$this->table_characters} p ON e.character_id = p.id
            WHERE e.chat_id = %d AND e.event_type = 'presence'
            ORDER BY e.start_time ASC",
            $id
        ));

        // Transform for frontend
        $messages = array_map(function ($event) {
            $data = json_decode($event->event_data, true);
            return (object) [
                'id' => $event->id,
                'chat_id' => $event->chat_id,
                'character_id' => $event->character_id,
                'message' => $data['text'] ?? '',
                'timestamp' => $data['timestamp'] ?? '',
                'start_time' => $event->start_time,
                'name' => $event->name,
                'avatar' => $event->avatar,
                'role' => $event->role,
            ];
        }, $events);

        $reaction_objects = array_map(function ($reaction) {
            $data = json_decode($reaction->event_data, true);
            return (object) [
                'id' => $reaction->id,
                'target_event_id' => $reaction->target_event_id,
                'character_id' => $reaction->character_id,
                'reaction' => $data['reaction'] ?? '👍',
                'start_time' => $reaction->start_time,
                'name' => $reaction->name,
            ];
        }, $reactions);

        $typing_objects = array_map(function ($typing) {
            $data = json_decode($typing->event_data, true);
            return (object) [
                'id' => $typing->id,
                'character_id' => $typing->character_id,
                'start_time' => $typing->start_time,
                'duration' => $data['duration'] ?? 3,
                'name' => $typing->name,
            ];
        }, $typing_events);

        $presence_objects = array_map(function ($presence) {
            $data = json_decode($presence->event_data, true);
            return (object) [
                'id' => $presence->id,
                'character_id' => $presence->character_id,
                'action' => $data['action'] ?? 'join',
                'start_time' => $presence->start_time,
                'name' => $presence->name,
                'avatar' => $presence->avatar,
                'role' => $presence->role,
            ];
        }, $presence_events);

        return rest_ensure_response([
            'chat' => $chat,
            'messages' => $messages,
            'reactions' => $reaction_objects,
            'typing_events' => $typing_objects,
            'presence_events' => $presence_objects,
        ]);
    }

    // ==================== HELPER METHODS ====================

    private function generate_unique_slug($name, $exclude_id = 0) {
        global $wpdb;

        $slug = sanitize_title($name);
        $suffix = 0;
        $original_slug = $slug;

        while (true) {
            $check_slug = $suffix > 0 ? $original_slug . '-' . $suffix : $slug;

            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->table_characters} WHERE slug = %s AND id != %d",
                $check_slug,
                $exclude_id
            ));

            if (!$exists) {
                return $check_slug;
            }

            $suffix++;
        }
    }
}

// Initialize REST API
new ChatStory_REST_API();
