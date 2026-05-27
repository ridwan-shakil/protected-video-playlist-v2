<?php
/**
 * Admin columns for the legacy pvp_video library.
 *
 * @package RSPLR\Admin
 */

namespace RSPLR\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VideoLibraryColumns {
	/**
	 * Register admin list-table hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'manage_pvp_video_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_pvp_video_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-pvp_video_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_sorting' ) );
	}

	/**
	 * Add source-focused columns.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function columns( $columns ) {
		$new = array();

		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'title' === $key ) {
				$new['rsplr_thumbnail'] = __( 'Thumbnail', 'protected-video-playlist' );
				$new['rsplr_shortcode'] = __( 'Shortcode', 'protected-video-playlist' );
				$new['rsplr_provider']  = __( 'Provider', 'protected-video-playlist' );
				$new['rsplr_video_id']  = __( 'Video ID', 'protected-video-playlist' );
				$new['rsplr_playlist']  = __( 'Playlist', 'protected-video-playlist' );
				$new['rsplr_source']    = __( 'Source', 'protected-video-playlist' );
			}
		}

		return $new;
	}

	/**
	 * Render a custom column.
	 *
	 * @param string $column Column key.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		switch ( $column ) {
			case 'rsplr_thumbnail':
				$thumbnail = get_post_meta( $post_id, '_pvp_thumbnail_url', true );
				if ( $thumbnail ) {
					printf(
						'<img src="%s" alt="" style="width:80px;height:45px;object-fit:cover;" />',
						esc_url( $thumbnail )
					);
				} else {
					echo '&mdash;';
				}
				break;

			case 'rsplr_shortcode':
				echo '<code>' . esc_html( '[rsplr_video id="' . absint( $post_id ) . '"]' ) . '</code>';
				break;

			case 'rsplr_provider':
				$provider = get_post_meta( $post_id, '_pvp_provider', true );
				echo esc_html( $provider ? ucfirst( $provider ) : 'YouTube' );
				break;

			case 'rsplr_video_id':
				$video_id = get_post_meta( $post_id, '_pvp_video_id', true );
				echo $video_id ? esc_html( $video_id ) : '&mdash;';
				break;

			case 'rsplr_playlist':
				$playlist_id = get_post_meta( $post_id, '_pvp_playlist_id', true );
				echo $playlist_id ? esc_html( $playlist_id ) : '&mdash;';
				break;

			case 'rsplr_source':
				$source = get_post_meta( $post_id, '_pvp_video_source_type', true );
				echo esc_html( $source ? str_replace( '_', ' ', ucfirst( $source ) ) : __( 'Playlist import', 'protected-video-playlist' ) );
				break;
		}
	}

	/**
	 * Mark source columns sortable where WordPress can sort by meta.
	 *
	 * @param array<string, string> $columns Existing sortable columns.
	 * @return array<string, string>
	 */
	public function sortable_columns( $columns ) {
		$columns['rsplr_provider'] = 'rsplr_provider';
		$columns['rsplr_playlist'] = 'rsplr_playlist';

		return $columns;
	}

	/**
	 * Map sortable admin columns to legacy meta keys.
	 *
	 * @param \WP_Query $query Query object.
	 * @return void
	 */
	public function apply_sorting( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( 'pvp_video' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'rsplr_provider' === $orderby ) {
			$query->set( 'meta_key', '_pvp_provider' );
			$query->set( 'orderby', 'meta_value' );
		}

		if ( 'rsplr_playlist' === $orderby ) {
			$query->set( 'meta_key', '_pvp_playlist_id' );
			$query->set( 'orderby', 'meta_value' );
		}
	}
}
