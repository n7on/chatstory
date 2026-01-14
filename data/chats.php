<?php
/**
 * Chat Data Layer
 *
 * Business logic for chat CRUD operations.
 */

namespace ChatStory\Data;

/**
 * Get all chats
 */
function get_chats() {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_chats';
    return $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC");
}

/**
 * Get a single chat by ID
 */
function get_chat($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_chats';
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $id
    ));
}

/**
 * Get chat with all messages and events
 */
function get_chat_with_messages($id) {
    global $wpdb;

    $chat = get_chat($id);
    if (!$chat) {
        return null;
    }

    $table_messages = $wpdb->prefix . 'chatstory_events';
    $table_characters = $wpdb->prefix . 'chatstory_characters';

    // Get message events
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.created_at,
                c.name, c.avatar, c.role
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.chat_id = %d AND e.event_type = 'message'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get reaction events
    $reactions = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.target_event_id, e.created_at,
                c.name
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.chat_id = %d AND e.event_type = 'reaction'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get typing events
    $typing_events = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.target_event_id, e.created_at,
                c.name
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.chat_id = %d AND e.event_type = 'typing'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get presence events
    $presence_events = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.created_at,
                c.name, c.avatar, c.role
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.chat_id = %d AND e.event_type = 'presence'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Transform messages
    $formatted_messages = array_map(function($message) {
        $data = json_decode($message->event_data, true);
        return [
            'id' => (int) $message->id,
            'chat_id' => (int) $message->chat_id,
            'character_id' => (int) $message->character_id,
            'name' => $message->name,
            'avatar' => $message->avatar,
            'role' => $message->role,
            'message' => $data['text'] ?? '',
            'timestamp' => $data['timestamp'] ?? '',
            'start_time' => (float) $message->start_time,
            'reactions' => [],
            'typing_event' => null,
        ];
    }, $messages);

    // Transform reactions and attach to messages
    $reaction_objects = array_map(function($reaction) {
        $data = json_decode($reaction->event_data, true);
        return [
            'id' => (int) $reaction->id,
            'target_event_id' => (int) $reaction->target_event_id,
            'character_id' => (int) $reaction->character_id,
            'reaction' => $data['reaction'] ?? '👍',
            'start_time' => (float) $reaction->start_time,
            'name' => $reaction->name,
        ];
    }, $reactions);

    // Transform typing events
    $typing_objects = array_map(function($typing) {
        $data = json_decode($typing->event_data, true);
        return [
            'id' => (int) $typing->id,
            'target_event_id' => (int) $typing->target_event_id,
            'character_id' => (int) $typing->character_id,
            'duration' => (float) ($data['duration'] ?? 3),
            'start_time' => (float) $typing->start_time,
            'name' => $typing->name,
        ];
    }, $typing_events);

    // Attach reactions to messages
    foreach ($formatted_messages as &$message) {
        foreach ($reaction_objects as $reaction) {
            if ($reaction['target_event_id'] == $message['id']) {
                $message['reactions'][] = $reaction;
            }
        }
    }

    // Attach typing events to messages
    foreach ($formatted_messages as &$message) {
        foreach ($typing_objects as $typing) {
            if ($typing['target_event_id'] == $message['id']) {
                $message['typing_event'] = $typing;
                break; // Only one typing event per message
            }
        }
    }

    // Transform presence events
    $formatted_presence = array_map(function($presence) {
        $data = json_decode($presence->event_data, true);
        return [
            'id' => (int) $presence->id,
            'character_id' => (int) $presence->character_id,
            'action' => $data['action'] ?? 'join',
            'start_time' => (float) $presence->start_time,
            'name' => $presence->name,
            'avatar' => $presence->avatar,
            'role' => $presence->role,
        ];
    }, $presence_events);

    return [
        'chat' => $chat,
        'messages' => $formatted_messages,
        'presence_events' => $formatted_presence,
    ];
}

/**
 * Create a new chat
 */
function create_chat($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_chats';

    // Validate required fields
    if (empty($data['title'])) {
        return new \WP_Error('missing_title', 'Chat title is required');
    }

    // Prepare data
    $insert_data = [
        'title' => sanitize_text_field($data['title']),
        'description' => sanitize_textarea_field($data['description'] ?? ''),
    ];

    $wpdb->insert($table, $insert_data);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return get_chat($wpdb->insert_id);
}

/**
 * Update an existing chat
 */
