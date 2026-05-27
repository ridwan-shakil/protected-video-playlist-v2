<?php
/**
 * Centralized frontend renderer.
 *
 * @package RSPLR\Rendering
 */

namespace RSPLR\Rendering;

use RSPLR\Repository\PlaylistRepository;
use RSPLR\Repository\VideoRepository;
use RSPLR\Repository\CampaignRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Renderer {
	/**
	 * Video repository.
	 *
	 * @var VideoRepository
	 */
	private $videos;

	/**
	 * Playlist repository.
	 *
	 * @var PlaylistRepository
	 */
	private $playlists;

	/**
	 * Campaign repository.
	 *
	 * @var CampaignRepository
	 */
	private $campaigns;

	/**
	 * Whether frontend assets have been requested by this renderer.
	 *
	 * @var bool
	 */
	private $assets_enqueued = false;

	/**
	 * Constructor.
	 *
	 * @param VideoRepository    $videos Video repository.
	 * @param PlaylistRepository $playlists Playlist repository.
	 * @param CampaignRepository $campaigns Campaign repository.
	 */
	public function __construct( VideoRepository $videos, PlaylistRepository $playlists, CampaignRepository $campaigns ) {
		$this->videos    = $videos;
		$this->playlists = $playlists;
		$this->campaigns = $campaigns;
	}

	/**
	 * Render a single reusable video.
	 *
	 * @param int                  $video_id Video post ID.
	 * @param array<string, mixed> $args Render args.
	 * @return string
	 */
	public function video( $video_id, array $args = array() ) {
		$this->enqueue_assets();

		$video = $this->videos->get( absint( $video_id ) );

		if ( ! $video || empty( $video['url'] ) ) {
			return $this->error( __( 'RS SecurePlayer: Video not found.', 'protected-video-playlist' ) );
		}

		return pvp_render_single_video( $video['url'], '', $video['id'] );
	}

	/**
	 * Render a playlist entity.
	 *
	 * @param int|string           $playlist_post_id Playlist post ID or external playlist ID.
	 * @param array<string, mixed> $args Render args.
	 * @return string
	 */
	public function playlist( $playlist_post_id, array $args = array() ) {
		$this->enqueue_assets();

		$playlist = $this->playlists->get_by_identifier( $playlist_post_id );

		if ( ! $playlist || empty( $playlist['playlist_id'] ) ) {
			return $this->error( __( 'RS SecurePlayer: Playlist not found.', 'protected-video-playlist' ) );
		}

		$layout  = isset( $args['layout'] ) ? sanitize_key( $args['layout'] ) : 'grid';
		$columns = isset( $args['columns'] ) ? max( 1, min( 4, intval( $args['columns'] ) ) ) : 3;
		$ids     = $this->videos->find_ids_by_playlist_id( $playlist['playlist_id'] );

		if ( empty( $ids ) ) {
			return $this->error( __( 'RS SecurePlayer: This playlist has no imported videos yet.', 'protected-video-playlist' ) );
		}

		if ( 'minimal' === $layout ) {
			return $this->video( $ids[0] );
		}

		$items = array();

		foreach ( $ids as $id ) {
			$video = $this->videos->get( $id );

			if ( ! $video || empty( $video['url'] ) ) {
				continue;
			}

			$items[] = array(
				'url'     => $video['url'],
				'title'   => $video['title'],
				'post_id' => $video['id'],
			);
		}

		if ( empty( $items ) ) {
			return $this->error( __( 'RS SecurePlayer: This playlist has no playable videos.', 'protected-video-playlist' ) );
		}

		$output = pvp_render_grid( $items, $columns );

		if ( 'sidebar' === $layout ) {
			$output = '<div class="rsplr-playlist rsplr-playlist--sidebar">' . $output . '</div>';
		}

		return $output;
	}

	/**
	 * Render campaign placeholder until Phase 6.
	 *
	 * @param int                  $campaign_id Campaign ID.
	 * @param array<string, mixed> $args Render args.
	 * @return string
	 */
	public function campaign( $campaign_id, array $args = array() ) {
		$this->enqueue_assets();

		unset( $args );

		$campaign = $this->campaigns->get( absint( $campaign_id ) );

		if ( ! $campaign || empty( $campaign['main_video_id'] ) ) {
			return $this->error( __( 'RS SecurePlayer: Campaign not found or missing a main video.', 'protected-video-playlist' ) );
		}

		$queue = array();

		foreach ( array( 'intro_video_id', 'main_video_id', 'outro_video_id' ) as $key ) {
			if ( empty( $campaign[ $key ] ) ) {
				continue;
			}

			$video = $this->videos->get( $campaign[ $key ] );

			if ( ! $video || empty( $video['url'] ) ) {
				continue;
			}

			$queue[] = array(
				'id'       => $video['id'],
				'title'    => $video['title'],
				'provider' => $video['provider'],
				'videoId'  => $video['video_id'],
				'url'      => $video['url'],
				'role'     => str_replace( '_video_id', '', $key ),
			);
		}

		if ( empty( $queue ) ) {
			return $this->error( __( 'RS SecurePlayer: Campaign has no playable videos.', 'protected-video-playlist' ) );
		}

		$output = '';

		foreach ( $queue as $item ) {
			$output .= sprintf(
				'<div class="rsplr-campaign__item rsplr-campaign__item--%1$s" data-rsplr-campaign-role="%1$s">%2$s</div>',
				esc_attr( $item['role'] ),
				pvp_render_single_video( $item['url'], '', $item['id'] )
			);
		}

		return sprintf(
			'<div class="rsplr-campaign" data-campaign-id="%1$d" data-rsplr-campaign-queue="%2$s">%3$s</div>',
			absint( $campaign['id'] ),
			esc_attr( wp_json_encode( $queue ) ),
			$output
		);
	}

	/**
	 * Render legacy URL input through the centralized renderer.
	 *
	 * @param string               $url Video or playlist URL.
	 * @param array<string, mixed> $args Render args.
	 * @return string
	 */
	public function url( $url, array $args = array() ) {
		$this->enqueue_assets();

		$url     = sanitize_text_field( $url );
		$columns = isset( $args['columns'] ) ? max( 1, min( 4, intval( $args['columns'] ) ) ) : 3;
		$cache   = isset( $args['cache'] ) ? max( 0, intval( $args['cache'] ) ) : 0;

		if ( '' === $url ) {
			return '';
		}

		if ( pvp_is_playlist_url( $url ) ) {
			return pvp_render_grid_from_url( $url, $columns, $cache );
		}

		return pvp_render_single_video( $url );
	}

	/**
	 * Render an escaped error.
	 *
	 * @param string $message Message.
	 * @return string
	 */
	private function error( $message ) {
		return '<p class="pvp-error rsplr-error">' . esc_html( $message ) . '</p>';
	}

	/**
	 * Enqueue frontend assets when shortcodes render outside normal detection.
	 *
	 * Elementor can render shortcode widgets from post meta or AJAX contexts where
	 * post-content detection is unreliable, so RS shortcodes request their own
	 * runtime dependencies here.
	 *
	 * @return void
	 */
	private function enqueue_assets() {
		if ( $this->assets_enqueued ) {
			return;
		}

		$this->assets_enqueued = true;

		wp_enqueue_style( 'protected-video-protected-video-style' );
		wp_enqueue_script( 'protected-video-protected-video-view-script' );

		wp_add_inline_script(
			'protected-video-protected-video-view-script',
			'window.ProtectedVideoSettings = window.ProtectedVideoSettings || ' . wp_json_encode(
				array(
					'disableRightClick' => '1' === get_option( 'protected_video_disable_right_click', '1' ),
				)
			) . ';',
			'before'
		);

		wp_enqueue_style(
			'pvp-grid-style',
			RSPLR_PLUGIN_URL . 'public/css/pvp-frontend.css',
			array( 'protected-video-protected-video-style' ),
			RSPLR_VERSION
		);

		wp_enqueue_script(
			'pvp-frontend',
			RSPLR_PLUGIN_URL . 'public/js/pvp-frontend.js',
			array( 'jquery' ),
			RSPLR_VERSION,
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
	}
}
