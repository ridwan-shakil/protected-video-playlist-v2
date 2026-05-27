<?php
/**
 * Campaign repository.
 *
 * @package RSPLR\Repository
 */

namespace RSPLR\Repository;

use RSPLR\CPT\CampaignPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignRepository {
	/**
	 * Return all campaign posts.
	 *
	 * @return \WP_Post[]
	 */
	public function all() {
		return get_posts(
			array(
				'post_type'      => CampaignPostType::POST_TYPE,
				'post_status'    => 'publish',
				'numberposts'    => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
	}

	/**
	 * Read one campaign.
	 *
	 * @param int $post_id Campaign post ID.
	 * @return array<string, mixed>|null
	 */
	public function get( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post || CampaignPostType::POST_TYPE !== $post->post_type ) {
			return null;
		}

		return array(
			'id'             => absint( $post->ID ),
			'name'           => $post->post_title,
			'intro_video_id' => absint( get_post_meta( $post->ID, '_rsplr_intro_video_id', true ) ),
			'main_video_id'  => absint( get_post_meta( $post->ID, '_rsplr_main_video_id', true ) ),
			'outro_video_id' => absint( get_post_meta( $post->ID, '_rsplr_outro_video_id', true ) ),
		);
	}

	/**
	 * Create or update a campaign.
	 *
	 * @param array<string, mixed> $data Campaign data.
	 * @return int|\WP_Error
	 */
	public function save( array $data ) {
		$post_id = isset( $data['id'] ) ? absint( $data['id'] ) : 0;
		$name    = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$main_id = isset( $data['main_video_id'] ) ? absint( $data['main_video_id'] ) : 0;

		if ( '' === $name ) {
			return new \WP_Error( 'rsplr_campaign_name_required', __( 'Campaign name is required.', 'protected-video-playlist' ) );
		}

		if ( ! $main_id ) {
			return new \WP_Error( 'rsplr_campaign_main_required', __( 'Main video is required.', 'protected-video-playlist' ) );
		}

		$post_data = array(
			'post_type'   => CampaignPostType::POST_TYPE,
			'post_title'  => $name,
			'post_status' => 'publish',
		);

		if ( $post_id ) {
			$post_data['ID'] = $post_id;
			$result = wp_update_post( $post_data, true );
		} else {
			$result = wp_insert_post( $post_data, true );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$post_id = absint( $result );

		update_post_meta( $post_id, '_rsplr_intro_video_id', isset( $data['intro_video_id'] ) ? absint( $data['intro_video_id'] ) : 0 );
		update_post_meta( $post_id, '_rsplr_main_video_id', $main_id );
		update_post_meta( $post_id, '_rsplr_outro_video_id', isset( $data['outro_video_id'] ) ? absint( $data['outro_video_id'] ) : 0 );

		return $post_id;
	}
}
