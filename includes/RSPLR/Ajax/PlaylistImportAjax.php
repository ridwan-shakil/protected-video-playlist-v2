<?php
/**
 * Playlist import AJAX endpoints.
 *
 * @package RSPLR\Ajax
 */

namespace RSPLR\Ajax;

use RSPLR\Import\PlaylistImporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PlaylistImportAjax {
	/**
	 * Importer.
	 *
	 * @var PlaylistImporter
	 */
	private $importer;

	/**
	 * Constructor.
	 *
	 * @param PlaylistImporter $importer Importer.
	 */
	public function __construct( PlaylistImporter $importer ) {
		$this->importer = $importer;
	}

	/**
	 * Register AJAX actions.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_rsplr_start_playlist_import', array( $this, 'start' ) );
		add_action( 'wp_ajax_rsplr_continue_playlist_import', array( $this, 'continue_import' ) );
		add_action( 'wp_ajax_rsplr_get_import_status', array( $this, 'status' ) );
	}

	/**
	 * Start import.
	 *
	 * @return void
	 */
	public function start() {
		$this->guard();

		$name = isset( $_POST['playlist_name'] ) ? sanitize_text_field( wp_unslash( $_POST['playlist_name'] ) ) : '';
		$url  = isset( $_POST['playlist_url'] ) ? esc_url_raw( wp_unslash( $_POST['playlist_url'] ) ) : '';

		$result = $this->importer->start( $name, $url );
		$this->send_result( $result );
	}

	/**
	 * Continue import.
	 *
	 * @return void
	 */
	public function continue_import() {
		$this->guard();

		$playlist_id = isset( $_POST['playlist_post_id'] ) ? absint( wp_unslash( $_POST['playlist_post_id'] ) ) : 0;
		$result      = $this->importer->continue_import( $playlist_id );

		$this->send_result( $result );
	}

	/**
	 * Return status.
	 *
	 * @return void
	 */
	public function status() {
		$this->guard();

		$playlist_id = isset( $_POST['playlist_post_id'] ) ? absint( wp_unslash( $_POST['playlist_post_id'] ) ) : 0;
		$result      = $this->importer->status( $playlist_id );

		$this->send_result( $result );
	}

	/**
	 * Shared nonce/capability guard.
	 *
	 * @return void
	 */
	private function guard() {
		check_ajax_referer( 'rsplr_playlist_import', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'protected-video-playlist' ) ), 403 );
		}
	}

	/**
	 * Send AJAX response.
	 *
	 * @param array<string, mixed>|\WP_Error $result Result.
	 * @return void
	 */
	private function send_result( $result ) {
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}
}
