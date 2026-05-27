# RS SecurePlayer Development Plan

## 1. Product Goal

RS SecurePlayer will evolve the current Protected Video playlist add-on into a standalone-ready WordPress video marketing platform.

The plugin should support secure branded playback, reusable remote video records, playlist imports, campaign playback, marketing overlays, global branding, protection deterrents, shortcodes, and Elementor widgets.

This roadmap is intentionally phased. The current plugin contains working pieces that must be preserved while the architecture is rebuilt. Existing `pvp_*` functions, `pvp_settings`, `pvp_video` posts, `_pvp_*` meta keys, legacy shortcode behavior, and protected-video player wrapper markup must remain compatible until a later migration explicitly replaces them.

## 2. Existing Architecture Analysis

The current plugin is a procedural add-on around the WordPress.org `Protected Video` plugin. Files named `class-*` mostly contain global functions. The main bootstrap checks for `PROTECTED_VIDEO_VERSION`, loads helper files, registers the `pvp_video` CPT, registers a block/shortcode, and renders protected-video-compatible markup.

Important current contracts:

- Single player markup uses `.wp-block-protected-video-protected-video`.
- `data-id1` stores `base64_encode( 'youtube' )`.
- `data-id2` stores `base64_encode( $video_id )`.
- Imported playlist videos are stored as `pvp_video` posts.
- Video identity is stored in `_pvp_video_id`.
- Playlist identity is stored in `_pvp_playlist_id`.
- Global settings are stored in `pvp_settings`.
- Overlay time ranges are stored as JSON arrays like `[{"start":0,"end":0}]`.

Critical problems:

- Playlist sync deleted and recreated imported posts, destroying post IDs and customizations.
- Frontend rendering could trigger remote YouTube API/RSS imports.
- The admin AJAX sync endpoint existed but had no complete playlist UI.
- Overlay logic depended on broad mutation/message listeners and could trigger unpredictably.
- Import, rendering, persistence, business rules, and frontend behavior were mixed together.
- There was no playlist entity, campaign entity, service layer, repository layer, background process, or clear settings hierarchy.

## 3. Reuse Vs Rewrite

Reuse these parts initially:

- Existing `pvp_video` CPT as the V1 video library storage.
- Existing `_pvp_*` meta values for imported video identity, branding, overlay, and control overrides.
- Existing `pvp_settings` values for global branding, overlay, playback, API key, and uninstall preference.
- Existing YouTube URL and playlist ID parsing helpers after coverage is added.
- Existing protected-video wrapper markup while RS player logic is developed.

Rewrite these parts:

- Playlist sync storage logic.
- Frontend render flow that performs imports.
- Admin playlist management.
- Overlay event architecture.
- Player queue/campaign playback logic.
- Settings UI structure.
- Elementor widgets.

Defer these until later phases:

- Custom database tables.
- Removing protected-video dependency.
- Playlist-inside-campaign.
- Dailymotion implementation.
- Full DRM-style claims.

## 4. Target Architecture

Target PHP architecture:

- `RSPLR\Core\Plugin`: bootstrap, hooks, service registration.
- `RSPLR\Admin\MenuRegistrar`: top-level admin menu.
- `RSPLR\Admin\SettingsPage`: tabbed settings UI.
- `RSPLR\Admin\PlaylistImportsPage`: playlist creation/import/progress UI.
- `RSPLR\Admin\CampaignsPage`: campaign editor/list.
- `RSPLR\Repository\VideoRepository`: `pvp_video` compatibility reads/writes.
- `RSPLR\Repository\PlaylistRepository`: playlist entity storage.
- `RSPLR\Repository\CampaignRepository`: campaign entity storage.
- `RSPLR\Import\YouTubeClient`: API/RSS fetch logic.
- `RSPLR\Import\PlaylistImporter`: batching, upsert, status, errors.
- `RSPLR\Rendering\Renderer`: centralized HTML rendering.
- `RSPLR\Settings\SettingsRepository`: global settings and compatibility fallback.
- `RSPLR\Overrides\OverrideResolver`: video > campaign > global resolution.
- `RSPLR\Elementor\...`: thin widgets only.

