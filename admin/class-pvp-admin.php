<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Register submenu page ─────────────────────────────────────────────────────
add_action( 'admin_menu', 'pvp_add_submenu_page', 999 );

function pvp_add_submenu_page() {
    add_submenu_page(
        'options-general.php',
        'Protected Video Add-on',
        'Protected Video Add-on',
        'manage_options',
        'protected-video-playlist',
        'pvp_render_settings_page'
    );
}

// ── Enqueue admin assets ──────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'pvp_admin_assets' );

function pvp_admin_assets( $hook ) {
    if (
        ! isset( $_GET['page'] ) ||
        sanitize_key( wp_unslash( $_GET['page'] ) ) !== 'protected-video-playlist'
    ) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_style(
        'pvp-admin-style',
        PVP_PLUGIN_URL . 'admin/css/admin.css',
        array(),
        PVP_VERSION
    );
    wp_enqueue_script(
        'wp-color-picker-alpha',
        PVP_PLUGIN_URL . 'admin/js/wp-color-picker-alpha.js',
        array( 'wp-color-picker' ),
        PVP_VERSION,
        true
    );
    wp_enqueue_script(
        'pvp-admin-js',
        PVP_PLUGIN_URL . 'admin/js/admin.js',
        array( 'wp-color-picker', 'wp-color-picker-alpha', 'jquery' ),
        PVP_VERSION,
        true
    );
    error_log( 'Alpha picker path: ' . PVP_PLUGIN_URL . 'admin/js/wp-color-picker-alpha.js' );
}

// ── Render settings page ──────────────────────────────────────────────────────
function pvp_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    settings_errors( 'pvp_messages' );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Protected Video Add-on Settings', 'protected-video-playlist' ); ?></h1>

        <form method="post" action="options.php">
            <?php
            settings_fields( 'pvp_settings_group' );
            do_settings_sections( 'protected-video-playlist' );
            submit_button();
            ?>
        </form>

        <hr>
        <h2><?php esc_html_e( 'Playlist Cache', 'protected-video-playlist' ); ?></h2>
        <p><?php esc_html_e( 'Playlist data is cached for 1 hour. Click below to force a refresh immediately.', 'protected-video-playlist' ); ?></p>
        <form method="post">
            <?php wp_nonce_field( 'pvp_flush_cache_action', 'pvp_flush_cache_nonce' ); ?>
            <?php submit_button( 'Flush Playlist Cache', 'secondary', 'pvp_flush_cache', false ); ?>
        </form>
    </div>
    <?php
}

// ── Register settings ─────────────────────────────────────────────────────────
add_action( 'admin_init', 'pvp_register_settings' );

