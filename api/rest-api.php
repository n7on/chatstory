<?php
/**
 * REST API Registration
 *
 * Registers REST API endpoints and delegates to data layer.
 */

namespace ChatStory\Api;

use ChatStory\Data;
use ChatStory\Data\Messages;

/**
 * Register all REST API routes
 */
function register_routes() {
    $namespace = 'chatstory/v1';

    // Character routes
    register_rest_route($namespace, '/characters', [
        [
            'methods' => 'GET',
            'callback' => __NAMESPACE__ . '\\get_characters',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'POST',
            'callback' => __NAMESPACE__ . '\\create_character',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
    ]);

    register_rest_route($namespace, '/characters/(?P<id>\d+)', [
        [
            'methods' => 'GET',
            'callback' => __NAMESPACE__ . '\\get_character',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'PUT',
            'callback' => __NAMESPACE__ . '\\update_character',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'DELETE',
            'callback' => __NAMESPACE__ . '\\delete_character',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
    ]);

    register_rest_route($namespace, '/characters/import', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\\import_characters',
        'permission_callback' => __NAMESPACE__ . '\\check_permission',
    ]);

    // Chat routes
    register_rest_route($namespace, '/chats', [
        [
            'methods' => 'GET',
            'callback' => __NAMESPACE__ . '\\get_chats',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'POST',
            'callback' => __NAMESPACE__ . '\\create_chat',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
    ]);

    register_rest_route($namespace, '/chats/(?P<id>\d+)', [
        [
            'methods' => 'GET',
            'callback' => __NAMESPACE__ . '\\get_chat',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'PUT',
            'callback' => __NAMESPACE__ . '\\update_chat',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'DELETE',
            'callback' => __NAMESPACE__ . '\\delete_chat',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
    ]);

    register_rest_route($namespace, '/chats/(?P<id>\d+)/frontend', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\\get_chat_frontend',
        'permission_callback' => '__return_true', // Public endpoint
    ]);

    register_rest_route($namespace, '/chats/published', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\\get_published_chats_handler',
        'permission_callback' => '__return_true', // Public endpoint
    ]);

    register_rest_route($namespace, '/chats/slug/(?P<slug>[a-z0-9-]+)', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\\get_chat_by_slug_handler',
        'permission_callback' => '__return_true', // Public endpoint
    ]);

    // Message routes
    register_rest_route($namespace, '/chats/(?P<chat_id>\d+)/messages', [
        [
            'methods' => 'GET',
            'callback' => __NAMESPACE__ . '\\get_messages',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'POST',
            'callback' => __NAMESPACE__ . '\\create_message',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
    ]);

    register_rest_route($namespace, '/messages/(?P<id>\d+)', [
        [
            'methods' => 'GET',
            'callback' => __NAMESPACE__ . '\\get_message',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'PUT',
            'callback' => __NAMESPACE__ . '\\update_message',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'DELETE',
            'callback' => __NAMESPACE__ . '\\delete_message',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
    ]);

    // Reaction routes
    register_rest_route($namespace, '/messages/(?P<message_id>\d+)/reactions', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\\create_reaction',
        'permission_callback' => __NAMESPACE__ . '\\check_permission',
    ]);

    register_rest_route($namespace, '/reactions/(?P<id>\d+)', [
        [
            'methods' => 'PUT',
            'callback' => __NAMESPACE__ . '\\update_reaction',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'DELETE',
            'callback' => __NAMESPACE__ . '\\delete_reaction',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
    ]);

    // Typing routes
    register_rest_route($namespace, '/messages/(?P<message_id>\d+)/typing', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\\create_typing',
        'permission_callback' => __NAMESPACE__ . '\\check_permission',
    ]);

    register_rest_route($namespace, '/typing/(?P<id>\d+)', [
        [
            'methods' => 'PUT',
            'callback' => __NAMESPACE__ . '\\update_typing',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'DELETE',
            'callback' => __NAMESPACE__ . '\\delete_typing',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
    ]);

    // Presence routes
    register_rest_route($namespace, '/chats/(?P<chat_id>\d+)/presence', [
        'methods' => 'POST',
        'callback' => __NAMESPACE__ . '\\create_presence',
        'permission_callback' => __NAMESPACE__ . '\\check_permission',
    ]);

    register_rest_route($namespace, '/presence/(?P<id>\d+)', [
        [
            'methods' => 'PUT',
            'callback' => __NAMESPACE__ . '\\update_presence',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
        [
            'methods' => 'DELETE',
            'callback' => __NAMESPACE__ . '\\delete_presence',
            'permission_callback' => __NAMESPACE__ . '\\check_permission',
        ],
    ]);
}

/**
 * Permission callback - checks if user can manage options
 */
function check_permission() {
    return current_user_can('manage_options');
}

// === CHARACTER HANDLERS ===

function get_characters() {
    return rest_ensure_response(Data\get_characters());
}

function get_character($request) {
    $result = Data\get_character($request['id']);
    if (!$result) {
        return new \WP_Error('not_found', 'Character not found', ['status' => 404]);
    }
    return rest_ensure_response($result);
}

function create_character($request) {
    $result = Data\create_character($request->get_json_params());
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function update_character($request) {
    $result = Data\update_character($request['id'], $request->get_json_params());
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function delete_character($request) {
    $result = Data\delete_character($request['id']);
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response(['success' => true, 'id' => $request['id']]);
}

function import_characters($request) {
    $data = $request->get_json_params();
    if (empty($data['characters']) || !is_array($data['characters'])) {
        return new \WP_Error('invalid_data', 'characters array is required', ['status' => 400]);
    }

    $result = Data\import_characters($data['characters']);
    return rest_ensure_response($result);
}

// === CHAT HANDLERS ===

function get_chats() {
    return rest_ensure_response(Data\get_chats());
}

function get_chat($request) {
    $result = Data\get_chat_with_messages($request['id']);
    if (!$result) {
        return new \WP_Error('not_found', 'Chat not found', ['status' => 404]);
    }
    return rest_ensure_response($result);
}

function create_chat($request) {
    $result = Data\create_chat($request->get_json_params());
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function update_chat($request) {
    $result = Data\update_chat($request['id'], $request->get_json_params());
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function delete_chat($request) {
    $result = Data\delete_chat($request['id']);
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response(['success' => true, 'id' => $request['id']]);
}

function get_chat_frontend($request) {
    $result = Data\get_chat_for_frontend($request['id']);
    if (!$result) {
        return new \WP_Error('not_found', 'Chat not found', ['status' => 404]);
    }
    return rest_ensure_response($result);
}

// === MESSAGE HANDLERS ===

function get_messages($request) {
    return rest_ensure_response(Messages\get_messages_by_chat($request['chat_id']));
}

function get_message($request) {
    $result = Messages\get_message($request['id']);
    if (!$result) {
        return new \WP_Error('not_found', 'Message not found', ['status' => 404]);
    }
    return rest_ensure_response($result);
}

function create_message($request) {
    $data = $request->get_json_params();
    $data['chat_id'] = $request['chat_id'];

    $result = Messages\create_message($data);
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function update_message($request) {
    $result = Messages\update_message($request['id'], $request->get_json_params());
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function delete_message($request) {
    $result = Messages\delete_message($request['id']);
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response(['success' => true, 'id' => $request['id']]);
}

// === REACTION HANDLERS ===

function create_reaction($request) {
    $data = $request->get_json_params();
    $data['target_event_id'] = $request['message_id'];

    $result = Messages\create_reaction($data);
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function update_reaction($request) {
    $result = Messages\update_reaction($request['id'], $request->get_json_params());
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function delete_reaction($request) {
    $result = Messages\delete_reaction($request['id']);
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response(['success' => true, 'id' => $request['id']]);
}

// === TYPING HANDLERS ===

function create_typing($request) {
    $data = $request->get_json_params();
    $data['target_event_id'] = $request['message_id'];

    $result = Messages\create_typing($data);
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function update_typing($request) {
    $result = Messages\update_typing($request['id'], $request->get_json_params());
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function delete_typing($request) {
    $result = Messages\delete_typing($request['id']);
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response(['success' => true, 'id' => $request['id']]);
}

// === PRESENCE HANDLERS ===

function create_presence($request) {
    $data = $request->get_json_params();
    $data['chat_id'] = $request['chat_id'];

    $result = Messages\create_presence($data);
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function update_presence($request) {
    $result = Messages\update_presence($request['id'], $request->get_json_params());
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response($result);
}

function delete_presence($request) {
    $result = Messages\delete_presence($request['id']);
    if (is_wp_error($result)) {
        return $result;
    }
    return rest_ensure_response(['success' => true, 'id' => $request['id']]);
}

// === PUBLIC CHAT HANDLERS ===

function get_published_chats_handler() {
    return rest_ensure_response(Data\get_published_chats());
}

function get_chat_by_slug_handler($request) {
    $chat = Data\get_chat_by_slug($request['slug']);
    if (!$chat) {
        return new \WP_Error('not_found', 'Chat not found', ['status' => 404]);
    }

    // Only return published chats or if user has permission
    if ($chat->status !== 'published' && !current_user_can('manage_options')) {
        return new \WP_Error('not_published', 'Chat is not published', ['status' => 404]);
    }

    $result = Data\get_chat_for_frontend($chat->id);
    return rest_ensure_response($result);
}
