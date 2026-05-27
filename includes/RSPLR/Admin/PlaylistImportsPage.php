<?php
/**
 * Playlist imports admin page.
 *
 * @package RSPLR\Admin
 */

namespace RSPLR\Admin;

use RSPLR\Repository\PlaylistRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PlaylistImportsPage {
	public const MENU_SLUG = 'rsplr-playlist-imports';

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
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register temporary Phase 3 menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'RS Protected Video', 'protected-video-playlist' ),
			__( 'RS Protected Video', 'protected-video-playlist' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' ),
			'dashicons-video-alt3',
			56
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Playlist Imports', 'protected-video-playlist' ),
			__( 'Playlist Imports', 'protected-video-playlist' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Enqueue page assets.
	 *
	 * @param string $hook Current hook suffix.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, self::MENU_SLUG ) ) {
			return;
		}

		wp_enqueue_script(
			'rsplr-playlist-imports',
			RSPLR_PLUGIN_URL . 'admin/js/playlist-imports.js',
			array( 'jquery' ),
			RSPLR_VERSION,
			true
		);

		wp_localize_script(
			'rsplr-playlist-imports',
			'RSPLRPlaylistImports',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'rsplr_playlist_import' ),
				'i18n'    => array(
					'starting' => __( 'Starting import...', 'protected-video-playlist' ),
					'running'  => __( 'Importing playlist...', 'protected-video-playlist' ),
					'complete' => __( 'Import complete.', 'protected-video-playlist' ),
					'failed'   => __( 'Import failed.', 'protected-video-playlist' ),
				),
			)
		);
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$playlists = $this->playlists->all();
		?>
		<div class="wrap rsplr-playlist-imports">
			<h1><?php esc_html_e( 'Playlist Imports', 'protected-video-playlist' ); ?></h1>

			<form id="rsplr-playlist-import-form" class="card" style="max-width: 760px; padding: 16px;">
				<h2><?php esc_html_e( 'Import YouTube Playlist', 'protected-video-playlist' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="rsplr-playlist-name"><?php esc_html_e( 'Playlist Name', 'protected-video-playlist' ); ?></label>
						</th>
						<td>
							<input type="text" id="rsplr-playlist-name" class="regular-text" required />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="rsplr-playlist-url"><?php esc_html_e( 'Playlist URL', 'protected-video-playlist' ); ?></label>
						</th>
						<td>
							<input type="url" id="rsplr-playlist-url" class="large-text" required />
							<p class="description"><?php esc_html_e( 'The importer automatically uses the YouTube API when configured, otherwise RSS fallback is used.', 'protected-video-playlist' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Import Playlist', 'protected-video-playlist' ), 'primary', 'submit', false, array( 'id' => 'rsplr-import-playlist-button' ) ); ?>
				<span id="rsplr-import-status" style="margin-left: 10px;"></span>
			</form>

			<h2><?php esc_html_e( 'Managed Playlists', 'protected-video-playlist' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'protected-video-playlist' ); ?></th>
						<th><?php esc_html_e( 'Playlist ID', 'protected-video-playlist' ); ?></th>
						<th><?php esc_html_e( 'Method', 'protected-video-playlist' ); ?></th>
						<th><?php esc_html_e( 'Status', 'protected-video-playlist' ); ?></th>
						<th><?php esc_html_e( 'Imported', 'protected-video-playlist' ); ?></th>
						<th><?php esc_html_e( 'Last Sync', 'protected-video-playlist' ); ?></th>
						<th><?php esc_html_e( 'Last Error', 'protected-video-playlist' ); ?></th>
					</tr>
				</thead>
				<tbody id="rsplr-playlist-imports-table">
					<?php if ( empty( $playlists ) ) : ?>
						<tr>
							<td colspan="7"><?php esc_html_e( 'No playlists have been imported yet.', 'protected-video-playlist' ); ?></td>
						</tr>
					<?php else : ?>
						<?php foreach ( $playlists as $playlist_post ) : ?>
							<?php $playlist = $this->playlists->get( $playlist_post->ID ); ?>
							<tr>
								<td><?php echo esc_html( $playlist['name'] ); ?></td>
								<td><code><?php echo esc_html( $playlist['playlist_id'] ); ?></code></td>
								<td><?php echo esc_html( strtoupper( $playlist['import_method'] ?: '-' ) ); ?></td>
								<td><?php echo esc_html( ucfirst( $playlist['status'] ) ); ?></td>
								<td><?php echo esc_html( $playlist['imported_count'] ); ?> / <?php echo esc_html( $playlist['total_videos'] ); ?></td>
								<td><?php echo esc_html( $playlist['last_sync'] ?: '-' ); ?></td>
								<td><?php echo esc_html( $playlist['last_error'] ?: '-' ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
