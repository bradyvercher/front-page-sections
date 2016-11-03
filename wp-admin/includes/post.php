<?php
/**
 * Replace slashes with dots and append a dot to the end of a page URI if it
 * exists.
 *
 * This will be less verbose in core.
 */
function fps_get_sample_permalink( $permalink, $post_id, $title, $name, $post ) {
	global $wp_rewrite;

	if ( ! is_front_page_section( $post_id ) || 'page' !== $post->post_type ) {
		return $permalink;
	}

	$uri = get_page_uri( $post_id );
	if ( $uri ) {
		$uri = untrailingslashit( $uri );
		$uri = strrev( stristr( strrev( $uri ), '/' ) );
		$uri = untrailingslashit( $uri );
	}

	$uri = apply_filters( 'editable_slug', $uri, $post );
	if ( ! empty( $uri ) ) {
		$uri .= '.';
		$uri = str_replace( '/', '.', $uri );
	}

	$token = $wp_rewrite->get_page_permastruct();
	if ( empty( $token ) ) {
		$token = '%pagename%';
	}
	$permalink[0] = home_url( '#' . $uri . $token );

	return $permalink;
}
add_filter( 'get_sample_permalink', 'fps_get_sample_permalink', 10, 5 );