Target frontend architecture:

- `rsplr-player.js`: instance manager and lifecycle.
- `rsplr-queue.js`: single/campaign/playlist queue state.
- `rsplr-overlays.js`: trigger evaluation and overlay state.
- `rsplr-branding.js`: logo and player styling.
- `rsplr-protection.js`: global deterrent behaviors.

Use jQuery-compatible modules for broad WordPress theme/plugin compatibility.

## 5. Entity Model

### Video Library

V1 storage remains `pvp_video`.

Required source metadata:

- provider: `youtube`, `vimeo`, `self_hosted`, future `dailymotion`.
- remote video ID.
- remote video URL.
- title.
- thumbnail URL.
- source playlist association.
- import position.

Existing meta compatibility:

- `_pvp_video_id`
- `_pvp_video_url`
- `_pvp_playlist_id`
- `_pvp_thumbnail_url`
- existing `_pvp_video_logo_*`, `_pvp_video_overlay_*`, `_pvp_override_*`, and branding color meta.

### Playlist Import

Create `rsplr_playlist` as a private managed entity.

Store:

- playlist name.
- source provider.
- playlist URL.
- external playlist ID.
- sync status: idle, queued, running, complete, partial, failed.
- last sync timestamp.
- total videos discovered.
- imported count.
- updated count.
- skipped count.
- failed count.
- last error.
- cursor/page token for batching.

### Campaign

Create `rsplr_campaign` as a private managed entity.

V1 campaign fields:

- intro video ID, optional.
- main video ID, required.
- outro video ID, optional.
- campaign-level overlay overrides.
- campaign-level branding overrides.
- campaign layout/playback options.

V1 must not support playlist-inside-campaign.

## 6. Settings Hierarchy

Final override priority:

1. Video override.
2. Campaign override.
3. Global settings.

Protection settings are global only. Do not add per-video or per-campaign protection in V1.

Settings tabs:

- Branding: logos, logo position, colors, player visual defaults.
- Overlay: global marketing overlay text/image/CTA defaults and triggers.
- Protection: right click, shortcuts, drag/download deterrents, redirect deterrents, YouTube branding controls where possible.
- Playback: layout defaults, autoplay/control defaults, playlist layout defaults.

Keep reading existing `pvp_settings` until a migration to `rsplr_settings` is implemented.

## 7. Shortcodes And Rendering

Add new shortcodes:

```text
[rsplr_video id="15"]
[rsplr_campaign id="8"]
[rsplr_playlist id="3"]
```

Optional V1 layout attributes:

```text
[rsplr_video id="15" layout="minimal"]
[rsplr_playlist id="3" layout="grid"]
[rsplr_playlist id="3" layout="sidebar"]
```

Rendering rules:

- Shortcodes call centralized renderer only.
- Elementor widgets call the same renderer.
- Blocks, widgets, and shortcodes must not duplicate business logic.
- Legacy `[protected_playlist]` remains as a compatibility wrapper during migration.
- Frontend rendering must never perform remote imports.

## 8. Import Engine Design

User flow:

1. Admin opens Playlist Imports.
2. Admin enters playlist URL and playlist name.
3. Admin clicks Import Playlist once.
4. JS starts an import job.
5. AJAX continues chunks automatically until complete.
6. UI shows progress, success, partial errors, or failure.

Import priority:

1. YouTube Data API if global API key is configured.
2. RSS fallback automatically when API key is missing or API failure is recoverable.

Upsert requirements:

- Match videos by provider + playlist ID + remote video ID.
- Preserve existing post IDs.
- Preserve customizations.
- Update only source metadata that changed.
- Never delete missing videos during resync in V1.
- Store private/deleted/unavailable states only after a clear UI policy exists.

Batching requirements:

- Process one API page or fixed chunk per request.
- Persist cursor/page token.
- Persist counters and errors.
- Return structured status for progress UI.
- Avoid requiring repeated manual clicks.

