<?php
/**
 * Campaign post type registration.
 *
 * @package RSPLR\CPT
 */

namespace RSPLR\CPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CampaignPostType {
	public const POST_TYPE = 'rsplr_campaign';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Register private campaign entity.
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'              => __( 'RS Campaigns', 'protected-video-playlist' ),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => false,
				'show_in_menu'       => false,
				'supports'           => array( 'title' ),
				'has_archive'        => false,
				'rewrite'            => false,
				'can_export'         => true,
			)
		);
	}

	/**
	 * Register campaign meta.
	 *
	 * @return void
	 */
	public function register_meta() {
		$fields = array(
			'_rsplr_intro_video_id' => 'integer',
			'_rsplr_main_video_id'  => 'integer',
			'_rsplr_outro_video_id' => 'integer',
		);

		foreach ( $fields as $key => $type ) {
			register_post_meta(
				self::POST_TYPE,
				$key,
				array(
					'type'          => $type,
					'single'        => true,
					'auth_callback' => function () {
						return current_user_can( 'manage_options' );
					},
				)
			);
		}
	}
}
