<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── AJAX handler for syncing playlist ──────────────────────────────────────────
add_action( 'wp_ajax_pvp_sync_playlist', 'pvp_ajax_sync_playlist' );

function pvp_ajax_sync_playlist() {
    check_ajax_referer( 'pvp_sync_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( __( 'Insufficient permissions.', 'protected-video-playlist' ) );
    }

    $playlist_index = isset( $_POST['playlist_index'] ) ? intval( wp_unslash( $_POST['playlist_index'] ) ) : 0;
    $options        = get_option( 'pvp_settings', array() );
    $playlists      = isset( $options['playlists'] ) ? $options['playlists'] : array();

    if ( ! isset( $playlists[ $playlist_index ] ) ) {
        wp_send_json_error( __( 'Playlist not found.', 'protected-video-playlist' ) );
    }

    $playlist = $playlists[ $playlist_index ];
    $api_key  = ! empty( $playlist['api_key'] ) ? $playlist['api_key'] : null;

    if ( ! empty( $api_key ) ) {
        // Use YouTube API v3 to fetch ALL videos
        $result = pvp_sync_via_api( $playlist['url'], $api_key );
    } else {
        // Fall back to RSS feed (15 videos only)
        $result = pvp_sync_via_rss( $playlist['url'] );
    }

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( $result->get_error_message() );
    }

    wp_send_json_success( array(
        'message' => sprintf(
            __( 'Synced %d videos successfully.', 'protected-video-playlist' ),
            count( $result )
        ),
        'count'   => count( $result ),
    ) );
}

// ── Sync via YouTube API v3 (all videos) ──────────────────────────────────────
function pvp_sync_via_api( $playlist_url, $api_key ) {
    $playlist_id = pvp_extract_playlist_id( $playlist_url );

    if ( ! $playlist_id ) {
        return new WP_Error(
            'pvp_invalid_url',
            __( 'Could not extract playlist ID from URL.', 'protected-video-playlist' )
        );
    }

    $videos      = array();
    $page_token  = '';
    $max_results = 50;

    while ( true ) {
        $url = add_query_arg( array(
            'key'          => $api_key,
            'playlistId'   => $playlist_id,
            'part'         => 'snippet',
            'maxResults'   => $max_results,
            'pageToken'    => $page_token,
        ), 'https://www.googleapis.com/youtube/v3/playlistItems' );

        $response = wp_remote_get( $url, array(
            'timeout'    => 15,
            'user-agent' => 'WordPress/' . get_bloginfo( 'version' ),
        ) );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'pvp_api_error',
                __( 'YouTube API request failed: ', 'protected-video-playlist' ) . $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! isset( $data['items'] ) ) {
            return new WP_Error(
                'pvp_api_response',
                __( 'Invalid YouTube API response.', 'protected-video-playlist' )
            );
        }

        foreach ( $data['items'] as $item ) {
            $video_id = $item['snippet']['resourceId']['videoId'] ?? null;
            $title    = $item['snippet']['title'] ?? '';
            $thumb    = $item['snippet']['thumbnails']['default']['url'] ?? '';

            if ( $video_id ) {
                $videos[] = array(
                    'video_id'  => sanitize_text_field( $video_id ),
                    'title'     => sanitize_text_field( $title ),
                    'thumbnail' => esc_url_raw( $thumb ),
                    'url'       => 'https://www.youtube.com/watch?v=' . sanitize_text_field( $video_id ),
                );
            }
        }

        $page_token = $data['nextPageToken'] ?? '';
        if ( empty( $page_token ) ) {
            break;
        }
    }

    // Save videos as CPT posts
    pvp_save_videos_as_cpt( $videos, $playlist_id );

    return $videos;
}

