# Sticky Announcements

Techn WordPress plugin repository for `tn-sticky-posts`.

## Purpose

Sticky Announcements extends native WordPress sticky posts with centrally managed announcement text, click labels, and click URLs.

## Key Features

- Adds Posts -> Sticky in WordPress admin.
- Lists standard posts currently marked sticky using WordPress native sticky-post storage.
- Lets editors save announcement text, click labels, click URLs, clear announcements, or remove sticky flags from one screen.
- Registers `[sticky_announcements]` for frontend announcement output.
- Supports plain announcements and one `%click%` CTA placeholder.
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

The plugin preserves the distinction between a native sticky post and an active sticky announcement. Sticky posts appear on the admin screen immediately, but only valid configured announcements appear in shortcode output. When an announcement contains `%click%`, the separate click label and click URL fields are used to generate the frontend link.

## Future Considerations

- GitHub release updater, if GitHub-based update delivery is required.
- Gutenberg block support.
- Announcement scheduling.
