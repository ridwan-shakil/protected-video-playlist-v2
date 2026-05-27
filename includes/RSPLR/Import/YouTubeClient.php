<?php
/**
 * YouTube playlist fetch client.
 *
 * @package RSPLR\Import
 */

namespace RSPLR\Import;

use RSPLR\Settings\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class YouTubeClient {
	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository $settings Settings repository.
	 */
	public function __construct( SettingsRepository $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Whether API imports are configured.
	 *
	 * @return bool
	 */
	public function has_api_key() {
		return '' !== $this->api_key();
	}

	/**
	 * Fetch one YouTube Data API playlist page.
	 *
	 * @param string $playlist_id External playlist ID.
	 * @param string $page_token Page token.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function fetch_api_page( $playlist_id, $page_token = '' ) {
		$api_key = $this->api_key();

		if ( '' === $api_key ) {
			return new \WP_Error( 'rsplr_missing_api_key', __( 'YouTube API key is not configured.', 'protected-video-playlist' ) );
		}

		$url = add_query_arg(
			array_filter(
				array(
					'key'        => $api_key,
					'playlistId' => sanitize_text_field( $playlist_id ),
					'part'       => 'snippet',
					'maxResults' => 50,
					'pageToken'  => sanitize_text_field( $page_token ),
				)
			),
			'https://www.googleapis.com/youtube/v3/playlistItems'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 15,
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$message = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'YouTube API request failed.', 'protected-video-playlist' );

			return new \WP_Error( 'rsplr_youtube_api_error', $message, array( 'status' => $code ) );
		}

		if ( ! is_array( $body ) || ! isset( $body['items'] ) ) {
			return new \WP_Error( 'rsplr_invalid_api_response', __( 'Invalid YouTube API response.', 'protected-video-playlist' ) );
		}

		$videos = array();

		foreach ( $body['items'] as $index => $item ) {
			$snippet  = isset( $item['snippet'] ) && is_array( $item['snippet'] ) ? $item['snippet'] : array();
			$video_id = isset( $snippet['resourceId']['videoId'] ) ? sanitize_text_field( $snippet['resourceId']['videoId'] ) : '';

			if ( '' === $video_id ) {
				continue;
			}

			$videos[] = array(
				'video_id'  => $video_id,
				'title'     => isset( $snippet['title'] ) ? sanitize_text_field( $snippet['title'] ) : '',
				'thumbnail' => isset( $snippet['thumbnails']['medium']['url'] ) ? esc_url_raw( $snippet['thumbnails']['medium']['url'] ) : $this->youtube_thumbnail( $video_id ),
				'url'       => 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id ),
				'provider'  => 'youtube',
				'position'  => isset( $snippet['position'] ) ? intval( $snippet['position'] ) : $index,
			);
		}

		return array(
			'videos'          => $videos,
			'next_page_token' => isset( $body['nextPageToken'] ) ? sanitize_text_field( $body['nextPageToken'] ) : '',
			'total_results'   => isset( $body['pageInfo']['totalResults'] ) ? intval( $body['pageInfo']['totalResults'] ) : count( $videos ),
		);
	}

	/**
	 * Fetch RSS playlist videos.
	 *
	 * @param string $playlist_id External playlist ID.
	 * @return array<string, mixed>|\WP_Error
	 */
	public function fetch_rss( $playlist_id ) {
		$feed_url = 'https://www.youtube.com/feeds/videos.xml?playlist_id=' . rawurlencode( $playlist_id );

		$response = wp_remote_get(
			$feed_url,
			array(
				'timeout'    => 15,
				'user-agent' => 'WordPress/' . get_bloginfo( 'version' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! function_exists( 'simplexml_load_string' ) ) {
			return new \WP_Error( 'rsplr_simplexml_missing', __( 'SimpleXML PHP extension is required but not available on this server.', 'protected-video-playlist' ) );
		}

		libxml_use_internal_errors( true );

		if ( PHP_VERSION_ID < 80000 ) {
			libxml_disable_entity_loader( true );
		}

		$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );

		if ( false === $xml ) {
			return new \WP_Error( 'rsplr_rss_parse_error', __( 'Failed to parse the YouTube RSS XML.', 'protected-video-playlist' ) );
		}

		$xml->registerXPathNamespace( 'yt', 'http://www.youtube.com/xml/schemas/2015' );

		$videos = array();

		foreach ( $xml->entry as $index => $entry ) {
			$id_nodes = $entry->xpath( 'yt:videoId' );

			if ( empty( $id_nodes ) ) {
				continue;
			}

			$video_id = sanitize_text_field( (string) $id_nodes[0] );

			$videos[] = array(
				'video_id'  => $video_id,
				'title'     => sanitize_text_field( (string) $entry->title ),
				'thumbnail' => $this->youtube_thumbnail( $video_id ),
				'url'       => 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id ),
				'provider'  => 'youtube',
				'position'  => intval( $index ),
			);
		}

		return array(
			'videos'          => $videos,
			'next_page_token' => '',
			'total_results'   => count( $videos ),
		);
	}

	/**
	 * Get API key.
	 *
	 * @return string
	 */
	private function api_key() {
		return sanitize_text_field( $this->settings->get( 'youtube_api_key', '' ) );
	}

	/**
	 * Build a standard YouTube thumbnail URL.
	 *
	 * @param string $video_id Video ID.
	 * @return string
	 */
	private function youtube_thumbnail( $video_id ) {
		return 'https://i.ytimg.com/vi/' . rawurlencode( $video_id ) . '/mqdefault.jpg';
	}
}