## 9. Playback Engine Design

The player engine must handle:

- single video playback.
- campaign queue playback.
- playlist playback.
- overlay triggers.
- branding overlays.
- protection hooks.
- active playback state.
- smooth intro -> main -> outro transitions.

Campaign playback target:

- Intro, main, and outro should feel like one continuous video experience.
- Preload the next queue item where the provider/player allows it.
- Keep one visible player shell.
- Avoid full page reloads and visible flicker.

Overlay triggers:

- after X seconds.
- on pause.
- on video end.

Overlay content:

- text.
- image.
- links.
- CTA buttons.

Event rules:

- One player instance owns its own timers/listeners.
- Do not use broad global message behavior without matching the iframe/source.
- Avoid random mutation-driven state changes.
- Keep overlay state deterministic.

## 10. Elementor Integration

Widgets:

- RS Single Video.
- RS Video Campaign.
- RS Playlist.

Widget responsibilities:

- Select entity.
- Select layout if applicable.
- Pass attributes to shortcode/renderer.

Widget non-responsibilities:

- No import logic.
- No direct provider API calls.
- No duplicate overlay logic.
- No duplicate campaign playback logic.

## 11. Development Phases

### Phase 0: Audit And Stabilization

Goal: make the existing plugin safer before larger changes.

Tasks:

- Create this development plan.
- Replace destructive sync with upsert.
- Stop frontend render from performing remote imports.
- Harden `pvp_video` save handling with nonce, autosave/revision, capability, and unslashing.
- Fix missing admin color picker asset path.
- Remove production debug logging.
- Make uninstall respect `delete_on_uninstall`.
- Preserve protected-video wrapper markup.

Acceptance:

- Resync no longer deletes imported videos.
- Existing customization meta survives resync.
- Frontend playlist rendering does not call YouTube API/RSS.
- Touched PHP files pass syntax checks.

### Phase 1: Core Architecture Foundation

Goal: introduce professional structure without breaking legacy behavior.

Tasks:

- Add `includes/RSPLR/` namespaced classes.
- Add a small PSR-4-style autoloader or explicit class loader.
- Add `Plugin`, `ServiceProvider`, `SettingsRepository`, and compatibility wrappers.
- Keep current procedural functions as wrappers where needed.
- Add internal naming constants for future `rsplr_*` migration.

Acceptance:

- Existing frontend output remains unchanged.
- Existing settings page still loads.
- New service classes can be unit tested independently.

### Phase 2: Video Library Refactor

Goal: formalize imported videos as reusable library records.

Tasks:

- Add `VideoRepository` around `pvp_video`.
- Register missing meta keys currently saved/read but not registered.
- Add provider/source metadata fields.
- Add admin columns for provider, source playlist, video ID, thumbnail, and last updated.
- Add safe helper methods for manual single video creation.

Acceptance:

- Existing `pvp_video` records remain editable.
- Repository can query by video ID, playlist ID, and provider.
- Manual single videos can be stored without playlist import.

### Phase 3: Playlist Entity And Import System

Goal: make playlists first-class managed entities.

Tasks:

- Register `rsplr_playlist` private CPT.
- Build Playlist Imports admin page.
- Add playlist URL + playlist name form.
- Add AJAX start/continue/status endpoints.
- Move YouTube API/RSS logic into `YouTubeClient`.
- Move upsert orchestration into `PlaylistImporter`.
- Store status, counters, cursor, last sync, and errors.

Acceptance:

- User clicks Import Playlist once.
- Large imports continue through AJAX batches.
- Existing videos are upserted, not deleted.
- Progress and partial errors are visible.

### Phase 4: Admin Experience Rebuild

Goal: replace scattered admin UX with product-grade navigation.

Tasks:

- Add top-level `RS Protected Video` menu.
- Add Dashboard, Campaigns, Video Library, Playlist Imports, Settings.
- Move current settings into tabs.
- Keep legacy Settings submenu if needed as a redirect or compatibility page.
- Improve admin asset loading so assets load only on plugin screens.

