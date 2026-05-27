<?php
/**
 * Playlist import orchestration.
 *
 * @package RSPLR\Import
 */

namespace RSPLR\Import;

use RSPLR\Repository\PlaylistRepository;
use RSPLR\Repository\VideoRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PlaylistImporter {
	/**
	 * Playlist repository.
	 *
	 * @var PlaylistRepository
	 */
	private $playlists;

	/**
	 * Video repository.
	 *
	 * @var VideoRepository
	 */
	private $videos;

	/**
	 * YouTube client.
	 *
	 * @var YouTubeClient
	 */
	private $youtube;

	/**
	 * Constructor.
	 *
	 * @param PlaylistRepository $playlists Playlist repository.
	 * @param VideoRepository    $videos Video repository.
	 * @param YouTubeClient      $youtube YouTube client.
	 */
	public function __construct( PlaylistRepository $playlists, VideoRepository $videos, YouTubeClient $youtube ) {
		$this->playlists = $playlists;
		$this->videos    = $videos;
		$this->youtube   = $youtube;
	}

	/**
	 * Start a playlist import.
	 *
	 * @param string $name Playlist name.
	 * @param string $url Playlist URL.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function start( $name, $url ) {
		$name        = sanitize_text_field( $name );
		$url         = esc_url_raw( $url );
		$playlist_id = pvp_extract_playlist_id( $url );

		if ( '' === $name ) {
			return new \WP_Error( 'rsplr_missing_playlist_name', __( 'Playlist name is required.', 'protected-video-playlist' ) );
		}

		if ( ! $playlist_id ) {
			return new \WP_Error( 'rsplr_invalid_playlist_url', __( 'Could not extract a YouTube playlist ID from the URL.', 'protected-video-playlist' ) );
		}

		$post_id = $this->playlists->create_or_update( $name, $url, $playlist_id );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$method = $this->youtube->has_api_key() ? 'api' : 'rss';

		$this->playlists->update_state(
			$post_id,
			array(
				'status'          => 'running',
				'total_videos'    => 0,
				'imported_count'  => 0,
				'updated_count'   => 0,
				'skipped_count'   => 0,
				'failed_count'    => 0,
				'last_error'      => '',
				'import_method'   => $method,
				'next_page_token' => '',
				'import_offset'   => 0,
			)
		);

		return $this->status( $post_id );
	}

	/**
	 * Continue an import by one batch.
	 *
	 * @param int $playlist_post_id Playlist post ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function continue_import( $playlist_post_id ) {
		$playlist = $this->playlists->get( $playlist_post_id );

		if ( ! $playlist ) {
			return new \WP_Error( 'rsplr_playlist_not_found', __( 'Playlist import record was not found.', 'protected-video-playlist' ) );
		}

		if ( 'complete' === $playlist['status'] || 'partial' === $playlist['status'] || 'failed' === $playlist['status'] ) {
			return $playlist;
		}

		$method = $playlist['import_method'] ?: ( $this->youtube->has_api_key() ? 'api' : 'rss' );

		if ( 'api' === $method ) {
			$result = $this->youtube->fetch_api_page( $playlist['playlist_id'], $playlist['next_page_token'] );

			if ( is_wp_error( $result ) ) {
				$method = 'rss';
				$this->playlists->update_state(
					$playlist['id'],
					array(
						'import_method' => 'rss',
						'last_error'    => $result->get_error_message(),
					)
				);
			}
		}

		if ( 'rss' === $method ) {
			$result = $this->youtube->fetch_rss( $playlist['playlist_id'] );
		}

		if ( is_wp_error( $result ) ) {
			$this->playlists->update_state(
				$playlist['id'],
				array(
					'status'       => 'failed',
					'failed_count' => intval( $playlist['failed_count'] ) + 1,
					'last_error'   => $result->get_error_message(),
					'last_sync'    => current_time( 'mysql' ),
				)
			);

			return $result;
		}

		$imported = 0;
		$failed   = intval( $playlist['failed_count'] );
		$offset   = intval( $playlist['import_offset'] );

		foreach ( $result['videos'] as $index => $video ) {
			$video['playlist_id'] = $playlist['playlist_id'];
			$video['provider']    = 'youtube';
			$video['position']    = $offset + $index;

			$post_id = $this->videos->upsert_imported_video( $video );

			if ( $post_id ) {
				$imported++;
			} else {
				$failed++;
			}
		}

		$next_page_token = isset( $result['next_page_token'] ) ? $result['next_page_token'] : '';
		$status          = $next_page_token ? 'running' : ( $failed > 0 ? 'partial' : 'complete' );

		$this->playlists->update_state(
			$playlist['id'],
			array(
				'status'          => $status,
				'total_videos'    => isset( $result['total_results'] ) ? intval( $result['total_results'] ) : intval( $playlist['total_videos'] ),
				'imported_count'  => intval( $playlist['imported_count'] ) + $imported,
				'failed_count'    => $failed,
				'next_page_token' => $next_page_token,
				'import_offset'   => $offset + count( $result['videos'] ),
				'last_sync'       => current_time( 'mysql' ),
			)
		);

		return $this->status( $playlist['id'] );
	}

	/**
	 * Read current import status.
	 *
	 * @param int $playlist_post_id Playlist post ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function status( $playlist_post_id ) {
		$playlist = $this->playlists->get( $playlist_post_id );

		if ( ! $playlist ) {
			return new \WP_Error( 'rsplr_playlist_not_found', __( 'Playlist import record was not found.', 'protected-video-playlist' ) );
		}

		$playlist['done'] = in_array( $playlist['status'], array( 'complete', 'partial', 'failed' ), true );

		return $playlist;
	}
}
