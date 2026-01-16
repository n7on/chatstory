<?php
/**
 * Message Data Layer
 *
 * Business logic for message, reaction, typing, and presence CRUD operations.
 */

namespace ChatStory\Data\Messages;

/**
 * Get all messages for a chat
 */
function get_messages_by_chat($post_id) {
    global $wpdb;
    $table_messages = $wpdb->prefix . 'chatstory_events';
    $table_characters = $wpdb->prefix . 'chatstory_characters';

    // Get message events
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.post_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.created_at,
                c.name, c.avatar, c.role
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.post_id = %d AND e.event_type = 'message'
        ORDER BY e.start_time ASC",
        $post_id
    ));

    // Transform events into message format
    $formatted_messages = array_map(function($message) {
        $data = json_decode($message->event_data, true);
        return [
            'id' => (int) $message->id,
            'chat_id' => (int) $message->post_id, // For backwards compatibility
            'character_id' => (int) $message->character_id,
            'name' => $message->name,
            'avatar' => $message->avatar,
            'role' => $message->role,
            'message' => $data['text'] ?? '',
            'timestamp' => $data['timestamp'] ?? '',
            'start_time' => (float) $message->start_time,
        ];
    }, $messages);

    return $formatted_messages;
}

/**
 * Get a single message by ID
 */
function get_message($id) {
    global $wpdb;
    $table_messages = $wpdb->prefix . 'chatstory_events';
    $table_characters = $wpdb->prefix . 'chatstory_characters';

    $message = $wpdb->get_row($wpdb->prepare(
        "SELECT e.id, e.post_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.created_at,
                c.name, c.avatar, c.role
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.id = %d AND e.event_type = 'message'",
        $id
    ));

    if (!$message) {
        return null;
    }

    $data = json_decode($message->event_data, true);
    return [
        'id' => (int) $message->id,
        'chat_id' => (int) $message->post_id, // For backwards compatibility
        'character_id' => (int) $message->character_id,
        'name' => $message->name,
        'avatar' => $message->avatar,
        'role' => $message->role,
        'message' => $data['text'] ?? '',
        'timestamp' => $data['timestamp'] ?? '',
        'start_time' => (float) $message->start_time,
    ];
}

/**
 * Create a new message
 */
function create_message($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    // Validate required fields
    if (empty($data['chat_id']) || empty($data['character_id']) || empty($data['message'])) {
        return new \WP_Error('missing_fields', 'chat_id, character_id, and message are required');
    }

    // Prepare event data
    $event_data = json_encode([
        'text' => sanitize_textarea_field($data['message']),
        'timestamp' => sanitize_text_field($data['timestamp'] ?? ''),
    ]);

    $insert_data = [
        'post_id' => (int) $data['chat_id'], // Accept chat_id for backwards compatibility
        'character_id' => (int) $data['character_id'],
        'event_type' => 'message',
        'start_time' => (float) ($data['start_time'] ?? 0),
        'event_data' => $event_data,
    ];

    $wpdb->insert($table, $insert_data);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return get_message($wpdb->insert_id);
}

/**
 * Update an existing message
 */
function update_message($id, $data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    // Check if message exists
    $message = get_message($id);
    if (!$message) {
        return new \WP_Error('not_found', 'Message not found');
    }

    // Get existing event data
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT event_data FROM {$table} WHERE id = %d",
        $id
    ));
    $existing_data = json_decode($row->event_data, true);

    // Update fields
    $update_data = [];

    if (isset($data['character_id'])) {
        $update_data['character_id'] = (int) $data['character_id'];
    }

    if (isset($data['start_time'])) {
        $update_data['start_time'] = (float) $data['start_time'];
    }

    if (isset($data['message']) || isset($data['timestamp'])) {
        $event_data = [
            'text' => isset($data['message']) ? sanitize_textarea_field($data['message']) : $existing_data['text'],
            'timestamp' => isset($data['timestamp']) ? sanitize_text_field($data['timestamp']) : ($existing_data['timestamp'] ?? ''),
        ];
        $update_data['event_data'] = json_encode($event_data);
    }

    if (empty($update_data)) {
        return $message; // Nothing to update
    }

    $wpdb->update($table, $update_data, ['id' => $id]);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return get_message($id);
}

/**
 * Delete a message and its associated events
 */
function delete_message($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    // Check if message exists
    $message = get_message($id);
    if (!$message) {
        return new \WP_Error('not_found', 'Message not found');
    }

    // Delete the message
    $wpdb->delete($table, ['id' => $id]);

    // Delete associated reactions and typing events
    $wpdb->delete($table, ['target_event_id' => $id]);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return true;
}

/**
 * Create a reaction
 */
