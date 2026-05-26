/**
 * Protected Video – Playlist Add-on
 * Editor script.
 *
 * Written in ES5-compatible plain JS using window.wp globals.
 * NO build step / npm / webpack required.
 *
 * Block behaviour:
 *   - Shows a URL input (same style as the parent Protected Video block).
 *   - If user enters a playlist URL  → shows a "playlist detected" preview card.
 *   - If user enters a single video  → shows the YouTube thumbnail (same as parent block).
 *   - Sidebar: Columns (1-4) and Cache controls (only meaningful for playlists).
 *   - Front-end rendering is 100% server-side (render_callback in PHP).
 */
( function () {
    'use strict';

    var el               = wp.element.createElement;
    var useState         = wp.element.useState;
    var Fragment         = wp.element.Fragment;
    var __               = wp.i18n.__;
    var registerBlockType = wp.blocks.registerBlockType;
    var useBlockProps    = wp.blockEditor.useBlockProps;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var BlockIcon        = wp.blockEditor.BlockIcon;
    var Placeholder      = wp.components.Placeholder;
    var TextControl      = wp.components.TextControl;
    var PanelBody        = wp.components.PanelBody;
    var RangeControl     = wp.components.RangeControl;
    var Button           = wp.components.Button;
    var Notice           = wp.components.Notice;

    // ── Helpers ───────────────────────────────────────────────────────────────

    function isPlaylistUrl( url ) {
        return /[?&]list=[A-Za-z0-9_-]{10,}/.test( url );
    }

    function extractPlaylistId( url ) {
        var m = url.match( /[?&]list=([A-Za-z0-9_-]{10,64})/ );
        return m ? m[ 1 ] : null;
    }

    function extractYouTubeVideoId( url ) {
        url = url.trim();
        // Bare video ID
        if ( /^[A-Za-z0-9_-]{6,64}$/.test( url ) ) { return url; }
        var m = url.match( /(?:youtu\.be\/|[?&]v=|youtube\.com\/(?:embed\/|v\/|shorts\/|live\/))([A-Za-z0-9_-]{6,64})/ );
        return m ? m[ 1 ] : null;
    }

    // ── Editor component ──────────────────────────────────────────────────────

    function Edit( props ) {
        var attributes    = props.attributes;
        var setAttributes = props.setAttributes;
        var blockProps    = useBlockProps();

        var url     = attributes.url     || '';
        var columns = attributes.columns || 3;
        var cache   = attributes.cache   || 3600;

        // Local state for the URL input before it is committed.
        var localUrlState = useState( url );
        var localUrl      = localUrlState[ 0 ];
        var setLocalUrl   = localUrlState[ 1 ];

        // ── Sidebar ───────────────────────────────────────────────────────────
        var sidebar = el(
            InspectorControls,
            null,
            el(
                PanelBody,
                { title: __( 'Playlist Settings', 'protected-video-playlist' ), initialOpen: true },
                el( RangeControl, {
                    label:    __( 'Columns', 'protected-video-playlist' ),
                    help:     __( 'Number of grid columns (applies to playlist URLs only).', 'protected-video-playlist' ),
                    value:    columns,
                    min:      1,
                    max:      4,
                    onChange: function ( v ) { setAttributes( { columns: v } ); },
                } ),
                el( RangeControl, {
                    label:    __( 'Cache duration (seconds)', 'protected-video-playlist' ),
                    help:     __( '0 = disabled. Recommended: 3600 (1 hour).', 'protected-video-playlist' ),
                    value:    cache,
                    min:      0,
                    max:      86400,
                    step:     300,
                    onChange: function ( v ) { setAttributes( { cache: v } ); },
                } ),
                url
                    ? el(
                        Button,
                        {
                            variant: 'secondary',
                            isSmall: true,
                            style:   { marginTop: '8px' },
                            onClick: function () {
                                setAttributes( { url: '' } );
                                setLocalUrl( '' );
                            },
                        },
                        __( 'Replace URL', 'protected-video-playlist' )
                    )
                    : null
            )
        );

// Always show input (even if URL exists)
var inputUI = el(
    'div',
    blockProps,
    el(
        Placeholder,
        {
            icon:         el( BlockIcon, { icon: 'playlist-video', showColors: true } ),
            label:        __( 'Protected Video / Playlist', 'protected-video-playlist' ),
            instructions: __( 'Paste a YouTube video URL for a single protected player, or a playlist URL to display a protected grid.', 'protected-video-playlist' ),
        },
        el(
            'div',
            { className: 'pvp-placeholder-row' },
            el( TextControl, {
                label:       __( 'Video or Playlist URL', 'protected-video-playlist' ),
                value:       localUrl,
                placeholder: 'https://www.youtube.com/playlist?list=PL… or https://youtu.be/…',
                onChange:    function ( v ) { setLocalUrl( v ); },
                onBlur:      function () {
                    if ( localUrl.trim() ) {
                        setAttributes( { url: localUrl.trim() } );
                    }
                }
            } ),
            el(
                Button,
                {
                    variant:  'primary',
                    disabled: ! localUrl.trim(),
                    onClick:  function () {
                        if ( localUrl.trim() ) {
                            setAttributes( { url: localUrl.trim() } );
                        }
                    },
                },
                __( 'Load', 'protected-video-playlist' )
            )
        )
    )
);

        // ── URL is set → show a preview card ─────────────────────────────────

        var previewContent;

        if ( isPlaylistUrl( url ) ) {
            // Playlist preview card.
            var pid = extractPlaylistId( url );
            previewContent = el(
                'div',
                { className: 'pvp-editor-preview pvp-editor-playlist' },
                el( 'span', { className: 'pvp-editor-icon dashicons dashicons-playlist-video' } ),
                el( 'p', { className: 'pvp-editor-label' },
                    __( 'YouTube Playlist detected', 'protected-video-playlist' )
                ),
                el( 'p', { className: 'pvp-editor-sub' },
                    'Playlist ID: ' + ( pid || '—' )
                ),
                el( 'p', { className: 'pvp-editor-sub' },
                    columns + ' column' + ( columns !== 1 ? 's' : '' ) + ' · cache: ' + ( cache ? cache + 's' : 'off' )
                ),
                el( 'p', { className: 'pvp-editor-note' },
                    __( 'A grid of protected players will be shown on the front-end.', 'protected-video-playlist' )
                )
            );
        } else {
            // Single video preview – show YouTube thumbnail.
            var vid = extractYouTubeVideoId( url );
            if ( vid ) {
                previewContent = el(
                    'div',
                    { className: 'pvp-editor-preview pvp-editor-single' },
                    el( 'img', {
                        src:       'https://i.ytimg.com/vi/' + vid + '/mqdefault.jpg',
                        alt:       __( 'Video thumbnail', 'protected-video-playlist' ),
                        className: 'pvp-editor-thumb',
                        width:     320,
                        height:    180,
                    } ),
                    el( 'p', { className: 'pvp-editor-label' },
                        __( 'Single protected video', 'protected-video-playlist' )
                    )
                );
            } else {
                previewContent = el(
                    Notice,
                    { status: 'error', isDismissible: false },
                    __( 'Could not recognise a YouTube video or playlist URL.', 'protected-video-playlist' )
                );
            }
        }


        return el(
            Fragment,
            null,
            sidebar,
            inputUI,
            url ? el( 'div', { style: { marginTop: '12px' } }, previewContent ) : null
        );
    }

    // ── Register ──────────────────────────────────────────────────────────────
    registerBlockType( 'protected-video-playlist/playlist', {
        edit: Edit,
        // save returns null → server-side rendered via render_callback in PHP.
        save: function () { return null; },
    } );

} )();
