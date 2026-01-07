<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="chatstory-container" data-chat-id="<?php echo esc_attr( $chat_id ); ?>">
    <div class="chatstory-header">
        <div class="chatstory-title"></div>
        <div class="chatstory-description"></div>
    </div>
    <div class="chatstory-messages">
        <div class="chatstory-loading"><?php _e( 'Loading chat...', 'chatstory' ); ?></div>
    </div>
</div>
