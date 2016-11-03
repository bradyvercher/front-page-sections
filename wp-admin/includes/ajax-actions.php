<?php
/**
 * Ajax handler for querying posts for the Find Posts modal.
 *
 * @see window.findPosts
 */
function fps_wp_ajax_find_posts() {
	check_ajax_referer( 'find-posts' );

	$post_types = array();
	if ( empty( $_POST['post_types'] ) ) {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		unset( $post_types['attachment'] );
	} else {
		$post_type_names = array_map( 'sanitize_text_field', wp_unslash( $_POST['post_types'] ) );
		foreach ( $post_type_names as $post_type ) {
			$post_types[ $post_type ] = get_post_type_object( $post_type );
		}
	}

	$args = array(
		'post_type'      => array_keys( $post_types ),
		'post_status'    => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'any',
		'posts_per_page' => 50,
	);

	if ( ! empty( $_POST['ps'] ) ) {
		$args['s'] = wp_unslash( $_POST['ps'] );
	}

	$posts = get_posts( $args );

	if ( ! $posts ) {
		wp_send_json_error( __( 'No items found.' ) );
	}

	if ( isset( $_POST['format'] ) && 'json' === $_POST['format'] ) {
		foreach ( $posts as $post ) {
			$data[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
				'type'  => $post_types[ $post->post_type ]->labels->singular_name,
			);
		}

		wp_send_json_success( $data );
	}
}
add_action( 'wp_ajax_fps_find_posts', 'fps_wp_ajax_find_posts' );
