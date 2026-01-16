# Theme Integration Guide

ChatStory uses WordPress's native template system to integrate with any theme (classic or block themes).

## How It Works

When someone visits `/chat/my-chat-slug/` or `/chats/`:

1. ChatStory creates a **virtual post object** with the chat content
2. WordPress renders it using your theme's **page template**
3. The chat content appears in `the_content()` loop
4. Your theme's header, footer, sidebar, and navigation all work normally

## For Theme Developers

### Override Templates (Optional)

Create these files in your theme to customize chat page layouts:

**Single Chat Page:**
```php
// chatstory-single.php or page-chatstory.php
<?php get_header(); ?>

<main>
    <?php while (have_posts()) : the_post(); ?>
        <article>
            <h1><?php the_title(); ?></h1>
            <div><?php the_content(); ?></div>
        </article>
    <?php endwhile; ?>
</main>

<?php get_footer(); ?>
```

**Chat Archive Page:**
```php
// chatstory-archive.php or page-chatstory-archive.php
<?php get_header(); ?>

<main>
    <?php while (have_posts()) : the_post(); ?>
        <h1><?php the_title(); ?></h1>
        <div><?php the_content(); ?></div>
    <?php endwhile; ?>
</main>

<?php get_footer(); ?>
```

### Fallback Behavior

If no override template exists, ChatStory uses your theme's standard templates in this order:

1. `page.php`
2. `singular.php`
3. `index.php`

## Styling

ChatStory automatically loads its frontend CSS on chat pages. You can override styles in your theme:

```css
/* Customize chat cards in archive */
.chatstory-card {
    border: 2px solid your-color;
}

/* Customize description */
.chatstory-description {
    font-family: your-font;
}
```

## Works With

✅ Block themes (Twenty Twenty-Five, Twenty Twenty-Four, etc.)
✅ Classic themes (Twenty Twenty-Two, Twenty Twenty-One, etc.)
✅ Page builders (Elementor, Beaver Builder, etc.)
✅ Custom themes

No special configuration needed!
