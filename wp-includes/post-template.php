<?php
function fps_post_class( $classes, $class, $post_id ) {
	// Front page sections.
	if ( is_front_page() && is_front_page_section( $post_id ) ) {
		$classes[] = 'front-page-section';
	}

	return $classes;
}
add_filter( 'post_class', 'fps_post_class', 10, 3 );

/**
 * Whether a post is a front page section.
 *
 * @since 4.7.0
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function is_front_page_section( $post_id ) {
	$section_ids = array_filter( wp_parse_id_list( get_option( 'front_page_sections' ) ) );
	return in_array( intval( $post_id ), $section_ids, true );
}
