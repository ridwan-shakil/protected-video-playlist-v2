<?php
/**
 * Minimal RS SecurePlayer class autoloader.
 *
 * @package RSPLR
 */

namespace RSPLR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Autoloader {
	/**
	 * Root namespace prefix.
	 *
	 * @var string
	 */
	private const PREFIX = 'RSPLR\\';

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Load a class under the RSPLR namespace.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public static function autoload( $class ) {
		if ( 0 !== strpos( $class, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class, strlen( self::PREFIX ) );
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
		$file     = RSPLR_PLUGIN_DIR . 'includes/RSPLR/' . $relative . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