function pvp_register_settings() {
    register_setting(
        'pvp_settings_group',
        'pvp_settings',
        array(
            'sanitize_callback' => 'pvp_sanitize_settings',
            'default'           => array(),
        )
    );

    add_settings_section(
        'pvp_general_section',
        __( 'General', 'protected-video-playlist' ),
        '__return_false',
        'protected-video-playlist'
    );

    add_settings_field(
        'pvp_delete_on_uninstall',
        __( 'Delete Data on Uninstall', 'protected-video-playlist' ),
        'pvp_render_delete_on_uninstall_field',
        'protected-video-playlist',
        'pvp_general_section'
    );

    add_settings_field(
        'pvp_youtube_api_key',
        __( 'YouTube API Key (Optional)', 'protected-video-playlist' ),
        'pvp_render_api_key_field',
        'protected-video-playlist',
        'pvp_general_section'
    );

    add_settings_field(
        'pvp_default_columns',
        __( 'Default Columns', 'protected-video-playlist' ),
        'pvp_render_default_columns_field',
        'protected-video-playlist',
        'pvp_general_section'
    );

    add_settings_field(
        'pvp_logo',
        __( 'Default Logo', 'protected-video-playlist' ),
        'pvp_render_image_field',
        'protected-video-playlist',
        'pvp_general_section',
        array( 'key' => 'logo' )
    );

    add_settings_field(
        'pvp_logo_url',
        __( 'Logo Link URL', 'protected-video-playlist' ),
        'pvp_render_logo_url_field',
        'protected-video-playlist',
        'pvp_general_section'
    );

    add_settings_field(
        'pvp_overlay_text',
        __( 'Marketing Text', 'protected-video-playlist' ),
        'pvp_render_overlay_text_field',
        'protected-video-playlist',
        'pvp_general_section'
    );

    // Logo settings section
    add_settings_section(
        'pvp_logo_section',
        __( 'Default Logo Settings', 'protected-video-playlist' ),
        '__return_false',
        'protected-video-playlist'
    );

    add_settings_field( 'pvp_logo_width', __( 'Logo Size', 'protected-video-playlist' ), 'pvp_render_logo_size_field', 'protected-video-playlist', 'pvp_logo_section' );
    add_settings_field( 'pvp_logo_opacity', __( 'Logo Opacity (%)', 'protected-video-playlist' ), 'pvp_render_logo_opacity_field', 'protected-video-playlist', 'pvp_logo_section' );
    add_settings_field( 'pvp_logo_position', __( 'Logo Placement', 'protected-video-playlist' ), 'pvp_render_logo_position_field', 'protected-video-playlist', 'pvp_logo_section' );
    add_settings_field( 'pvp_logo_radius', __( 'Logo Rounded', 'protected-video-playlist' ), 'pvp_render_logo_radius_field', 'protected-video-playlist', 'pvp_logo_section' );

    // Overlay settings section
    add_settings_section(
        'pvp_overlay_section',
        __( 'Default Marketing Overlay Settings', 'protected-video-playlist' ),
        '__return_false',
        'protected-video-playlist'
    );

    add_settings_field( 'pvp_overlay_on_pause', __( 'Display On', 'protected-video-playlist' ), 'pvp_render_overlay_display_on_field', 'protected-video-playlist', 'pvp_overlay_section' );
    add_settings_field( 'pvp_overlay_time_ranges', __( 'Display Time Ranges', 'protected-video-playlist' ), 'pvp_render_overlay_time_ranges_field', 'protected-video-playlist', 'pvp_overlay_section' );
    add_settings_field( 'pvp_overlay_position', __( 'Overlay Position', 'protected-video-playlist' ), 'pvp_render_overlay_position_field', 'protected-video-playlist', 'pvp_overlay_section' );
    add_settings_field( 'pvp_overlay_bg', __( 'Background Color', 'protected-video-playlist' ), 'pvp_render_overlay_bg_field', 'protected-video-playlist', 'pvp_overlay_section' );


    $checkboxes = array(
        'disable_volume'     => __( 'Disable Volume', 'protected-video-playlist' ),
        'disable_playbutton' => __( 'Disable Play Button', 'protected-video-playlist' ),
        'disable_fullscreen' => __( 'Disable Fullscreen', 'protected-video-playlist' ),
        'disable_controls'   => __( 'Disable Controls', 'protected-video-playlist' ),
        'disable_autoplay'   => __( 'Disable Autoplay', 'protected-video-playlist' ),
    );

    foreach ( $checkboxes as $key => $label ) {
        add_settings_field(
            'pvp_' . $key,
            $label,
            'pvp_render_checkbox_field',
            'protected-video-playlist',
            'pvp_general_section',
            array( 'key' => $key )
        );
    }

    add_settings_section(
        'pvp_branding_section',
        __( 'Branding Colors', 'protected-video-playlist' ),
        '__return_false',
        'protected-video-playlist'
    );

    $color_fields = array(
        'controls_bg_color'       => __( 'Controls Bar Background', 'protected-video-playlist' ),
        'controls_color'          => __( 'Controls Icons Color', 'protected-video-playlist' ),
        'play_btn_bg_color'       => __( 'Play Button Background', 'protected-video-playlist' ),
        'play_btn_color'          => __( 'Play Button Icon Color', 'protected-video-playlist' ),
    );

    foreach ( $color_fields as $key => $label ) {
        add_settings_field(
            'pvp_' . $key,
            $label,
            'pvp_render_branding_color_field',
            'protected-video-playlist',
            'pvp_branding_section',
            array( 'key' => $key )
        );
    }


}

