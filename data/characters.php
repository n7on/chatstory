<?php
/**
 * Character Data Layer
 *
 * Business logic for character CRUD operations.
 * No WordPress coupling - pure data functions.
 */

namespace ChatStory\Data;

/**
 * Get all characters
 */
function get_characters() {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_characters';
    return $wpdb->get_results("SELECT * FROM {$table} ORDER BY name ASC");
}

/**
 * Get a single character by ID
 */
function get_character($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_characters';
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$table} WHERE id = %d",
        $id
    ));
}

/**
 * Create a new character
 */
function create_character($data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_characters';

    // Validate required fields
    if (empty($data['name'])) {
        return new \WP_Error('missing_name', 'Character name is required');
    }

    // Auto-generate slug if not provided
    $slug = !empty($data['slug']) ? sanitize_title($data['slug']) : sanitize_title($data['name']);
    $slug = generate_unique_slug($slug);

    // Prepare data
    $insert_data = [
        'name' => sanitize_text_field($data['name']),
        'slug' => $slug,
        'role' => sanitize_text_field($data['role'] ?? ''),
        'avatar' => sanitize_text_field($data['avatar'] ?? ''),
        'character_traits' => sanitize_textarea_field($data['character_traits'] ?? ''),
    ];

    $wpdb->insert($table, $insert_data);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return get_character($wpdb->insert_id);
}

/**
 * Update an existing character
 */
function update_character($id, $data) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_characters';

    // Check if character exists
    $character = get_character($id);
    if (!$character) {
        return new \WP_Error('not_found', 'Character not found');
    }

    // Prepare update data
    $update_data = [];

    if (isset($data['name'])) {
        $update_data['name'] = sanitize_text_field($data['name']);
    }

    if (isset($data['slug'])) {
        $slug = sanitize_title($data['slug']);
        $update_data['slug'] = generate_unique_slug($slug, $id);
    }

    if (isset($data['role'])) {
        $update_data['role'] = sanitize_text_field($data['role']);
    }

    if (isset($data['avatar'])) {
        $update_data['avatar'] = sanitize_text_field($data['avatar']);
    }

    if (isset($data['character_traits'])) {
        $update_data['character_traits'] = sanitize_textarea_field($data['character_traits']);
    }

    if (empty($update_data)) {
        return $character; // Nothing to update
    }

    $wpdb->update($table, $update_data, ['id' => $id]);

    if ($wpdb->last_error) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return get_character($id);
}

/**
 * Delete a character
 */
function delete_character($id) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_characters';

    // Check if character exists
    $character = get_character($id);
    if (!$character) {
        return new \WP_Error('not_found', 'Character not found');
    }

    $result = $wpdb->delete($table, ['id' => $id]);

    if ($result === false) {
        return new \WP_Error('db_error', $wpdb->last_error);
    }

    return true;
}

/**
 * Import multiple characters
 */
function import_characters($characters_data) {
    $imported = 0;
    $skipped = 0;
    $errors = [];

    foreach ($characters_data as $character) {
        if (empty($character['name'])) {
            $skipped++;
            continue;
        }

        $result = create_character($character);

        if (is_wp_error($result)) {
            $errors[] = $result->get_error_message();
            $skipped++;
        } else {
            $imported++;
        }
    }

    return [
        'imported' => $imported,
        'skipped' => $skipped,
        'errors' => $errors,
    ];
}

/**
 * Generate a unique slug for a character
 */
function generate_unique_slug($slug, $exclude_id = 0) {
    global $wpdb;
    $table = $wpdb->prefix . 'chatstory_characters';

    $original_slug = $slug;
    $suffix = 0;

    while (true) {
        $check_slug = $suffix > 0 ? $original_slug . '-' . $suffix : $slug;

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE slug = %s AND id != %d",
            $check_slug,
            $exclude_id
        ));

        if (!$exists) {
            return $check_slug;
        }

        $suffix++;
    }
}
