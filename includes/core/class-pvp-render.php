<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



// ─────────────────────────────────────────────────────────────────────────────
// CORE RENDERING FUNCTIONS
// ─────────────────────────────────────────────────────────────────────────────
function pvp_render_single_video( $url, $overlay_text = '', $post_id = null ) {
    $video_id = pvp_extract_youtube_id( $url );
    if ( ! $video_id ) {
        return '<p class="pvp-error">' . esc_html__( 'Protected Playlist: Could not extract a video ID from:', 'protected-video-playlist' ) . ' ' . esc_html( $url ) . '</p>';
    }

    $options = pvp_get_settings();

    // ── Logo ──────────────────────────────────────────────────────────────────
    $logo_url      = '';
    $logo_unit     = 'px';
    $logo_width    = 80;
    $logo_opacity  = 100;
    $logo_position = 'TL';
    $logo_rounded  = 0;
    $logo_cropped  = 0;
    $radius      = 0;
    $radius_unit = 'px';
    $circle      = 0;

    if ( $post_id ) {
        $logo_active = get_post_meta( $post_id, '_pvp_video_logo_active', true );
        // Default to active if not set
        $logo_active = $logo_active === '' ? 1 : intval( $logo_active );
        
        $video_logo = get_post_meta( $post_id, '_pvp_video_logo', true );
        if ( $video_logo && $logo_active ) {
            $logo_url      = esc_url( $video_logo );
            $logo_width    = intval( get_post_meta( $post_id, '_pvp_video_logo_width', true ) )   ?: 80;
            $logo_unit     = sanitize_text_field( get_post_meta( $post_id, '_pvp_video_logo_unit', true ) ) ?: 'px'; 
            $logo_opacity  = intval( get_post_meta( $post_id, '_pvp_video_logo_opacity', true ) ) ?: 100;
            $logo_position = sanitize_text_field( get_post_meta( $post_id, '_pvp_video_logo_position', true ) ) ?: 'TL';
            $logo_rounded  = intval( get_post_meta( $post_id, '_pvp_video_logo_rounded', true ) );
            $logo_cropped  = intval( get_post_meta( $post_id, '_pvp_video_logo_cropped', true ) );
            $radius       = intval( get_post_meta( $post_id, '_pvp_video_logo_radius', true ) );
            $radius_unit  = get_post_meta( $post_id, '_pvp_video_logo_radius_unit', true ) ?: 'px';
            $circle       = intval( get_post_meta( $post_id, '_pvp_video_logo_circle', true ) );
        }
    }

    // Fall back to global logo
    if ( ! $logo_url && ! empty( $options['logo'] ) ) {
        $logo_url      = esc_url( $options['logo'] );
        $logo_width    = isset( $options['logo_width'] )       ? intval( $options['logo_width'] )                       : 80;
        $logo_unit     = isset( $options['logo_unit'] )        ? sanitize_text_field( $options['logo_unit'] )            : 'px';
        $logo_opacity  = isset( $options['logo_opacity'] )     ? intval( $options['logo_opacity'] )                     : 100;
        $logo_position = isset( $options['logo_position'] )    ? sanitize_text_field( $options['logo_position'] )       : 'TL';
        $radius        = isset( $options['logo_radius'] )      ? intval( $options['logo_radius'] )                      : 0;
        $radius_unit   = isset( $options['logo_radius_unit'] ) ? sanitize_text_field( $options['logo_radius_unit'] )    : 'px';
        $circle        = isset( $options['logo_circle'] )      ? intval( $options['logo_circle'] )                      : 0;
    }


    // Build logo position CSS
    $logo_position_css = 'position: absolute; z-index: 9999; pointer-events: none;';

    switch ( $logo_position ) {
        case 'TL':
            $logo_position_css .= ' top: 10px; left: 10px;';
            break;
        case 'TC':
            $logo_position_css .= ' top: 10px; left: 50%; transform: translateX(-50%);';
            break;
        case 'TR':
            $logo_position_css .= ' top: 10px; right: 10px;';
            break;
        case 'ML':
            $logo_position_css .= ' top: 50%; left: 10px; transform: translateY(-50%);';
            break;
        case 'MC':
            $logo_position_css .= ' top: 50%; left: 50%; transform: translate(-50%, -50%);';
            break;
        case 'MR':
            $logo_position_css .= ' top: 50%; right: 10px; transform: translateY(-50%);';
            break;
        case 'BL':
            $logo_position_css .= ' bottom: 40px; left: 10px;';
            break;
        case 'BC':
            $logo_position_css .= ' bottom: 40px; left: 50%; transform: translateX(-50%);';
            break;
        case 'BR':
            $logo_position_css .= ' bottom: 40px; right: 10px;';
            break;
        default:
            $logo_position_css .= ' top: 10px; left: 10px;';
    }

    $logo_style  = $logo_position_css;
    // $logo_style .= ' width: ' . intval( $logo_width ) . 'px;';
    // $logo_unit = get_post_meta( $post_id, '_pvp_video_logo_unit', true ) ?: 'px';
    $logo_style .= ' width: ' . intval( $logo_width ) . $logo_unit . ';';
    $logo_style .= ' opacity: ' . ( intval( $logo_opacity ) / 100 ) . ';';


    // $radius       = intval( get_post_meta( $post_id, '_pvp_video_logo_radius', true ) );
    // $radius_unit  = get_post_meta( $post_id, '_pvp_video_logo_radius_unit', true ) ?: 'px';
    // $circle       = intval( get_post_meta( $post_id, '_pvp_video_logo_circle', true ) );

    if ( $circle ) {
        $logo_style .= ' border-radius: 50%;';
        $logo_style .= ' height: ' . intval( $logo_width ) . $logo_unit . ';';
    } elseif ( $radius > 0 ) {
        $logo_style .= ' border-radius: ' . $radius . $radius_unit . ';';
        
    }


    $logo_link_url = '';
    if ( $post_id ) {
        $video_logo_url = get_post_meta( $post_id, '_pvp_video_logo_url', true );
        if ( $video_logo_url ) {
            $logo_link_url = esc_url( $video_logo_url );
        }
    }
    // Fall back to global logo URL
    if ( ! $logo_link_url && ! empty( $options['logo_url'] ) ) {
        $logo_link_url = esc_url( $options['logo_url'] );
    }

    // $logo_html = '';


    // if ( $logo_url ) {
    //     $logo_html = '<img class="pvp-logo" src="' . $logo_url . '" alt="" style="' . esc_attr( $logo_style ) . '" />';
    // }

    $logo_html = '';
if ( $logo_url ) {
    // Image gets only size and opacity — no positioning
    $img_style  = 'width: ' . intval( $logo_width ) . esc_attr( $logo_unit ) . ';';
    $img_style .= ' opacity: ' . ( intval( $logo_opacity ) / 100 ) . ';';
    $img_style .= ' height: auto; display: block;';

    if ( $circle ) {
        $img_style .= ' border-radius: 50%;';
        $img_style .= ' height: ' . intval( $logo_width ) . esc_attr( $logo_unit ) . ';';
    } elseif ( $radius > 0 ) {
        $img_style .= ' border-radius: ' . $radius . esc_attr( $radius_unit ) . ';';
    }

    $img = '<img class="pvp-logo" src="' . $logo_url . '" alt="" style="' . esc_attr( $img_style ) . '" />';

    if ( $logo_link_url ) {
        // Anchor gets all positioning styles
        $logo_html = '<a href="' . $logo_link_url . '" target="_blank" rel="noopener noreferrer" class="pvp-logo-link" style="' . esc_attr( $logo_position_css ) . ' pointer-events: all; display: block;">' . $img . '</a>';
    } else {
        // No link — wrap in span to hold positioning
        $logo_html = '<span class="pvp-logo-link" style="' . esc_attr( $logo_position_css ) . ' display: block;">' . $img . '</span>';
    }
}




    // ── Overlay text ──────────────────────────────────────────────────────────
    $final_overlay_text  = '';
    $overlay_width       = 100;
    $overlay_height      = 0;
    $overlay_x           = 0;
    $overlay_y           = 0;
    $overlay_padding     = 5;
    $overlay_bg          = '';
    $overlay_on_pause = 0;
    $overlay_on_end   = 0;
    $overlay_start = 0;
    $overlay_end   = 0;
    $time_ranges = wp_json_encode( array( array( 'start' => 0, 'end' => 0 ) ) );

    if ( $post_id ) {

        $overlay_active = get_post_meta( $post_id, '_pvp_video_overlay_active', true );
        $overlay_active = $overlay_active === '' ? 1 : intval( $overlay_active );


        $video_overlay = get_post_meta( $post_id, '_pvp_video_overlay_text', true );
        if ( $video_overlay && $overlay_active ) {
            $final_overlay_text = $video_overlay;
            $overlay_width      = intval( get_post_meta( $post_id, '_pvp_video_overlay_width', true ) )   ?: 100;
            $overlay_height     = intval( get_post_meta( $post_id, '_pvp_video_overlay_height', true ) )  ?: 0;
            $overlay_x          = intval( get_post_meta( $post_id, '_pvp_video_overlay_x', true ) )       ?: 0;
            $overlay_y          = intval( get_post_meta( $post_id, '_pvp_video_overlay_y', true ) )       ?: 0;
            $overlay_padding    = intval( get_post_meta( $post_id, '_pvp_video_overlay_padding', true ) ) ?: 5;
            $overlay_bg         =  pvp_sanitize_color( get_post_meta( $post_id, '_pvp_video_overlay_bg', true ) );

        }
        $overlay_start = intval( get_post_meta( $post_id, '_pvp_video_overlay_start', true ) );
        $overlay_end   = intval( get_post_meta( $post_id, '_pvp_video_overlay_end', true ) );
        $overlay_on_pause = intval( get_post_meta( $post_id, '_pvp_video_overlay_on_pause', true ) );
        $overlay_on_end   = intval( get_post_meta( $post_id, '_pvp_video_overlay_on_end', true ) );

        $ranges_raw  = get_post_meta( $post_id, '_pvp_video_overlay_time_ranges', true );
        $time_ranges = ! empty( $ranges_raw ) ? $ranges_raw : wp_json_encode( array( array( 'start' => 0, 'end' => 0 ) ) );
    }

    // Fall back to global overlay text
    if ( ! $final_overlay_text && ! empty( $options['overlay_text'] ) ) {
        $final_overlay_text = $options['overlay_text'];
        // Global time ranges
        if ( ! empty( $options['overlay_time_ranges'] ) ) {
            $time_ranges = $options['overlay_time_ranges'];
        }
    }

    // Build overlay CSS
    $overlay_style  = 'position: absolute; z-index: 9998; pointer-events: none;';
    $overlay_style .= ' width: '      . intval( $overlay_width )   . '%;';
    $overlay_style .= ' padding: '    . intval( $overlay_padding ) . 'px;';
    $overlay_style .= ' max-width: 100%; box-sizing: border-box;';
    $overlay_style .= ' color: var(--pvp-theme-color, #ff0000);';
    $overlay_style .= ' display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;';

    // Clamp position so overlay never goes out of bounds
    $x = intval( $overlay_x );
    $y = intval( $overlay_y );

    // Max left = 100 - width to keep overlay fully within frame
    $max_x = 100 - intval( $overlay_width );
    $x     = max( 0, min( $x, $max_x ) );
    $y     = max( 0, min( $y, 90 ) ); // Max 90% from top

    $overlay_style .= ' left: ' . $x . '%;';
    $overlay_style .= ' top: '  . $y . '%;';

    if ( $overlay_height > 0 ) {
        $overlay_style .= ' height: ' . intval( $overlay_height ) . '%;';
    }

    if ( $overlay_bg ) {
        $overlay_style .= ' background-color: ' . $overlay_bg . ';';
    }

    $overlay_html = '';
    if ( ! empty( $final_overlay_text ) ) {
        // $overlay_html = '<div class="pvp-overlay-text" style="' . esc_attr( $overlay_style ) . '">' . wp_kses_post( $final_overlay_text ) . '</div>';
        $overlay_html = '<div class="pvp-overlay-text" 
            style="' . esc_attr( $overlay_style ) . '"
            data-overlay-on-pause="' . esc_attr( $overlay_on_pause ) . '"
            data-overlay-on-end="' . esc_attr( $overlay_on_end ) . '"
            data-time-ranges="' . esc_attr( $time_ranges ) . '">'
            . wp_kses_post( $final_overlay_text ) .
        '</div>';
    }

    // ── Disable settings ──────────────────────────────────────────────────────
    $disable_volume     = ! empty( $options['disable_volume'] )     ? '1' : '0';
    $disable_controls   = ! empty( $options['disable_controls'] )   ? '1' : '0';
    $disable_fullscreen = ! empty( $options['disable_fullscreen'] ) ? '1' : '0';
    $disable_playbutton = ! empty( $options['disable_playbutton'] ) ? '1' : '0';
    $disable_autoplay   = ! empty( $options['disable_autoplay'] )   ? '1' : '0';

    
    if ( $post_id ) {
        $override_keys = array(
            'disable_volume',
            'disable_controls',
            'disable_fullscreen',
            'disable_playbutton',
            'disable_autoplay',
        );

        foreach ( $override_keys as $key ) {
            $meta_value = get_post_meta( $post_id, '_pvp_override_' . $key, true );
            if ( $meta_value !== '' ) {
                $$key = $meta_value ? '1' : '0';
            }
        }
    }

    // ── Branding colors ───────────────────────────────────────────────────────────
    $controls_bg_color = ! empty( $options['controls_bg_color'] ) ? $options['controls_bg_color'] : '';
    $controls_color    = ! empty( $options['controls_color'] )    ? $options['controls_color']    : '';
    $play_btn_bg_color = ! empty( $options['play_btn_bg_color'] ) ? $options['play_btn_bg_color'] : '';
    $play_btn_color    = ! empty( $options['play_btn_color'] )    ? $options['play_btn_color']    : '';

    // Per-video branding overrides global
    if ( $post_id ) {
        $video_controls_bg = get_post_meta( $post_id, '_pvp_controls_bg_color', true );
        $video_controls_c  = get_post_meta( $post_id, '_pvp_controls_color', true );
        $video_play_bg     = get_post_meta( $post_id, '_pvp_play_btn_bg_color', true );
        $video_play_c      = get_post_meta( $post_id, '_pvp_play_btn_color', true );

        if ( $video_controls_bg ) { $controls_bg_color = $video_controls_bg; }
        if ( $video_controls_c )  { $controls_color    = $video_controls_c; }
        if ( $video_play_bg )     { $play_btn_bg_color = $video_play_bg; }
        if ( $video_play_c )      { $play_btn_color    = $video_play_c; }
    }

    // Build inline CSS for branding
    $wrapper_id = 'pvp-video-' . ( $post_id ? intval( $post_id ) : uniqid() );
    $branding_css = '';
    if ( $controls_bg_color ) {
        $branding_css .= '#' . $wrapper_id . ' .plyr__controls, .plyr__controls { background: ' . $controls_bg_color . ' !important; }';
    }
    if ( $controls_color ) {
        $branding_css .= '#' . $wrapper_id . ' .plyr__controls .plyr__control, .plyr__controls .plyr__control { color: ' . $controls_color . ' !important; }';
        $branding_css .= '#' . $wrapper_id . ' .plyr__controls input[type="range"] { color: ' . $controls_color . ' !important; }';
    }
    if ( $play_btn_bg_color ) {
        $branding_css .= '#' . $wrapper_id . ' .plyr__control--overlaid, .plyr__control--overlaid { background: ' . $play_btn_bg_color . ' !important; }';
    }
    if ( $play_btn_color ) {
        $branding_css .= '#' . $wrapper_id . ' .plyr__control--overlaid svg, .plyr__control--overlaid svg { color: ' . $play_btn_color . ' !important; fill: ' . $play_btn_color . ' !important; }';
    }

    $branding_style = $branding_css ? '<style>' . $branding_css . '</style>' : '';

    // phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
    $encoded_service  = base64_encode( 'youtube' );
    $encoded_video_id = base64_encode( $video_id );
    // phpcs:enable

    return sprintf(
        '<div id="%s" class="pvp-video-wrapper" data-disable-volume="%s" data-disable-controls="%s" data-disable-fullscreen="%s" data-disable-playbutton="%s" data-disable-autoplay="%s">
            %s
            <div class="wp-block-protected-video-protected-video" data-id1="%s" data-id2="%s"></div>
            %s
            %s
        </div>',
        esc_attr( $wrapper_id ),
        esc_attr( $disable_volume ),
        esc_attr( $disable_controls ),
        esc_attr( $disable_fullscreen ),
        esc_attr( $disable_playbutton ),
        esc_attr( $disable_autoplay ),
        $branding_style,
        esc_attr( $encoded_service ),
        esc_attr( $encoded_video_id ),
        $overlay_html,
        $logo_html
    );
}