function create_reaction($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    // Validate required fields
    if (empty($data['chat_id']) || empty($data['character_id']) || empty($data['target_event_id']) || empty($data['reaction'])) {
        return new \WP_Error('missing_fields', 'chat_id, character_id, target_event_id, and reaction are required');
    }

    $event_data = json_encode([
        'reaction' => sanitize_text_field($data['reaction']),
    ]);

    $insert_data = [
        'post_id' => (int) $data['chat_id'], // Accept chat_id for backwards compatibility
        'character_id' => (int) $data['character_id'],
        'event_type' => 'reaction',
        'start_time' => (float) ($data['start_time'] ?? 0),
        'event_data' => $event_data,
        'target_event_id' => (int) $data['target_event_id'],
    ];

    $wpdb->insert($table, $insert_data);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return ['id' => $wpdb->insert_id];
}

/**
 * Update a reaction
 */
function update_reaction($id, $data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    $update_data = [];

    if (isset($data['start_time'])) {
        $update_data['start_time'] = (float) $data['start_time'];
    }

    if (isset($data['reaction'])) {
        $event_data = json_encode(['reaction' => sanitize_text_field($data['reaction'])]);
        $update_data['event_data'] = $event_data;
    }

    if (empty($update_data)) {
        return ['id' => $id];
    }

    $wpdb->update($table, $update_data, ['id' => $id, 'event_type' => 'reaction']);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return ['id' => $id];
}

/**
 * Delete a reaction
 */
function delete_reaction($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    $result = $wpdb->delete($table, ['id' => $id, 'event_type' => 'reaction']);

    if ($result === false) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return true;
}

/**
 * Create a typing event
 */
function create_typing($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    if (empty($data['chat_id']) || empty($data['character_id']) || empty($data['target_event_id'])) {
        return new \WP_Error('missing_fields', 'chat_id, character_id, and target_event_id are required');
    }

    $event_data = json_encode([
        'duration' => (float) ($data['duration'] ?? 3),
    ]);

    $insert_data = [
        'post_id' => (int) $data['chat_id'], // Accept chat_id for backwards compatibility
        'character_id' => (int) $data['character_id'],
        'event_type' => 'typing',
        'start_time' => (float) ($data['start_time'] ?? 0),
        'event_data' => $event_data,
        'target_event_id' => (int) $data['target_event_id'],
    ];

    $wpdb->insert($table, $insert_data);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return ['id' => $wpdb->insert_id];
}

/**
 * Update a typing event
 */
function update_typing($id, $data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    $update_data = [];

    if (isset($data['start_time'])) {
        $update_data['start_time'] = (float) $data['start_time'];
    }

    if (isset($data['duration'])) {
        $event_data = json_encode(['duration' => (float) $data['duration']]);
        $update_data['event_data'] = $event_data;
    }

    if (empty($update_data)) {
        return ['id' => $id];
    }

    $wpdb->update($table, $update_data, ['id' => $id, 'event_type' => 'typing']);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return ['id' => $id];
}

/**
 * Delete a typing event
 */
function delete_typing($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    $result = $wpdb->delete($table, ['id' => $id, 'event_type' => 'typing']);

    if ($result === false) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return true;
}

/**
 * Create a presence event
 */
function create_presence($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    if (empty($data['chat_id']) || empty($data['character_id'])) {
        return new \WP_Error('missing_fields', 'chat_id and character_id are required');
    }

    $event_data = json_encode([
        'action' => sanitize_text_field($data['action'] ?? 'join'),
    ]);

    $insert_data = [
        'post_id' => (int) $data['chat_id'], // Accept chat_id for backwards compatibility
        'character_id' => (int) $data['character_id'],
        'event_type' => 'presence',
        'start_time' => (float) ($data['start_time'] ?? 0),
        'event_data' => $event_data,
    ];

    $wpdb->insert($table, $insert_data);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return ['id' => $wpdb->insert_id];
}

/**
 * Update a presence event
 */
function update_presence($id, $data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    $update_data = [];

    if (isset($data['start_time'])) {
        $update_data['start_time'] = (float) $data['start_time'];
    }

    if (isset($data['action'])) {
        $event_data = json_encode(['action' => sanitize_text_field($data['action'])]);
        $update_data['event_data'] = $event_data;
    }

    if (empty($update_data)) {
        return ['id' => $id];
    }

    $wpdb->update($table, $update_data, ['id' => $id, 'event_type' => 'presence']);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return ['id' => $id];
}

/**
 * Delete a presence event
 */
function delete_presence($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_events';

    $result = $wpdb->delete($table, ['id' => $id, 'event_type' => 'presence']);

    if ($result === false) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return true;
}
