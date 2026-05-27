<?php
/**
 * Playlist import post type registration.
 *
 * @package RSPLR\CPT
 */

namespace RSPLR\CPT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PlaylistPostType {
	public const POST_TYPE = 'rsplr_playlist';

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
	 * Register private playlist entity.
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'              => __( 'RS Playlists', 'protected-video-playlist' ),
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
	 * Register playlist meta keys.
	 *
	 * @return void
	 */
	public function register_meta() {
		$fields = array(
			'_rsplr_source_provider' => 'string',
			'_rsplr_playlist_url'    => 'string',
			'_rsplr_playlist_id'     => 'string',
			'_rsplr_sync_status'     => 'string',
			'_rsplr_last_sync'       => 'string',
			'_rsplr_total_videos'    => 'integer',
			'_rsplr_imported_count'  => 'integer',
			'_rsplr_updated_count'   => 'integer',
			'_rsplr_skipped_count'   => 'integer',
			'_rsplr_failed_count'    => 'integer',
			'_rsplr_last_error'      => 'string',
			'_rsplr_import_method'   => 'string',
			'_rsplr_next_page_token' => 'string',
			'_rsplr_import_offset'   => 'integer',
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
