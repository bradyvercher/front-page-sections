<?php
add_filter( 'twentyseventeen_front_page_sections', '__return_zero' );

function fps_twentyseventeen_after_setup_theme() {
	if ( 'twentyseventeen' !== get_template() ) {
		return;
	}

	add_theme_support( 'front-page-sections', array(
		'section_render_callback' => 'fps_twentyseventeen_render_front_page_section',
		'section_selector'        => '#post-%d',
	) );
}
add_action( 'after_setup_theme', 'fps_twentyseventeen_after_setup_theme' );

function fps_twentyseventeen_render_front_page_section() {
	get_template_part( 'template-parts/page/content', 'front-page' );
}
