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

define( 'RSPLR_VERSION', PVP_VERSION );
define( 'RSPLR_PLUGIN_FILE', __FILE__ );
define( 'RSPLR_PLUGIN_DIR', PVP_PLUGIN_DIR );
define( 'RSPLR_PLUGIN_URL', PVP_PLUGIN_URL );
define( 'RSPLR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once RSPLR_PLUGIN_DIR . 'includes/RSPLR/Autoloader.php';
\RSPLR\Autoloader::register();

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

	rsplr_plugin()->init();
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
		if ( function_exists( 'rsplr_settings' ) ) {
			return rsplr_settings()->all();
		}

		return get_option( 'pvp_settings', array() );
	}
}

if ( ! function_exists( 'rsplr_plugin' ) ) {
	function rsplr_plugin() {
		static $plugin = null;

		if ( null === $plugin ) {
			$plugin = new \RSPLR\Core\Plugin();
		}

		return $plugin;
	}
}

if ( ! function_exists( 'rsplr_settings' ) ) {
	function rsplr_settings() {
		return rsplr_plugin()->settings();
	}
}