// ── Settings field renderers ──────────────────────────────────────────────────
function pvp_render_api_key_field() {
    $options = get_option( 'pvp_settings', array() );
    $value   = isset( $options['youtube_api_key'] ) ? $options['youtube_api_key'] : '';
    ?>
    <input
        type="text"
        name="pvp_settings[youtube_api_key]"
        value="<?php echo esc_attr( $value ); ?>"
        class="regular-text pvp-api-key"
        placeholder="AIza..."
        autocomplete="off"
    />
    <p class="description">
        <?php esc_html_e( 'YouTube Data API v3 key. Leave blank to use RSS feed (15 videos). With API key, all videos from playlist are synced as CPT.', 'protected-video-playlist' ); ?>
    </p>
    <?php
}


function pvp_render_delete_on_uninstall_field() {
    $options = get_option( 'pvp_settings', array() );
    $checked = ! empty( $options['delete_on_uninstall'] );
    ?>
    <label style="display: flex; align-items: center; gap: 8px;">
        <input
            type="checkbox"
            name="pvp_settings[delete_on_uninstall]"
            value="1"
            <?php checked( $checked ); ?>
        />
        <span><?php esc_html_e( 'Delete all saved playlist videos and settings data when plugin is uninstalled.', 'protected-video-playlist' ); ?></span>
    </label>
    <p class="description" style="color: #dc3232; margin-top: 5px;">
        <?php esc_html_e( 'Warning: This cannot be undone. Leave unchecked to keep your data when uninstalling.', 'protected-video-playlist' ); ?>
    </p>
    <?php
}


