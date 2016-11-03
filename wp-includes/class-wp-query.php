<?php
function fps_parse_query( $wp_query ) {
	$wp_query->is_front_page_with_sections = false;
	$qv = &$wp_query->query_vars;

	// Add section pages, if they exist.
	if (
		   $wp_query->is_main_query()
		&& 'page' == get_option( 'show_on_front' )
		&& get_option( 'page_on_front' )
		&& ! empty( $qv['page_id'] )
		&& get_option( 'page_on_front' ) == $qv['page_id']
	) {
		$front_page_sections = array_filter( wp_parse_id_list( get_option( 'front_page_sections' ) ) );
		if ( $front_page_sections ) {
			$wp_query->is_front_page_with_sections = true;
			if ( ! in_array( $qv['page_id'], $front_page_sections ) ) {
				array_unshift( $front_page_sections, $qv['page_id'] );
			}

			$qv['post__in'] = $front_page_sections;
			$qv['orderby'] = 'post__in';
		}
	}
}
add_action( 'parse_query', 'fps_parse_query' );

function fps_posts_where( $where, $wp_query ) {
	global $wpdb;

	if ( $wp_query->is_front_page_with_sections ) {
		$wp_query->query_vars['p'] = 0;

		// Remove the clause restricting the results to the front page ID.
		$where = str_replace( "AND {$wpdb->posts}.ID = " . $wp_query->query_vars['page_id'], '', $where );

		$post__in = implode( ',', array_map( 'absint', $wp_query->query_vars['post__in'] ) );
		$where .= " AND {$wpdb->posts}.ID IN ($post__in)";
	}

	return $where;
}
add_filter( 'posts_where', 'fps_posts_where', 10, 2 );

function fps_the_post( $post, $wp_query ) {
	if ( ! $wp_query->is_main_query() || ! $wp_query->is_front_page_with_sections ) {
		return;
	}

	echo '<a id="' . str_replace( '/', '.', get_page_uri( $post->ID ) ) . '"></a>';
}
add_action( 'the_post', 'fps_the_post', 10, 2 );

/**
 * Reset the global post to the front page.
 *
 * This will go in WP_Query::get_posts(), but has to be run after the last hook.
 */
function fps_wp( $wp ) {
	global $wp_query;

	if ( $wp_query->is_front_page_with_sections ) {
		$wp_query->post = reset( $wp_query->posts );
		do {
			if ( $wp_query->post && $wp_query->post->ID == get_option( 'page_on_front' ) ) {
				reset( $wp_query->posts );
				break;
			}
		} while ( $wp_query->post = next( $wp_query->posts ) );

		if ( ! $wp_query->post ) {
			$wp_query->post = reset( $wp_query->posts );
		}
	}

	$wp->register_globals();
}
add_action( 'wp', 'fps_wp' );
