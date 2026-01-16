<?php
if (!defined("ABSPATH")) {
    exit();
} ?>

<div class="wrap chatstory-admin">
    <h1><?php _e("Manage Chats", "chatstory"); ?></h1>

    <div class="chatstory-content">
        <div class="chatstory-list">
            <h2><?php _e("Chats", "chatstory"); ?></h2>
            <div class="chatstory-actions">
                <button class="button button-primary" id="add-chat-btn">
                    <?php _e("Add New Chat", "chatstory"); ?>
                </button>
                <button class="button" id="import-json-btn">
                    <?php _e("Import from JSON", "chatstory"); ?>
                </button>
            </div>
            <table class="wp-list-table widefat fixed striped" id="chats-table">
                <thead>
                    <tr>
                        <th><?php _e("Title", "chatstory"); ?></th>
                        <th><?php _e("Status", "chatstory"); ?></th>
                        <th><?php _e("Description", "chatstory"); ?></th>
                        <th><?php _e("Actions", "chatstory"); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="no-items">
                        <td colspan="4"><?php _e(
                            "Loading...",
                            "chatstory",
                        ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="chatstory-form" id="chat-form" style="display: none;">
            <h2 id="chat-form-title"><?php _e("Add Chat", "chatstory"); ?></h2>
            <form id="chat-edit-form">
                <input type="hidden" id="chat-id" value="0">

                <table class="form-table">
                    <tr>
                        <th><label for="chat-title"><?php _e(
                            "Title",
                            "chatstory",
                        ); ?> *</label></th>
                        <td><input type="text" id="chat-title" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="chat-slug"><?php _e(
                            "Slug",
                            "chatstory",
                        ); ?></label></th>
                        <td>
                            <input type="text" id="chat-slug" class="regular-text">
                            <p class="description"><?php _e(
                                "URL-friendly version of the title. Leave empty to auto-generate.",
                                "chatstory",
                            ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="chat-status"><?php _e(
                            "Status",
                            "chatstory",
                        ); ?></label></th>
                        <td>
                            <select id="chat-status" class="regular-text">
                                <option value="draft"><?php _e(
                                    "Draft",
                                    "chatstory",
                                ); ?></option>
                                <option value="published"><?php _e(
                                    "Published",
                                    "chatstory",
                                ); ?></option>
                            </select>
                            <p class="description"><?php _e(
                                "Published chats are visible to all visitors at /chat/slug/",
                                "chatstory",
                            ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="chat-description"><?php _e(
                            "Description",
                            "chatstory",
                        ); ?></label></th>
                        <td>
                            <textarea id="chat-description" rows="3" class="large-text"></textarea>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e(
                        "Save Chat",
                        "chatstory",
                    ); ?></button>
                    <button type="button" class="button" id="cancel-chat-btn"><?php _e(
                        "Cancel",
                        "chatstory",
                    ); ?></button>
                </p>
            </form>

            <div id="messages-section" style="display: none;">
                <hr>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 style="margin: 0;"><?php _e(
                        "Messages & Events",
                        "chatstory",
                    ); ?></h3>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <button class="button" id="add-message-btn"><?php _e(
                        "Add Message",
                        "chatstory",
                    ); ?></button>
                    <button class="button" id="add-presence-btn"><?php _e(
                        "Add Join/Leave Event",
                        "chatstory",
                    ); ?></button>
                </div>
                <div id="messages-list"></div>
            </div>
        </div>

        <div class="chatstory-modal" id="message-modal" style="display: none;">
            <div class="chatstory-modal-content">
                <span class="chatstory-modal-close">&times;</span>
                <h2 id="message-modal-title"><?php _e(
                    "Add Message",
                    "chatstory",
                ); ?></h2>
                <form id="message-edit-form">
                    <input type="hidden" id="message-id" value="0">
                    <input type="hidden" id="message-chat-id" value="0">

                    <table class="form-table">
                        <tr>
                            <th><label for="message-character"><?php _e(
                                "Character",
                                "chatstory",
                            ); ?> *</label></th>
                            <td>
                                <select id="message-character" class="regular-text" required>
                                    <option value=""><?php _e(
                                        "Select character...",
                                        "chatstory",
                                    ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="message-text"><?php _e(
                                "Message",
                                "chatstory",
                            ); ?> *</label></th>
                            <td>
                                <textarea id="message-text" rows="4" class="large-text" required></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="message-timestamp"><?php _e(
                                "Timestamp",
                                "chatstory",
                            ); ?></label></th>
                            <td>
                                <input type="text" id="message-timestamp" class="regular-text" placeholder="e.g., 10:30 AM">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="message-start-time"><?php _e(
                                "Start Time (seconds)",
                                "chatstory",
                            ); ?></label></th>
                            <td>
                                <input type="number" id="message-start-time" class="small-text" value="0" min="0" step="0.5">
                                <p class="description"><?php _e(
                                    "When this message appears in the conversation (seconds from start)",
                                    "chatstory",
                                ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e(
                            "Save Message",
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

        <div class="chatstory-modal" id="import-modal" style="display: none;">
            <div class="chatstory-modal-content">
                <span class="chatstory-modal-close">&times;</span>
                <h2><?php _e("Import from JSON", "chatstory"); ?></h2>
                <form id="import-json-form">
                    <p><?php _e(
                        "Paste your JSON data below:",
                        "chatstory",
                    ); ?></p>
                    <textarea id="import-json-data" rows="15" class="large-text code" required></textarea>

                    <details>
                        <summary><?php _e(
                            "JSON Format Example",
                            "chatstory",
                        ); ?></summary>
                        <pre class="code">{
  "chat": {
    "title": "Project Kickoff",
    "description": "Initial project discussion"
  },
  "messages": [
    {
      "character": "john-doe",
      "message": "Hello team! Ready to start?",
      "timestamp": "10:00 AM",
      "start_time": 0,
      "reactions": [
        {
          "character": "jane-smith",
          "reaction": "👍",
          "start_time": 1.5
        }
      ]
    },
    {
      "character": "jane-smith",
      "message": "Absolutely! Let's do this.",
      "timestamp": "10:01 AM",
      "start_time": 3,
      "reactions": [
        {
          "character": "john-doe",
          "reaction": "🎉",
          "start_time": 5
        }
      ]
    }
  ]
}

Note: Characters must be imported separately before importing chats.</pre>
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

        <div class="chatstory-modal" id="reaction-modal" style="display: none;">
            <div class="chatstory-modal-content">
                <span class="chatstory-modal-close">&times;</span>
                <h2 id="reaction-modal-title"><?php _e(
                    "Add Reaction",
                    "chatstory",
                ); ?></h2>
                <form id="reaction-edit-form">
                    <input type="hidden" id="reaction-id" value="0">
                    <input type="hidden" id="reaction-chat-id" value="0">
                    <input type="hidden" id="reaction-target-event-id" value="0">

                    <table class="form-table">
                        <tr>
                            <th><label for="reaction-character"><?php _e(
                                "Who reacted?",
                                "chatstory",
                            ); ?> *</label></th>
                            <td>
                                <select id="reaction-character" class="regular-text" required>
                                    <option value=""><?php _e(
                                        "Select character...",
                                        "chatstory",
                                    ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="reaction-type"><?php _e(
                                "Reaction Type",
                                "chatstory",
                            ); ?> *</label></th>
                            <td>
                                <select id="reaction-type" class="regular-text" required>
                                    <option value="👍">👍 Thumbs Up</option>
                                    <option value="❤️">❤️ Heart</option>
                                    <option value="😂">😂 Laughing</option>
                                    <option value="😮">😮 Surprised</option>
                                    <option value="😢">😢 Sad</option>
                                    <option value="🎉">🎉 Celebrate</option>
                                    <option value="🔥">🔥 Fire</option>
                                    <option value="👏">👏 Clap</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="reaction-start-time"><?php _e(
                                "Start Time (seconds)",
                                "chatstory",
                            ); ?></label></th>
                            <td>
                                <input type="number" id="reaction-start-time" class="small-text" value="0" min="0" step="0.5">
                                <p class="description"><?php _e(
                                    "When this reaction appears (seconds from start)",
                                    "chatstory",
                                ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e(
                            "Save Reaction",
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

        <div class="chatstory-modal" id="typing-modal" style="display: none;">
            <div class="chatstory-modal-content">
                <span class="chatstory-modal-close">&times;</span>
                <h2 id="typing-modal-title"><?php _e(
                    "Add Typing Event",
                    "chatstory",
                ); ?></h2>
                <form id="typing-edit-form">
                    <input type="hidden" id="typing-id" value="0">
                    <input type="hidden" id="typing-chat-id" value="0">
                    <input type="hidden" id="typing-target-event-id" value="0">
                    <input type="hidden" id="typing-character-id" value="0">

                    <table class="form-table">
                        <tr>
                            <th><label for="typing-duration"><?php _e(
                                "Duration (seconds)",
                                "chatstory",
                            ); ?> *</label></th>
                            <td>
                                <input type="number" id="typing-duration" class="small-text" value="3" min="0.5" step="0.5" required>
                                <p class="description"><?php _e(
                                    "How long before the message the typing indicator starts showing (e.g., 10 means typing starts 10 seconds before the message appears)",
                                    "chatstory",
                                ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e(
                            "Save Typing Event",
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

        <div class="chatstory-modal" id="presence-modal" style="display: none;">
            <div class="chatstory-modal-content">
                <span class="chatstory-modal-close">&times;</span>
                <h2 id="presence-modal-title"><?php _e(
                    "Add Join/Leave Event",
                    "chatstory",
                ); ?></h2>
                <form id="presence-edit-form">
                    <input type="hidden" id="presence-id" value="0">
                    <input type="hidden" id="presence-chat-id" value="0">

                    <table class="form-table">
                        <tr>
                            <th><label for="presence-character"><?php _e(
                                "Character",
                                "chatstory",
                            ); ?> *</label></th>
                            <td>
                                <select id="presence-character" class="regular-text" required>
                                    <option value=""><?php _e(
                                        "Select character...",
                                        "chatstory",
                                    ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="presence-action"><?php _e(
                                "Action",
                                "chatstory",
                            ); ?> *</label></th>
                            <td>
                                <select id="presence-action" class="regular-text" required>
                                    <option value="join"><?php _e(
                                        "Join (character enters the chat)",
                                        "chatstory",
                                    ); ?></option>
                                    <option value="leave"><?php _e(
                                        "Leave (character exits the chat)",
                                        "chatstory",
                                    ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="presence-start-time"><?php _e(
                                "Start Time (seconds)",
                                "chatstory",
                            ); ?> *</label></th>
                            <td>
                                <input type="number" id="presence-start-time" class="small-text" value="0" min="0" step="0.5" required>
                                <p class="description"><?php _e(
                                    "When this join/leave event happens (seconds from start)",
                                    "chatstory",
                                ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e(
                            "Save Event",
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
