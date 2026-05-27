<?php
/**
 * RS SecurePlayer shortcode registration.
 *
 * @package RSPLR\Shortcode
 */

namespace RSPLR\Shortcode;

use RSPLR\Rendering\Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ShortcodeRegistrar {
	/**
	 * Renderer.
	 *
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param Renderer $renderer Renderer.
	 */
	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Register shortcodes.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'rsplr_video', array( $this, 'video' ) );
		add_shortcode( 'rsplr_playlist', array( $this, 'playlist' ) );
		add_shortcode( 'rsplr_campaign', array( $this, 'campaign' ) );
	}

	/**
	 * Render single video shortcode.
	 *
	 * @param array<int|string, mixed>|string $atts Attributes.
	 * @return string
	 */
	public function video( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'     => 0,
				'layout' => 'minimal',
			),
			is_array( $atts ) ? $atts : array(),
			'rsplr_video'
		);

		return $this->renderer->video( absint( $atts['id'] ), $atts );
	}

	/**
	 * Render playlist shortcode.
	 *
	 * @param array<int|string, mixed>|string $atts Attributes.
	 * @return string
	 */
	public function playlist( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'      => 0,
				'layout'  => 'grid',
				'columns' => 3,
			),
			is_array( $atts ) ? $atts : array(),
			'rsplr_playlist'
		);

		return $this->renderer->playlist( absint( $atts['id'] ), $atts );
	}

	/**
	 * Render campaign shortcode.
	 *
	 * @param array<int|string, mixed>|string $atts Attributes.
	 * @return string
	 */
	public function campaign( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => 0,
			),
			is_array( $atts ) ? $atts : array(),
			'rsplr_campaign'
		);

		return $this->renderer->campaign( absint( $atts['id'] ), $atts );
	}
}
