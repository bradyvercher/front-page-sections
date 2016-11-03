<?php
function fps_twentysixteen_after_setup_theme() {
	if ( 'twentysixteen' !== get_template() ) {
		return;
	}

	add_theme_support( 'front-page-sections', array(
		'section_selector' => '#post-%d',
	) );
}
add_action( 'after_setup_theme', 'fps_twentysixteen_after_setup_theme' );
