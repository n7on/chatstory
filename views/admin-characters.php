<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap chatstory-admin">
    <h1><?php _e( 'Manage Characters', 'chatstory' ); ?></h1>

    <div class="chatstory-content">
        <div class="chatstory-list">
            <h2><?php _e( 'Characters', 'chatstory' ); ?></h2>
            <div class="chatstory-actions">
                <button class="button button-primary" id="add-character-btn">
                    <?php _e( 'Add New Character', 'chatstory' ); ?>
                </button>
                <button class="button" id="import-characters-btn">
                    <?php _e( 'Import from JSON', 'chatstory' ); ?>
                </button>
            </div>
            <table class="wp-list-table widefat fixed striped" id="characters-table">
                <thead>
                    <tr>
                        <th><?php _e( 'Name', 'chatstory' ); ?></th>
                        <th><?php _e( 'Role', 'chatstory' ); ?></th>
                        <th><?php _e( 'Avatar', 'chatstory' ); ?></th>
                        <th><?php _e( 'Actions', 'chatstory' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="no-items">
                        <td colspan="4"><?php _e( 'Loading...', 'chatstory' ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="chatstory-form" id="character-form" style="display: none;">
            <h2 id="form-title"><?php _e( 'Add Character', 'chatstory' ); ?></h2>
            <form id="character-edit-form">
                <input type="hidden" id="character-id" value="0">

                <table class="form-table">
                    <tr>
                        <th><label for="character-name"><?php _e( 'Name', 'chatstory' ); ?> *</label></th>
                        <td><input type="text" id="character-name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="character-slug"><?php _e( 'Slug', 'chatstory' ); ?></label></th>
                        <td>
                            <input type="text" id="character-slug" class="regular-text" placeholder="auto-generated">
                            <p class="description"><?php _e( 'Unique identifier for importing chats. Leave empty to auto-generate from name.', 'chatstory' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="character-role"><?php _e( 'Role', 'chatstory' ); ?></label></th>
                        <td><input type="text" id="character-role" class="regular-text" placeholder="e.g., CEO, Developer, Designer"></td>
                    </tr>
                    <tr>
                        <th><label for="character-avatar"><?php _e( 'Avatar', 'chatstory' ); ?></label></th>
                        <td>
                            <div class="character-avatar-upload">
                                <input type="hidden" id="character-avatar" value="">
                                <div id="character-avatar-preview" style="margin-bottom: 10px; display: none;">
                                    <img src="" style="max-width: 150px; height: auto; border-radius: 8px; border: 2px solid #ddd;">
                                </div>
                                <button type="button" class="button" id="character-avatar-upload-btn">
                                    <?php _e( 'Select Image', 'chatstory' ); ?>
                                </button>
                                <button type="button" class="button" id="character-avatar-remove-btn" style="display: none;">
                                    <?php _e( 'Remove Image', 'chatstory' ); ?>
                                </button>
                                <p class="description"><?php _e( 'Upload or select an image from the media library', 'chatstory' ); ?></p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="character-traits"><?php _e( 'Character Traits', 'chatstory' ); ?></label></th>
                        <td>
                            <textarea id="character-traits" rows="5" class="large-text" placeholder="<?php _e( 'Describe the character: humorous, professional, technical, etc.', 'chatstory' ); ?>"></textarea>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e( 'Save Character', 'chatstory' ); ?></button>
                    <button type="button" class="button" id="cancel-character-btn"><?php _e( 'Cancel', 'chatstory' ); ?></button>
                </p>
            </form>
        </div>

        <div class="chatstory-modal" id="import-characters-modal" style="display: none;">
            <div class="chatstory-modal-content">
                <span class="chatstory-modal-close">&times;</span>
                <h2><?php _e("Import Characters from JSON", "chatstory"); ?></h2>
                <form id="import-characters-form">
                    <p><?php _e(
                        "Paste your JSON data below:",
                        "chatstory",
                    ); ?></p>
                    <textarea id="import-characters-data" rows="15" class="large-text code" required></textarea>

                    <details>
                        <summary><?php _e(
                            "JSON Format Example",
                            "chatstory",
                        ); ?></summary>
                        <pre class="code">[
  {
    "name": "Sarah Chen",
    "slug": "sarah-chen",
    "role": "CEO",
    "avatar": "https://example.com/avatar.jpg",
    "character_traits": "Visionary leader, strategic thinker"
  },
  {
    "name": "Mike Rodriguez",
    "slug": "mike-rodriguez",
    "role": "Developer",
    "avatar": "",
    "character_traits": "Technical expert, problem solver"
  }
]</pre>
                    </details>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e(
                            "Import",
                            "chatstory",
                        ); ?></button>
                        <button type="button" class="button chatstory-modal-close"><?php _e(
                            "Cancel",
                            "chatstory",
                        ); ?></button>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>
