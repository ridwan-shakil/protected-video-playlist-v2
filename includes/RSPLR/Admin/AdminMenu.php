<?php
/**
 * Main RS SecurePlayer admin menu.
 *
 * @package RSPLR\Admin
 */

namespace RSPLR\Admin;

use RSPLR\Repository\PlaylistRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminMenu {
	public const MENU_SLUG = 'rsplr-dashboard';

	/**
	 * Playlist repository.
	 *
	 * @var PlaylistRepository
	 */
	private $playlists;

	/**
	 * Constructor.
	 *
	 * @param PlaylistRepository $playlists Playlist repository.
	 */
	public function __construct( PlaylistRepository $playlists ) {
		$this->playlists = $playlists;
	}

	/**
	 * Register menu hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 10 );
	}

	/**
	 * Register main navigation.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'RS Protected Video', 'protected-video-playlist' ),
			__( 'RS Protected Video', 'protected-video-playlist' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-video-alt3',
			56
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard', 'protected-video-playlist' ),
			__( 'Dashboard', 'protected-video-playlist' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Video Library', 'protected-video-playlist' ),
			__( 'Video Library', 'protected-video-playlist' ),
			'manage_options',
			'edit.php?post_type=pvp_video'
		);
	}

	/**
	 * Render dashboard.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$video_counts = wp_count_posts( 'pvp_video' );
		$video_total  = isset( $video_counts->publish ) ? intval( $video_counts->publish ) : 0;
		$playlists    = $this->playlists->all();
		$failed       = 0;
		$running      = 0;

		foreach ( $playlists as $playlist_post ) {
			$playlist = $this->playlists->get( $playlist_post->ID );

			if ( ! $playlist ) {
				continue;
			}

			if ( 'failed' === $playlist['status'] ) {
				$failed++;
			}

			if ( 'running' === $playlist['status'] ) {
				$running++;
			}
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'RS Protected Video', 'protected-video-playlist' ); ?></h1>
			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;max-width:900px;margin-top:16px;">
				<?php $this->metric_card( __( 'Videos', 'protected-video-playlist' ), $video_total ); ?>
				<?php $this->metric_card( __( 'Playlists', 'protected-video-playlist' ), count( $playlists ) ); ?>
				<?php $this->metric_card( __( 'Running Imports', 'protected-video-playlist' ), $running ); ?>
				<?php $this->metric_card( __( 'Failed Imports', 'protected-video-playlist' ), $failed ); ?>
			</div>

			<h2><?php esc_html_e( 'Workflows', 'protected-video-playlist' ); ?></h2>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=rsplr-playlist-imports' ) ); ?>"><?php esc_html_e( 'Import Playlist', 'protected-video-playlist' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=pvp_video' ) ); ?>"><?php esc_html_e( 'Open Video Library', 'protected-video-playlist' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=rsplr-settings' ) ); ?>"><?php esc_html_e( 'Open Settings', 'protected-video-playlist' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Render a dashboard metric.
	 *
	 * @param string $label Label.
	 * @param int    $value Value.
	 * @return void
	 */
	private function metric_card( $label, $value ) {
		?>
		<div style="background:#fff;border:1px solid #dcdcde;border-radius:4px;padding:14px;">
			<div style="font-size:13px;color:#646970;"><?php echo esc_html( $label ); ?></div>
			<div style="font-size:28px;font-weight:600;line-height:1.3;"><?php echo esc_html( $value ); ?></div>
		</div>
		<?php
	}
}
