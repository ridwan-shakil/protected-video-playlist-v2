<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete options
delete_option( 'pvp_settings' );

// Delete all video posts
$posts = get_posts(
	array(
		'post_type'   => 'pvp_video',
		'numberposts' => -1,
	)
);

foreach ( $posts as $post ) {
	wp_delete_post( $post->ID, true );
}
