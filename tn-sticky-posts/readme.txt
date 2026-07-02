# Sticky Announcements

Author: Techn
Version: 1.0.0
Status: MVP

## Purpose

Sticky Announcements adds centrally managed announcement text and destination URLs to native WordPress sticky posts.

## Key Features

- Adds a Posts -> Sticky admin screen.
- Lists standard posts marked with the native WordPress sticky flag.
- Stores announcement text and destination URL in post meta.
- Renders active announcements with the `[sticky_announcements]` shortcode.
- Supports one `%click%...%/click%` token pair per announcement.
- Rotates valid announcements with lightweight vanilla JavaScript.

## Folder Structure

- `tn-sticky-posts.php` - plugin bootstrap.
- `includes/` - plugin classes for admin, validation, shortcode, assets, and meta.
- `assets/css/` - admin and frontend styles.
- `assets/js/` - frontend rotation script.
- `languages/` - translation files.

## Important Notes

The plugin does not create a custom sticky state. A post appears in Posts -> Sticky only when it is marked sticky in the normal WordPress post editor.

A post appears in shortcode output only when it is published, sticky, has non-empty valid announcement text, and passes CTA token validation.

## Future Considerations

- Gutenberg block support.
- Scheduling.
- Multiple CTA links.
- Custom post type support.
