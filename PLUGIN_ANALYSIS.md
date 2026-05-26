# Plugin Overview

Protected Video - Playlist Add-on extends a required parent plugin named `protected-video`. Its main job is to render YouTube single videos and playlists through the parent plugin's protected video block/player, then layer add-on features such as playlist grids, logos, marketing overlays, per-video settings, control hiding, and branding colors.

The plugin is versioned as `2.0.0` in `protected-video-playlist.php`. It is mostly procedural PHP despite many files being named `class-*`. There are no namespaces, autoloaders, Composer dependencies, custom database tables, REST routes, or cron jobs.

# Folder Structure

| Path | Purpose |
| --- | --- |
| `protected-video-playlist.php` | Main plugin file, constants, parent plugin check, conditional includes. |
| `admin/class-pvp-admin.php` | Settings page, settings registration, sanitization, cache flush form. |
| `includes/core/class-pvp-block.php` | Registers server-rendered Gutenberg block `protected-video-playlist/playlist`. |
| `includes/core/class-pvp-render.php` | Renders protected single videos, playlist grids, pagination, and settings field helpers. |
| `includes/core/class-pvp-cpt.php` | Registers `pvp_video` CPT, post meta, metabox UI, and save handlers. |
| `includes/core/class-pvp-sync.php` | YouTube API/RSS sync and admin AJAX sync handler. |
| `includes/api/class-pvp-youtube.php` | RSS-only playlist fetcher with transient cache. Appears mostly legacy/unused by current render flow. |
| `includes/public/class-pvp-frontend.php` | Frontend asset enqueue and inline JS/CSS injection. This is the loaded frontend class. |
| `public/class-pvp-frontend.php` | Duplicate older frontend file, not loaded by bootstrap. References missing CSS. |
| `fallback/class-pvp-functions.php` | URL/ID extraction and color sanitizer helpers. |
| `fallback/class-pvp-shortcode.php` | `[protected_playlist]` shortcode. |
| `assets/js/editor.js`, `assets/css/editor.css` | Gutenberg editor block UI. |
| `public/js/pvp-frontend.js`, `public/css/pvp-frontend.css` | Frontend overlay/control behavior and grid styles. |
| `admin/js/admin.js`, `admin/css/admin.css` | Settings/metabox admin helpers. |
| `uninstall.php` | Deletes plugin option and all `pvp_video` posts unconditionally. |

# Bootstrap / Loading Process

1. WordPress loads `protected-video-playlist.php`.
2. Constants are defined: `PVP_VERSION`, `PVP_PLUGIN_DIR`, `PVP_PLUGIN_URL`.
3. Empty activation/deactivation callbacks are registered.
4. On `plugins_loaded`, `pvp_init_addon()` checks `PROTECTED_VIDEO_VERSION`.
5. If the parent plugin is missing, only an admin notice is registered.
6. If present, shared files are required:
   - helpers
   - CPT/meta
   - sync/import
   - YouTube RSS helper
   - renderer
   - block registration
7. Admin-only requests load `admin/class-pvp-admin.php`.
8. Frontend-only requests load `includes/public/class-pvp-frontend.php` and shortcode support.

# Execution Flow

## Block Rendering

1. `pvp_register_block()` registers `protected-video-playlist/playlist`.
2. Editor users enter a URL in `assets/js/editor.js`.
3. Frontend render calls `pvp_render_block($attributes)`.
4. `pvp_is_playlist_url()` decides if the URL is a playlist.
5. Single video URLs call `pvp_render_single_video()`.
6. Playlist URLs call `pvp_render_grid_from_url()`.
7. Playlist rendering checks for existing `pvp_video` posts for the playlist ID.
8. If none exist, it syncs via YouTube Data API if a global API key exists, otherwise via RSS.
9. CPT posts are queried, paginated, and rendered into protected video wrappers.

## Shortcode Rendering

`[protected_playlist url="..." columns="3" cache="3600"]` is converted into the same attribute shape as the block and passed to `pvp_render_block()`.

# Important Hooks

| Hook | Callback | Purpose |
| --- | --- | --- |
| `plugins_loaded` | `pvp_init_addon` | Parent check and include loading. |
| `admin_notices` | `pvp_missing_parent_notice` | Missing parent plugin notice. |
| `init` | `pvp_register_video_cpt` | Register `pvp_video`. |
| `init` | `pvp_register_video_meta` | Register `pvp_video` meta keys. |
| `init` | `pvp_register_block` | Register dynamic block. |
| `admin_menu` | `pvp_add_submenu_page` | Add settings submenu under Settings. |
| `admin_init` | `pvp_register_settings` | Register settings sections/fields. |
| `admin_init` | `pvp_handle_cache_flush` | Process cache flush form. |
| `admin_enqueue_scripts` | `pvp_admin_assets` | Load settings page assets. |
| `admin_enqueue_scripts` | `pvp_enqueue_cpt_assets` | Load CPT edit screen assets. |
| `add_meta_boxes` | `pvp_add_video_meta_boxes` | Main video settings metabox. |
| `add_meta_boxes` | `pvp_add_branding_meta_box` | Branding metabox. |
| `add_meta_boxes` | `pvp_add_controls_meta_box` | Player controls metabox. |
| `save_post_pvp_video` | `pvp_save_video_meta` | Save per-video settings. |
| `wp_ajax_pvp_sync_playlist` | `pvp_ajax_sync_playlist` | Admin AJAX playlist sync. |
| `wp_enqueue_scripts` | `pvp_enqueue_frontend_styles` | Conditional frontend assets. |

