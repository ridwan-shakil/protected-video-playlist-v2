<?php
/**
 * Tabbed RS SecurePlayer settings page.
 *
 * @package RSPLR\Admin
 */

namespace RSPLR\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsPage {
	public const MENU_SLUG = 'rsplr-settings';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register submenu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			AdminMenu::MENU_SLUG,
			__( 'Settings', 'protected-video-playlist' ),
			__( 'Settings', 'protected-video-playlist' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Enqueue settings assets.
	 *
	 * @param string $hook Hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'pvp-admin-style', RSPLR_PLUGIN_URL . 'admin/css/admin.css', array(), RSPLR_VERSION );
		wp_enqueue_script( 'wp-color-picker-alpha', RSPLR_PLUGIN_URL . 'admin/js/wp-color-picker-alpha.js', array( 'wp-color-picker' ), RSPLR_VERSION, true );
		wp_enqueue_script( 'pvp-admin-js', RSPLR_PLUGIN_URL . 'admin/js/admin.js', array( 'wp-color-picker', 'wp-color-picker-alpha', 'jquery' ), RSPLR_VERSION, true );
		wp_enqueue_script( 'rsplr-settings-tabs', RSPLR_PLUGIN_URL . 'admin/js/settings-tabs.js', array( 'jquery' ), RSPLR_VERSION, true );
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		settings_errors( 'pvp_messages' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'RS SecurePlayer Settings', 'protected-video-playlist' ); ?></h1>
			<nav class="nav-tab-wrapper" style="margin-bottom:16px;">
				<?php $this->tab_link( 'branding', __( 'Branding', 'protected-video-playlist' ), true ); ?>
				<?php $this->tab_link( 'overlay', __( 'Overlay', 'protected-video-playlist' ), false ); ?>
				<?php $this->tab_link( 'protection', __( 'Protection', 'protected-video-playlist' ), false ); ?>
				<?php $this->tab_link( 'playback', __( 'Playback', 'protected-video-playlist' ), false ); ?>
			</nav>

