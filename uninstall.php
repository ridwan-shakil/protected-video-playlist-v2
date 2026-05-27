<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'pvp_settings', array() );

if ( empty( $settings['delete_on_uninstall'] ) ) {
	return;
}

delete_option( 'pvp_settings' );
delete_option( 'pvp_transient_keys' );

$posts = get_posts(
	array(
		'post_type'      => 'pvp_video',
		'post_status'    => 'any',
		'numberposts'    => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	)
);

foreach ( $posts as $post_id ) {
	wp_delete_post( $post_id, true );
}