Acceptance:

- Admin users can find all plugin workflows from one menu.
- Settings are grouped by Branding, Overlay, Protection, Playback.
- No import workflow is hidden in frontend rendering.

### Phase 5: Rendering Layer Refactor

Goal: centralize rendering for shortcodes, Elementor, blocks, and legacy wrappers.

Tasks:

- Add `Renderer` service.
- Add render methods for video, campaign, and playlist.
- Add `[rsplr_video]`, `[rsplr_campaign]`, `[rsplr_playlist]`.
- Keep `[protected_playlist]` as a wrapper.
- Add layout support: grid, playlist sidebar, minimal.

Acceptance:

- All display entrypoints use the same renderer.
- Existing protected-video wrapper contract remains intact.
- Assets load conditionally only when RS output exists.

### Phase 6: Campaign System

Goal: introduce the core video marketing feature.

Tasks:

- Register `rsplr_campaign`.
- Add campaign editor with intro/main/outro selectors.
- Require main video.
- Add campaign-level overlay and branding override fields.
- Render campaign queue data for frontend player.

Acceptance:

- Campaigns can be created from existing single videos.
- Intro and outro are optional.
- Playlist-inside-campaign is not available in V1.

### Phase 7: Frontend Player Engine

Goal: replace unstable frontend behavior with modular player instances.

Tasks:

- Add jQuery-compatible player module.
- Add queue module for single/campaign/playlist states.
- Add deterministic state transitions.
- Add preloading strategy for campaign next item.
- Scope postMessage/event listeners per player instance.
- Preserve compatibility with protected-video/Plyr where still used.

Acceptance:

- Campaign playback proceeds intro -> main -> outro.
- Player shell remains stable between queue items.
- Multiple players on one page do not interfere with each other.

### Phase 8: Marketing Overlay System

Goal: make overlays reliable and flexible.

Tasks:

- Add overlay data normalization in PHP.
- Add overlay JS module.
- Support text, images, links, CTA buttons.
- Support after-X-seconds, pause, and end triggers.
- Apply video > campaign > global hierarchy.

Acceptance:

- Overlay triggers are predictable.
- Pause/end/time triggers can coexist.
- Overlay content is sanitized on save and escaped on output.

### Phase 9: Elementor Integration

Goal: expose product features to Elementor without duplicating logic.

Tasks:

- Add Elementor integration loader.
- Add widgets for single video, campaign, playlist.
- Add entity selectors.
- Render through shortcode/renderer.

Acceptance:

- Elementor widgets render the same frontend output as shortcodes.
- Widgets do not contain import, overlay, or playback business logic.

### Phase 10: Standalone Hardening

Goal: reduce reliance on the bundled Protected Video plugin.

Tasks:

- Audit protected-video behavior used by RS SecurePlayer.
- Move compatible protection/player logic into RS abstractions.
- Add provider abstraction for YouTube, Vimeo, self-hosted, future Dailymotion.
- Keep protected-video compatibility layer until replacement is verified.

Acceptance:

- RS rendering can target a player abstraction.
- Protected-video dependency can be disabled only after parity is proven.
- No impossible security claims are introduced.

### Phase 11: QA, Migration, And Release Preparation

Goal: prepare a maintainable product release.

Tasks:

- Add regression tests for imports, rendering, settings, and overrides.
- Add migration routines for future `rsplr_*` keys.
- Add release checklist.
- Add backup guidance before migration.
- Add WP_DEBUG-safe review.
- Review uninstall behavior.

Acceptance:

- Existing installations keep data.
- New installations use RS-branded interfaces.
- Upgrade path is documented and reversible where practical.

## 12. Testing Checklist By Phase

Phase 0:

- PHP syntax checks for touched files.
- Resync preserves post IDs.
- Custom meta survives resync.
- Frontend playlist render does not call remote imports.
- Uninstall respects setting.

Phase 1:

