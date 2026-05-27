<?php
/**
 * Centralized frontend renderer.
 *
 * @package RSPLR\Rendering
 */

namespace RSPLR\Rendering;

use RSPLR\Repository\PlaylistRepository;
use RSPLR\Repository\VideoRepository;

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
	 * Constructor.
	 *
	 * @param VideoRepository    $videos Video repository.
	 * @param PlaylistRepository $playlists Playlist repository.
	 */
	public function __construct( VideoRepository $videos, PlaylistRepository $playlists ) {
		$this->videos    = $videos;
		$this->playlists = $playlists;
	}

	/**
	 * Render a single reusable video.
	 *
	 * @param int                  $video_id Video post ID.
	 * @param array<string, mixed> $args Render args.
	 * @return string
	 */
	public function video( $video_id, array $args = array() ) {
		$video = $this->videos->get( absint( $video_id ) );

		if ( ! $video || empty( $video['url'] ) ) {
			return $this->error( __( 'RS SecurePlayer: Video not found.', 'protected-video-playlist' ) );
		}

		return pvp_render_single_video( $video['url'], '', $video['id'] );
	}

	/**
	 * Render a playlist entity.
	 *
	 * @param int                  $playlist_post_id Playlist post ID.
	 * @param array<string, mixed> $args Render args.
	 * @return string
	 */
	public function playlist( $playlist_post_id, array $args = array() ) {
		$playlist = $this->playlists->get( absint( $playlist_post_id ) );

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
		unset( $campaign_id, $args );

		return $this->error( __( 'RS SecurePlayer: Campaign rendering will be available after the campaign system is implemented.', 'protected-video-playlist' ) );
	}

	/**
	 * Render legacy URL input through the centralized renderer.
	 *
	 * @param string               $url Video or playlist URL.
	 * @param array<string, mixed> $args Render args.
	 * @return string
	 */
	public function url( $url, array $args = array() ) {
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
}
