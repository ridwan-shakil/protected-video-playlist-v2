<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// URL / ID HELPERS
// ─────────────────────────────────────────────────────────────────────────────
function pvp_is_playlist_url( $url ) {
	return (bool) preg_match( '/[?&]list=[A-Za-z0-9_-]{10,}/', $url );
}

function pvp_extract_playlist_id( $url ) {
	$url = trim( $url );

	if ( preg_match( '/^[A-Za-z0-9_-]{10,64}$/', $url ) ) {
		return $url;
	}

	if ( preg_match( '/[?&]list=([A-Za-z0-9_-]{10,64})/', $url, $matches ) ) {
		return $matches[1];
	}

	return null;
}

function pvp_extract_youtube_id( $url ) {
	$url = trim( $url );

	if ( preg_match( '/^[A-Za-z0-9_-]{6,64}$/', $url ) ) {
		return $url;
	}

	if (
		preg_match(
			'~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:embed/|v/|shorts/|live/)|[?&]v=)([A-Za-z0-9_-]{6,64})~',
			$url,
			$matches
		)
	) {
		return $matches[1];
	}

	return null;
}


if ( ! function_exists( 'pvp_sanitize_color' ) ) {
	function pvp_sanitize_color( $color ) {
		if ( empty( $color ) ) {
			return '';
		}

		$color = trim( $color );

		// #rrggbb
		if ( preg_match( '/^#[a-fA-F0-9]{6}$/', $color ) ) {
			return $color;
		}

		// #rrggbbaa
		if ( preg_match( '/^#[a-fA-F0-9]{8}$/', $color ) ) {
			return $color;
		}

		// rgb() or rgba()
		if ( preg_match(
			'/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(\s*,\s*(0|1|0?\.\d+))?\s*\)$/',
			$color
		) ) {
			return $color;
		}

		return '';
	}
}
