<?php
/**
 * Plugin Name: Front Page Sections
 * Plugin URI:  https://github.com/bradyvercher/front-page-sections
 * Description: A prototype for exploring a concept for multiple sections on the front page.
 * Version:     0.1.0
 * Author:      Contributors
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

include( dirname( __FILE__ ) . '/wp-admin/includes/ajax-actions.php' );
include( dirname( __FILE__ ) . '/wp-admin/includes/post.php' );
include( dirname( __FILE__ ) . '/wp-admin/includes/template.php' );

include( dirname( __FILE__ ) . '/wp-includes/canonical.php' );
include( dirname( __FILE__ ) . '/wp-includes/class-wp-customize-manager.php' );
include( dirname( __FILE__ ) . '/wp-includes/class-wp-query.php' );
include( dirname( __FILE__ ) . '/wp-includes/link-template.php' );
include( dirname( __FILE__ ) . '/wp-includes/post-template.php' );

include( dirname( __FILE__ ) . '/theme-compat/twentysixteen.php' );
include( dirname( __FILE__ ) . '/theme-compat/twentyseventeen.php' );

function fps_customize_register( $wp_customize ) {
	include( dirname( __FILE__ ) . '/wp-includes/customize/class-wp-customize-front-page-sections.php' );
	include( dirname( __FILE__ ) . '/wp-includes/customize/class-wp-customize-post-collection-control.php' );

	new WP_Customize_Front_Page_Sections( $wp_customize );
	$wp_customize->register_control_type( 'WP_Customize_Post_Collection_Control' );
}
add_action( 'customize_register', 'fps_customize_register', 1 );

function fps_enqueue_customizer_assets() {
	wp_enqueue_script(
		'fps-customize-post-collection',
		plugin_dir_url( __FILE__ ) . 'wp-admin/js/customize-post-collection.js',
		array( 'customize-controls', 'jquery', 'jquery-ui-sortable', 'jquery-ui-droppable', 'wp-backbone', 'wp-util' )
	);

	wp_enqueue_style(
		'fps-customize-controls',
		plugin_dir_url( __FILE__ ) . 'wp-admin/css/customize-controls.css'
	);
}
add_action( 'customize_controls_enqueue_scripts', 'fps_enqueue_customizer_assets' );

/**
 * Disable comments for front page sections.
 *
 * @since 4.x.x
 *
 * @param bool $open    Whether comments are open.
 * @param int  $post_id Post ID.
 * @return bool
 */
function fps_comments_open( $open, $post_id ) {
	if ( is_front_page_section( $post_id ) ) {
		$open = false;
	}

	return $open;
}
add_filter( 'comments_open', 'fps_comments_open', 10, 2 );
