# Changelog

All notable changes to TN Sticky Posts are recorded here.

## 1.0.4 - 2026-07-02

- Hardened the plugin-row manual update check so it directly refreshes WordPress update data.
- Added visible admin notices for successful checks, no-update results, and failed GitHub lookups.

## 1.0.3 - 2026-07-02

- Changed the slide transition so the active announcement scrolls out upward and the next announcement scrolls in from the bottom.
- Preserved reduced-motion behavior by showing only the active announcement without animation.

## 1.0.2 - 2026-07-02

- Renamed the visible plugin row name to `TN Sticky Posts`.
- Added `Plugin URI`, `Update URI`, and `Author URI` headers.
- Added a GitHub release updater with `View details`, `GitHub`, and `Check for updates` plugin row support.
- Added native WordPress update transient integration for GitHub releases.

## 1.0.1 - 2026-07-02

- Changed CTA editing to use separate Announcement, Click label, and Click URL fields.
- Updated `%click%` handling to render a separate click label in place of the token.
- Added the click label post meta field to validation, rendering, clearing, and uninstall cleanup.

## 1.0.0 - 2026-07-02

- Added initial Techn plugin scaffold for `tn-sticky-posts`.
- Added Posts -> Sticky management screen for native sticky posts.
- Added announcement validation, token parsing, shortcode rendering, and frontend rotation assets.
