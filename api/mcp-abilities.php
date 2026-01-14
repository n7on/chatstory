<?php
/**
 * MCP Abilities Registration
 *
 * Registers all ChatStory abilities for MCP (Model Context Protocol).
 * These abilities delegate to the data layer for actual implementation.
 */

namespace ChatStory\Api;

use ChatStory\Data;
use ChatStory\Data\Messages;

/**
 * Register all MCP abilities
 */
function register_mcp_abilities() {
    register_character_abilities();
    register_chat_abilities();
    register_message_abilities();
}

/**
 * Register character abilities
 */
function register_character_abilities() {
    // List characters
    wp_register_ability('chatstory/list-characters', [
        'label' => __('List Characters', 'chatstory'),
        'description' => __('Retrieve all ChatStory characters with their details', 'chatstory'),
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
                ],
            ],
        ],
        'execute_callback' => function() {
            return Data\get_characters();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Get character
    wp_register_ability('chatstory/get-character', [
        'label' => __('Get Character', 'chatstory'),
        'description' => __('Retrieve a specific ChatStory character by ID', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'The character ID'],
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
        'execute_callback' => function($input) {
            return Data\get_character($input['id']);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Create character
    wp_register_ability('chatstory/create-character', [
        'label' => __('Create Character', 'chatstory'),
        'description' => __('Create a new ChatStory character', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'Character name (required)'],
                'slug' => ['type' => 'string', 'description' => 'Character slug (optional)'],
                'role' => ['type' => 'string', 'description' => 'Character role/job title'],
                'avatar' => ['type' => 'string', 'description' => 'Avatar URL'],
                'character_traits' => ['type' => 'string', 'description' => 'Character traits/personality'],
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
        'execute_callback' => function($input) {
            return Data\create_character($input);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Update character
    wp_register_ability('chatstory/update-character', [
        'label' => __('Update Character', 'chatstory'),
        'description' => __('Update an existing ChatStory character', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Character ID (required)'],
                'name' => ['type' => 'string', 'description' => 'Character name'],
                'slug' => ['type' => 'string', 'description' => 'Character slug'],
                'role' => ['type' => 'string', 'description' => 'Character role/job title'],
                'avatar' => ['type' => 'string', 'description' => 'Avatar URL'],
                'character_traits' => ['type' => 'string', 'description' => 'Character traits/personality'],
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
        'execute_callback' => function($input) {
            $id = $input['id'];
            unset($input['id']);
            return Data\update_character($id, $input);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Delete character
    wp_register_ability('chatstory/delete-character', [
        'label' => __('Delete Character', 'chatstory'),
        'description' => __('Delete a ChatStory character by ID', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Character ID'],
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
        'execute_callback' => function($input) {
            $result = Data\delete_character($input['id']);
            if (is_wp_error($result)) {
                return $result;
            }
            return ['success' => true, 'id' => $input['id']];
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);
}

/**
 * Register chat abilities
 */
function register_chat_abilities() {
    // List chats
    wp_register_ability('chatstory/list-chats', [
        'label' => __('List Chats', 'chatstory'),
        'description' => __('Retrieve all ChatStory chats', 'chatstory'),
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
        'execute_callback' => function() {
            return Data\get_chats();
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Get chat
    wp_register_ability('chatstory/get-chat', [
        'label' => __('Get Chat', 'chatstory'),
        'description' => __('Retrieve a specific ChatStory chat with all messages', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'The chat ID'],
            ],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'chat' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'created_at' => ['type' => 'string'],
                    ],
                ],
                'messages' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'character_id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'avatar' => ['type' => 'string'],
                            'role' => ['type' => 'string'],
                            'message' => ['type' => 'string'],
                            'timestamp' => ['type' => 'string'],
                            'start_time' => ['type' => 'number'],
                            'reactions' => ['type' => 'array'],
                            'typing_event' => ['type' => ['object', 'null']],
                        ],
                    ],
                ],
                'presence_events' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'character_id' => ['type' => 'integer'],
                            'name' => ['type' => 'string'],
                            'action' => ['type' => 'string'],
                            'start_time' => ['type' => 'number'],
                        ],
                    ],
                ],
            ],
        ],
        'execute_callback' => function($input) {
            return Data\get_chat_with_messages($input['id']);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Create chat
    wp_register_ability('chatstory/create-chat', [
        'label' => __('Create Chat', 'chatstory'),
        'description' => __('Create a new ChatStory chat conversation', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string', 'description' => 'Chat title (required)'],
                'description' => ['type' => 'string', 'description' => 'Chat description'],
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
        'execute_callback' => function($input) {
            return Data\create_chat($input);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Update chat
    wp_register_ability('chatstory/update-chat', [
        'label' => __('Update Chat', 'chatstory'),
        'description' => __('Update an existing ChatStory chat', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Chat ID (required)'],
                'title' => ['type' => 'string', 'description' => 'Chat title'],
                'description' => ['type' => 'string', 'description' => 'Chat description'],
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
        'execute_callback' => function($input) {
            $id = $input['id'];
            unset($input['id']);
            return Data\update_chat($id, $input);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Delete chat
    wp_register_ability('chatstory/delete-chat', [
        'label' => __('Delete Chat', 'chatstory'),
        'description' => __('Delete a ChatStory chat and all its messages', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Chat ID'],
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
        'execute_callback' => function($input) {
            $result = Data\delete_chat($input['id']);
            if (is_wp_error($result)) {
                return $result;
            }
            return ['success' => true, 'id' => $input['id']];
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);
}

/**
 * Register message abilities
 */
function register_message_abilities() {
    // List messages
    wp_register_ability('chatstory/list-messages', [
        'label' => __('List Messages', 'chatstory'),
        'description' => __('Retrieve all messages for a specific chat', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'chat_id' => ['type' => 'integer', 'description' => 'The chat ID'],
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
                    'name' => ['type' => 'string'],
                    'avatar' => ['type' => 'string'],
                    'role' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'timestamp' => ['type' => 'string'],
                    'start_time' => ['type' => 'number'],
                ],
            ],
        ],
        'execute_callback' => function($input) {
            return Messages\get_messages_by_chat($input['chat_id']);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Get message
    wp_register_ability('chatstory/get-message', [
        'label' => __('Get Message', 'chatstory'),
        'description' => __('Retrieve a specific message by ID', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'The message ID'],
            ],
            'required' => ['id'],
        ],
        'output_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer'],
                'chat_id' => ['type' => 'integer'],
                'character_id' => ['type' => 'integer'],
                'name' => ['type' => 'string'],
                'avatar' => ['type' => 'string'],
                'role' => ['type' => 'string'],
                'message' => ['type' => 'string'],
                'timestamp' => ['type' => 'string'],
                'start_time' => ['type' => 'number'],
            ],
        ],
        'execute_callback' => function($input) {
            return Messages\get_message($input['id']);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Create message
    wp_register_ability('chatstory/create-message', [
        'label' => __('Create Message', 'chatstory'),
        'description' => __('Create a new message in a chat', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'chat_id' => ['type' => 'integer', 'description' => 'Chat ID (required)'],
                'character_id' => ['type' => 'integer', 'description' => 'Character ID (required)'],
                'message' => ['type' => 'string', 'description' => 'Message text (required)'],
                'timestamp' => ['type' => 'string', 'description' => 'Display timestamp'],
                'start_time' => ['type' => 'number', 'description' => 'Start time in seconds'],
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
        'execute_callback' => function($input) {
            return Messages\create_message($input);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Update message
    wp_register_ability('chatstory/update-message', [
        'label' => __('Update Message', 'chatstory'),
        'description' => __('Update an existing message', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Message ID (required)'],
                'character_id' => ['type' => 'integer', 'description' => 'Character ID'],
                'message' => ['type' => 'string', 'description' => 'Message text'],
                'timestamp' => ['type' => 'string', 'description' => 'Display timestamp'],
                'start_time' => ['type' => 'number', 'description' => 'Start time in seconds'],
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
        'execute_callback' => function($input) {
            $id = $input['id'];
            unset($input['id']);
            return Messages\update_message($id, $input);
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);

    // Delete message
    wp_register_ability('chatstory/delete-message', [
        'label' => __('Delete Message', 'chatstory'),
        'description' => __('Delete a message by ID', 'chatstory'),
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'id' => ['type' => 'integer', 'description' => 'Message ID'],
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
        'execute_callback' => function($input) {
            $result = Messages\delete_message($input['id']);
            if (is_wp_error($result)) {
                return $result;
            }
            return ['success' => true, 'id' => $input['id']];
        },
        'permission_callback' => function() {
            return current_user_can('manage_options');
        },
    ]);
}
