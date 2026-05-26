<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// YOUTUBE RSS FEED FETCHER
// ─────────────────────────────────────────────────────────────────────────────
function pvp_fetch_playlist_videos( $playlist_id, $cache_seconds ) {
    $transient_key       = 'pvp_pl_' . md5( $playlist_id );
    $stale_transient_key = 'pvp_pl_stale_' . md5( $playlist_id );
    $lock_key            = 'pvp_lock_' . md5( $playlist_id );

    // Return fresh cache if available
    if ( $cache_seconds > 0 ) {
        $cached = get_transient( $transient_key );
        if ( false !== $cached ) {
            return $cached;
        }
    }

    // Prevent simultaneous fetches - if lock exists serve stale or bail
    if ( get_transient( $lock_key ) ) {
        $stale = get_transient( $stale_transient_key );
        if ( false !== $stale ) {
            return $stale;
        }
        return new WP_Error(
            'pvp_locked',
            __( 'Playlist is currently being fetched. Please try again shortly.', 'protected-video-playlist' )
        );
    }

    // Set lock for 15 seconds - same as timeout
    set_transient( $lock_key, 1, 15 );

    $feed_url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=' . rawurlencode( $playlist_id );

    $response = wp_remote_get(
        $feed_url,
        array(
            'timeout'    => 15,
            'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
        )
    );

    // Always delete lock after fetch attempt
    delete_transient( $lock_key );

    if ( is_wp_error( $response ) ) {
        $stale = get_transient( $stale_transient_key );
        if ( false !== $stale ) {
            return $stale;
        }
        return new WP_Error(
            'pvp_fetch_failed',
            __( 'Could not reach the YouTube RSS feed: ', 'protected-video-playlist' ) . $response->get_error_message()
        );
    }

    $status = wp_remote_retrieve_response_code( $response );
    if ( 200 !== (int) $status ) {
        $stale = get_transient( $stale_transient_key );
        if ( false !== $stale ) {
            return $stale;
        }
        return new WP_Error(
            'pvp_bad_status',
            sprintf(
                __( 'YouTube RSS feed returned HTTP %d. Ensure the playlist ID is correct and the playlist is public.', 'protected-video-playlist' ),
                $status
            )
        );
    }

    $body = wp_remote_retrieve_body( $response );

    // Issue 3: SimpleXML check
    if ( ! function_exists( 'simplexml_load_string' ) ) {
        return new WP_Error(
            'pvp_simplexml_missing',
            __( 'SimpleXML PHP extension is required but not available on this server.', 'protected-video-playlist' )
        );
    }

    // Issue 4: XXE prevention
    libxml_use_internal_errors( true );
    if ( PHP_VERSION_ID < 80000 ) {
        libxml_disable_entity_loader( true );
    }

    $xml = simplexml_load_string( $body );
    if ( false === $xml ) {
        $stale = get_transient( $stale_transient_key );
        if ( false !== $stale ) {
            return $stale;
        }
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
        // Issue 1: Sanitize XPath output immediately
        $videos[] = array(
            'url'   => 'https://www.youtube.com/watch?v=' . sanitize_text_field( (string) $id_nodes[0] ),
            'title' => isset( $entry->title ) ? sanitize_text_field( (string) $entry->title ) : '',
        );
    }

    if ( $cache_seconds > 0 && ! empty( $videos ) ) {
        set_transient( $transient_key, $videos, $cache_seconds );
        set_transient( $stale_transient_key, $videos, WEEK_IN_SECONDS );
    }

    return $videos;
}