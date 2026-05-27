<?php
/**
 * Campaigns admin page.
 *
 * @package RSPLR\Admin
 */

namespace RSPLR\Admin;

use RSPLR\Repository\CampaignRepository;
use RSPLR\Repository\VideoRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignsPage {
	public const MENU_SLUG = 'rsplr-campaigns';

	/**
	 * Campaign repository.
	 *
	 * @var CampaignRepository
	 */
	private $campaigns;

	/**
	 * Video repository.
	 *
	 * @var VideoRepository
	 */
	private $videos;

	/**
	 * Constructor.
	 *
	 * @param CampaignRepository $campaigns Campaign repository.
	 * @param VideoRepository    $videos Video repository.
	 */
	public function __construct( CampaignRepository $campaigns, VideoRepository $videos ) {
		$this->campaigns = $campaigns;
		$this->videos    = $videos;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 15 );
		add_action( 'admin_post_rsplr_save_campaign', array( $this, 'save' ) );
	}

	/**
	 * Register menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			AdminMenu::MENU_SLUG,
			__( 'Campaigns', 'protected-video-playlist' ),
			__( 'Campaigns', 'protected-video-playlist' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Save campaign.
	 *
	 * @return void
	 */
	public function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'protected-video-playlist' ) );
		}

		check_admin_referer( 'rsplr_save_campaign' );

		$data = array(
			'id'             => isset( $_POST['campaign_id'] ) ? absint( wp_unslash( $_POST['campaign_id'] ) ) : 0,
			'name'           => isset( $_POST['campaign_name'] ) ? sanitize_text_field( wp_unslash( $_POST['campaign_name'] ) ) : '',
			'intro_video_id' => isset( $_POST['intro_video_id'] ) ? absint( wp_unslash( $_POST['intro_video_id'] ) ) : 0,
			'main_video_id'  => isset( $_POST['main_video_id'] ) ? absint( wp_unslash( $_POST['main_video_id'] ) ) : 0,
			'outro_video_id' => isset( $_POST['outro_video_id'] ) ? absint( wp_unslash( $_POST['outro_video_id'] ) ) : 0,
		);

		$result = $this->campaigns->save( $data );

		$redirect = add_query_arg(
			array(
				'page' => self::MENU_SLUG,
			),
			admin_url( 'admin.php' )
		);

		if ( is_wp_error( $result ) ) {
			$redirect = add_query_arg(
				array(
					'rsplr_error' => rawurlencode( $result->get_error_message() ),
				),
				$redirect
			);
		} else {
			$redirect = add_query_arg(
				array(
					'rsplr_saved' => 1,
					'edit'        => absint( $result ),
				),
				$redirect
			);
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Render page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$edit_id  = isset( $_GET['edit'] ) ? absint( wp_unslash( $_GET['edit'] ) ) : 0;
		$current  = $edit_id ? $this->campaigns->get( $edit_id ) : null;
		$videos   = $this->videos->all();
		$campaigns = $this->campaigns->all();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Campaigns', 'protected-video-playlist' ); ?></h1>

			<?php if ( isset( $_GET['rsplr_saved'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Campaign saved.', 'protected-video-playlist' ); ?></p></div>
			<?php endif; ?>

			<?php if ( isset( $_GET['rsplr_error'] ) ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php echo esc_html( wp_unslash( $_GET['rsplr_error'] ) ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="card" style="max-width:760px;padding:16px;">
				<h2><?php echo $current ? esc_html__( 'Edit Campaign', 'protected-video-playlist' ) : esc_html__( 'Create Campaign', 'protected-video-playlist' ); ?></h2>
				<input type="hidden" name="action" value="rsplr_save_campaign" />
				<input type="hidden" name="campaign_id" value="<?php echo esc_attr( $current['id'] ?? 0 ); ?>" />
				<?php wp_nonce_field( 'rsplr_save_campaign' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="rsplr-campaign-name"><?php esc_html_e( 'Campaign Name', 'protected-video-playlist' ); ?></label></th>
						<td><input type="text" id="rsplr-campaign-name" name="campaign_name" class="regular-text" required value="<?php echo esc_attr( $current['name'] ?? '' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="rsplr-intro-video"><?php esc_html_e( 'Intro Video', 'protected-video-playlist' ); ?></label></th>
						<td><?php $this->video_select( 'intro_video_id', 'rsplr-intro-video', $videos, absint( $current['intro_video_id'] ?? 0 ), true ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="rsplr-main-video"><?php esc_html_e( 'Main Video', 'protected-video-playlist' ); ?></label></th>
						<td><?php $this->video_select( 'main_video_id', 'rsplr-main-video', $videos, absint( $current['main_video_id'] ?? 0 ), false ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="rsplr-outro-video"><?php esc_html_e( 'Outro Video', 'protected-video-playlist' ); ?></label></th>
						<td><?php $this->video_select( 'outro_video_id', 'rsplr-outro-video', $videos, absint( $current['outro_video_id'] ?? 0 ), true ); ?></td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Campaign', 'protected-video-playlist' ), 'primary', 'submit', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Saved Campaigns', 'protected-video-playlist' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Name', 'protected-video-playlist' ); ?></th>
						<th><?php esc_html_e( 'Shortcode', 'protected-video-playlist' ); ?></th>
						<th><?php esc_html_e( 'Intro', 'protected-video-playlist' ); ?></th>
						<th><?php esc_html_e( 'Main', 'protected-video-playlist' ); ?></th>
						<th><?php esc_html_e( 'Outro', 'protected-video-playlist' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'protected-video-playlist' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $campaigns ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No campaigns created yet.', 'protected-video-playlist' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $campaigns as $campaign_post ) : ?>
							<?php $campaign = $this->campaigns->get( $campaign_post->ID ); ?>
							<tr>
								<td><?php echo esc_html( $campaign['name'] ); ?></td>
								<td><code><?php echo esc_html( '[rsplr_campaign id="' . $campaign['id'] . '"]' ); ?></code></td>
								<td><?php echo esc_html( $this->video_title( $campaign['intro_video_id'] ) ); ?></td>
								<td><?php echo esc_html( $this->video_title( $campaign['main_video_id'] ) ); ?></td>
								<td><?php echo esc_html( $this->video_title( $campaign['outro_video_id'] ) ); ?></td>
								<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => self::MENU_SLUG, 'edit' => $campaign['id'] ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Edit', 'protected-video-playlist' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render a video selector.
	 *
	 * @param string     $name Field name.
	 * @param string     $id Field ID.
	 * @param \WP_Post[] $videos Videos.
	 * @param int        $selected Selected ID.
	 * @param bool       $optional Whether empty value is allowed.
	 * @return void
	 */
	private function video_select( $name, $id, array $videos, $selected, $optional ) {
		?>
		<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $id ); ?>" class="regular-text" <?php echo $optional ? '' : 'required'; ?>>
			<?php if ( $optional ) : ?>
				<option value="0"><?php esc_html_e( 'None', 'protected-video-playlist' ); ?></option>
			<?php else : ?>
				<option value="0"><?php esc_html_e( 'Select a video', 'protected-video-playlist' ); ?></option>
			<?php endif; ?>

			<?php foreach ( $videos as $video ) : ?>
				<option value="<?php echo esc_attr( $video->ID ); ?>" <?php selected( $selected, $video->ID ); ?>>
					<?php echo esc_html( $video->post_title . ' (#' . $video->ID . ')' ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Get video title for list display.
	 *
	 * @param int $video_id Video post ID.
	 * @return string
	 */
	private function video_title( $video_id ) {
		if ( ! $video_id ) {
			return '-';
		}

		$title = get_the_title( $video_id );

		return $title ? $title : '#' . $video_id;
	}
}
