<?php
/**
 * Shortcodes
 *
 * Handles frontend shortcode rendering.
 */

namespace ChatStory;

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