function pvp_render_grid_from_url( $url, $columns, $cache, $overlay_text = '' ) {
    $playlist_id = pvp_extract_playlist_id( $url );

    if ( ! $playlist_id ) {
        return '<p class="pvp-error">' . esc_html__( 'Protected Playlist: Could not extract a playlist ID from the URL.', 'protected-video-playlist' ) . '</p>';
    }

    // Check if CPT posts exist for this playlist
    $existing_ids = get_posts( array(
        'post_type'   => 'pvp_video',
        'meta_key'    => '_pvp_playlist_id',
        'meta_value'  => $playlist_id,
        'numberposts' => -1,
        'fields'      => 'ids',
    ) );

    if ( empty( $existing_ids ) ) {
        return '<p class="pvp-error">' . esc_html__( 'Protected Playlist: No imported videos found for this playlist. Please import or sync this playlist from the WordPress admin area before displaying it on the frontend.', 'protected-video-playlist' ) . '</p>';
    }

    // Pagination
    $per_page    = 12;
    $total       = count( $existing_ids );
    $total_pages = ceil( $total / $per_page );
    $paged       = isset( $_GET['pvp_page'] ) ? max( 1, intval( $_GET['pvp_page'] ) ) : 1;
    $offset      = ( $paged - 1 ) * $per_page;

    $posts = get_posts( array(
        'post_type'   => 'pvp_video',
        'meta_key'    => '_pvp_playlist_id',
        'meta_value'  => $playlist_id,
        'numberposts' => $per_page,
        'offset'      => $offset,
        'orderby'     => 'date',
        'order'       => 'ASC',
    ) );

    if ( empty( $posts ) ) {
        return '<p class="pvp-error">' . esc_html__( 'Protected Playlist: No videos found on this page.', 'protected-video-playlist' ) . '</p>';
    }

    $videos = array();
    foreach ( $posts as $post ) {
        $videos[] = array(
            'url'     => get_post_meta( $post->ID, '_pvp_video_url', true ),
            'title'   => $post->post_title,
            'post_id' => $post->ID,
        );
    }

    $output = pvp_render_grid( $videos, $columns, $overlay_text );

    // Pagination links
    
    if ( $total_pages > 1 ) {
    $output .= '<nav class="navigation pagination pvp-pagination" aria-label="' . esc_attr__( 'Videos navigation', 'protected-video-playlist' ) . '">';
    $output .= '<div class="nav-links">';
    $output .= paginate_links( array(
        'base'      => add_query_arg( 'pvp_page', '%#%', get_permalink() ),
        'format'    => '?pvp_page=%#%',
        'current'   => $paged,
        'total'     => $total_pages,
        'prev_text' => '&laquo;',
        'next_text' => '&raquo;',
        'end_size'  => 2,
        'mid_size'  => 1,
        'type'      => 'plain',
    ) );
    $output .= '</div>';
    $output .= '</nav>';
}
    return $output;
}

