<?php
/**
 * MCP Abilities for Character Management
 *
 * Registers WordPress abilities for CRUD operations on ChatStory characters.
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

    // List all characters
    wp_register_ability('chatstory/list-characters', [
        'label' => __('List Characters', 'chatstory'),
        'description' => __('Retrieve all ChatStory characters with their details (name, role, avatar, traits)', 'chatstory'),
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
                    'name' => ['type' => 'string'],
                    'slug' => ['type' => 'string'],
                    'role' => ['type' => 'string'],
                    'avatar' => ['type' => 'string'],
                    'character_traits' => ['type' => 'string'],
                    'created_at' => ['type' => 'string'],
                ],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $request = new WP_REST_Request('GET', '/chatstory/v1/characters');
            $response = $api->get_characters($request);
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Get a specific character
    wp_register_ability('chatstory/get-character', [
        'label' => __('Get Character', 'chatstory'),
        'description' => __('Retrieve a specific ChatStory character by ID', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'The character ID',
                ],
            ],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'slug' => ['type' => 'string'],
                'role' => ['type' => 'string'],
                'avatar' => ['type' => 'string'],
                'character_traits' => ['type' => 'string'],
                'created_at' => ['type' => 'string'],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $request = new WP_REST_Request('GET', '/chatstory/v1/characters/' . intval($input['id']));
            $request->set_url_params(['id' => intval($input['id'])]);
            $response = $api->get_character($request);
            if (is_wp_error($response)) {
                return $response;
            }
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Create a new character
    wp_register_ability('chatstory/create-character', [
        'label' => __('Create Character', 'chatstory'),
        'description' => __('Create a new ChatStory character with name, role, avatar, and traits', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Character name (required)',
                ],
                'slug' => [
                    'type' => 'string',
                    'description' => 'Character slug (optional, auto-generated from name if not provided)',
                ],
                'role' => [
                    'type' => 'string',
                    'description' => 'Character role/job title',
                ],
                'avatar' => [
                    'type' => 'string',
                    'description' => 'Avatar URL',
                ],
                'character_traits' => [
                    'type' => 'string',
                    'description' => 'Character traits/personality description',
                ],
            ],
            'required' => ['name'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'slug' => ['type' => 'string'],
                'role' => ['type' => 'string'],
                'avatar' => ['type' => 'string'],
                'character_traits' => ['type' => 'string'],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $request = new WP_REST_Request('POST', '/chatstory/v1/characters');
            $request->set_body_params($input);
            $response = $api->create_character($request);
            if (is_wp_error($response)) {
                return $response;
            }
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Update a character
    wp_register_ability('chatstory/update-character', [
        'label' => __('Update Character', 'chatstory'),
        'description' => __('Update an existing ChatStory character', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Character ID (required)',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Character name',
                ],
                'slug' => [
                    'type' => 'string',
                    'description' => 'Character slug',
                ],
                'role' => [
                    'type' => 'string',
                    'description' => 'Character role/job title',
                ],
                'avatar' => [
                    'type' => 'string',
                    'description' => 'Avatar URL',
                ],
                'character_traits' => [
                    'type' => 'string',
                    'description' => 'Character traits/personality description',
                ],
            ],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'slug' => ['type' => 'string'],
                'role' => ['type' => 'string'],
                'avatar' => ['type' => 'string'],
                'character_traits' => ['type' => 'string'],
            ],
        ],
        'execute_callback' => function($input) use ($get_rest_api) {
            $api = $get_rest_api();
            $id = intval($input['id']);
            $request = new WP_REST_Request('PUT', '/chatstory/v1/characters/' . $id);
            $request->set_url_params(['id' => $id]);
            $request->set_body_params($input);
            $response = $api->update_character($request);
            if (is_wp_error($response)) {
                return $response;
            }
            return $response->get_data();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Delete a character
    wp_register_ability('chatstory/delete-character', [
        'label' => __('Delete Character', 'chatstory'),
        'description' => __('Delete a ChatStory character by ID', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'description' => 'Character ID',
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
            $request = new WP_REST_Request('DELETE', '/chatstory/v1/characters/' . $id);
            $request->set_url_params(['id' => $id]);
            $response = $api->delete_character($request);
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
