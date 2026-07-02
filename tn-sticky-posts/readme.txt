# TN Sticky Posts

Author: Techn
Version: 1.0.2
Status: MVP

## Purpose

TN Sticky Posts adds centrally managed announcement text, click labels, and click URLs to native WordPress sticky posts.

## Key Features

- Adds a Posts -> Sticky admin screen.
- Lists standard posts marked with the native WordPress sticky flag.
- Stores announcement text, click label, and click URL in post meta.
- Renders active announcements with the `[sticky_announcements]` shortcode.
- Supports one `%click%` placeholder per announcement.
- Rotates valid announcements with lightweight vanilla JavaScript.
- Supports GitHub release updates from the WordPress Plugins screen.

## Folder Structure

- `tn-sticky-posts.php` - plugin bootstrap.
- `includes/` - plugin classes for admin, validation, shortcode, assets, and meta.
- `assets/css/` - admin and frontend styles.
- `assets/js/` - frontend rotation script.
- `languages/` - translation files.

## Important Notes

The plugin does not create a custom sticky state. A post appears in Posts -> Sticky only when it is marked sticky in the normal WordPress post editor.

A post appears in shortcode output only when it is published, sticky, has non-empty valid announcement text, and passes CTA token validation. When `%click%` appears in the announcement, the click label and click URL fields are required.

## Future Considerations

- Gutenberg block support.
- Scheduling.
- Multiple CTA links.
- Custom post type support.
