<?php
/**
 * Core RS SecurePlayer bootstrap.
 *
 * @package RSPLR\Core
 */

namespace RSPLR\Core;

use RSPLR\Settings\SettingsRepository;
use RSPLR\Repository\VideoRepository;
use RSPLR\Admin\VideoLibraryColumns;

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
			( new VideoLibraryColumns() )->register();
		}

		if ( ! is_admin() ) {
			require_once RSPLR_PLUGIN_DIR . 'includes/public/class-pvp-frontend.php';
			require_once RSPLR_PLUGIN_DIR . 'fallback/class-pvp-shortcode.php';
		}
	}
}