# Admin Pages and Settings

The plugin adds `Settings > Protected Video Add-on`. Settings are stored in a single option: `pvp_settings`.

Main settings include:

- `youtube_api_key`
- `delete_on_uninstall`
- `default_columns`
- `logo`, `logo_url`
- logo width/unit/opacity/position/radius/circle
- `overlay_text`
- overlay display triggers and time ranges
- overlay dimensions/position/background
- control-disabling flags
- branding color fields

Important mismatch: `pvp_ajax_sync_playlist()` expects `$options['playlists']`, but no playlist settings field currently creates or saves `playlists`. This makes the admin AJAX sync endpoint effectively unusable unless another omitted component writes that option.

# Database Structure

No custom tables are created. Storage uses WordPress core tables:

- `wp_options`: `pvp_settings`, possibly `pvp_transient_keys` but the key registry is never populated.
- `wp_posts`: imported videos as `pvp_video` posts.
- `wp_postmeta`: video URL, playlist ID, thumbnail URL, overlays, logo, branding, and controls.
- `wp_options` transient rows: RSS cache from `pvp_fetch_playlist_videos()`.

# Custom Post Type

`pvp_video`:

- `public`: false
- `publicly_queryable`: false
- `show_ui`: true
- `show_in_menu`: true
- supports only `title`
- used as the local imported video store for playlists

# Important Meta Keys

Import-related:

- `_pvp_video_id`
- `_pvp_video_url`
- `_pvp_playlist_id`
- `_pvp_thumbnail_url`

Display/config-related:

- `_pvp_video_logo`
- `_pvp_video_logo_url`
- `_pvp_video_logo_width`
- `_pvp_video_logo_unit` (used but not registered)
- `_pvp_video_logo_opacity`
- `_pvp_video_logo_position`
- `_pvp_video_logo_radius` (used but not registered)
- `_pvp_video_logo_radius_unit` (used but not registered)
- `_pvp_video_logo_circle` (used but not registered)
- `_pvp_video_logo_active`
- `_pvp_video_overlay_text`
- `_pvp_video_overlay_start`
- `_pvp_video_overlay_end`
- `_pvp_video_overlay_height`
- `_pvp_video_overlay_width`
- `_pvp_video_overlay_x`
- `_pvp_video_overlay_y`
- `_pvp_video_overlay_padding`
- `_pvp_video_overlay_bg`
- `_pvp_video_overlay_on_pause`
- `_pvp_video_overlay_on_end`
- `_pvp_video_overlay_active`
- `_pvp_video_overlay_time_ranges`
- `_pvp_override_disable_volume`
- `_pvp_override_disable_playbutton`
- `_pvp_override_disable_fullscreen`
- `_pvp_override_disable_controls`
- `_pvp_override_disable_autoplay`
- `_pvp_controls_bg_color`
- `_pvp_controls_color`
- `_pvp_play_btn_bg_color`
- `_pvp_play_btn_color`

# AJAX / REST Endpoints

AJAX:

- `wp_ajax_pvp_sync_playlist`
  - Callback: `pvp_ajax_sync_playlist()`
  - Nonce: `pvp_sync_nonce`
  - Capability: `manage_options`
  - Status: likely incomplete because no UI localizes/uses this nonce and no settings UI stores `pvp_settings['playlists']`.

REST:

- No REST API routes found.

# Cron / Background Jobs

No cron events, async queues, or background jobs were found. Playlist sync runs inline during frontend render when no CPT posts exist, or via the unfinished admin AJAX endpoint.

# Third-Party Integrations

- Parent plugin `protected-video`, required by constant `PROTECTED_VIDEO_VERSION`.
- YouTube Data API v3 endpoint: `https://www.googleapis.com/youtube/v3/playlistItems`.
- YouTube RSS feed endpoint: `https://www.youtube.com/feeds/videos.xml?playlist_id=...`.
- WordPress media uploader.
- WordPress color picker plus bundled `wp-color-picker-alpha.js`.
- Parent plugin frontend player assets, likely Plyr-based.

# Security Implementation

Good:

- Direct file access blocked in PHP files with `ABSPATH` checks, except some frontend PHP files lack this in duplicates.
- Settings API handles options nonce.
- Cache flush has nonce and `manage_options`.
- Admin AJAX sync has nonce and `manage_options`.
- Output is generally escaped with `esc_attr`, `esc_url`, `esc_html`, and `wp_kses_post`.
- RSS XML parsing disables external entity loading on PHP < 8 and uses internal errors.

Risks:

- `pvp_save_video_meta()` does not check autosave, revision saves, or `current_user_can()` before updating meta.
- Many `$_POST` reads are not `wp_unslash()`ed before sanitization.
- Several inline styles and dynamic CSS values depend on custom sanitizers; some values are used raw after retrieval.
- API key is stored plainly in `wp_options`.
- `uninstall.php` deletes all data regardless of the `delete_on_uninstall` setting.
- Frontend JS uses `postMessage('*')` and global message listeners without verifying `event.origin` or iframe association.
- Inline frontend JS injects `overlay.innerHTML` from admin-provided content; it is sanitized before JSON encoding, but DOM injection still deserves careful review.

# Asset Loading

Admin settings page:

- `admin/css/admin.css`
- `admin/js/wp-color-picker-alpha.js`
- `admin/js/admin.js`
- media library and color picker

CPT edit screen:

- media library
- `admin/js/wp-color-picker-alpha.min.js` is referenced, but only `wp-color-picker-alpha.js` exists.
- `admin/js/admin.js`

Frontend:

- Parent plugin style handle `protected-video-protected-video-style`
- Parent plugin script handle `protected-video-protected-video-view-script`
- `public/css/pvp-frontend.css`
- `public/js/pvp-frontend.js`

Potential bug: unused duplicate `public/class-pvp-frontend.php` references `public/css/pvp-grid.css`, which does not exist. The loaded file references the correct `pvp-frontend.css`.

# Template Structure

There are no separate template files. Markup is generated directly in PHP functions in `includes/core/class-pvp-render.php` and metabox markup is inline in `includes/core/class-pvp-cpt.php`.

# OOP Patterns / Classes

There are no actual PHP classes in the loaded code. The architecture is procedural functions grouped into files. This increases global namespace collision risk and makes unit testing/refactoring harder.

# Dependencies / Libraries / Frameworks

No Composer, npm, build pipeline, package manifests, or external PHP libraries are present. Gutenberg code is plain ES5 using `window.wp` globals. The player depends on the parent Protected Video plugin's registered assets and markup contract.

# Coding Quality Issues

- Files named `class-*` contain procedural functions, not classes.
- Long rendering functions mix data lookup, sanitization, business rules, and HTML output.
- Large metabox renderer has extensive inline CSS and inline JS event attributes.
- Duplicate frontend file exists and can confuse maintenance.
- Some registered meta keys do not match saved/read keys.
- The `cache` block/shortcode attribute is no longer honored in the CPT-backed render path.
- Debug `error_log()` calls remain in production paths.
- Settings page says cache is one hour, but the main render path imports to CPT and does not use the RSS transient cache helper.

# Performance Bottlenecks

- First frontend page view for a playlist can trigger a full remote YouTube sync.
- `pvp_save_videos_as_cpt()` deletes all old posts and recreates all videos on every sync, causing churn and losing per-video customizations.
- Playlist queries use post meta filtering without a custom index.
- `get_posts(... numberposts => -1)` can become expensive for large playlists.
- Each rendered video emits a protected video block wrapper, causing many player instances on one page.
- Inline CSS/JS grows with settings and rendered instances.
- No lazy loading, batch rendering, or background queue for large playlist imports.

# Deprecated / Risky Code

- `libxml_disable_entity_loader()` is deprecated in PHP 8, but guarded by `PHP_VERSION_ID < 80000`.
- `delete_on_uninstall` setting is misleading because uninstall ignores it.
- `pvp_fetch_playlist_videos()` appears legacy and is not used by the current block render path.
- `pvp_transient_keys` is flushed but never populated.
- Duplicate frontend file can cause accidental edits to inactive code.

# UI / UX Structure

Admin UI is split between:

- global Settings page
- `pvp_video` CPT edit screens for per-video logo/overlay/control branding
- Gutenberg block sidebar for URL, columns, and cache

Weaknesses:

- No obvious playlist management screen.
- Imported videos appear as a raw CPT menu, which may confuse nontechnical users.
- The sync AJAX endpoint has no visible UI in current code.
- Large metabox forms are dense and rely heavily on inline styles.
- Important concepts such as global vs per-video override are not consistently surfaced.

# Safe Areas To Modify

- Documentation files.
- Removing or clearly marking duplicate inactive frontend file after verifying no external loader uses it.
- Fixing asset references.
- Adding capability/autosave checks to `pvp_save_video_meta()`.
- Making uninstall respect `delete_on_uninstall`.
- Improving settings UI layout without changing stored option names.

# Risky Areas To Avoid Breaking

- Parent plugin asset handles and expected protected block wrapper markup.
- `data-id1` and `data-id2` base64 contract used by the parent plugin.
- Existing `pvp_video` post meta names.
- Playlist ID extraction and `_pvp_playlist_id` matching.
- Overlay timing data format stored as JSON.

# Development Notes For Future AI Agents

- Treat this plugin as a procedural WordPress add-on, not a class-based system.
- Do not assume files named `class-*` expose classes.
- The loaded frontend file is `includes/public/class-pvp-frontend.php`; `public/class-pvp-frontend.php` is inactive.
- Preserve the parent plugin markup contract in `pvp_render_single_video()`.
- Before changing import behavior, decide whether `pvp_video` posts are a cache or the canonical editable imported video records. Current code treats them as both, which causes major data-loss risk during resync.
