<?php
/**
 * Playlist import repository.
 *
 * @package RSPLR\Repository
 */

namespace RSPLR\Repository;

use RSPLR\CPT\PlaylistPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PlaylistRepository {
	/**
	 * Create or update a playlist entity.
	 *
	 * @param string $name Playlist display name.
	 * @param string $url Playlist URL.
	 * @param string $external_id External playlist ID.
	 * @return int|\WP_Error
	 */
	public function create_or_update( $name, $url, $external_id ) {
		$existing_id = $this->find_by_external_id( $external_id );

		if ( $existing_id ) {
			wp_update_post(
				array(
					'ID'         => $existing_id,
					'post_title' => $name,
				)
			);

			$post_id = $existing_id;
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type'   => PlaylistPostType::POST_TYPE,
					'post_title'  => $name,
					'post_status' => 'publish',
				)
			);
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return $post_id;
		}

		update_post_meta( $post_id, '_rsplr_source_provider', 'youtube' );
		update_post_meta( $post_id, '_rsplr_playlist_url', esc_url_raw( $url ) );
		update_post_meta( $post_id, '_rsplr_playlist_id', sanitize_text_field( $external_id ) );
		update_post_meta( $post_id, '_rsplr_sync_status', 'idle' );

		return absint( $post_id );
	}

	/**
	 * Find playlist entity by external playlist ID.
	 *
	 * @param string $external_id External playlist ID.
	 * @return int
	 */
	public function find_by_external_id( $external_id ) {
		$ids = get_posts(
			array(
				'post_type'      => PlaylistPostType::POST_TYPE,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'numberposts'    => 1,
				'no_found_rows'  => true,
				'meta_key'       => '_rsplr_playlist_id',
				'meta_value'     => sanitize_text_field( $external_id ),
			)
		);

		return ! empty( $ids ) ? absint( $ids[0] ) : 0;
	}

	/**
	 * Resolve a playlist by numeric post ID or external playlist ID.
	 *
	 * @param int|string $identifier Playlist post ID or external playlist ID.
	 * @return array<string, mixed>|null
	 */
	public function get_by_identifier( $identifier ) {
		if ( is_numeric( $identifier ) ) {
			$playlist = $this->get( absint( $identifier ) );

			if ( $playlist ) {
				return $playlist;
			}
		}

		$external_id = sanitize_text_field( (string) $identifier );

		if ( '' === $external_id ) {
			return null;
		}

		$post_id = $this->find_by_external_id( $external_id );

		return $post_id ? $this->get( $post_id ) : null;
	}

	/**
	 * Return all playlist entities.
	 *
	 * @return \WP_Post[]
	 */
	public function all() {
		return get_posts(
			array(
				'post_type'      => PlaylistPostType::POST_TYPE,
				'post_status'    => 'publish',
				'numberposts'    => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
	}

	/**
	 * Read one playlist as an array.
	 *
	 * @param int $post_id Playlist post ID.
	 * @return array<string, mixed>|null
	 */
	public function get( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || PlaylistPostType::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return array(
			'id'              => absint( $post->ID ),
			'name'            => $post->post_title,
			'provider'        => get_post_meta( $post->ID, '_rsplr_source_provider', true ) ?: 'youtube',
			'url'             => get_post_meta( $post->ID, '_rsplr_playlist_url', true ),
			'playlist_id'     => get_post_meta( $post->ID, '_rsplr_playlist_id', true ),
			'status'          => get_post_meta( $post->ID, '_rsplr_sync_status', true ) ?: 'idle',
			'last_sync'       => get_post_meta( $post->ID, '_rsplr_last_sync', true ),
			'total_videos'    => intval( get_post_meta( $post->ID, '_rsplr_total_videos', true ) ),
			'imported_count'  => intval( get_post_meta( $post->ID, '_rsplr_imported_count', true ) ),
			'updated_count'   => intval( get_post_meta( $post->ID, '_rsplr_updated_count', true ) ),
			'skipped_count'   => intval( get_post_meta( $post->ID, '_rsplr_skipped_count', true ) ),
			'failed_count'    => intval( get_post_meta( $post->ID, '_rsplr_failed_count', true ) ),
			'last_error'      => get_post_meta( $post->ID, '_rsplr_last_error', true ),
			'import_method'   => get_post_meta( $post->ID, '_rsplr_import_method', true ),
			'next_page_token' => get_post_meta( $post->ID, '_rsplr_next_page_token', true ),
			'import_offset'   => intval( get_post_meta( $post->ID, '_rsplr_import_offset', true ) ),
		);
	}

	/**
	 * Update playlist meta state.
	 *
	 * @param int                  $post_id Playlist post ID.
	 * @param array<string, mixed> $data Meta data without leading key names.
	 * @return void
	 */
	public function update_state( $post_id, array $data ) {
		$map = array(
			'status'          => '_rsplr_sync_status',
			'last_sync'       => '_rsplr_last_sync',
			'total_videos'    => '_rsplr_total_videos',
			'imported_count'  => '_rsplr_imported_count',
			'updated_count'   => '_rsplr_updated_count',
			'skipped_count'   => '_rsplr_skipped_count',
			'failed_count'    => '_rsplr_failed_count',
			'last_error'      => '_rsplr_last_error',
			'import_method'   => '_rsplr_import_method',
			'next_page_token' => '_rsplr_next_page_token',
			'import_offset'   => '_rsplr_import_offset',
		);

		foreach ( $map as $key => $meta_key ) {
			if ( array_key_exists( $key, $data ) ) {
				update_post_meta( $post_id, $meta_key, $data[ $key ] );
			}
		}
	}
}