			<form method="post" action="options.php">
				<?php settings_fields( 'pvp_settings_group' ); ?>
				<?php $this->render_tab_panel( 'branding', true ); ?>
				<?php $this->render_tab_panel( 'overlay', false ); ?>
				<?php $this->render_tab_panel( 'protection', false ); ?>
				<?php $this->render_tab_panel( 'playback', false ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render one tab link.
	 *
	 * @param string $tab Tab key.
	 * @param string $label Label.
	 * @param bool   $active Whether this tab is active.
	 * @return void
	 */
	private function tab_link( $tab, $label, $active ) {
		?>
		<a class="nav-tab <?php echo $active ? 'nav-tab-active' : ''; ?>" href="#rsplr-settings-<?php echo esc_attr( $tab ); ?>" data-rsplr-settings-tab="<?php echo esc_attr( $tab ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php
	}

	/**
	 * Render one tab panel.
	 *
	 * @param string $tab Tab key.
	 * @param bool   $active Whether this tab is active.
	 * @return void
	 */
	private function render_tab_panel( $tab, $active ) {
		?>
		<div id="rsplr-settings-<?php echo esc_attr( $tab ); ?>" class="rsplr-settings-panel" data-rsplr-settings-panel="<?php echo esc_attr( $tab ); ?>" <?php echo $active ? '' : 'hidden'; ?>>
			<table class="form-table" role="presentation">
				<?php $this->render_tab_fields( $tab ); ?>
			</table>
		</div>
		<?php
	}

	/**
	 * Render fields for active tab.
	 *
	 * @param string $tab Active tab.
	 * @return void
	 */
	private function render_tab_fields( $tab ) {
		if ( 'branding' === $tab ) {
			$this->field( __( 'Default Logo', 'protected-video-playlist' ), 'pvp_render_image_field', array( 'key' => 'logo' ) );
			$this->field( __( 'Logo Link URL', 'protected-video-playlist' ), 'pvp_render_logo_url_field' );
			$this->field( __( 'Logo Size', 'protected-video-playlist' ), 'pvp_render_logo_size_field' );
			$this->field( __( 'Logo Opacity (%)', 'protected-video-playlist' ), 'pvp_render_logo_opacity_field' );
			$this->field( __( 'Logo Placement', 'protected-video-playlist' ), 'pvp_render_logo_position_field' );
			$this->field( __( 'Logo Rounded', 'protected-video-playlist' ), 'pvp_render_logo_radius_field' );
			$this->field( __( 'Controls Bar Background', 'protected-video-playlist' ), 'pvp_render_branding_color_field', array( 'key' => 'controls_bg_color' ) );
			$this->field( __( 'Controls Icons Color', 'protected-video-playlist' ), 'pvp_render_branding_color_field', array( 'key' => 'controls_color' ) );
			$this->field( __( 'Play Button Background', 'protected-video-playlist' ), 'pvp_render_branding_color_field', array( 'key' => 'play_btn_bg_color' ) );
			$this->field( __( 'Play Button Icon Color', 'protected-video-playlist' ), 'pvp_render_branding_color_field', array( 'key' => 'play_btn_color' ) );
		}

		if ( 'overlay' === $tab ) {
			$this->field( __( 'Marketing Text', 'protected-video-playlist' ), 'pvp_render_overlay_text_field' );
			$this->field( __( 'Display On', 'protected-video-playlist' ), 'pvp_render_overlay_display_on_field' );
			$this->field( __( 'Display Time Ranges', 'protected-video-playlist' ), 'pvp_render_overlay_time_ranges_field' );
			$this->field( __( 'Overlay Position', 'protected-video-playlist' ), 'pvp_render_overlay_position_field' );
			$this->field( __( 'Background Color', 'protected-video-playlist' ), 'pvp_render_overlay_bg_field' );
		}

		if ( 'protection' === $tab ) {
			$this->field( __( 'Disable Volume', 'protected-video-playlist' ), 'pvp_render_checkbox_field', array( 'key' => 'disable_volume' ) );
			$this->field( __( 'Disable Play Button', 'protected-video-playlist' ), 'pvp_render_checkbox_field', array( 'key' => 'disable_playbutton' ) );
			$this->field( __( 'Disable Fullscreen', 'protected-video-playlist' ), 'pvp_render_checkbox_field', array( 'key' => 'disable_fullscreen' ) );
			$this->field( __( 'Disable Controls', 'protected-video-playlist' ), 'pvp_render_checkbox_field', array( 'key' => 'disable_controls' ) );
		}

		if ( 'playback' === $tab ) {
			$this->field( __( 'YouTube API Key', 'protected-video-playlist' ), 'pvp_render_api_key_field' );
			$this->field( __( 'Default Columns', 'protected-video-playlist' ), 'pvp_render_default_columns_field' );
			$this->field( __( 'Disable Autoplay', 'protected-video-playlist' ), 'pvp_render_checkbox_field', array( 'key' => 'disable_autoplay' ) );
			$this->field( __( 'Delete Data on Uninstall', 'protected-video-playlist' ), 'pvp_render_delete_on_uninstall_field' );
		}
	}

	/**
	 * Render one settings table row.
	 *
	 * @param string $label Field label.
	 * @param string $callback Global callback.
	 * @param array  $args Callback arguments.
	 * @return void
	 */
	private function field( $label, $callback, $args = array() ) {
		if ( ! function_exists( $callback ) ) {
			return;
		}
		?>
		<tr>
			<th scope="row"><?php echo esc_html( $label ); ?></th>
			<td>
				<?php
				if ( empty( $args ) ) {
					call_user_func( $callback );
				} else {
					call_user_func( $callback, $args );
				}
				?>
			</td>
		</tr>
		<?php
	}
}