// ── Sync via RSS feed (15 videos only) ─────────────────────────────────────────
function pvp_sync_via_rss( $playlist_url ) {
    $playlist_id = pvp_extract_playlist_id( $playlist_url );

    if ( ! $playlist_id ) {
        return new WP_Error(
            'pvp_invalid_url',
            __( 'Could not extract playlist ID from URL.', 'protected-video-playlist' )
        );
    }

    $feed_url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=' . rawurlencode( $playlist_id );

    $response = wp_remote_get( $feed_url, array(
        'timeout'    => 15,
        'user-agent' => 'WordPress/' . get_bloginfo( 'version' ),
    ) );

    if ( is_wp_error( $response ) ) {
        return new WP_Error(
            'pvp_feed_error',
            __( 'Could not fetch YouTube RSS feed: ', 'protected-video-playlist' ) . $response->get_error_message()
        );
    }

    $body = wp_remote_retrieve_body( $response );

    if ( ! function_exists( 'simplexml_load_string' ) ) {
        return new WP_Error(
            'pvp_simplexml_missing',
            __( 'SimpleXML PHP extension is required but not available on this server.', 'protected-video-playlist' )
        );
    }

    libxml_use_internal_errors( true );
    if ( PHP_VERSION_ID < 80000 ) {
        libxml_disable_entity_loader( true );
    }

    $xml = simplexml_load_string( $body );
    if ( false === $xml ) {
        return new WP_Error(
            'pvp_parse_error',
            __( 'Failed to parse the YouTube RSS XML.', 'protected-video-playlist' )
        );
    }

    $xml->registerXPathNamespace( 'yt', 'http://www.youtube.com/xml/schemas/2015' );

    $videos = array();
    foreach ( $xml->entry as $entry ) {
        $id_nodes = $entry->xpath( 'yt:videoId' );
        if ( empty( $id_nodes ) ) {
            continue;
        }

        $video_id = sanitize_text_field( (string) $id_nodes[0] );
        $title    = sanitize_text_field( (string) $entry->title );

        $videos[] = array(
            'video_id'  => $video_id,
            'title'     => $title,
            'thumbnail' => 'https://i.ytimg.com/vi/' . $video_id . '/mqdefault.jpg',
            'url'       => 'https://www.youtube.com/watch?v=' . $video_id,
        );
    }

    // Save videos as CPT posts
    pvp_save_videos_as_cpt( $videos, $playlist_id );

    return $videos;
}

// ── Save videos as CPT posts ──────────────────────────────────────────────────
function pvp_save_videos_as_cpt( $videos, $playlist_id ) {
    foreach ( $videos as $video ) {
        if ( empty( $video['video_id'] ) ) {
            continue;
        }

        $video_id  = sanitize_text_field( $video['video_id'] );
        $title     = isset( $video['title'] ) ? sanitize_text_field( $video['title'] ) : '';
        $video_url = isset( $video['url'] ) ? esc_url_raw( $video['url'] ) : '';
        $thumbnail = isset( $video['thumbnail'] ) ? esc_url_raw( $video['thumbnail'] ) : '';

        $existing_posts = get_posts( array(
            'post_type'      => 'pvp_video',
            'post_status'    => 'any',
            'fields'         => 'ids',
            'numberposts'    => 1,
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'   => '_pvp_playlist_id',
                    'value' => $playlist_id,
                ),
                array(
                    'key'   => '_pvp_video_id',
                    'value' => $video_id,
                ),
            ),
        ) );

        $post_id = ! empty( $existing_posts ) ? absint( $existing_posts[0] ) : 0;

        if ( $post_id ) {
            $current_title = get_the_title( $post_id );
            if ( $title && $title !== $current_title ) {
                wp_update_post( array(
                    'ID'         => $post_id,
                    'post_title' => $title,
                ) );
            }
        } else {
            $post_id = wp_insert_post( array(
                'post_type'   => 'pvp_video',
                'post_title'  => $title,
                'post_status' => 'publish',
            ) );
        }

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_pvp_video_id', $video_id );
            update_post_meta( $post_id, '_pvp_video_url', $video_url );
            update_post_meta( $post_id, '_pvp_playlist_id', $playlist_id );
            update_post_meta( $post_id, '_pvp_thumbnail_url', $thumbnail );
        }
    }
}
