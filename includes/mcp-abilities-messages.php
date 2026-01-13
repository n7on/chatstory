<?php
/**
 * MCP Abilities for Message Management
 *
 * Registers WordPress abilities for CRUD operations on ChatStory messages.
 * These abilities are automatically exposed through the MCP adapter.
 * They delegate to the REST API for actual implementation.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_abilities_api_init', function() {
    // Create a helper function to call REST API methods
    $get_rest_api = function() {
        static $api = null;
        if ($api === null) {
            require_once plugin_dir_path(__FILE__) . 'class-chatstory-rest-api.php';
            $api = new ChatStory_REST_API();
        }
        return $api;
    };

    // List messages for a chat
    wp_register_ability('chatstory/list-messages', [
        'label' => __('List Messages', 'chatstory'),
        'description' => __('Retrieve all messages for a specific ChatStory chat', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'chat_id' => [
                    'type' => 'integer',
                    'description' => 'The chat ID',
                ],
            ],
            'required' => ['chat_id'],
        ],
        'output_schema' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'chat_id' => ['type' => 'integer'],
                    'character_id' => ['type' => 'integer'],
                    'character_name' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'timestamp' => ['type' => 'string'],
                    'start_time' => ['type' => 'number'],
                ],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $chat_id = intval($input['chat_id']);
            $request = new WP_REST_Request('GET', '/chatstory/v1/chats/' . $chat_id . '/messages');
            $request->set_url_params(['chat_id' => $chat_id]);
            $response = $api->get_messages($request);
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Get a specific message
    wp_register_ability('chatstory/get-message', [
        'label' => __('Get Message', 'chatstory'),
        'description' => __('Retrieve a specific ChatStory message by ID', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'The message ID',
                ],
            ],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'chat_id' => ['type' => 'integer'],
                'character_id' => ['type' => 'integer'],
                'character_name' => ['type' => 'string'],
                'message' => ['type' => 'string'],
                'timestamp' => ['type' => 'string'],
                'start_time' => ['type' => 'number'],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $id = intval($input['id']);
            $request = new WP_REST_Request('GET', '/chatstory/v1/messages/' . $id);
            $request->set_url_params(['id' => $id]);
            $response = $api->get_message($request);
            if (is_wp_error($response)) {
                return $response;
            }
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Create a new message
    wp_register_ability('chatstory/create-message', [
        'label' => __('Create Message', 'chatstory'),
        'description' => __('Create a new message in a ChatStory chat', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'chat_id' => [
                    'type' => 'integer',
                    'description' => 'Chat ID (required)',
                ],
                'character_id' => [
                    'type' => 'integer',
                    'description' => 'Character ID who sends the message (required)',
                ],
                'message' => [
                    'type' => 'string',
                    'description' => 'Message text (required)',
                ],
                'timestamp' => [
                    'type' => 'string',
                    'description' => 'Display timestamp (e.g., "10:30 AM")',
                ],
                'start_time' => [
                    'type' => 'number',
                    'description' => 'Start time in seconds for playback (defaults to 0)',
                ],
            ],
            'required' => ['chat_id', 'character_id', 'message'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'chat_id' => ['type' => 'integer'],
                'character_id' => ['type' => 'integer'],
                'message' => ['type' => 'string'],
                'timestamp' => ['type' => 'string'],
                'start_time' => ['type' => 'number'],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $chat_id = intval($input['chat_id']);
            $request = new WP_REST_Request('POST', '/chatstory/v1/chats/' . $chat_id . '/messages');
            $request->set_url_params(['chat_id' => $chat_id]);
            $request->set_body_params($input);
            $response = $api->create_message($request);
            if (is_wp_error($response)) {
                return $response;
            }
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Update a message
    wp_register_ability('chatstory/update-message', [
        'label' => __('Update Message', 'chatstory'),
        'description' => __('Update an existing ChatStory message', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Message ID (required)',
                ],
                'character_id' => [
                    'type' => 'integer',
                    'description' => 'Character ID',
                ],
                'message' => [
                    'type' => 'string',
                    'description' => 'Message text',
                ],
                'timestamp' => [
                    'type' => 'string',
                    'description' => 'Display timestamp',
                ],
                'start_time' => [
                    'type' => 'number',
                    'description' => 'Start time in seconds',
                ],
            ],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'chat_id' => ['type' => 'integer'],
                'character_id' => ['type' => 'integer'],
                'message' => ['type' => 'string'],
                'timestamp' => ['type' => 'string'],
                'start_time' => ['type' => 'number'],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $id = intval($input['id']);
            $request = new WP_REST_Request('PUT', '/chatstory/v1/messages/' . $id);
            $request->set_url_params(['id' => $id]);
            $request->set_body_params($input);
            $response = $api->update_message($request);
            if (is_wp_error($response)) {
                return $response;
            }
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Delete a message
    wp_register_ability('chatstory/delete-message', [
        'label' => __('Delete Message', 'chatstory'),
        'description' => __('Delete a ChatStory message by ID', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Message ID',
                ],
            ],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'success' => ['type' => 'boolean'],
                'id' => ['type' => 'integer'],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $id = intval($input['id']);
            $request = new WP_REST_Request('DELETE', '/chatstory/v1/messages/' . $id);
            $request->set_url_params(['id' => $id]);
            $response = $api->delete_message($request);
            if (is_wp_error($response)) {
                return $response;
            }
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);
});
