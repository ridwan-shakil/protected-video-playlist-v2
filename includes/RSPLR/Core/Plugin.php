<?php
/**
 * Core RS SecurePlayer bootstrap.
 *
 * @package RSPLR\Core
 */

namespace RSPLR\Core;

use RSPLR\Settings\SettingsRepository;
use RSPLR\Repository\VideoRepository;
use RSPLR\Repository\PlaylistRepository;
use RSPLR\Admin\VideoLibraryColumns;
use RSPLR\Admin\PlaylistImportsPage;
use RSPLR\Ajax\PlaylistImportAjax;
use RSPLR\CPT\PlaylistPostType;
use RSPLR\Import\PlaylistImporter;
use RSPLR\Import\YouTubeClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Plugin {
	/**
	 * Whether legacy files have been loaded.
	 *
	 * @var bool
	 */
	private $initialized = false;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository|null
	 */
	private $settings = null;

	/**
	 * Video repository.
	 *
	 * @var VideoRepository|null
	 */
	private $videos = null;

	/**
	 * Playlist repository.
	 *
	 * @var PlaylistRepository|null
	 */
	private $playlists = null;

	/**
	 * Playlist importer.
	 *
	 * @var PlaylistImporter|null
	 */
	private $playlist_importer = null;

	/**
	 * Initialize the current compatibility layer.
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->initialized ) {
			return;
		}

		$this->initialized = true;
		$this->load_legacy_files();
		$this->register_rs_components();
	}

	/**
	 * Get global settings through the compatibility repository.
	 *
	 * @return SettingsRepository
	 */
	public function settings() {
		if ( null === $this->settings ) {
			$this->settings = new SettingsRepository();
		}

		return $this->settings;
	}

	/**
	 * Get video library repository.
	 *
	 * @return VideoRepository
	 */
	public function videos() {
		if ( null === $this->videos ) {
			$this->videos = new VideoRepository();
		}

		return $this->videos;
	}

	/**
	 * Get playlist repository.
	 *
	 * @return PlaylistRepository
	 */
	public function playlists() {
		if ( null === $this->playlists ) {
			$this->playlists = new PlaylistRepository();
		}

		return $this->playlists;
	}

	/**
	 * Get playlist importer.
	 *
	 * @return PlaylistImporter
	 */
	public function playlist_importer() {
		if ( null === $this->playlist_importer ) {
			$this->playlist_importer = new PlaylistImporter(
				$this->playlists(),
				$this->videos(),
				new YouTubeClient( $this->settings() )
			);
		}

		return $this->playlist_importer;
	}

	/**
	 * Load existing procedural files in their original order.
	 *
	 * @return void
	 */
	private function load_legacy_files() {
		require_once RSPLR_PLUGIN_DIR . 'fallback/class-pvp-functions.php';
		require_once RSPLR_PLUGIN_DIR . 'includes/core/class-pvp-cpt.php';
		require_once RSPLR_PLUGIN_DIR . 'includes/core/class-pvp-sync.php';
		require_once RSPLR_PLUGIN_DIR . 'includes/api/class-pvp-youtube.php';
		require_once RSPLR_PLUGIN_DIR . 'includes/core/class-pvp-render.php';
		require_once RSPLR_PLUGIN_DIR . 'includes/core/class-pvp-block.php';

		if ( is_admin() ) {
			require_once RSPLR_PLUGIN_DIR . 'admin/class-pvp-admin.php';
		}

		if ( ! is_admin() ) {
			require_once RSPLR_PLUGIN_DIR . 'includes/public/class-pvp-frontend.php';
			require_once RSPLR_PLUGIN_DIR . 'fallback/class-pvp-shortcode.php';
		}
	}

	/**
	 * Register new RS SecurePlayer components.
	 *
	 * @return void
	 */
	private function register_rs_components() {
		( new PlaylistPostType() )->register();
		( new PlaylistImportAjax( $this->playlist_importer() ) )->register();

		if ( is_admin() ) {
			( new VideoLibraryColumns() )->register();
			( new PlaylistImportsPage( $this->playlists() ) )->register();
		}
	}
}