function pvp_render_default_columns_field() {
    $options = get_option( 'pvp_settings', array() );
    $value   = isset( $options['default_columns'] ) ? intval( $options['default_columns'] ) : 3;
    ?>
    <select name="pvp_settings[default_columns]">
        <?php foreach ( array( 1, 2, 3, 4 ) as $n ) : ?>
            <option value="<?php echo esc_attr( $n ); ?>" <?php selected( $value, $n ); ?>>
                <?php echo esc_html( $n ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        <?php esc_html_e( 'Number of columns used in the playlist grid by default.', 'protected-video-playlist' ); ?>
    </p>
    <?php
}

function pvp_render_image_field( $args ) {
    $options = get_option( 'pvp_settings', array() );
    $key     = $args['key'];
    $value   = isset( $options[ $key ] ) ? $options[ $key ] : '';
    ?>
    <div style="display: flex; align-items: center; gap: 10px;">
        <input
            type="text"
            name="pvp_settings[<?php echo esc_attr( $key ); ?>]"
            value="<?php echo esc_url( $value ); ?>"
            class="pvp-image-url regular-text"
        />
        <button type="button" class="button pvp-upload-btn">
            <?php esc_html_e( 'Upload', 'protected-video-playlist' ); ?>
        </button>
        <img
            class="pvp-image-preview"
            src="<?php echo esc_url( $value ); ?>"
            alt=""
            style="max-width: 60px; max-height: 60px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; padding: 3px; <?php echo $value ? '' : 'display: none;'; ?>"
        />
    </div>
    <?php
}


function pvp_render_logo_url_field() {
    $options = get_option( 'pvp_settings', array() );
    $value   = isset( $options['logo_url'] ) ? $options['logo_url'] : '';
    ?>
    <input
        type="url"
        name="pvp_settings[logo_url]"
        value="<?php echo esc_url( $value ); ?>"
        class="regular-text"
        placeholder="https://..."
    />
    <p class="description">
        <?php esc_html_e( 'URL to open when logo is clicked. Leave blank for no link.', 'protected-video-playlist' ); ?>
    </p>
    <?php
}

function pvp_render_overlay_text_field() {
    $options = get_option( 'pvp_settings', array() );
    $value   = isset( $options['overlay_text'] ) ? $options['overlay_text'] : '';

    ?>

    <div class="pvp-editor-field">
        <?php

        wp_editor(
            $value,
            'pvp_overlay_text_editor', // unique ID
            array(
                'textarea_name' => 'pvp_settings[overlay_text]',
                'textarea_rows' => 3,
                'media_buttons' => true,
                'teeny'         => false,
                'quicktags'     => true,
                'tinymce'       => array(
                    'toolbar1' => 'bold,italic,underline,forecolor,backcolor,alignleft,aligncenter,alignright,link,unlink',
                ),
            )
        );
        ?>
        
        <p class="description">
            <?php esc_html_e( 'Text displayed as a marketing overlay on top of each video.', 'protected-video-playlist' ); ?>
        </p>
    </div>
    <?php
}

function pvp_render_checkbox_field( $args ) {
    $options = get_option( 'pvp_settings', array() );
    $key     = $args['key'];
    $checked = ! empty( $options[ $key ] );
    ?>
    <input
        type="checkbox"
        name="pvp_settings[<?php echo esc_attr( $key ); ?>]"
        value="1"
        <?php checked( $checked ); ?>
    />
    <?php
}

// ── Sanitize settings ─────────────────────────────────────────────────────────
function pvp_sanitize_settings( $input ) {
    error_log( 'pvp_sanitize input: ' . print_r( $input, true ) );
    $output = array();

    if ( isset( $input['youtube_api_key'] ) ) {
        $output['youtube_api_key'] = sanitize_text_field( $input['youtube_api_key'] );
    }

    $output['delete_on_uninstall'] = isset( $input['delete_on_uninstall'] ) ? 1 : 0;
    
    if ( isset( $input['default_columns'] ) ) {
        $output['default_columns'] = max( 1, min( 4, intval( $input['default_columns'] ) ) );
    }

    if ( isset( $input['logo'] ) ) {
        $output['logo'] = esc_url_raw( $input['logo'] );
    }

    if ( isset( $input['logo_url'] ) ) {
        $output['logo_url'] = esc_url_raw( $input['logo_url'] );
    }

    // Logo settings
if ( isset( $input['logo_width'] ) )       { $output['logo_width']       = intval( $input['logo_width'] ); }
if ( isset( $input['logo_unit'] ) )        { $output['logo_unit']        = sanitize_text_field( $input['logo_unit'] ); }
if ( isset( $input['logo_opacity'] ) )     { $output['logo_opacity']     = max( 0, min( 100, intval( $input['logo_opacity'] ) ) ); }
if ( isset( $input['logo_position'] ) )    { $output['logo_position']    = sanitize_text_field( $input['logo_position'] ); }
if ( isset( $input['logo_radius'] ) )      { $output['logo_radius']      = intval( $input['logo_radius'] ); }
if ( isset( $input['logo_radius_unit'] ) ) { $output['logo_radius_unit'] = sanitize_text_field( $input['logo_radius_unit'] ); }
$output['logo_circle'] = isset( $input['logo_circle'] ) ? 1 : 0;

// Overlay settings
$output['overlay_on_pause'] = isset( $input['overlay_on_pause'] ) ? 1 : 0;
$output['overlay_on_end']   = isset( $input['overlay_on_end'] )   ? 1 : 0;
if ( isset( $input['overlay_width'] ) )   { $output['overlay_width']   = intval( $input['overlay_width'] ); }
if ( isset( $input['overlay_height'] ) )  { $output['overlay_height']  = intval( $input['overlay_height'] ); }
if ( isset( $input['overlay_x'] ) )       { $output['overlay_x']       = intval( $input['overlay_x'] ); }
if ( isset( $input['overlay_y'] ) )       { $output['overlay_y']       = intval( $input['overlay_y'] ); }
if ( isset( $input['overlay_padding'] ) ) { $output['overlay_padding'] = intval( $input['overlay_padding'] ); }
if ( isset( $input['overlay_bg'] ) )      { $output['overlay_bg']      = pvp_sanitize_color( $input['overlay_bg'] ); }

// Overlay time ranges
if ( isset( $input['overlay_time_ranges'] ) && is_array( $input['overlay_time_ranges'] ) ) {
    $time_ranges = array();
    foreach ( $input['overlay_time_ranges'] as $range ) {
        $start = intval( $range['start_h'] ?? 0 ) * 3600 + intval( $range['start_m'] ?? 0 ) * 60 + intval( $range['start_s'] ?? 0 );
        $end   = intval( $range['end_h']   ?? 0 ) * 3600 + intval( $range['end_m']   ?? 0 ) * 60 + intval( $range['end_s']   ?? 0 );
        $time_ranges[] = array( 'start' => $start, 'end' => $end );
    }
    $output['overlay_time_ranges'] = wp_json_encode( $time_ranges );
}

    if ( isset( $input['overlay_text'] ) ) {
        // $output['overlay_text'] = sanitize_text_field( $input['overlay_text'] );
        $output['overlay_text'] = wp_kses_post( $input['overlay_text'] );
    }

    $checkboxes = array(
        'disable_volume',
        'disable_playbutton',
        'disable_fullscreen',
        'disable_controls',
        'disable_autoplay',
    );

    foreach ( $checkboxes as $cb ) {
        $output[ $cb ] = isset( $input[ $cb ] ) ? 1 : 0;
    }


    $color_fields = array(
        'controls_bg_color',
        'controls_color',
        'play_btn_bg_color',
        'play_btn_color',
    );

    foreach ( $color_fields as $field ) {
        if ( isset( $input[ $field ] ) ) {
            $output[ $field ] = pvp_sanitize_color( $input[ $field ] );
        }
    }

    return $output;
}

function pvp_render_branding_color_field( $args ) {
    $options = get_option( 'pvp_settings', array() );
    $key     = $args['key'];
    $value   = isset( $options[ $key ] ) ? $options[ $key ] : '';
    ?>
    <input
        type="text"
        class="pvp-color-field"
        data-alpha-enabled="true"
        name="pvp_settings[<?php echo esc_attr( $key ); ?>]"
        value="<?php echo esc_attr( $value ); ?>"
    />
    <?php
}

add_action( 'admin_init', 'pvp_handle_cache_flush' );

function pvp_handle_cache_flush() {

    // Capability check (SECURITY)
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // Proper page check
    if (
        ! isset( $_GET['page'] ) ||
        sanitize_key( wp_unslash( $_GET['page'] ) ) !== 'protected-video-playlist'
    ) {
        return;
    }

    // Check form submit + nonce
    if (
        isset( $_POST['pvp_flush_cache'] ) &&
        check_admin_referer( 'pvp_flush_cache_action', 'pvp_flush_cache_nonce' )
    ) {

        // Delete transients safely (no DB query)
        $keys = get_option( 'pvp_transient_keys', array() );

        if ( ! empty( $keys ) && is_array( $keys ) ) {
            foreach ( $keys as $key ) {
                delete_transient( $key );
            }
        }

        delete_option( 'pvp_transient_keys' );

        // Admin notice
        add_settings_error(
            'pvp_messages',
            'pvp_cache_flushed',
            __( 'Playlist cache cleared successfully.', 'protected-video-playlist' ),
            'updated'
        );
    }
}