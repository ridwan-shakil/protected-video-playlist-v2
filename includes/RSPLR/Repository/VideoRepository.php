<?php
/**
 * Video library repository backed by the legacy pvp_video CPT.
 *
 * @package RSPLR\Repository
 */

namespace RSPLR\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VideoRepository {
	public const POST_TYPE = 'pvp_video';

	/**
	 * Find one video by playlist and remote video ID.
	 *
	 * @param string $playlist_id External playlist ID.
	 * @param string $video_id External video ID.
	 * @return int
	 */
	public function find_by_playlist_and_video_id( $playlist_id, $video_id ) {
		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'numberposts'    => 1,
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_pvp_playlist_id',
						'value' => $playlist_id,
					),
					array(
						'key'   => '_pvp_video_id',
						'value' => $video_id,
					),
				),
			)
		);

		return ! empty( $ids ) ? absint( $ids[0] ) : 0;
	}

	/**
	 * Find one video by provider and remote video ID.
	 *
	 * @param string $provider Provider key.
	 * @param string $video_id External video ID.
	 * @return int
	 */
	public function find_by_provider_and_video_id( $provider, $video_id ) {
		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'numberposts'    => 1,
				'no_found_rows'  => true,
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'   => '_pvp_provider',
						'value' => $provider,
					),
					array(
						'key'   => '_pvp_video_id',
						'value' => $video_id,
					),
				),
			)
		);

		return ! empty( $ids ) ? absint( $ids[0] ) : 0;
	}

	/**
	 * Return video IDs for a playlist.
	 *
	 * @param string $playlist_id External playlist ID.
	 * @param int    $limit Number of posts to return. -1 for all.
	 * @param int    $offset Query offset.
	 * @return int[]
	 */
	public function find_ids_by_playlist_id( $playlist_id, $limit = -1, $offset = 0 ) {
		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'numberposts'    => intval( $limit ),
				'offset'         => max( 0, intval( $offset ) ),
				'no_found_rows'  => true,
				'meta_key'       => '_pvp_playlist_id',
				'meta_value'     => $playlist_id,
				'orderby'        => 'date',
				'order'          => 'ASC',
			)
		);

		return array_map( 'absint', $ids );
	}

	/**
	 * Read one video source record.
	 *
	 * @param int $post_id Video post ID.
	 * @return array<string, mixed>|null
	 */
	public function get( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || self::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return array(
			'id'          => absint( $post->ID ),
			'title'       => $post->post_title,
			'provider'    => get_post_meta( $post->ID, '_pvp_provider', true ) ?: 'youtube',
			'video_id'    => get_post_meta( $post->ID, '_pvp_video_id', true ),
			'url'         => get_post_meta( $post->ID, '_pvp_video_url', true ),
			'playlist_id' => get_post_meta( $post->ID, '_pvp_playlist_id', true ),
			'thumbnail'   => get_post_meta( $post->ID, '_pvp_thumbnail_url', true ),
		);
	}

	/**
	 * Return videos for admin selectors.
	 *
	 * @return \WP_Post[]
	 */
	public function all() {
		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'numberposts'    => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
	}

	/**
	 * Upsert a remotely hosted video while preserving customization meta.
	 *
	 * @param array<string, mixed> $video Source metadata.
	 * @return int
	 */
	public function upsert_imported_video( array $video ) {
		$playlist_id = isset( $video['playlist_id'] ) ? sanitize_text_field( $video['playlist_id'] ) : '';
		$video_id    = isset( $video['video_id'] ) ? sanitize_text_field( $video['video_id'] ) : '';

		if ( '' === $video_id ) {
			return 0;
		}

		$title     = isset( $video['title'] ) ? sanitize_text_field( $video['title'] ) : '';
		$video_url = isset( $video['url'] ) ? esc_url_raw( $video['url'] ) : '';
		$thumbnail = isset( $video['thumbnail'] ) ? esc_url_raw( $video['thumbnail'] ) : '';
		$provider  = isset( $video['provider'] ) ? sanitize_key( $video['provider'] ) : 'youtube';
		$position  = isset( $video['position'] ) ? intval( $video['position'] ) : null;

		$post_id = '' !== $playlist_id
			? $this->find_by_playlist_and_video_id( $playlist_id, $video_id )
			: $this->find_by_provider_and_video_id( $provider, $video_id );

		if ( $post_id ) {
			$current_title = get_the_title( $post_id );
			if ( $title && $title !== $current_title ) {
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $title,
					)
				);
			}
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type'   => self::POST_TYPE,
					'post_title'  => $title,
					'post_status' => 'publish',
				)
			);
		}

		if ( ! $post_id || is_wp_error( $post_id ) ) {
			return 0;
		}

		update_post_meta( $post_id, '_pvp_provider', $provider );
		update_post_meta( $post_id, '_pvp_video_id', $video_id );
		update_post_meta( $post_id, '_pvp_video_url', $video_url );
		update_post_meta( $post_id, '_pvp_playlist_id', $playlist_id );
		update_post_meta( $post_id, '_pvp_thumbnail_url', $thumbnail );
		update_post_meta( $post_id, '_pvp_video_source_type', $playlist_id ? 'playlist_import' : 'manual' );
		update_post_meta( $post_id, '_pvp_import_updated_at', current_time( 'mysql' ) );

		if ( null !== $position ) {
			update_post_meta( $post_id, '_pvp_playlist_position', $position );
		}

		return absint( $post_id );
	}

	/**
	 * Create a reusable manual video record.
	 *
	 * @param array<string, mixed> $video Source metadata.
	 * @return int
	 */
	public function create_manual_video( array $video ) {
		$video['playlist_id'] = '';
		$video['provider']    = isset( $video['provider'] ) ? $video['provider'] : 'youtube';

		$post_id = $this->upsert_imported_video( $video );

		if ( $post_id ) {
			update_post_meta( $post_id, '_pvp_video_source_type', 'manual' );
		}

		return $post_id;
	}
}
