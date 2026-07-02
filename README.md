# Sticky Announcements

Techn WordPress plugin repository for `tn-sticky-posts`.

## Purpose

Sticky Announcements extends native WordPress sticky posts with centrally managed announcement text and an optional click-token destination URL.

## Key Features

- Adds Posts -> Sticky in WordPress admin.
- Lists standard posts currently marked sticky using WordPress native sticky-post storage.
- Lets editors save, clear, or remove sticky announcements from one screen.
- Registers `[sticky_announcements]` for frontend announcement output.
- Supports plain announcements and one `%click%...%/click%` CTA token.
- Loads frontend CSS and JavaScript only when the shortcode renders valid announcements.

## Folder Structure

```text
tn-sticky-posts/
├── tn-sticky-posts.php
├── readme.txt
├── uninstall.php
├── includes/
├── assets/
└── languages/
```

## Important Notes

The plugin preserves the distinction between a native sticky post and an active sticky announcement. Sticky posts appear on the admin screen immediately, but only valid configured announcements appear in shortcode output.

## Future Considerations

- GitHub release updater, if GitHub-based update delivery is required.
- Gutenberg block support.
- Announcement scheduling.
