# UI / UX Improvement Notes

# Current UI Structure

The plugin has three main UI surfaces:

1. Settings page: `Settings > Protected Video Add-on`
2. Gutenberg block: `Protected Video / Playlist`
3. `pvp_video` CPT edit screen for individual imported videos

The frontend UI is a responsive grid of protected video players with optional titles, logo overlays, marketing overlays, pagination, and hidden-control styling.

# Main UX Problems

- The plugin manages playlists, but there is no playlist management screen.
- Manual sync exists in PHP as AJAX but has no obvious interface.
- The imported video CPT is exposed directly, which is not ideal for client workflows.
- Global settings are long and dense.
- Per-video metaboxes are large and visually inconsistent.
- No clear "global default vs per-video override" hierarchy.
- No visible import status, last sync time, video count, or errors.
- The block editor preview is only a placeholder/preview card, not the actual playlist state.

# Suggested Admin IA

Recommended admin menu:

- Protected Video Playlists
  - Playlists
  - Imported Videos
  - Global Defaults
  - Tools / Logs

Playlist list columns:

- Playlist name / URL
- YouTube playlist ID
- Import method: API or RSS
- Video count
- Last sync
- Last status
- Shortcode
- Actions: Sync, Edit, View Videos

# Suggested Playlist Screen

Fields:

- Playlist URL or ID
- Label/name
- API key override, optional
- Sync method: Auto / API / RSS
- Columns default
- Items per page
- Sync schedule, if cron is added
- Status panel

Actions:

- Validate URL
- Test fetch
- Sync now
- Clear local videos
- Copy shortcode

# Suggested Imported Video UX

Replace raw CPT-first workflow with playlist-scoped editing:

- List videos within a playlist.
- Show thumbnail, title, YouTube ID, imported date, customized status.
- Bulk actions: resync metadata, reset overlay, reset logo, hide/unhide.
- Preserve customizations on sync.

# Suggested Settings Page Cleanup

Split into tabs:

- Import
- Player Controls
- Logo
- Overlay
- Branding
- Data / Uninstall

Use consistent WordPress fields, descriptions, and section headers. Avoid large inline layouts in PHP where possible.

# Frontend Improvements

- Add lazy loading or click-to-load players for playlist grids.
- Render thumbnail cards first and initialize player only when selected/played.
- Add playlist-level pagination that does not conflict across multiple playlists on one page.
- Avoid global `pvp_page` query var conflicts.
- Improve mobile spacing and title truncation.
- Add accessible labels for pagination and controls.
- Keep overlays within safe bounds on all screen sizes.

# Block Editor Improvements

- Add playlist validation feedback.
- Show fetched playlist title/video count after sync.
- Provide a "Sync now" button for admins.
- Let users choose an existing saved playlist instead of pasting URL repeatedly.
- Clarify that `cache` currently has no effect, or restore actual cache behavior.

# Refactoring Suggestions For UI

- Move settings field rendering into smaller view partials or component functions.
- Remove inline styles from metabox PHP and move them to admin CSS.
- Replace inline `onchange` handlers with delegated JS in `admin/js/admin.js`.
- Normalize field names and meta registration.
- Add reusable UI helpers for time range rows and position pickers.

# Safe UI Changes

- Reorganize settings into sections/tabs while keeping option keys unchanged.
- Add explanatory status panels.
- Fix missing asset reference.
- Add admin notices for sync status.
- Add playlist dashboard reading existing `_pvp_playlist_id` groups.

# Risky UI Changes

- Renaming option keys.
- Renaming meta keys.
- Changing block attribute names.
- Changing frontend wrapper classes used by CSS/JS.
- Removing `pvp_video` UI before an alternative edit workflow exists.

# Feature Expansion Opportunities

- Scheduled auto-sync.
- Playlist source manager.
- Import logs.
- Upsert sync preserving customizations.
- Import video description, duration, published date, playlist position, and channel metadata.
- Hide unavailable/private/deleted videos.
- Search/filter imported videos.
- Per-playlist defaults overriding global defaults.
- Export/import settings.
- REST endpoint for editor previews and sync status.

# Development Notes For Future AI Agents

- Improve UX incrementally after fixing data-loss risks.
- Keep the first UI refactor storage-compatible.
- Do not make frontend rendering depend on admin-only state unless migration is included.
- Favor WordPress-native UI patterns over custom inline controls when possible.
