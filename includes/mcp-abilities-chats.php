<?php
/**
 * MCP Abilities for Chat Management
 *
 * Registers WordPress abilities for CRUD operations on ChatStory chats.
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

    // List all chats
    wp_register_ability('chatstory/list-chats', [
        'label' => __('List Chats', 'chatstory'),
        'description' => __('Retrieve all ChatStory chats with their details', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [],
        ],
        'output_schema' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'created_at' => ['type' => 'string'],
                ],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $request = new WP_REST_Request('GET', '/chatstory/v1/chats');
            $response = $api->get_chats($request);
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Get a specific chat with all messages
    wp_register_ability('chatstory/get-chat', [
        'label' => __('Get Chat', 'chatstory'),
        'description' => __('Retrieve a specific ChatStory chat by ID with all its messages', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'The chat ID',
                ],
            ],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'created_at' => ['type' => 'string'],
                'messages' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'character_id' => ['type' => 'integer'],
                            'character_name' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                            'timestamp' => ['type' => 'string'],
                            'start_time' => ['type' => 'number'],
                        ],
                    ],
                ],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $id = intval($input['id']);
            $request = new WP_REST_Request('GET', '/chatstory/v1/chats/' . $id);
            $request->set_url_params(['id' => $id]);
            $response = $api->get_chat($request);
            if (is_wp_error($response)) {
                return $response;
            }
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Create a new chat
    wp_register_ability('chatstory/create-chat', [
        'label' => __('Create Chat', 'chatstory'),
        'description' => __('Create a new ChatStory chat conversation', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'Chat title (required)',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Chat description',
                ],
            ],
            'required' => ['title'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $request = new WP_REST_Request('POST', '/chatstory/v1/chats');
            $request->set_body_params($input);
            $response = $api->create_chat($request);
            if (is_wp_error($response)) {
                return $response;
            }
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Update a chat
    wp_register_ability('chatstory/update-chat', [
        'label' => __('Update Chat', 'chatstory'),
        'description' => __('Update an existing ChatStory chat', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Chat ID (required)',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Chat title',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Chat description',
                ],
            ],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $id = intval($input['id']);
            $request = new WP_REST_Request('PUT', '/chatstory/v1/chats/' . $id);
            $request->set_url_params(['id' => $id]);
            $request->set_body_params($input);
            $response = $api->update_chat($request);
            if (is_wp_error($response)) {
                return $response;
            }
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Delete a chat
    wp_register_ability('chatstory/delete-chat', [
        'label' => __('Delete Chat', 'chatstory'),
        'description' => __('Delete a ChatStory chat and all its messages by ID', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Chat ID',
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
            $request = new WP_REST_Request('DELETE', '/chatstory/v1/chats/' . $id);
            $request->set_url_params(['id' => $id]);
            $response = $api->delete_chat($request);
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