function pvp_render_grid( $videos, $columns, $overlay_text = '' ) {
    $allowed_html = array(
        'style'                   => array(),
        'div' => array(
            'id'                      => array(),
            'class'                   => array(),
            'data-id1'                => array(),
            'data-id2'                => array(),
            'data-disable-volume'     => array(),
            'data-disable-controls'   => array(),
            'data-disable-fullscreen' => array(),
            'data-disable-playbutton' => array(),
            'data-disable-autoplay'   => array(),
            'data-overlay-start'      => array(),
            'data-overlay-end'        => array(),
            'data-overlay-on-pause'   => array(),
            'data-overlay-on-end'     => array(),
            'style'                   => array(),
            'data-time-ranges'        => array(),
        ),
        'img' => array(
            'class' => array(),
            'src'   => array(),
            'alt'   => array(),
            'style' => array(),
        ),
        'p' => array(
            'class' => array(),
        ),
        'strong' => array(),
        'em'     => array(),
        'br'     => array(),
        'a'      => array(
            'href'  => array(),
            'title' => array(),
            'target'=> array(),
        ),
        'span' => array(
            'style' => array(),
            'class' => array(),
        ),
        'a' => array(
            'href'   => array(),
            'target' => array(),
            'rel'    => array(),
            'style'  => array(),
            'class'  => array(),
        ),
        
    );

    ob_start();
    ?>
    <div class="pvp-grid pvp-columns-<?php echo esc_attr( $columns ); ?>">
        <?php foreach ( $videos as $video ) : ?>
            <div class="pvp-grid__item">
                <?php echo wp_kses( pvp_render_single_video( $video['url'], '', $video['post_id'] ?? null ), $allowed_html ); ?>
                <?php if ( ! empty( $video['title'] ) ) : ?>
                    <p class="pvp-grid__title"><?php echo esc_html( $video['title'] ); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

function pvp_render_logo_size_field() {
    $options = get_option( 'pvp_settings', array() );
    $width   = isset( $options['logo_width'] ) ? $options['logo_width'] : 80;
    $unit    = isset( $options['logo_unit'] )  ? $options['logo_unit']  : 'px';
    ?>
    <div style="display: flex; align-items: center; gap: 8px;">
        <input type="number" name="pvp_settings[logo_width]" value="<?php echo esc_attr( $width ); ?>" min="1" style="width: 80px;" />
        <select name="pvp_settings[logo_unit]">
            <option value="px"  <?php selected( $unit, 'px' ); ?>>px</option>
            <option value="%"   <?php selected( $unit, '%' ); ?>>%</option>
            <option value="rem" <?php selected( $unit, 'rem' ); ?>>rem</option>
        </select>
    </div>
    <?php
}

function pvp_render_logo_opacity_field() {
    $options = get_option( 'pvp_settings', array() );
    $value   = isset( $options['logo_opacity'] ) ? $options['logo_opacity'] : 100;
    ?>
    <div style="display: flex; align-items: center; gap: 10px;">
        <input type="number" name="pvp_settings[logo_opacity]" value="<?php echo esc_attr( $value ); ?>" min="0" max="100" style="width: 70px;" oninput="document.getElementById('pvp_logo_opacity_range_global').value = this.value" />
        <input type="range" id="pvp_logo_opacity_range_global" value="<?php echo esc_attr( $value ); ?>" min="0" max="100" style="width: 120px;" oninput="this.previousElementSibling.value = this.value" />
    </div>
    <?php
}

function pvp_render_logo_position_field() {
    $options  = get_option( 'pvp_settings', array() );
    $position = isset( $options['logo_position'] ) ? $options['logo_position'] : 'TL';
    $positions = array(
        'TL' => '↖', 'TC' => '↑', 'TR' => '↗',
        'ML' => '←', 'MC' => '·', 'MR' => '→',
        'BL' => '↙', 'BC' => '↓', 'BR' => '↘',
    );
    ?>
    <div style="display: grid; grid-template-columns: repeat(3, 40px); gap: 5px;">
        <?php foreach ( $positions as $pos => $icon ) :
            $is_active = $position === $pos;
        ?>
            <label style="cursor: pointer; text-align: center;">
                <input
                    type="radio"
                    name="pvp_settings[logo_position]"
                    value="<?php echo esc_attr( $pos ); ?>"
                    <?php checked( $position, $pos ); ?>
                    style="display: none;"
                    onchange="document.querySelectorAll('.pvp-global-pos-btn').forEach(function(b){ b.style.background='#fff'; b.style.color='#000'; b.style.borderColor='#ddd'; }); this.nextElementSibling.style.background='#007cba'; this.nextElementSibling.style.color='#fff'; this.nextElementSibling.style.borderColor='#007cba';"
                />
                <span class="pvp-global-pos-btn" title="<?php echo esc_attr( $pos ); ?>" style="display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid <?php echo $is_active ? '#007cba' : '#ddd'; ?>; border-radius: 4px; background: <?php echo $is_active ? '#007cba' : '#fff'; ?>; color: <?php echo $is_active ? '#fff' : '#000'; ?>; font-size: 1.2rem;">
                    <?php echo esc_html( $icon ); ?>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
    <?php
}

function pvp_render_logo_radius_field() {
    $options     = get_option( 'pvp_settings', array() );
    $radius      = isset( $options['logo_radius'] )      ? $options['logo_radius']      : 0;
    $radius_unit = isset( $options['logo_radius_unit'] ) ? $options['logo_radius_unit'] : 'px';
    $circle      = isset( $options['logo_circle'] )      ? $options['logo_circle']      : 0;
    ?>
    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 8px;">
            <input type="number" name="pvp_settings[logo_radius]" value="<?php echo esc_attr( $radius ); ?>" min="0" style="width: 70px;" />
            <select name="pvp_settings[logo_radius_unit]">
                <option value="px" <?php selected( $radius_unit, 'px' ); ?>>px</option>
                <option value="%" <?php selected( $radius_unit, '%' ); ?>>%</option>
            </select>
        </div>
        <label style="display: flex; align-items: center; gap: 6px;">
            <input type="checkbox" name="pvp_settings[logo_circle]" value="1" <?php checked( $circle, 1 ); ?> />
            <strong><?php esc_html_e( 'Circle', 'protected-video-playlist' ); ?></strong>
        </label>
    </div>
    <?php
}

function pvp_render_overlay_display_on_field() {
    $options  = get_option( 'pvp_settings', array() );
    $on_pause = isset( $options['overlay_on_pause'] ) ? $options['overlay_on_pause'] : 0;
    $on_end   = isset( $options['overlay_on_end'] )   ? $options['overlay_on_end']   : 0;
    ?>
    <div style="display: flex; gap: 20px;">
        <label style="display: flex; align-items: center; gap: 5px;">
            <input type="checkbox" name="pvp_settings[overlay_on_pause]" value="1" <?php checked( $on_pause, 1 ); ?> />
            <?php esc_html_e( 'Pause Screen', 'protected-video-playlist' ); ?>
        </label>
        <label style="display: flex; align-items: center; gap: 5px;">
            <input type="checkbox" name="pvp_settings[overlay_on_end]" value="1" <?php checked( $on_end, 1 ); ?> />
            <?php esc_html_e( 'End Screen', 'protected-video-playlist' ); ?>
        </label>
    </div>
    <?php
}

function pvp_render_overlay_time_ranges_field() {
    $options     = get_option( 'pvp_settings', array() );
    $time_ranges = isset( $options['overlay_time_ranges'] ) ? json_decode( $options['overlay_time_ranges'], true ) : array();
    if ( empty( $time_ranges ) ) {
        $time_ranges = array( array( 'start' => 0, 'end' => 0 ) );
    }
    ?>
    <p class="description"><?php esc_html_e( 'Leave all at 0 to always show.', 'protected-video-playlist' ); ?></p>
    <div id="pvp-default-time-ranges-container">
        <?php foreach ( $time_ranges as $ri => $range ) :
            $start = intval( $range['start'] ?? 0 );
            $end   = intval( $range['end']   ?? 0 );
            $sh = floor( $start / 3600 ); $sm = floor( ( $start % 3600 ) / 60 ); $ss = $start % 60;
            $eh = floor( $end / 3600 );   $em = floor( ( $end % 3600 ) / 60 );   $es = $end % 60;
        ?>
            <div class="pvp-default-time-range-row" style="display: flex; gap: 20px; flex-wrap: wrap; align-items: center; margin-bottom: 10px; padding: 10px; border: 1px solid #eee; border-radius: 4px;">
                <div>
                    <label><strong><?php esc_html_e( 'Show at', 'protected-video-playlist' ); ?></strong></label>
                    <div style="display: flex; gap: 5px; align-items: center; margin-top: 4px;">
                        <input type="number" name="pvp_settings[overlay_time_ranges][<?php echo esc_attr( $ri ); ?>][start_h]" value="<?php echo esc_attr( $sh ); ?>" min="0" max="23" style="width:55px;" />:
                        <input type="number" name="pvp_settings[overlay_time_ranges][<?php echo esc_attr( $ri ); ?>][start_m]" value="<?php echo esc_attr( $sm ); ?>" min="0" max="59" style="width:55px;" />:
                        <input type="number" name="pvp_settings[overlay_time_ranges][<?php echo esc_attr( $ri ); ?>][start_s]" value="<?php echo esc_attr( $ss ); ?>" min="0" max="59" style="width:55px;" />
                    </div>
                </div>
                <div>
                    <label><strong><?php esc_html_e( 'Hide at', 'protected-video-playlist' ); ?></strong></label>
                    <div style="display: flex; gap: 5px; align-items: center; margin-top: 4px;">
                        <input type="number" name="pvp_settings[overlay_time_ranges][<?php echo esc_attr( $ri ); ?>][end_h]" value="<?php echo esc_attr( $eh ); ?>" min="0" max="23" style="width:55px;" />:
                        <input type="number" name="pvp_settings[overlay_time_ranges][<?php echo esc_attr( $ri ); ?>][end_m]" value="<?php echo esc_attr( $em ); ?>" min="0" max="59" style="width:55px;" />:
                        <input type="number" name="pvp_settings[overlay_time_ranges][<?php echo esc_attr( $ri ); ?>][end_s]" value="<?php echo esc_attr( $es ); ?>" min="0" max="59" style="width:55px;" />
                    </div>
                </div>
                <?php if ( $ri > 0 ) : ?>
                    <div style="align-self: flex-end;">
                        <button type="button" class="button pvp-delete-default-time-range" style="background: #dc3232; color: #fff; border-color: #dc3232;"><?php esc_html_e( 'Delete', 'protected-video-playlist' ); ?></button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="pvp-add-default-time-range" class="button" style="border: 2px solid #007cba; color: #007cba; background: transparent; width: 40px; height: 40px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; border-radius: 4px; margin-top: 10px;">+</button>
    <?php
}

function pvp_render_overlay_position_field() {
    $options = get_option( 'pvp_settings', array() );
    $width   = isset( $options['overlay_width'] )   ? $options['overlay_width']   : 100;
    $height  = isset( $options['overlay_height'] )  ? $options['overlay_height']  : 0;
    $x       = isset( $options['overlay_x'] )       ? $options['overlay_x']       : 0;
    $y       = isset( $options['overlay_y'] )       ? $options['overlay_y']       : 0;
    $padding = isset( $options['overlay_padding'] ) ? $options['overlay_padding'] : 5;
    ?>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; max-width: 500px;">
        <div>
            <label><strong><?php esc_html_e( 'Height (%)', 'protected-video-playlist' ); ?></strong> <span id="pvp_g_overlay_height_val"><?php echo esc_html( $height ); ?>%</span></label>
            <input type="range" name="pvp_settings[overlay_height]" value="<?php echo esc_attr( $height ); ?>" min="0" max="100" style="width: 100%;" oninput="document.getElementById('pvp_g_overlay_height_val').textContent = this.value + '%'" />
        </div>
        <div>
            <label><strong><?php esc_html_e( 'Width (%)', 'protected-video-playlist' ); ?></strong> <span id="pvp_g_overlay_width_val"><?php echo esc_html( $width ); ?>%</span></label>
            <input type="range" name="pvp_settings[overlay_width]" value="<?php echo esc_attr( $width ); ?>" min="0" max="100" style="width: 100%;" oninput="document.getElementById('pvp_g_overlay_width_val').textContent = this.value + '%'" />
        </div>
        <div>
            <label><strong><?php esc_html_e( 'X Position (%)', 'protected-video-playlist' ); ?></strong> <span id="pvp_g_overlay_x_val"><?php echo esc_html( $x ); ?>%</span></label>
            <input type="range" name="pvp_settings[overlay_x]" value="<?php echo esc_attr( $x ); ?>" min="0" max="100" style="width: 100%;" oninput="document.getElementById('pvp_g_overlay_x_val').textContent = this.value + '%'" />
        </div>
        <div>
            <label><strong><?php esc_html_e( 'Y Position (%)', 'protected-video-playlist' ); ?></strong> <span id="pvp_g_overlay_y_val"><?php echo esc_html( $y ); ?>%</span></label>
            <input type="range" name="pvp_settings[overlay_y]" value="<?php echo esc_attr( $y ); ?>" min="0" max="100" style="width: 100%;" oninput="document.getElementById('pvp_g_overlay_y_val').textContent = this.value + '%'" />
        </div>
        <div>
            <label><strong><?php esc_html_e( 'Padding (px)', 'protected-video-playlist' ); ?></strong> <span id="pvp_g_overlay_padding_val"><?php echo esc_html( $padding ); ?>px</span></label>
            <input type="range" name="pvp_settings[overlay_padding]" value="<?php echo esc_attr( $padding ); ?>" min="0" max="50" style="width: 100%;" oninput="document.getElementById('pvp_g_overlay_padding_val').textContent = this.value + 'px'" />
        </div>
    </div>
    <?php
}

function pvp_render_overlay_bg_field() {
    $options = get_option( 'pvp_settings', array() );
    $value   = isset( $options['overlay_bg'] ) ? $options['overlay_bg'] : '';
    ?>
    <input type="text" class="pvp-color-field" data-alpha-enabled="true" name="pvp_settings[overlay_bg]" value="<?php echo esc_attr( $value ); ?>" />
    <?php
}
