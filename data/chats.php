<?php
/**
 * Chat Data Layer
 *
 * Business logic for chat CRUD operations.
 * Chats are stored as WordPress posts with post_type = 'chatstory'
 */

namespace ChatStory\Data;

/**
 * Get all chats
 */
function get_chats() {
    $posts = get_posts([
        'post_type' => 'chatstory',
        'post_status' => 'any',
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    return array_map(function($post) {
        return post_to_chat_object($post);
    }, $posts);
}

/**
 * Get a single chat by ID
 */
function get_chat($id) {
    $post = get_post($id);

    if (!$post || $post->post_type !== 'chatstory') {
        return null;
    }

    return post_to_chat_object($post);
}

/**
 * Convert WP_Post to chat object
 */
function post_to_chat_object($post) {
    return (object) [
        'id' => $post->ID,
        'post_id' => $post->ID, // For backwards compatibility
        'title' => $post->post_title,
        'slug' => $post->post_name,
        'description' => $post->post_excerpt,
        'status' => ($post->post_status === 'publish') ? 'published' : 'draft',
        'created_at' => $post->post_date,
    ];
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
        "SELECT e.id, e.post_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.created_at,
                c.name, c.avatar, c.role
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.post_id = %d AND e.event_type = 'message'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get reaction events
    $reactions = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.post_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.target_event_id, e.created_at,
                c.name
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.post_id = %d AND e.event_type = 'reaction'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get typing events
    $typing_events = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.post_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.target_event_id, e.created_at,
                c.name
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.post_id = %d AND e.event_type = 'typing'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get presence events
    $presence_events = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.post_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.created_at,
                c.name, c.avatar, c.role
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.post_id = %d AND e.event_type = 'presence'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Transform messages
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
    // Validate required fields
    if (empty($data['title'])) {
        return new \WP_Error('missing_title', 'Chat title is required');
    }

    // Map custom status to WordPress post status
    $status = sanitize_text_field($data['status'] ?? 'draft');
    $post_status = ($status === 'published') ? 'publish' : 'draft';

    // Create WordPress post
    $post_data = [
        'post_title' => sanitize_text_field($data['title']),
        'post_name' => !empty($data['slug']) ? sanitize_title($data['slug']) : '',
        'post_content' => '', // Will be populated by shortcode
        'post_excerpt' => sanitize_textarea_field($data['description'] ?? ''),
        'post_status' => $post_status,
        'post_type' => 'chatstory',
    ];

    $post_id = wp_insert_post($post_data, true);

    if (is_wp_error($post_id)) {
        return $post_id;
    }

    return get_chat($post_id);
}

/**
 * Update an existing chat
 */
function update_chat($id, $data) {
    // Check if chat exists
    $chat = get_chat($id);
    if (!$chat) {
        return new \WP_Error('not_found', 'Chat not found');
    }

    // Prepare update data
    $post_update_data = ['ID' => $id];

    if (isset($data['title'])) {
        $post_update_data['post_title'] = sanitize_text_field($data['title']);
    }

    if (isset($data['slug'])) {
        $post_update_data['post_name'] = sanitize_title($data['slug']);
    }

    if (isset($data['description'])) {
        $post_update_data['post_excerpt'] = sanitize_textarea_field($data['description']);
    }

    if (isset($data['status'])) {
        $status = sanitize_text_field($data['status']);
        if (!in_array($status, ['draft', 'published'])) {
            return new \WP_Error('invalid_status', 'Status must be either "draft" or "published"');
        }
        $post_update_data['post_status'] = ($status === 'published') ? 'publish' : 'draft';
    }

    if (count($post_update_data) === 1) {
        return $chat; // Nothing to update
    }

    $result = wp_update_post($post_update_data, true);

    if (is_wp_error($result)) {
        return $result;
    }

    return get_chat($id);
}

/**
 * Delete a chat and all its messages
 */
function delete_chat($id) {
    global $wpdb;
    $table_messages = $wpdb->prefix . 'chatstory_events';

    // Check if chat exists
    $chat = get_chat($id);
    if (!$chat) {
        return new \WP_Error('not_found', 'Chat not found');
    }

    // Delete all events/messages first
    $wpdb->delete($table_messages, ['post_id' => $id]);

    // Delete WordPress post (force delete, skip trash)
    $result = wp_delete_post($id, true);

    if (!$result) {
        return new \WP_Error('delete_failed', 'Failed to delete chat');
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
        "SELECT e.id, e.post_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.created_at,
                c.name, c.avatar, c.role
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.post_id = %d AND e.event_type = 'message'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get reaction events
    $reactions = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.post_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.target_event_id, e.created_at,
                c.name
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.post_id = %d AND e.event_type = 'reaction'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get typing events
    $typing_events = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.post_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.target_event_id, e.created_at,
                c.name
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.post_id = %d AND e.event_type = 'typing'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Get presence events
    $presence_events = $wpdb->get_results($wpdb->prepare(
        "SELECT e.id, e.post_id, e.character_id, e.event_type, e.start_time,
                e.event_data, e.created_at,
                c.name, c.avatar, c.role
        FROM {$table_messages} e
        LEFT JOIN {$table_characters} c ON e.character_id = c.id
        WHERE e.post_id = %d AND e.event_type = 'presence'
        ORDER BY e.start_time ASC",
        $id
    ));

    // Transform messages (simple format for frontend)
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
 * Get a chat by slug
 */
function get_chat_by_slug($slug) {
    $post = get_page_by_path($slug, OBJECT, 'chatstory');

    if (!$post) {
        return null;
    }

    return post_to_chat_object($post);
}

/**
 * Get all published chats
 */
function get_published_chats() {
    $posts = get_posts([
        'post_type' => 'chatstory',
        'post_status' => 'publish',
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    return array_map(function($post) {
        return post_to_chat_object($post);
    }, $posts);
}

/**
 * Get the permalink URL for a chat
 */
function get_chat_permalink($chat) {
    if (is_numeric($chat)) {
        $chat = get_chat($chat);
    }
    if (!$chat) {
        return '';
    }

    return get_permalink($chat->id);
}
