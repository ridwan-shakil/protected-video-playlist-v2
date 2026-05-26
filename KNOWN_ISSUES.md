# Known Issues

# Critical / High Risk

1. Resync deletes imported video posts and customizations.

`pvp_save_videos_as_cpt()` deletes all existing `pvp_video` posts for a playlist and reinserts them. This changes post IDs and loses per-video logo, overlay, branding, and control overrides.

2. Frontend page render can perform remote imports.

`pvp_render_grid_from_url()` triggers YouTube API/RSS sync if no local posts exist. A visitor can be the first request that pays the remote API cost and waits for insertion.

3. Admin AJAX sync appears incomplete.

`pvp_ajax_sync_playlist()` expects `pvp_settings['playlists']`, but no current settings UI stores that array. No visible script localizes `pvp_sync_nonce` or triggers this endpoint.

4. Uninstall ignores delete preference.

The settings UI has `delete_on_uninstall`, but `uninstall.php` always deletes `pvp_settings` and all `pvp_video` posts.

# Security Risks

- `pvp_save_video_meta()` lacks `current_user_can()`, autosave, and revision checks.
- Many `$_POST` values are sanitized but not passed through `wp_unslash()`.
- API key is stored in plain options.
- Frontend `postMessage('*')` handling does not validate origin or match messages to a specific iframe.
- Some PHP files in inactive duplicate paths do not guard direct access.
- Dynamic inline styles and CSS should be audited for every field that can store `rgba()` or units.

# Bugs / Broken References

- `includes/core/class-pvp-cpt.php` enqueues `admin/js/wp-color-picker-alpha.min.js`, but the repo contains `admin/js/wp-color-picker-alpha.js`.
- Inactive `public/class-pvp-frontend.php` references missing `public/css/pvp-grid.css`.
- `pvp_transient_keys` is flushed but never populated.
- `pvp_fetch_playlist_videos()` cache helper is not used by the current renderer.
- Block default cache in PHP is `5`, editor default effectively uses `3600`, shortcode default uses `3600`, but the active CPT render path does not use the cache value.
- `theme_color` is read on frontend but no settings field currently saves it.
- Several meta keys are saved/read but not registered, including logo unit/radius/circle variants.

# Data / Import Problems

- No upsert logic.
- No stable sort by YouTube playlist position.
- RSS import only gets feed-limited videos.
- API import only requests `snippet`; no duration, statistics, content details, or availability metadata.
- API response status and error body are not checked robustly.
- No handling for deleted/private videos with placeholder titles.
- No last sync timestamp or sync status storage.
- No playlist-level record except repeated `_pvp_playlist_id` meta.

# Performance Issues

- Large playlists can create many posts synchronously.
- Large grids instantiate many protected player blocks on a single page.
- Meta queries on `_pvp_playlist_id` may be slow at scale.
- Pagination first counts by loading all IDs.
- Inline CSS/JS and duplicated player controls can add page weight.

# UX Weaknesses

- No playlist dashboard.
- No manual sync button visible in current UI.
- No import progress, last synced time, or error log.
- Imported videos are exposed as a generic CPT list, not a playlist-oriented workflow.
- Metabox UI is large, dense, and inconsistent with WordPress component patterns.
- Global settings and per-video overrides are not clearly separated.
- The block preview does not show actual imported playlist videos in editor.

# Coding Quality Issues

- Procedural code in `class-*` files.
- Large functions with mixed responsibilities.
- Inline styles and inline JS attributes in PHP renderers.
- Production `error_log()` debug statements.
- Duplicate frontend file increases maintenance risk.
- No tests or static analysis config.

# Suggested Fix Order

1. Make uninstall honor `delete_on_uninstall`.
2. Add capability/autosave/revision checks to `pvp_save_video_meta()`.
3. Fix missing asset references.
4. Replace destructive import save with upsert.
5. Add playlist management UI and connect manual sync.
6. Move import out of frontend render path.
7. Add import status/error storage.
8. Clean duplicate/inactive files.
9. Split rendering, import, and persistence concerns.

# Risky Areas To Avoid Breaking

- `pvp_render_single_video()` parent block wrapper markup.
- Existing `pvp_settings` keys.
- Existing `pvp_video` meta keys.
- Shortcode/block attribute names.
- Overlay time range JSON format.

# Development Notes For Future AI Agents

- Confirm whether any client data already exists in `pvp_video` before changing import storage.
- If refactoring, keep old function names as wrappers until compatibility is verified.
- Do not remove the CPT without a migration plan.
- Treat YouTube imports as data mutation, not cache-only behavior, because users can customize imported video posts.