- Bootstrap loads in admin and frontend.
- Legacy functions still exist.
- No duplicate hook side effects.

Phase 2:

- Repository queries existing videos.
- Manual video creation works.
- Registered meta matches saved/read keys.

Phase 3:

- API import works.
- RSS fallback works.
- Batch progress completes without repeated clicks.
- Error states are visible.

Phase 4:

- All menu pages load.
- Settings tabs save.
- Assets load only on plugin screens.

Phase 5:

- All shortcodes render.
- Legacy shortcode still works.
- Layouts render without overlap.

Phase 6:

- Campaign requires main video.
- Optional intro/outro playback data is generated.
- Campaign overrides resolve correctly.

Phase 7:

- Multiple players on a page remain isolated.
- Campaign queue transitions smoothly.
- Player state does not leak between instances.

Phase 8:

- Time, pause, and end overlays work separately and together.
- CTA links/buttons are sanitized and functional.
- Override priority is correct.

Phase 9:

- Elementor editor loads widgets.
- Widgets render through central renderer.
- Frontend output matches shortcode output.

Phase 10:

- Protected-video compatibility remains intact.
- RS abstraction supports current providers.
- Protection UI avoids false security claims.

Phase 11:

- Upgrade/migration preserves data.
- Uninstall behavior is intentional.
- WP_DEBUG logs are clean.

## 13. Compatibility Rules

- Do not rename or delete `pvp_video` during early phases.
- Do not remove existing `_pvp_*` meta without migration.
- Do not remove `pvp_settings` without migration.
- Do not remove legacy shortcodes until a deprecation plan exists.
- Do not change protected-video `data-id1`/`data-id2` markup until RS player replacement is complete.
- Do not claim true DRM or impossible video security.

## 14. Phase 0 Completion Notes

Phase 0 is complete when the repository contains this roadmap and the current plugin no longer deletes imported videos during sync, no longer imports playlists from frontend rendering, respects uninstall preference, and passes PHP syntax checks for touched files.

## 15. Phase 1 Completion Notes

Phase 1 is complete when the plugin has an `RSPLR` namespace foundation, autoloading, a core bootstrap class, settings access through a repository, and compatibility wrappers that keep existing procedural behavior intact. The current legacy files must still load in their original order, and existing frontend/admin behavior should remain unchanged.

## 16. Phase 2 Completion Notes

Phase 2 is complete when imported videos have a repository abstraction around the legacy `pvp_video` CPT, missing saved/read meta keys are registered, playlist import upserts use the repository, and the Video Library admin list exposes provider/source metadata without changing existing `pvp_video` storage.

## 17. Phase 3 Completion Notes

Phase 3 is complete when playlists are first-class `rsplr_playlist` entities, admins can enter a playlist URL and name from the Playlist Imports page, AJAX can start and continue imports from one click, YouTube API imports process one page per request, RSS fallback is automatic, and imported videos continue to upsert into the existing video library without deleting customizations.

## 18. Phase 4 Completion Notes

Phase 4 is complete when the admin experience is consolidated under the `RS Protected Video` top-level menu with Dashboard, Campaigns, Video Library, Playlist Imports, and Settings. The new settings screen uses Branding, Overlay, Protection, and Playback tabs while continuing to save the existing `pvp_settings` option for compatibility. The legacy settings page may remain available during migration.

## 19. Phase 5 Completion Notes

Phase 5 is complete when frontend rendering is centralized behind an RS renderer service, `[rsplr_video]`, `[rsplr_playlist]`, and `[rsplr_campaign]` are registered, legacy block and `[protected_playlist]` rendering delegate through the same renderer, and frontend assets load when RS shortcodes are present.

## 20. Phase 6 Completion Notes

Phase 6 is complete when campaigns are stored as `rsplr_campaign` entities, admins can create/edit intro/main/outro single-video campaigns, main video is required, saved campaign shortcodes are shown in the Campaigns page, and `[rsplr_campaign id="..."]` renders campaign queue data plus the first playable video for the upcoming frontend player engine.
