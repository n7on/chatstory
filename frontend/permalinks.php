<?php
/**
 * Theme Integration
 *
 * Handles theme integration for chat display.
 */

namespace ChatStory\Frontend;

/**
 * Inject chat content into post content
 */
function inject_chat_content($content) {
    if (is_singular('chatstory') && in_the_loop() && is_main_query()) {
        global $post;

        // The post ID IS the chat ID now
        $chat_id = $post->ID;

        // Add description before the chat if it exists
        $description = '';
        if (!empty($post->post_excerpt)) {
            $description = '<div class="chatstory-description-text">' . wpautop($post->post_excerpt) . '</div>';
        }

        // Render the chat using our shortcode
        $chat_content = render_shortcode(['id' => $chat_id]);

        return $description . $chat_content;
    }

    return $content;
}

