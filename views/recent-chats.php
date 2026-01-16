<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="chatstory-recent-chats">
    <?php if (!empty($title)): ?>
        <h3 class="chatstory-recent-title"><?php echo esc_html($title); ?></h3>
    <?php endif; ?>

    <ul class="chatstory-recent-list">
        <?php foreach ($chats as $chat): ?>
            <li>
                <a href="<?php echo esc_url(get_permalink($chat->ID)); ?>">
                    <?php echo esc_html($chat->post_title); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
