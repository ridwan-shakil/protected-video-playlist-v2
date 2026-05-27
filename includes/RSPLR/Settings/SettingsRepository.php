<?php
/**
 * Settings access with legacy compatibility.
 *
 * @package RSPLR\Settings
 */

namespace RSPLR\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SettingsRepository {
	/**
	 * Current legacy option key.
	 *
	 * @var string
	 */
	private const LEGACY_OPTION = 'pvp_settings';

	/**
	 * Future RS option key.
	 *
	 * @var string
	 */
	private const OPTION = 'rsplr_settings';

	/**
	 * Return merged settings.
	 *
	 * Existing pvp_settings remains the source of truth during Phase 1.
	 *
	 * @return array<string, mixed>
	 */
	public function all() {
		$legacy = get_option( self::LEGACY_OPTION, array() );
		$modern = get_option( self::OPTION, array() );

		$legacy = is_array( $legacy ) ? $legacy : array();
		$modern = is_array( $modern ) ? $modern : array();

		return array_merge( $legacy, $modern );
	}

	/**
	 * Read one setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$settings = $this->all();

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	/**
	 * Read a boolean-like setting.
	 *
	 * @param string $key Setting key.
	 * @param bool   $default Default value.
	 * @return bool
	 */
	public function bool( $key, $default = false ) {
		$value = $this->get( $key, $default ? 1 : 0 );

		return ! empty( $value );
	}

	/**
	 * Read an integer setting.
	 *
	 * @param string $key Setting key.
	 * @param int    $default Default value.
	 * @return int
	 */
	public function int( $key, $default = 0 ) {
		return intval( $this->get( $key, $default ) );
	}
}
