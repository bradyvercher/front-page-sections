<?php
function fps_display_post_states( $post_states, $post ) {
	if ( 'page' === get_option( 'show_on_front' ) ) {
		if ( intval( get_option( 'page_on_front' ) ) === $post->ID ) {
			$post_states['page_on_front'] = __( 'Front Page' );
		} elseif ( in_array( $post->ID, $front_page_sections = array_filter( wp_parse_id_list( get_option( 'front_page_sections' ) ) ), true ) ) {
			$post_states['front_page_section'] = __( 'Front Page Section' );
		}

		if ( intval( get_option( 'page_for_posts' ) ) === $post->ID ) {
			$post_states['page_for_posts'] = __( 'Posts Page' );
		}
	}

	return $post_states;
}
add_filter( 'display_post_states', 'fps_display_post_states', 10, 2 );
