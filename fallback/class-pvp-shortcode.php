<?php
add_shortcode( 'protected_playlist', 'pvp_render_shortcode' );

function pvp_render_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'url'     => '',
			'columns' => 3,
			'cache'   => 3600,
		),
		$atts,
		'protected_playlist'
	);

	if ( function_exists( 'rsplr_renderer' ) ) {
		return rsplr_renderer()->url(
			sanitize_text_field( $atts['url'] ),
			array(
				'columns' => intval( $atts['columns'] ),
				'cache'   => intval( $atts['cache'] ),
			)
		);
	}

	return pvp_render_block(
		array(
			'url'     => sanitize_text_field( $atts['url'] ),
			'columns' => intval( $atts['columns'] ),
			'cache'   => intval( $atts['cache'] ),
		)
	);
}
