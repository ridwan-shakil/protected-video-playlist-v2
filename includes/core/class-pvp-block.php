<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Register Gutenberg Block (protected-video-playlist/playlist) ──────────────
add_action( 'init', 'pvp_register_block' );

function pvp_register_block() {
    if ( ! defined( 'PROTECTED_VIDEO_VERSION' ) ) {
        return;
    }

    wp_register_script(
        'pvp-playlist-editor',
        PVP_PLUGIN_URL . 'assets/js/editor.js',
        array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
        PVP_VERSION,
        false
    );

    wp_register_style(
        'pvp-playlist-editor-style',
        PVP_PLUGIN_URL . 'assets/css/editor.css',
        array(),
        PVP_VERSION
    );

    register_block_type(
        'protected-video-playlist/playlist',
        array(
            'title'           => 'Protected Video / Playlist',
            'category'        => 'embed',
            'icon'            => 'playlist-video',
            'description'     => 'Paste a YouTube video URL (single protected player) or a playlist URL (grid of protected players).',
            'attributes'      => array(
                'url'     => array( 'type' => 'string', 'default' => '' ),
                'columns' => array( 'type' => 'integer', 'default' => 3 ),
                'cache'   => array( 'type' => 'integer', 'default' => 5 ),
            ),
            'editor_script'   => 'pvp-playlist-editor',
            'editor_style'    => 'pvp-playlist-editor-style',
            'render_callback' => 'pvp_render_block',
            'supports'        => array(
                'html'  => false,
                'align' => array( 'wide', 'full' ),
            ),
        )
    );
}

function pvp_render_block( $attributes ) {
    $url     = isset( $attributes['url'] )     ? sanitize_text_field( $attributes['url'] ) : '';
    $columns = isset( $attributes['columns'] ) ? max( 1, min( 4, intval( $attributes['columns'] ) ) ) : 3;
    $cache   = isset( $attributes['cache'] )   ? max( 0, intval( $attributes['cache'] ) ) : 5;

    if ( empty( $url ) ) {
        return '';
    }

    if ( function_exists( 'rsplr_renderer' ) ) {
        return rsplr_renderer()->url(
            $url,
            array(
                'columns' => $columns,
                'cache'   => $cache,
            )
        );
    }

    if ( pvp_is_playlist_url( $url ) ) {
        return pvp_render_grid_from_url( $url, $columns, $cache );
    }

    return pvp_render_single_video( $url );
}

