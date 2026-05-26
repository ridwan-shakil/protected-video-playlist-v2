# YouTube Import System

This plugin imports YouTube playlist items into a private custom post type named `pvp_video`. The import system is partly active and partly unfinished.

# Files / Functions Involved

| File | Role |
| --- | --- |
| `fallback/class-pvp-functions.php` | Extracts playlist IDs and video IDs. |
| `includes/core/class-pvp-render.php` | Detects playlist render requests and lazily triggers sync if no imported posts exist. |
| `includes/core/class-pvp-sync.php` | Main API/RSS import implementation and CPT saving. |
| `includes/api/class-pvp-youtube.php` | RSS fetch helper with transient cache; appears legacy or bypassed by current CPT sync flow. |
| `includes/core/class-pvp-cpt.php` | Registers imported video CPT/meta and per-video edit UI. |
| `includes/core/class-pvp-block.php` | Sends playlist URLs to renderer from the block. |
| `fallback/class-pvp-shortcode.php` | Sends playlist URLs to renderer from shortcode. |

# Supported Import Methods

| Method | Present | Notes |
| --- | --- | --- |
| YouTube Data API v3 | Yes | Uses `playlistItems` endpoint if global API key exists in render path. |
| YouTube RSS feed | Yes | Fallback when no API key exists. RSS returns latest limited feed entries, commonly 15. |
| Scraping | No | No HTML scraping found. |
| `yt-dlp` | No | No shell/video downloader integration. |
| iframe parsing | No | The iframe/player is frontend display only. |
| oEmbed | No | No `wp_oembed_get()` or oEmbed endpoint usage. |
| unofficial APIs | No | Only Google API and YouTube RSS were found. |
| background jobs | No | Sync runs inline or through unfinished AJAX. |
| cron auto-import | No | No scheduler found. |

# Authentication

YouTube Data API imports use an API key stored in `pvp_settings['youtube_api_key']`. RSS imports require no authentication.

Important inconsistency: `pvp_ajax_sync_playlist()` looks for a per-playlist `api_key` inside `pvp_settings['playlists'][index]['api_key']`, but the admin settings UI only exposes a global `youtube_api_key` and does not create `playlists`.

# Import Workflow: Frontend Lazy Sync

This is the active workflow for normal block/shortcode frontend rendering.

1. A page contains `protected-video-playlist/playlist` or `[protected_playlist]`.
2. Frontend assets load if the block/shortcode is detected.
3. `pvp_render_block()` receives URL, columns, and cache attributes.
4. `pvp_is_playlist_url($url)` checks for `?list=` or `&list=`.
5. Playlist URLs call `pvp_render_grid_from_url($url, $columns, $cache)`.
6. `pvp_extract_playlist_id($url)` extracts the playlist ID.
7. WordPress queries `pvp_video` posts where `_pvp_playlist_id` equals the playlist ID.
8. If posts already exist, no remote fetch occurs.
9. If no posts exist:
   - `pvp_settings['youtube_api_key']` is read.
   - If set, `pvp_sync_via_api($url, $api_key)` runs.
   - If not set, `pvp_sync_via_rss($url)` runs.
10. Imported videos are saved by `pvp_save_videos_as_cpt($videos, $playlist_id)`.
11. Posts are queried again and paginated at 12 per page.
12. Each video is rendered through `pvp_render_single_video()`, using post meta for per-video overrides.

# Import Workflow: Admin AJAX Sync

This path exists but appears unfinished.

1. AJAX action `pvp_sync_playlist` calls `pvp_ajax_sync_playlist()`.
2. Nonce `pvp_sync_nonce` is checked.
3. Capability `manage_options` is required.
4. `playlist_index` is read from `$_POST`.
5. Code expects `pvp_settings['playlists'][$playlist_index]`.
6. If found, it chooses per-playlist `api_key` or RSS.
7. Sync result is returned as JSON.

Current problem: no admin UI or script in this repo creates `pvp_settings['playlists']`, localizes `pvp_sync_nonce`, or triggers this endpoint.

# YouTube Data API Flow

Function: `pvp_sync_via_api($playlist_url, $api_key)`

1. Extract playlist ID.
2. Initialize `pageToken` as empty.
3. Request:
   - URL: `https://www.googleapis.com/youtube/v3/playlistItems`
   - Query: `key`, `playlistId`, `part=snippet`, `maxResults=50`, `pageToken`
4. Parse JSON.
5. For each `items[]` entry:
   - video ID: `snippet.resourceId.videoId`
   - title: `snippet.title`
   - thumbnail: `snippet.thumbnails.default.url`
   - URL: `https://www.youtube.com/watch?v={video_id}`
6. Continue while `nextPageToken` exists.
7. Save all videos to CPT.

Missing handling:

- HTTP status code validation.
- YouTube API error object parsing.
- quota/rate-limit handling.
- private/deleted/unavailable video filtering.
- retry/backoff.
- partial sync recovery.

# RSS Flow

Function: `pvp_sync_via_rss($playlist_url)`

1. Extract playlist ID.
2. Request `https://www.youtube.com/feeds/videos.xml?playlist_id={playlist_id}`.
3. Parse XML with SimpleXML.
4. Register namespace `yt`.
5. For each `<entry>`:
   - video ID from `yt:videoId`
   - title from `entry->title`
   - thumbnail as `https://i.ytimg.com/vi/{video_id}/mqdefault.jpg`
   - URL as watch URL
6. Save videos to CPT.

`includes/api/class-pvp-youtube.php` also has `pvp_fetch_playlist_videos($playlist_id, $cache_seconds)`, which:

- fetches the same RSS feed,
- caches successful results in transients,
- maintains a stale fallback transient,
- uses a short lock transient.

However, the active grid render path does not call this helper.

