<?php
/**
 * Preview Template
 * This template displays a chat preview in the context of the WordPress theme
 */

if (!defined("ABSPATH")) {
    exit();
}

// Suppress deprecation warnings for WordPress core theme.json compatibility
error_reporting(E_ALL & ~E_DEPRECATED);

$chat_id = intval($_GET["chat_id"]);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo("charset"); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body class="chatstory-preview <?php echo esc_attr(join(' ', get_body_class())); ?>">
<?php wp_body_open(); ?>

<div id="page" class="site">
    <div class="chatstory-preview-badge" style="background: #f0f0f1; border-left: 4px solid #2271b1; padding: 15px 20px; margin: 0; text-align: center;">
        <strong><?php _e("Preview Mode", "chatstory"); ?></strong> -
        <?php _e(
            "This is how your chat will appear to visitors",
            "chatstory",
        ); ?>
    </div>

    <div id="content" class="site-content" style="padding: 40px 20px; max-width: 1200px; margin: 0 auto;">
        <?php echo do_shortcode('[chatstory id="' . $chat_id . '"]'); ?>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
