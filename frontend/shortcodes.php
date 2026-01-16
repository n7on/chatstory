<?php
/**
 * Shortcodes
 *
 * Handles frontend shortcode rendering.
 */

namespace ChatStory\Frontend;

/**
 * Render chat shortcode
 *
 * Usage: [chatstory id="123"]
 */
function render_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $chat_id = intval($atts['id']);

    if ($chat_id === 0) {
        return '<p>' . __('No chat ID specified', 'chatstory') . '</p>';
    }

    ob_start();
    include plugin_dir_path(CHATSTORY_PLUGIN_FILE) . 'views/frontend-chat.php';
    return ob_get_clean();
}

/**
 * Render recent chats shortcode
 *
 * Usage: [recent_chats limit="5" title="Recent Chat Stories"]
 */
function render_recent_chats_shortcode($atts) {
    $atts = shortcode_atts([
        'limit' => 5,
        'title' => '',
    ], $atts);

    $limit = intval($atts['limit']);
    $title = sanitize_text_field($atts['title']);

    // Get recent published chats
    $chats = get_posts([
        'post_type' => 'chatstory',
        'post_status' => 'publish',
        'numberposts' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    if (empty($chats)) {
        return '';
    }

    ob_start();
    include plugin_dir_path(CHATSTORY_PLUGIN_FILE) . 'views/recent-chats.php';
    return ob_get_clean();
}
