<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_enqueue_scripts', 'pvp_enqueue_cpt_assets' );

function pvp_enqueue_cpt_assets( $hook ) {
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
        return;
    }

    $screen = get_current_screen();
    if ( ! $screen || 'pvp_video' !== $screen->post_type ) {
        return;
    }

    wp_enqueue_media();
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
        array( 'jquery', 'wp-color-picker' ), 
        PVP_VERSION,
        true
    );
}


// ── Register Custom Post Type for videos ──────────────────────────────────────
add_action( 'init', 'pvp_register_video_cpt' );

function pvp_register_video_cpt() {
    $args = array(
        'label'              => __( 'Playlist Videos', 'protected-video-playlist' ),
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'supports'           => array( 'title' ),
        'has_archive'        => false,
        'rewrite'            => false,
        'can_export'         => false,
    );

    register_post_type( 'pvp_video', $args );
}

// ── Register meta fields ──────────────────────────────────────────────────────
add_action( 'init', 'pvp_register_video_meta' );

function pvp_register_video_meta() {
    register_post_meta( 'pvp_video', '_pvp_video_id', array(
        'type'       => 'string',
        'single'     => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_video_url', array(
        'type'       => 'string',
        'single'     => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_playlist_id', array(
        'type'       => 'string',
        'single'     => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_thumbnail_url', array(
        'type'       => 'string',
        'single'     => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_video_logo', array(
        'type'       => 'string',
        'single'     => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_video_logo_url', array(
        'type'          => 'string',
        'single'        => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_video_logo_width', array(
        'type'       => 'integer',
        'single'     => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_video_overlay_text', array(
        'type'       => 'string',
        'single'     => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_video_overlay_start', array(
        'type'       => 'integer',
        'single'     => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_video_overlay_end', array(
        'type'       => 'integer',
        'single'     => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    $new_meta_fields = array(
        '_pvp_provider'             => 'string',
        '_pvp_video_source_type'    => 'string',
        '_pvp_playlist_position'    => 'integer',
        '_pvp_import_updated_at'    => 'string',
        '_pvp_video_logo_opacity'  => 'integer',
        '_pvp_video_logo_position' => 'string',
        '_pvp_video_logo_unit'     => 'string',
        '_pvp_video_logo_radius'   => 'integer',
        '_pvp_video_logo_radius_unit' => 'string',
        '_pvp_video_logo_circle'   => 'integer',
        '_pvp_video_logo_rounded'  => 'integer',
        '_pvp_video_logo_cropped'  => 'integer',
        '_pvp_video_overlay_height'  => 'integer',
        '_pvp_video_overlay_width'   => 'integer',
        '_pvp_video_overlay_x'       => 'integer',
        '_pvp_video_overlay_y'       => 'integer',
        '_pvp_video_overlay_padding' => 'integer',
        '_pvp_video_overlay_bg'      => 'string',
    );

    foreach ( $new_meta_fields as $key => $type ) {
        register_post_meta( 'pvp_video', $key, array(
            'type'          => $type,
            'single'        => true,
            'auth_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );
    }

    register_post_meta( 'pvp_video', '_pvp_video_overlay_on_pause', array(
        'type'          => 'integer',
        'single'        => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_video_overlay_on_end', array(
        'type'          => 'integer',
        'single'        => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    $control_keys = array(
        'disable_volume',
        'disable_playbutton',
        'disable_fullscreen',
        'disable_controls',
        'disable_autoplay',
    );

    foreach ( $control_keys as $key ) {
        register_post_meta( 'pvp_video', '_pvp_override_' . $key, array(
            'type'          => 'integer',
            'single'        => true,
            'auth_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );
    }

    $branding_fields = array(
        '_pvp_controls_bg_color',
        '_pvp_controls_color',
        '_pvp_play_btn_bg_color',
        '_pvp_play_btn_color',
    );

    foreach ( $branding_fields as $field ) {
        register_post_meta( 'pvp_video', $field, array(
            'type'          => 'string',
            'single'        => true,
            'auth_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );
    }

    register_post_meta( 'pvp_video', '_pvp_video_logo_active', array(
        'type'          => 'integer',
        'single'        => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_video_overlay_active', array(
        'type'          => 'integer',
        'single'        => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );

    register_post_meta( 'pvp_video', '_pvp_video_overlay_time_ranges', array(
        'type'          => 'string',
        'single'        => true,
        'auth_callback' => function () {
            return current_user_can( 'manage_options' );
        },
    ) );
}

// ── Display meta box ──────────────────────────────────────────────────────────
add_action( 'add_meta_boxes', 'pvp_add_video_meta_boxes' );

function pvp_add_video_meta_boxes() {
    add_meta_box(
        'pvp_video_settings',
        __( 'Video Settings', 'protected-video-playlist' ),
        'pvp_render_video_meta_box',
        'pvp_video',
        'normal',
        'high'
    );
}

function pvp_render_video_meta_box( $post ) {
    wp_nonce_field( 'pvp_video_meta_nonce', 'pvp_video_meta_nonce' );
    wp_enqueue_editor();


    $logo_active    = get_post_meta( $post->ID, '_pvp_video_logo_active', true );
    $overlay_active = get_post_meta( $post->ID, '_pvp_video_overlay_active', true );

    // Default to active if not set
    $logo_active    = $logo_active    === '' ? 1 : intval( $logo_active );
    $overlay_active = $overlay_active === '' ? 1 : intval( $overlay_active );


    $logo          = get_post_meta( $post->ID, '_pvp_video_logo', true );
    $logo_width    = get_post_meta( $post->ID, '_pvp_video_logo_width', true )    ?: 80;
    $logo_opacity  = get_post_meta( $post->ID, '_pvp_video_logo_opacity', true )  ?: 100;
    $logo_position = get_post_meta( $post->ID, '_pvp_video_logo_position', true ) ?: 'TL';
    $logo_rounded  = get_post_meta( $post->ID, '_pvp_video_logo_rounded', true )  ?: 0;
    $logo_cropped  = get_post_meta( $post->ID, '_pvp_video_logo_cropped', true )  ?: 0;



    $overlay_text = get_post_meta( $post->ID, '_pvp_video_overlay_text', true );
    $overlay_start = get_post_meta( $post->ID, '_pvp_video_overlay_start', true ) ?: 0;
    $overlay_end = get_post_meta( $post->ID, '_pvp_video_overlay_end', true ) ?: 0;

    $overlay_height  = get_post_meta( $post->ID, '_pvp_video_overlay_height', true )  ?: 0;
    $overlay_width   = get_post_meta( $post->ID, '_pvp_video_overlay_width', true )   ?: 100;
    $overlay_x       = get_post_meta( $post->ID, '_pvp_video_overlay_x', true )       ?: 0;
    $overlay_y       = get_post_meta( $post->ID, '_pvp_video_overlay_y', true )       ?: 0;
    $overlay_padding = get_post_meta( $post->ID, '_pvp_video_overlay_padding', true ) ?: 5;
    $overlay_bg      = get_post_meta( $post->ID, '_pvp_video_overlay_bg', true )      ?: '';
    $overlay_on_pause = get_post_meta( $post->ID, '_pvp_video_overlay_on_pause', true ) ?: 0;
    $overlay_on_end   = get_post_meta( $post->ID, '_pvp_video_overlay_on_end', true )   ?: 0;

    $overlay_time_ranges = get_post_meta( $post->ID, '_pvp_video_overlay_time_ranges', true );
    $overlay_time_ranges = ! empty( $overlay_time_ranges ) ? json_decode( $overlay_time_ranges, true ) : array();
    if ( empty( $overlay_time_ranges ) ) {
        $overlay_time_ranges = array(
            array( 'start' => 0, 'end' => 0 )
        );
    }

    ?>
    <style>
        .pvp-toggle-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .pvp-toggle {
            position: relative;
            display: inline-block;
            width: 46px;
            height: 24px;
        }
        .pvp-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .pvp-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            border-radius: 24px;
            transition: 0.3s;
        }
        .pvp-toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        .pvp-toggle input:checked + .pvp-toggle-slider {
            background-color: #007cba;
        }
        .pvp-toggle input:checked + .pvp-toggle-slider:before {
            transform: translateX( 22px );
        }
        .pvp-section-content {
            transition: opacity 0.3s;
        }
        .pvp-section-content.pvp-inactive {
            opacity: 0.4;
            pointer-events: none;
        }

    </style>
    <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
        <div class="pvp-toggle-wrapper">
            <h3 style="margin: 0;"><?php esc_html_e( 'Video Logo', 'protected-video-playlist' ); ?></h3>
            <label class="pvp-toggle" title="<?php esc_attr_e( 'Enable/Disable logo for this video', 'protected-video-playlist' ); ?>">
                <input
                    type="checkbox"
                    name="pvp_video_logo_active"
                    value="1"
                    <?php checked( $logo_active, 1 ); ?>
                    onchange="this.closest('.pvp-section-box').querySelector('.pvp-section-content').classList.toggle('pvp-inactive', !this.checked)"
                />
                <span class="pvp-toggle-slider"></span>
            </label>
        </div>
        <!-- <h3><b><?php esc_html_e( 'Video Logo', 'protected-video-playlist' ); ?></b></h3> -->

        <div class="pvp-section-content <?php echo $logo_active ? '' : 'pvp-inactive'; ?>">
        <!-- <div style="margin-bottom: 10px;"> -->
            <label for="pvp_video_logo">
                <strong><?php esc_html_e( 'Logo Image URL', 'protected-video-playlist' ); ?></strong>
            </label>
            <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                <input
                    type="text"
                    id="pvp_video_logo"
                    name="pvp_video_logo"
                    value="<?php echo esc_url( $logo ); ?>"
                    class="regular-text pvp-image-url"
                />
                <button type="button" class="button pvp-upload-btn">
                    <?php esc_html_e( 'Upload', 'protected-video-playlist' ); ?>
                </button>
                <img
                    class="pvp-image-preview"
                    src="<?php echo esc_url( $logo ); ?>"
                    alt=""
                    style="max-width: 60px; max-height: 60px; object-fit: contain; border: 1px solid #ddd; border-radius: 4px; padding: 3px; <?php echo $logo ? '' : 'display: none;'; ?>"
                />
            </div>

        </div>

        <div style="margin-top: 10px;">
            <label for="pvp_video_logo_url">
                <strong><?php esc_html_e( 'Logo Link URL', 'protected-video-playlist' ); ?></strong>
            </label>
            <input
                type="url"
                id="pvp_video_logo_url"
                name="pvp_video_logo_url"
                value="<?php echo esc_url( get_post_meta( $post->ID, '_pvp_video_logo_url', true ) ); ?>"
                class="regular-text"
                placeholder="https://..."
                style="width: 100%; margin-top: 4px;"
            />
        </div>

        <div style="margin-bottom: 10px;">
            <label for="pvp_video_logo_width">
                <strong><?php esc_html_e( 'Size', 'protected-video-playlist' ); ?></strong>
            </label>

            <div style="display: flex; align-items: center; gap: 8px; margin-top: 5px;">
                
                <!-- Number input -->
                <input
                    type="number"
                    id="pvp_video_logo_width"
                    name="pvp_video_logo_width"
                    value="<?php echo esc_attr( $logo_width ); ?>"
                    min="1"
                    style="width: 80px;"
                />

                <!-- Unit selector -->
                <select name="pvp_video_logo_unit" id="pvp_video_logo_unit">
                    <?php 
                    $unit = get_post_meta( $post->ID, '_pvp_video_logo_unit', true ) ?: 'px';
                    ?>
                    <option value="px" <?php selected( $unit, 'px' ); ?>>px</option>
                    <option value="%" <?php selected( $unit, '%' ); ?>>%</option>
                    <option value="rem" <?php selected( $unit, 'rem' ); ?>>rem</option>
                </select>
            </div>
        </div>
        <div style="margin-bottom: 10px;">
            <label for="pvp_video_logo_opacity">
                <strong><?php esc_html_e( 'Opacity (%)', 'protected-video-playlist' ); ?></strong>
            </label>

            <div style="display: flex; align-items: center; gap: 10px; margin-top: 5px;">
                
                <!-- Number input with arrows -->
                <input
                    type="number"
                    id="pvp_video_logo_opacity"
                    name="pvp_video_logo_opacity"
                    value="<?php echo esc_attr( $logo_opacity ); ?>"
                    min="0"
                    max="100"
                    step="1"
                    style="width: 70px;"
                    oninput="document.getElementById('pvp_logo_opacity_range').value = this.value"
                />

                <!-- Small slider (optional but nice UX) -->
                <input
                    type="range"
                    id="pvp_logo_opacity_range"
                    value="<?php echo esc_attr( $logo_opacity ); ?>"
                    min="0"
                    max="100"
                    style="width: 120px;"
                    oninput="document.getElementById('pvp_video_logo_opacity').value = this.value"
                />
            </div>
        </div>
        <div style="margin-bottom: 10px;">
                <strong><?php esc_html_e( 'Placement', 'protected-video-playlist' ); ?></strong>
        <div style="display: grid; grid-template-columns: repeat(3, 40px); gap: 5px; margin-top: 8px;">
            <?php
            $positions = array(
                'TL' => '↖', 'TC' => '↑', 'TR' => '↗',
                'ML' => '←', 'MC' => '·', 'MR' => '→',
                'BL' => '↙', 'BC' => '↓', 'BR' => '↘',
            );
            foreach ( $positions as $pos => $icon ) :
                $is_active = $logo_position === $pos;
            ?>
                <label style="cursor: pointer; text-align: center;">
                    <input
                        type="radio"
                        name="pvp_video_logo_position"
                        value="<?php echo esc_attr( $pos ); ?>"
                        <?php checked( $logo_position, $pos ); ?>
                        style="display: none;"
                        onchange="document.querySelectorAll('.pvp-pos-btn').forEach(function(b){ b.style.background='#fff'; b.style.color='#000'; b.style.borderColor='#ddd'; }); this.nextElementSibling.style.background='#007cba'; this.nextElementSibling.style.color='#fff'; this.nextElementSibling.style.borderColor='#007cba';"
                    />
                    <span
                        class="pvp-pos-btn"
                        title="<?php echo esc_attr( $pos ); ?>"
                        style="
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            width: 40px;
                            height: 40px;
                            border: 1px solid <?php echo $is_active ? '#007cba' : '#ddd'; ?>;
                            border-radius: 4px;
                            background: <?php echo $is_active ? '#007cba' : '#fff'; ?>;
                            color: <?php echo $is_active ? '#fff' : '#000'; ?>;
                            font-size: 1.2rem;
                        ">
                        <?php echo esc_html( $icon ); ?>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
                <div style="display: flex; align-items: center; gap: 20px; margin-top: 10px; flex-wrap: wrap;">

                    <!-- Rounded with value + unit -->
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <label>
                            <strong><?php esc_html_e( 'Rounded', 'protected-video-playlist' ); ?></strong>
                        </label>

                        <input
                            type="number"
                            name="pvp_video_logo_radius"
                            value="<?php echo esc_attr( get_post_meta( $post->ID, '_pvp_video_logo_radius', true ) ?: 0 ); ?>"
                            min="0"
                            style="width: 70px;"
                        />

                        <select name="pvp_video_logo_radius_unit">
                            <?php 
                            $radius_unit = get_post_meta( $post->ID, '_pvp_video_logo_radius_unit', true ) ?: 'px';
                            ?>
                            <option value="px" <?php selected( $radius_unit, 'px' ); ?>>px</option>
                            <option value="%" <?php selected( $radius_unit, '%' ); ?>>%</option>
                        </select>
                    </div>

                    <!-- Circle checkbox -->
                    <label style="display: flex; align-items: center; gap: 6px;">
                        <input
                            type="checkbox"
                            name="pvp_video_logo_circle"
                            value="1"
                            <?php checked( get_post_meta( $post->ID, '_pvp_video_logo_circle', true ), 1 ); ?>
                        />
                        <strong><?php esc_html_e( 'Circle', 'protected-video-playlist' ); ?></strong>
                    </label>

                </div>
            </div>
        </div>
    </div>


    <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">
        <div class="pvp-toggle-wrapper">
            <h3 style="margin: 0;"><?php esc_html_e( 'Marketing Overlay', 'protected-video-playlist' ); ?></h3>
            <label class="pvp-toggle" title="<?php esc_attr_e( 'Enable/Disable overlay for this video', 'protected-video-playlist' ); ?>">
                <input
                    type="checkbox"
                    name="pvp_video_overlay_active"
                    value="1"
                    <?php checked( $overlay_active, 1 ); ?>
                    onchange="this.closest('.pvp-section-box').querySelector('.pvp-section-content').classList.toggle('pvp-inactive', !this.checked)"
                />
                <span class="pvp-toggle-slider"></span>
            </label>
        </div>
        <!-- <h3><b><?php esc_html_e( 'Marketing Overlay', 'protected-video-playlist' ); ?></b></h3> -->
        <br/>
        <div class="pvp-section-content <?php echo $overlay_active ? '' : 'pvp-inactive'; ?>">
            <label for="pvp_video_overlay_text">
                <strong><?php esc_html_e( 'Overlay Content', 'protected-video-playlist' ); ?></strong>
            </label>
            <?php
            wp_editor( $overlay_text, 'pvp_video_overlay_text', array(
                'textarea_name' => 'pvp_video_overlay_text',
                'media_buttons' => true,
                'teeny'         => false,
                'quicktags'     => true,
                'tinymce'       => array(
                    'toolbar1' => 'bold,italic,underline,strikethrough,forecolor,backcolor,alignleft,aligncenter,alignright,link,unlink',
                ),
            ) );
            ?>

            <!-- <div style="display: flex; gap: 30px; flex-wrap: wrap; margin-top: 15px;">
                <div style="flex: 1; min-width: 220px;">
                    <label>
                        <strong><?php esc_html_e( 'Show at', 'protected-video-playlist' ); ?></strong>
                    </label>

                    <div style="display: flex; gap: 8px; align-items: center;">
                        <?php
                        $h = floor( $overlay_start / 3600 );
                        $m = floor( ( $overlay_start % 3600 ) / 60 );
                        $s = $overlay_start % 60;
                        ?>

                        <input type="number" name="pvp_video_overlay_start_h" value="<?php echo esc_attr($h); ?>" min="0" max="23" style="width:60px;" />
                        :
                        <input type="number" name="pvp_video_overlay_start_m" value="<?php echo esc_attr($m); ?>" min="0" max="59" style="width:60px;" />
                        :
                        <input type="number" name="pvp_video_overlay_start_s" value="<?php echo esc_attr($s); ?>" min="0" max="59" style="width:60px;" />
                    </div>
                </div>

                <div style="flex: 1; min-width: 220px;">
                    <label>
                        <strong><?php esc_html_e( 'Hide at', 'protected-video-playlist' ); ?></strong>
                    </label>

                    <div style="display: flex; gap: 8px; align-items: center;">
                        <?php
                        $h = floor( $overlay_end / 3600 );
                        $m = floor( ( $overlay_end % 3600 ) / 60 );
                        $s = $overlay_end % 60;
                        ?>

                        <input type="number" name="pvp_video_overlay_end_h" value="<?php echo esc_attr($h); ?>" min="0" max="23" style="width:60px;" />
                        :
                        <input type="number" name="pvp_video_overlay_end_m" value="<?php echo esc_attr($m); ?>" min="0" max="59" style="width:60px;" />
                        :
                        <input type="number" name="pvp_video_overlay_end_s" value="<?php echo esc_attr($s); ?>" min="0" max="59" style="width:60px;" />
                    </div>
                </div>
            </div> -->


            <div style="margin-top: 15px;">
                <strong><?php esc_html_e( 'Display Time Ranges', 'protected-video-playlist' ); ?></strong>
                <p class="description"><?php esc_html_e( 'Add multiple time ranges when the overlay should appear. Leave all at 0 to always show.', 'protected-video-playlist' ); ?></p>

                <div id="pvp-time-ranges-container">
                    <?php foreach ( $overlay_time_ranges as $ri => $range ) :
                        $start = intval( $range['start'] ?? 0 );
                        $end   = intval( $range['end']   ?? 0 );
                        $sh    = floor( $start / 3600 );
                        $sm    = floor( ( $start % 3600 ) / 60 );
                        $ss    = $start % 60;
                        $eh    = floor( $end / 3600 );
                        $em    = floor( ( $end % 3600 ) / 60 );
                        $es    = $end % 60;
                    ?>
                        <div class="pvp-time-range-row" style="display: flex; gap: 20px; flex-wrap: wrap; align-items: center; margin-top: 10px; padding: 10px; border: 1px solid #eee; border-radius: 4px;">

                            <div>
                                <label><strong><?php esc_html_e( 'Show at', 'protected-video-playlist' ); ?></strong></label>
                                <div style="display: flex; gap: 5px; align-items: center; margin-top: 4px;">
                                    <input type="number" name="pvp_time_ranges[<?php echo esc_attr( $ri ); ?>][start_h]" value="<?php echo esc_attr( $sh ); ?>" min="0" max="23" style="width:55px;" />
                                    :
                                    <input type="number" name="pvp_time_ranges[<?php echo esc_attr( $ri ); ?>][start_m]" value="<?php echo esc_attr( $sm ); ?>" min="0" max="59" style="width:55px;" />
                                    :
                                    <input type="number" name="pvp_time_ranges[<?php echo esc_attr( $ri ); ?>][start_s]" value="<?php echo esc_attr( $ss ); ?>" min="0" max="59" style="width:55px;" />
                                </div>
                            </div>

                            <div>
                                <label><strong><?php esc_html_e( 'Hide at', 'protected-video-playlist' ); ?></strong></label>
                                <div style="display: flex; gap: 5px; align-items: center; margin-top: 4px;">
                                    <input type="number" name="pvp_time_ranges[<?php echo esc_attr( $ri ); ?>][end_h]" value="<?php echo esc_attr( $eh ); ?>" min="0" max="23" style="width:55px;" />
                                    :
                                    <input type="number" name="pvp_time_ranges[<?php echo esc_attr( $ri ); ?>][end_m]" value="<?php echo esc_attr( $em ); ?>" min="0" max="59" style="width:55px;" />
                                    :
                                    <input type="number" name="pvp_time_ranges[<?php echo esc_attr( $ri ); ?>][end_s]" value="<?php echo esc_attr( $es ); ?>" min="0" max="59" style="width:55px;" />
                                </div>
                            </div>

                            <?php if ( $ri > 0 ) : ?>
                                <div style="align-self: flex-end;">
                                    <button type="button" class="button pvp-delete-time-range" style="background: #dc3232; color: #fff; border-color: #dc3232;">
                                        <?php esc_html_e( 'Delete', 'protected-video-playlist' ); ?>
                                    </button>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" id="pvp-add-time-range" class="button" style="border: 2px solid #007cba; color: #007cba; background: transparent; width: 40px; height: 40px; font-size: 1.5rem; display: flex; align-items: center; justify-content: center; border-radius: 4px; margin-top: 10px;">+</button>
            </div>

            <div style="margin-top: 15px;">
                <strong><?php esc_html_e( 'Display On', 'protected-video-playlist' ); ?></strong>
                <div style="display: flex; gap: 20px; margin-top: 8px;">
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input
                            type="checkbox"
                            name="pvp_video_overlay_on_pause"
                            value="1"
                            <?php checked( $overlay_on_pause, 1 ); ?>
                        />
                        <?php esc_html_e( 'Pause Screen', 'protected-video-playlist' ); ?>
                    </label>

                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input
                            type="checkbox"
                            name="pvp_video_overlay_on_end"
                            value="1"
                            <?php checked( $overlay_on_end, 1 ); ?>
                        />
                        <?php esc_html_e( 'End Screen', 'protected-video-playlist' ); ?>
                    </label>
                </div>
                <p class="description" style="margin-top: 5px;">
                    <?php esc_html_e( 'Show overlay when video is paused and/or when video ends.', 'protected-video-playlist' ); ?>
                </p>
            </div>


            <div style="margin-top: 15px; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label for="pvp_video_overlay_height">
                        <strong><?php esc_html_e( 'Height (%)', 'protected-video-playlist' ); ?></strong>
                    </label>
                    <input
                        type="range"
                        id="pvp_video_overlay_height"
                        name="pvp_video_overlay_height"
                        value="<?php echo esc_attr( $overlay_height ); ?>"
                        min="0"
                        max="100"
                        style="width: 100%;"
                        oninput="document.getElementById('pvp_overlay_height_val').textContent = this.value + '%'"
                    />
                    <span id="pvp_overlay_height_val"><?php echo esc_html( $overlay_height ); ?>%</span>
                </div>

                <div>
                    <label for="pvp_video_overlay_width">
                        <strong><?php esc_html_e( 'Width (%)', 'protected-video-playlist' ); ?></strong>
                    </label>
                    <input
                        type="range"
                        id="pvp_video_overlay_width"
                        name="pvp_video_overlay_width"
                        value="<?php echo esc_attr( $overlay_width ); ?>"
                        min="0"
                        max="100"
                        style="width: 100%;"
                        oninput="document.getElementById('pvp_overlay_width_val').textContent = this.value + '%'"
                    />
                    <span id="pvp_overlay_width_val"><?php echo esc_html( $overlay_width ); ?>%</span>
                </div>

                <div>
                    <label for="pvp_video_overlay_x">
                        <strong><?php esc_html_e( 'X Position (%)', 'protected-video-playlist' ); ?></strong>
                    </label>
                    <input
                        type="range"
                        id="pvp_video_overlay_x"
                        name="pvp_video_overlay_x"
                        value="<?php echo esc_attr( $overlay_x ); ?>"
                        min="0"
                        max="100"
                        style="width: 100%;"
                        oninput="document.getElementById('pvp_overlay_x_val').textContent = this.value + '%'"
                    />
                    <span id="pvp_overlay_x_val"><?php echo esc_html( $overlay_x ); ?>%</span>
                </div>

                <div>
                    <label for="pvp_video_overlay_y">
                        <strong><?php esc_html_e( 'Y Position (%)', 'protected-video-playlist' ); ?></strong>
                    </label>
                    <input
                        type="range"
                        id="pvp_video_overlay_y"
                        name="pvp_video_overlay_y"
                        value="<?php echo esc_attr( $overlay_y ); ?>"
                        min="0"
                        max="100"
                        style="width: 100%;"
                        oninput="document.getElementById('pvp_overlay_y_val').textContent = this.value + '%'"
                    />
                    <span id="pvp_overlay_y_val"><?php echo esc_html( $overlay_y ); ?>%</span>
                </div>

                <div>
                    <label for="pvp_video_overlay_padding">
                        <strong><?php esc_html_e( 'Padding (px)', 'protected-video-playlist' ); ?></strong>
                    </label>
                    <input
                        type="range"
                        id="pvp_video_overlay_padding"
                        name="pvp_video_overlay_padding"
                        value="<?php echo esc_attr( $overlay_padding ); ?>"
                        min="0"
                        max="50"
                        style="width: 100%;"
                        oninput="document.getElementById('pvp_overlay_padding_val').textContent = this.value + 'px'"
                    />
                    <span id="pvp_overlay_padding_val"><?php echo esc_html( $overlay_padding ); ?>px</span>
                </div>

                <div>
                    <label for="pvp_video_overlay_bg">
                        <strong><?php esc_html_e( 'Background Color', 'protected-video-playlist' ); ?></strong>
                    </label>
                    <input
                        type="text"
                        id="pvp_video_overlay_bg"
                        name="pvp_video_overlay_bg"
                        value="<?php echo esc_attr( $overlay_bg ); ?>"
                        class="pvp-color-field"
                    />
                </div>
            </div>
        </div>
    </div>
    <?php
}


add_action( 'add_meta_boxes', 'pvp_add_branding_meta_box' );

function pvp_add_branding_meta_box() {
    add_meta_box(
        'pvp_video_branding',
        __( 'Branding Colors', 'protected-video-playlist' ),
        'pvp_render_branding_meta_box',
        'pvp_video',
        'side',
        'default'
    );
}

function pvp_render_branding_meta_box( $post ) {
    $color_fields = array(
        '_pvp_controls_bg_color'   => array(
            'label' => __( 'Controls Bar Background', 'protected-video-playlist' ),
            'name'  => 'pvp_controls_bg_color',
        ),
        '_pvp_controls_color'      => array(
            'label' => __( 'Controls Icons Color', 'protected-video-playlist' ),
            'name'  => 'pvp_controls_color',
        ),
        '_pvp_play_btn_bg_color'   => array(
            'label' => __( 'Play Button Background', 'protected-video-playlist' ),
            'name'  => 'pvp_play_btn_bg_color',
        ),
        '_pvp_play_btn_color'      => array(
            'label' => __( 'Play Button Icon Color', 'protected-video-playlist' ),
            'name'  => 'pvp_play_btn_color',
        ),
    );

    foreach ( $color_fields as $meta_key => $field ) :
        $value = get_post_meta( $post->ID, $meta_key, true );
        ?>
        <div style="margin-bottom: 15px;">
            <label>
                <strong><?php echo esc_html( $field['label'] ); ?></strong>
            </label>
            <input
                type="text"
                class="pvp-color-field"
                name="<?php echo esc_attr( $field['name'] ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
                style="width: 100%;"
            />
        </div>
        <?php
    endforeach;
}


// ── Save meta box data ────────────────────────────────────────────────────────
add_action( 'save_post_pvp_video', 'pvp_save_video_meta' );

function pvp_save_video_meta( $post_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $nonce = isset( $_POST['pvp_video_meta_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['pvp_video_meta_nonce'] ) ) : '';
    if ( ! $nonce || ! wp_verify_nonce( $nonce, 'pvp_video_meta_nonce' ) ) {
        return;
    }

    $post_data = wp_unslash( $_POST );

    if ( isset( $post_data['pvp_video_logo'] ) ) {
        update_post_meta( $post_id, '_pvp_video_logo', esc_url_raw( $post_data['pvp_video_logo'] ) );
    }

    if ( isset( $post_data['pvp_video_logo_url'] ) ) {
        update_post_meta( $post_id, '_pvp_video_logo_url', esc_url_raw( $post_data['pvp_video_logo_url'] ) );
    }

    if ( isset( $post_data['pvp_video_logo_unit'] ) ) {
        update_post_meta( $post_id, '_pvp_video_logo_unit', sanitize_text_field( $post_data['pvp_video_logo_unit'] ) );
    }


    if ( isset( $post_data['pvp_video_logo_width'] ) ) {
        update_post_meta( $post_id, '_pvp_video_logo_width', intval( $post_data['pvp_video_logo_width'] ) );
    }

    if ( isset( $post_data['pvp_video_logo_opacity'] ) ) {
        update_post_meta( $post_id, '_pvp_video_logo_opacity', intval( $post_data['pvp_video_logo_opacity'] ) );
    }

    if ( isset( $post_data['pvp_video_logo_position'] ) ) {
        update_post_meta( $post_id, '_pvp_video_logo_position', sanitize_text_field( $post_data['pvp_video_logo_position'] ) );
    }

    if ( isset( $post_data['pvp_video_logo_radius'] ) ) {
        update_post_meta( $post_id, '_pvp_video_logo_radius', intval( $post_data['pvp_video_logo_radius'] ) );
    }

    if ( isset( $post_data['pvp_video_logo_radius_unit'] ) ) {
        update_post_meta( $post_id, '_pvp_video_logo_radius_unit', sanitize_text_field( $post_data['pvp_video_logo_radius_unit'] ) );
    }

    update_post_meta( $post_id, '_pvp_video_logo_circle', isset( $post_data['pvp_video_logo_circle'] ) ? 1 : 0 );
    // update_post_meta( $post_id, '_pvp_video_logo_rounded', isset( $_POST['pvp_video_logo_rounded'] ) ? 1 : 0 );
    // update_post_meta( $post_id, '_pvp_video_logo_cropped', isset( $_POST['pvp_video_logo_cropped'] ) ? 1 : 0 );

    if ( isset( $post_data['pvp_video_overlay_text'] ) ) {
        update_post_meta( $post_id, '_pvp_video_overlay_text', wp_kses_post( $post_data['pvp_video_overlay_text'] ) );
    }

    // if ( isset( $_POST['pvp_video_overlay_start'] ) ) {
    //     update_post_meta( $post_id, '_pvp_video_overlay_start', intval( $_POST['pvp_video_overlay_start'] ) );
    // }

    if (
        isset($post_data['pvp_video_overlay_start_h'], $post_data['pvp_video_overlay_start_m'], $post_data['pvp_video_overlay_start_s'])
    ) {
        $seconds =
            intval($post_data['pvp_video_overlay_start_h']) * 3600 +
            intval($post_data['pvp_video_overlay_start_m']) * 60 +
            intval($post_data['pvp_video_overlay_start_s']);

        update_post_meta($post_id, '_pvp_video_overlay_start', $seconds);
    }

    // if ( isset( $_POST['pvp_video_overlay_end'] ) ) {
    //     update_post_meta( $post_id, '_pvp_video_overlay_end', intval( $_POST['pvp_video_overlay_end'] ) );
    // }
    if (
        isset($post_data['pvp_video_overlay_end_h'], $post_data['pvp_video_overlay_end_m'], $post_data['pvp_video_overlay_end_s'])
    ) {
        $seconds =
            intval($post_data['pvp_video_overlay_end_h']) * 3600 +
            intval($post_data['pvp_video_overlay_end_m']) * 60 +
            intval($post_data['pvp_video_overlay_end_s']);

        update_post_meta($post_id, '_pvp_video_overlay_end', $seconds);
    }
    if ( isset( $post_data['pvp_video_overlay_height'] ) ) {
        update_post_meta( $post_id, '_pvp_video_overlay_height', intval( $post_data['pvp_video_overlay_height'] ) );
    }

    if ( isset( $post_data['pvp_video_overlay_width'] ) ) {
        update_post_meta( $post_id, '_pvp_video_overlay_width', intval( $post_data['pvp_video_overlay_width'] ) );
    }

    if ( isset( $post_data['pvp_video_overlay_x'] ) ) {
        update_post_meta( $post_id, '_pvp_video_overlay_x', intval( $post_data['pvp_video_overlay_x'] ) );
    }

    if ( isset( $post_data['pvp_video_overlay_y'] ) ) {
        update_post_meta( $post_id, '_pvp_video_overlay_y', intval( $post_data['pvp_video_overlay_y'] ) );
    }

    if ( isset( $post_data['pvp_video_overlay_padding'] ) ) {
        update_post_meta( $post_id, '_pvp_video_overlay_padding', intval( $post_data['pvp_video_overlay_padding'] ) );
    }

    if ( isset( $post_data['pvp_video_overlay_bg'] ) ) {
        update_post_meta( $post_id, '_pvp_video_overlay_bg', pvp_sanitize_color( $post_data['pvp_video_overlay_bg'] ) );
    }

    update_post_meta( $post_id, '_pvp_video_overlay_on_pause', isset( $post_data['pvp_video_overlay_on_pause'] ) ? 1 : 0 );
    update_post_meta( $post_id, '_pvp_video_overlay_on_end',   isset( $post_data['pvp_video_overlay_on_end'] )   ? 1 : 0 );

    
    // Save override controls — separate nonce check for controls meta box
    $control_keys = array(
            'disable_volume',
            'disable_playbutton',
            'disable_fullscreen',
            'disable_controls',
            'disable_autoplay',
        );

        foreach ( $control_keys as $key ) {
            update_post_meta(
                $post_id,
                '_pvp_override_' . $key,
                isset( $post_data[ 'pvp_override_' . $key ] ) ? 1 : 0
            );
        }

    $branding_fields = array(
        'pvp_controls_bg_color'  => '_pvp_controls_bg_color',
        'pvp_controls_color'     => '_pvp_controls_color',
        'pvp_play_btn_bg_color'  => '_pvp_play_btn_bg_color',
        'pvp_play_btn_color'     => '_pvp_play_btn_color',
    );

    foreach ( $branding_fields as $post_key => $meta_key ) {
        if ( isset( $post_data[ $post_key ] ) ) {
            update_post_meta( $post_id, $meta_key, pvp_sanitize_color( $post_data[ $post_key ] ) );
        }
    }

    update_post_meta( $post_id, '_pvp_video_logo_active',    isset( $post_data['pvp_video_logo_active'] )    ? 1 : 0 );
    update_post_meta( $post_id, '_pvp_video_overlay_active', isset( $post_data['pvp_video_overlay_active'] ) ? 1 : 0 );
    if ( isset( $post_data['pvp_time_ranges'] ) && is_array( $post_data['pvp_time_ranges'] ) ) {
        $time_ranges = array();
        foreach ( $post_data['pvp_time_ranges'] as $range ) {
            $start = intval( $range['start_h'] ?? 0 ) * 3600 +
                     intval( $range['start_m'] ?? 0 ) * 60 +
                     intval( $range['start_s'] ?? 0 );
            $end   = intval( $range['end_h'] ?? 0 ) * 3600 +
                     intval( $range['end_m'] ?? 0 ) * 60 +
                     intval( $range['end_s'] ?? 0 );

            $time_ranges[] = array(
                'start' => $start,
                'end'   => $end,
            );
        }
        update_post_meta( $post_id, '_pvp_video_overlay_time_ranges', wp_json_encode( $time_ranges ) );
    }
        
}



// ── Override Controls Meta Box (sidebar) ──────────────────────────────────────
add_action( 'add_meta_boxes', 'pvp_add_controls_meta_box' );

function pvp_add_controls_meta_box() {
    add_meta_box(
        'pvp_video_controls',
        __( 'Player Controls', 'protected-video-playlist' ),
        'pvp_render_controls_meta_box',
        'pvp_video',
        'side',
        'default'
    );
}

function pvp_render_controls_meta_box( $post ) {
    // wp_nonce_field( 'pvp_controls_meta_nonce', 'pvp_controls_meta_nonce' );

    $checkboxes = array(
        'disable_volume'     => __( 'Disable Volume', 'protected-video-playlist' ),
        'disable_playbutton' => __( 'Disable Play Button', 'protected-video-playlist' ),
        'disable_fullscreen' => __( 'Disable Fullscreen', 'protected-video-playlist' ),
        'disable_controls'   => __( 'Disable Controls', 'protected-video-playlist' ),
        'disable_autoplay'   => __( 'Disable Autoplay', 'protected-video-playlist' ),
    );

    $options = get_option( 'pvp_settings', array() );

    foreach ( $checkboxes as $key => $label ) :
        // Get per-video value — null means not set (use global)
        $meta_value = get_post_meta( $post->ID, '_pvp_override_' . $key, true );
        $is_set     = $meta_value !== '';
        $is_checked = $is_set ? (bool) $meta_value : ! empty( $options[ $key ] );
        ?>
        <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
            <input
                type="checkbox"
                id="pvp_override_<?php echo esc_attr( $key ); ?>"
                name="pvp_override_<?php echo esc_attr( $key ); ?>"
                value="1"
                <?php checked( $is_checked ); ?>
            />
            <label for="pvp_override_<?php echo esc_attr( $key ); ?>">
                <?php echo esc_html( $label ); ?>
            </label>
        </div>
        <?php
    endforeach;

    echo '<p class="description" style="margin-top: 8px; font-size: 11px;">';
    esc_html_e( 'These settings override the global defaults for this video only.', 'protected-video-playlist' );
    echo '</p>';
}
