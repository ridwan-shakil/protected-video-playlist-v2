<?php
add_action( 'wp_enqueue_scripts', 'pvp_enqueue_frontend_styles' );

function pvp_enqueue_frontend_styles() {
	global $post;

	if ( ! is_a( $post, 'WP_Post' ) ) {
		return;
	}

	$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
	$search_content = $post->post_content . ' ' . ( is_string( $elementor_data ) ? $elementor_data : '' );

	$has_block     = has_block( 'protected-video-playlist/playlist', $post->ID );
	$has_shortcode = has_shortcode( $search_content, 'protected_playlist' );
	$has_rs_shortcode = has_shortcode( $search_content, 'rsplr_video' ) ||
		has_shortcode( $search_content, 'rsplr_playlist' ) ||
		has_shortcode( $search_content, 'rsplr_campaign' ) ||
		false !== strpos( $search_content, 'rsplr_video' ) ||
		false !== strpos( $search_content, 'rsplr_playlist' ) ||
		false !== strpos( $search_content, 'rsplr_campaign' );
	$has_parent    = has_block( 'protected-video/protected-video', $post->ID );

	if ( ! $has_block && ! $has_shortcode && ! $has_rs_shortcode && ! $has_parent ) {
		return;
	}

	wp_enqueue_style( 'protected-video-protected-video-style' );
	wp_enqueue_script( 'protected-video-protected-video-view-script' );

	wp_enqueue_style(
		'pvp-grid-style',
		PVP_PLUGIN_URL . 'public/css/pvp-frontend.css',
		array( 'protected-video-protected-video-style' ),
		PVP_VERSION
	);

	wp_enqueue_script(
		'pvp-frontend',
		PVP_PLUGIN_URL . 'public/js/pvp-frontend.js',
		array( 'jquery' ),
		PVP_VERSION,
		true
	);

	wp_localize_script(
		'pvp-frontend',
		'pvpData',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pvp_load_more_nonce' ),
		)
	);

	// ── Dynamic CSS ───────────────────────────────────────────────────────────
	$options = pvp_get_settings();
	$color   = isset( $options['theme_color'] ) ? sanitize_hex_color( $options['theme_color'] ) : '#ff0000';

	$custom_css = ":root { --pvp-theme-color: {$color}; }";

	// Global branding colors
	$controls_bg_color = ! empty( $options['controls_bg_color'] ) ? sanitize_hex_color( $options['controls_bg_color'] ) : '';
	$controls_color    = ! empty( $options['controls_color'] ) ? sanitize_hex_color( $options['controls_color'] ) : '';
	$play_btn_bg_color = ! empty( $options['play_btn_bg_color'] ) ? sanitize_hex_color( $options['play_btn_bg_color'] ) : '';
	$play_btn_color    = ! empty( $options['play_btn_color'] ) ? sanitize_hex_color( $options['play_btn_color'] ) : '';

	if ( $controls_bg_color ) {
		$custom_css .= ' .plyr__controls { background: ' . $controls_bg_color . ' !important; }';
	}
	if ( $controls_color ) {
		$custom_css .= ' .plyr__controls .plyr__control { color: ' . $controls_color . ' !important; }';
		$custom_css .= ' .plyr__controls input[type="range"] { color: ' . $controls_color . ' !important; }';
	}
	if ( $play_btn_bg_color ) {
		$custom_css .= ' .plyr__control--overlaid { background: ' . $play_btn_bg_color . ' !important; }';
	}
	if ( $play_btn_color ) {
		$custom_css .= ' .plyr__control--overlaid svg { color: ' . $play_btn_color . ' !important; fill: ' . $play_btn_color . ' !important; }';
	}

	wp_add_inline_style( 'pvp-grid-style', $custom_css );

	// ── Inject default branding into parent plugin single videos ──────────

	$logo_url     = ! empty( $options['logo'] ) ? esc_url( $options['logo'] ) : '';
	$overlay_text = ! empty( $options['overlay_text'] ) ? wp_kses_post( $options['overlay_text'] ) : '';

	if ( $logo_url || $overlay_text ) {

		$branding_js = "
        window.addEventListener('load', function() {

            document.querySelectorAll('.plyr').forEach(function(plyrEl) {

                // Skip playlist addon videos
                if ( plyrEl.closest('.pvp-video-wrapper') ) {
                    return;
                }

                // Correct internal player container
                var container = plyrEl.querySelector('.plyr__video-wrapper') || plyrEl;

                if ( ! container ) {
                    return;
                }

                container.style.position = 'relative';


                // ── Logo ─────────────────────────────
                if ( " . wp_json_encode( ! empty( $logo_url ) ) . " ) {

                    if ( ! container.querySelector('.pvp-logo') ) {

                        var logo = document.createElement('img');
                        logo.src       = " . wp_json_encode( $logo_url ) . ";
                        logo.alt       = '';
                        logo.className = 'pvp-logo';
                        logo.style.cssText = 'width: 80px; height: auto; display: block;';

                        " . ( ! empty( $options['logo_url'] ) ? "
                        var logoLink = document.createElement('a');
                        logoLink.href   = " . wp_json_encode( esc_url( $options['logo_url'] ) ) . ";
                        logoLink.target = '_blank';
                        logoLink.rel    = 'noopener noreferrer';
                        logoLink.style.cssText = 'position: absolute; top: 10px; left: 10px; z-index: 9999; pointer-events: all; display: block;';
                        logoLink.appendChild(logo);
                        container.appendChild(logoLink);
                        " : "
                        logo.style.cssText = 'position: absolute; top: 10px; left: 10px; z-index: 9999; pointer-events: none; width: 80px; height: auto;';
                        container.appendChild(logo);
                        " ) . '
                    }
                }
                

                // ── Overlay Text ─────────────────────
                if ( ' . wp_json_encode( ! empty( $overlay_text ) ) . " ) {

                    if ( ! container.querySelector('.pvp-overlay-text') ) {

                        var overlay = document.createElement('div');

                        overlay.className = 'pvp-overlay-text';

                        overlay.innerHTML = " . wp_json_encode( $overlay_text ) . ';

                        overlay.style.cssText = `
                            position: absolute;
                            left: 0%;
                            top: 0%;
                            width: 100%;
                            padding: 5px;
                            z-index: 9998;
                            pointer-events: none;
                            color: var(--pvp-theme-color, #ff0000);
                        `;

                        container.appendChild(overlay);
                    }
                }

            });

        });
        ';

		wp_add_inline_script(
			'protected-video-protected-video-view-script',
			$branding_js
		);
	}
}
