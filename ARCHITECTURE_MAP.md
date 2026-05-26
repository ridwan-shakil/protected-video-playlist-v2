# Architecture Map

The plugin is a procedural WordPress add-on for the parent `protected-video` plugin.

# High-Level Layers

```text
WordPress
  |
  v
protected-video-playlist.php
  |
  +-- Parent plugin gate: PROTECTED_VIDEO_VERSION
  |
  +-- Shared helpers
  |     URL parsing, color sanitization
  |
  +-- Data layer
  |     pvp_video CPT, post meta, pvp_settings option, transients
  |
  +-- Import layer
  |     YouTube Data API / RSS -> pvp_video posts
  |
  +-- Render layer
  |     block/shortcode -> protected video wrappers -> parent player
  |
  +-- Admin layer
  |     settings page, CPT metaboxes
  |
  +-- Frontend layer
        assets, overlay timing, control hiding, fullscreen relocation
```

# Request Lifecycle

## Admin Settings Page

```text
admin.php?page=protected-video-playlist
  -> pvp_admin_assets()
  -> pvp_render_settings_page()
  -> WordPress Settings API
  -> pvp_sanitize_settings()
  -> pvp_settings option
```

## Admin Video Edit Screen

```text
post.php?post={pvp_video}
  -> pvp_enqueue_cpt_assets()
  -> pvp_render_video_meta_box()
  -> pvp_render_branding_meta_box()
  -> pvp_render_controls_meta_box()
  -> save_post_pvp_video
  -> pvp_save_video_meta()
  -> post meta updates
```

## Frontend Playlist Page

```text
page content
  -> block render callback or shortcode
  -> pvp_render_block()
  -> pvp_render_grid_from_url()
  -> maybe sync from YouTube
  -> pvp_render_grid()
  -> pvp_render_single_video()
  -> parent protected-video frontend JS creates player
  -> pvp-frontend.js applies overlay/control behavior
```

# Important Data Contracts

## Parent Plugin Contract

`pvp_render_single_video()` outputs:

```html
<div class="wp-block-protected-video-protected-video" data-id1="..." data-id2="..."></div>
```

`data-id1` is `base64_encode('youtube')`. `data-id2` is `base64_encode($video_id)`. The parent plugin is expected to turn this into a playable protected player. Breaking this markup breaks the add-on.

## Playlist Identity

Playlist identity is stored as `_pvp_playlist_id`. The renderer uses it to find imported posts.

## Video Identity

Video identity is stored as `_pvp_video_id`, but current saving does not upsert by this key. It deletes and recreates.

## Overlay Timing

Overlay time ranges are JSON arrays:

```json
[{"start":0,"end":0}]
```

All zeros means always show if no pause/end trigger is enabled.

# Important Functions

| Function | File | Responsibility |
| --- | --- | --- |
| `pvp_init_addon` | `protected-video-playlist.php` | Parent check and file loading. |
| `pvp_get_settings` | `protected-video-playlist.php` | Read `pvp_settings`. |
| `pvp_is_playlist_url` | `fallback/class-pvp-functions.php` | Detect playlist URLs. |
| `pvp_extract_playlist_id` | `fallback/class-pvp-functions.php` | Extract playlist IDs. |
| `pvp_extract_youtube_id` | `fallback/class-pvp-functions.php` | Extract video IDs. |
| `pvp_register_block` | `includes/core/class-pvp-block.php` | Register Gutenberg block. |
| `pvp_render_block` | `includes/core/class-pvp-block.php` | Route single vs playlist rendering. |
| `pvp_render_grid_from_url` | `includes/core/class-pvp-render.php` | Playlist import/query/pagination/render orchestration. |
| `pvp_render_single_video` | `includes/core/class-pvp-render.php` | Build protected player wrapper, overlays, logos, branding. |
| `pvp_sync_via_api` | `includes/core/class-pvp-sync.php` | YouTube Data API playlist import. |
| `pvp_sync_via_rss` | `includes/core/class-pvp-sync.php` | YouTube RSS playlist import. |
| `pvp_save_videos_as_cpt` | `includes/core/class-pvp-sync.php` | Save imported videos as `pvp_video`. |
| `pvp_register_video_cpt` | `includes/core/class-pvp-cpt.php` | Register CPT. |
| `pvp_register_video_meta` | `includes/core/class-pvp-cpt.php` | Register meta keys. |
| `pvp_save_video_meta` | `includes/core/class-pvp-cpt.php` | Save per-video settings. |
| `pvp_enqueue_frontend_styles` | `includes/public/class-pvp-frontend.php` | Conditional frontend assets and inline styles/scripts. |

# Options

`pvp_settings` is the central option. It currently stores global import, display, branding, and uninstall preferences in one array.

Recommended future split:

- `pvp_settings`: display defaults.
- `pvp_import_settings`: API/auth/import behavior.
- `pvp_playlist_sources`: playlist source records.

# Transients

`pvp_fetch_playlist_videos()` uses:

- `pvp_pl_{md5(playlist_id)}`
- `pvp_pl_stale_{md5(playlist_id)}`
- `pvp_lock_{md5(playlist_id)}`

But this function is not used by the active CPT-backed renderer.

# Missing Architecture Pieces

- No service classes.
- No repository layer for `pvp_video`.
- No import state model.
- No playlist source model.
- No background scheduler.
- No REST API.
- No automated tests.
- No build tooling.

# Recommended Target Architecture

Keep compatibility first, then introduce thin internal modules:

1. `PVP_Playlist_Source` or equivalent array schema for playlist URL, API key, sync schedule, last sync status.
2. `PVP_Video_Repository` for CPT upserts and queries.
3. `PVP_YouTube_Client` for API/RSS fetching and errors.
4. `PVP_Importer` for orchestration, batching, logging, and preserving custom meta.
5. `PVP_Renderer` to isolate frontend markup from import logic.

This can be done incrementally while keeping existing function wrappers.

# Development Notes For Future AI Agents

- Prefer adding compatibility wrappers instead of renaming public functions immediately.
- Add tests around URL extraction and import upsert before changing import logic.
- Do not trigger remote YouTube calls in normal page render after a proper sync system exists.
- Use WordPress APIs for settings, nonce, meta, and HTTP requests.
- Keep all existing option/meta keys unless a migration is explicitly implemented.
