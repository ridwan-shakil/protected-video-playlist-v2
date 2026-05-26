<?php
/**
 * Plugin Name: Protected Video – Playlist Add-on
 * Description: Extends the Protected Video block to support YouTube playlists.
 * Version:     2.0.0
 * Requires Plugins: protected-video
 * Author:      Homaira Sharmin
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Constants ─────────────────────────────────────────────
define( 'PVP_VERSION', '2.0.0' );
define( 'PVP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
// define( 'PVP_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
define( 'PVP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ── Activation / Deactivation Hooks ───────────────────────
register_activation_hook( __FILE__, 'pvp_activate' );
register_deactivation_hook( __FILE__, 'pvp_deactivate' );

function pvp_activate() {}
function pvp_deactivate() {}

// ── Safety Check ──────────────────────────────────────────
add_action( 'plugins_loaded', 'pvp_init_addon' );

function pvp_init_addon() {

	// ── Check parent plugin ──
	if ( ! defined( 'PROTECTED_VIDEO_VERSION' ) ) {
		add_action( 'admin_notices', 'pvp_missing_parent_notice' );
		return;
	}

	// ── Load shared/core (needed everywhere) ──
	require_once PVP_PLUGIN_DIR . 'fallback/class-pvp-functions.php';
	require_once PVP_PLUGIN_DIR . 'includes/core/class-pvp-cpt.php';
	require_once PVP_PLUGIN_DIR . 'includes/core/class-pvp-sync.php';
	require_once PVP_PLUGIN_DIR . 'includes/api/class-pvp-youtube.php';
	require_once PVP_PLUGIN_DIR . 'includes/core/class-pvp-render.php';
	require_once PVP_PLUGIN_DIR . 'includes/core/class-pvp-block.php';
	// ── Admin only ──
	if ( is_admin() ) {
		require_once PVP_PLUGIN_DIR . 'admin/class-pvp-admin.php';

	}

	// ── Frontend only ──
	if ( ! is_admin() ) {
		require_once PVP_PLUGIN_DIR . 'includes/public/class-pvp-frontend.php';
		require_once PVP_PLUGIN_DIR . 'fallback/class-pvp-shortcode.php';

	}
}

function pvp_missing_parent_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<?php
			esc_html_e(
				'Protected Video – Playlist Add-on requires the Protected Video plugin to be installed and activated.',
				'pvp'
			);
			?>
		</p>
	</div>
	<?php
}


if ( ! function_exists( 'pvp_get_settings' ) ) {
	function pvp_get_settings() {
		return get_option( 'pvp_settings', array() );
	}
}