# Metadata Storage

Imported videos are stored as posts:

- `post_type`: `pvp_video`
- `post_title`: YouTube title
- `post_status`: `publish`

Post meta:

- `_pvp_video_id`: YouTube video ID
- `_pvp_video_url`: YouTube watch URL
- `_pvp_playlist_id`: playlist ID
- `_pvp_thumbnail_url`: thumbnail URL

Additional per-video logo, overlay, controls, and branding meta is managed by the CPT edit screen.

# Thumbnails

API imports store `snippet.thumbnails.default.url`.

RSS imports synthesize `https://i.ytimg.com/vi/{video_id}/mqdefault.jpg`.

The frontend grid does not display thumbnail cards directly. It renders embedded protected video players and titles. Thumbnails are mainly stored for potential admin/future use.

# Categories / Tags / Playlists / Channels

No WordPress taxonomies are registered for categories, tags, channels, or playlists. Playlist association is a post meta string `_pvp_playlist_id`.

The YouTube channel ID, playlist title, video description, published date, position, duration, tags, and categories are not imported.

# Duplicate Prevention

Current duplicate prevention is destructive:

1. `pvp_save_videos_as_cpt()` queries all old `pvp_video` posts for the playlist ID.
2. It permanently deletes all of them.
3. It inserts new posts for every fetched video.

This prevents duplicates, but it also destroys existing post IDs and all per-video customizations whenever sync runs.

# Scheduling / Auto Import

No scheduled auto-import exists.

The only automatic behavior is lazy first-render sync when a playlist has no imported CPT posts.

# Error Handling

Existing:

- Invalid playlist URL returns `WP_Error`.
- `wp_remote_get()` errors are wrapped in `WP_Error`.
- missing SimpleXML returns `WP_Error`.
- RSS parse errors return `WP_Error`.
- AJAX returns JSON error for `WP_Error`.

Weak:

- API path does not check HTTP status.
- API path does not expose Google API error messages.
- Frontend lazy sync ignores sync `WP_Error`; it simply queries again and may show "No videos found".
- No structured logging or admin-visible import history.

# Rate Limit / Quota Handling

No explicit rate-limit or quota handling exists. The API loop fetches every page synchronously until `nextPageToken` is absent or a failure occurs.

# Caching

There are two conflicting caching concepts:

1. `pvp_fetch_playlist_videos()` RSS transient cache in `includes/api/class-pvp-youtube.php`.
2. `pvp_video` CPT records used by the active grid renderer.

The block `cache` attribute and shortcode `cache` attribute are accepted, but the active CPT-backed render path does not use them once CPT sync was introduced.

# Import Queue Logic

No queue exists. Imports happen synchronously in the request that triggered them.

# Admin UI Controlling Import

Current visible admin UI:

- global API key on settings page.
- `pvp_video` CPT edit screens for imported video customization.

Missing/incomplete:

- no playlist list/manager.
- no visible sync button tied to `wp_ajax_pvp_sync_playlist`.
- no import progress UI.
- no import logs.

# Frontend Components Displaying Imported Videos

- `pvp_render_grid_from_url()`
- `pvp_render_grid()`
- `pvp_render_single_video()`
- `public/js/pvp-frontend.js`
- `public/css/pvp-frontend.css`

# Flowchart

```text
Block / shortcode URL
        |
        v
pvp_render_block()
        |
        +-- single video --> pvp_render_single_video()
        |
        +-- playlist --> pvp_render_grid_from_url()
                            |
                            v
                    extract playlist ID
                            |
                            v
                  query pvp_video posts
                            |
             +--------------+--------------+
             |                             |
       posts found                    no posts found
             |                             |
             |                   read global API key
             |                             |
             |          +------------------+------------------+
             |          |                                     |
             |   API key exists                         no API key
             |          |                                     |
             |  pvp_sync_via_api()                  pvp_sync_via_rss()
             |          |                                     |
             +----------+------------------+------------------+
                                        |
                                        v
                         pvp_save_videos_as_cpt()
                                        |
                                        v
                              query posts again
                                        |
                                        v
                         paginate and render grid
```

# Dependency Map

```text
protected-video-playlist.php
  -> fallback/class-pvp-functions.php
       -> pvp_is_playlist_url()
       -> pvp_extract_playlist_id()
       -> pvp_extract_youtube_id()
       -> pvp_sanitize_color()
  -> includes/core/class-pvp-cpt.php
       -> pvp_video CPT and meta
  -> includes/core/class-pvp-sync.php
       -> pvp_sync_via_api()
       -> pvp_sync_via_rss()
       -> pvp_save_videos_as_cpt()
  -> includes/api/class-pvp-youtube.php
       -> pvp_fetch_playlist_videos() [legacy/unused in current render path]
  -> includes/core/class-pvp-render.php
       -> pvp_render_block() dependency through block callback
       -> pvp_render_grid_from_url()
       -> pvp_render_single_video()
  -> includes/core/class-pvp-block.php
       -> Gutenberg dynamic block
  -> admin/class-pvp-admin.php [admin only]
  -> includes/public/class-pvp-frontend.php [frontend only]
  -> fallback/class-pvp-shortcode.php [frontend only]
```

# Current Problems

- Resync deletes all imported posts and per-video customizations.
- Admin AJAX sync path is not connected to a working playlist UI.
- Cache setting is misleading in current CPT-backed import path.
- No scheduled refresh.
- No import status, history, or failure visibility.
- No API quota handling.
- No channel/playlist metadata import.

# Safe Starting Point For Import Refactor

Start by changing `pvp_save_videos_as_cpt()` into an upsert routine keyed by `_pvp_playlist_id + _pvp_video_id`. Preserve existing post IDs and per-video settings. Add tests or a temporary debug command around this before changing UI.
