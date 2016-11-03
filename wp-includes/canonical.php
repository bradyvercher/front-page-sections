<?php
/**
 * Redirect front page section permalinks to the anchor on the front page.
 *
 * @since 4.7.0
 */
function wp_redirect_front_page_sections() {
	$object_id = get_queried_object_id();

	if (
		   is_front_page()
		|| is_home()
		|| ! is_singular()
		|| ! is_front_page_section( $object_id )
	) {
		return;
	}

	wp_redirect( get_permalink( $object_id ) );
	exit;
}
add_action( 'template_redirect', 'wp_redirect_front_page_sections' );