function update_chat($id, $data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_chats';

    // Check if chat exists
    $chat = get_chat($id);
    if (!$chat) {
        return new \WP_Error('not_found', 'Chat not found');
    }

    // Prepare update data
    $update_data = [];

    if (isset($data['title'])) {
        $update_data['title'] = sanitize_text_field($data['title']);
    }

    if (isset($data['description'])) {
        $update_data['description'] = sanitize_textarea_field($data['description']);
    }

    if (empty($update_data)) {
        return $chat; // Nothing to update
    }

    $wpdb->update($table, $update_data, ['id' => $id]);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return get_chat($id);
}

/**
 * Delete a chat and all its messages
 */
function delete_chat($id) {
    global $wpdb;
    $table_chats = $wpdb->prefix . 'chatstory_chats';
    $table_messages = $wpdb->prefix . 'chatstory_events';

    // Check if chat exists
    $chat = get_chat($id);
    if (!$chat) {
        return new \WP_Error('not_found', 'Chat not found');
    }

    // Delete all messages first
    $wpdb->delete($table_messages, ['chat_id' => $id]);

    // Delete the chat
    $result = $wpdb->delete($table_chats, ['id' => $id]);

    if ($result === false) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return true;
}

/**
 * Get chat with events for frontend display (flat structure)
 */
function get_chat_for_frontend($id) {
    global $wpdb;

    $chat = get_chat($id);
    if (!$chat) {
        return null;
    }

    $table_messages = $wpdb->prefix . 'chatstory_events';
    $table_characters = $wpdb->prefix . 'chatstory_characters';

    // Get message events
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.created_at,
                c.name, c.avatar, c.role
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.chat_id = %d AND e.event_type = 'message'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get reaction events
    $reactions = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.target_event_id, e.created_at,
                c.name
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.chat_id = %d AND e.event_type = 'reaction'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get typing events
    $typing_events = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.target_event_id, e.created_at,
                c.name
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.chat_id = %d AND e.event_type = 'typing'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get presence events
    $presence_events = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.chat_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.created_at,
                c.name, c.avatar, c.role
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.chat_id = %d AND e.event_type = 'presence'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Transform messages (simple format for frontend)
    $formatted_messages = array_map(function($message) {
        $data = json_decode($message->event_data, true);
        return [
            'id' => (int) $message->id,
            'chat_id' => (int) $message->chat_id,
            'character_id' => (int) $message->character_id,
            'name' => $message->name,
            'avatar' => $message->avatar,
            'role' => $message->role,
            'message' => $data['text'] ?? '',
            'timestamp' => $data['timestamp'] ?? '',
            'start_time' => (float) $message->start_time,
        ];
    }, $messages);

    // Transform reactions (flat array for frontend)
    $formatted_reactions = array_map(function($reaction) {
        $data = json_decode($reaction->event_data, true);
        return [
            'id' => (int) $reaction->id,
            'target_event_id' => (int) $reaction->target_event_id,
            'character_id' => (int) $reaction->character_id,
            'reaction' => $data['reaction'] ?? '👍',
            'start_time' => (float) $reaction->start_time,
            'name' => $reaction->name,
        ];
    }, $reactions);

    // Transform typing events (flat array for frontend)
    $formatted_typing = array_map(function($typing) {
        $data = json_decode($typing->event_data, true);
        return [
            'id' => (int) $typing->id,
            'character_id' => (int) $typing->character_id,
            'start_time' => (float) $typing->start_time,
            'duration' => (float) ($data['duration'] ?? 3),
            'name' => $typing->name,
        ];
    }, $typing_events);

    // Transform presence events
    $formatted_presence = array_map(function($presence) {
        $data = json_decode($presence->event_data, true);
        return [
            'id' => (int) $presence->id,
            'character_id' => (int) $presence->character_id,
            'action' => $data['action'] ?? 'join',
            'start_time' => (float) $presence->start_time,
            'name' => $presence->name,
            'avatar' => $presence->avatar,
            'role' => $presence->role,
        ];
    }, $presence_events);

    return [
        'chat' => $chat,
        'messages' => $formatted_messages,
        'reactions' => $formatted_reactions,
        'typing_events' => $formatted_typing,
        'presence_events' => $formatted_presence,
    ];
}

/**
 * Get preview URL for a chat
 */
function get_preview_url($chat_id) {
    return add_query_arg([
        'chatstory_preview' => '1',
        'chat_id' => $chat_id,
        '_wpnonce' => wp_create_nonce('chatstory_preview_' . $chat_id),
    ], home_url('/'));
}
