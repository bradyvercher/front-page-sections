<?php
/**
 * Filter front page section permalinks to point them to the anchor on the front
 * page.
 *
 * Replaces slashes with dots in the page URI and prepends the URL path with a
 * '#' symbol.
 *
 * Replicates some of the functionality in get_page_link() and _get_page_link().
 *
 * This will be less verbose in core.
 */
function fps_page_link( $link, $post_id, $sample ) {
	global $wp_rewrite;

	if ( 'page' == get_option( 'show_on_front' ) && $post_id == get_option( 'page_on_front' ) ) {
		return $link;
	}

	if ( ! is_front_page_section( $post_id ) ) {
		return $link;
	}

	$post = get_post( $post_id );
	$draft_or_pending = in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) );

	$token = $wp_rewrite->get_page_permastruct();
	if ( empty( $token ) ) {
		$token = '%pagename%';
	}

	if ( isset( $post->post_status ) && ! $draft_or_pending ) {
		$token = str_replace( '%pagename%', get_page_uri( $post_id ), $token );
		$link = home_url( '#' . str_replace( '/', '.', $token ) );
	}

	return $link;
}
add_filter( 'page_link', 'fps_page_link', 10, 3 );
