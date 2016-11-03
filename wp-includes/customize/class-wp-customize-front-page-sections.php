<?php
/**
 * Customize API: WP_Customize_Front_Page_Sections class.
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.x.x
 */

/**
 * Customizer class for implementing front page sections.
 *
 * @since 4.x.x
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Front_Page_Sections {
	/**
	 * Create the front page sections controller.
	 *
	 * @since 4.x.x
	 *
	 * @param WP_Customize_Manager $manager Manager instance.
	 */
	public function __construct( WP_Customize_Manager $manager ) {
		$this->manager = $manager;

		add_action( 'customize_register', array( $this, 'customize_register' ) );
		add_action( 'customize_preview_init', array( $this, 'enqueue_customize_preview_assets' ) );
		add_filter( 'customize_dynamic_partial_args', array( $this, 'customize_dynamic_partial_args' ), 10, 2 );

		add_action( 'loop_start', array( $this, 'loop_start' ) );
		add_action( 'loop_end', array( $this, 'loop_end' ) );
	}

	public function customize_register() {
		$support = get_theme_support( 'front-page-sections' );

		$this->manager->add_setting( 'front_page_sections', array(
			'type'              => 'option',
			'capability'        => 'manage_options',
			'sanitize_callback' => array( $this, 'sanitize_id_list' ),
		) );

		if ( isset( $support[0]['section_selector'] ) ) {
			$this->manager->get_setting( 'front_page_sections' )->transport = 'postMessage';
		}

		$this->manager->add_control( new WP_Customize_Post_Collection_Control( $this->manager, 'front_page_sections', array(
			'label'              => __( 'Front page sections' ),
			'description'        => '',
			'section'            => 'static_front_page',
			'settings'           => 'front_page_sections',
			'post_types'         => apply_filters( 'front_page_sections_post_types', array( 'page' ) ),
			'include_front_page' => true,
			'labels'             => array(
				'addPost'                => __( 'Add Section' ),
				'addPosts'               => __( 'Add Sections' ),
				'movedUp'                => __( 'Section moved up' ),
				'movedDown'              => __( 'Section moved down' ),
				'removePost'             => __( 'Remove Section' ),
				'searchPosts'            => __( 'Search Sections' ),
				'searchPostsPlaceholder' => __( 'Search sections&hellip;' ),
			),
		) ) );

		if ( isset( $support[0]['section_selector'] ) ) {
			$this->manager->selective_refresh->add_partial( 'front_page_sections', array(
				'selector'            => '#wp-front-page-sections',
				'settings'            => array( 'front_page_sections' ),
				'container_inclusive' => true,
				'type'                => 'front_page_sections',
				'section_selector'    => 'hey',
			) );
		}
	}

	public function enqueue_customize_preview_assets() {
		wp_enqueue_script(
			'fps-customize-preview',
			plugin_dir_url( dirname( __FILE__ ) ) . 'js/customize-preview.js',
			array( 'customize-preview', 'customize-selective-refresh' )
		);

		$support = get_theme_support( 'front-page-sections' );
		wp_localize_script( 'fps-customize-preview', '_frontPageSectionsSettings', array(
			'section_selector' => $support[0]['section_selector'],
		) );
	}

	public function customize_dynamic_partial_args( $partial_args, $partial_id ) {
		if ( preg_match( '/^front_page_section\[\d+\]$/', $partial_id ) ) {
			if ( false === $partial_args ) {
				$partial_args = array();
			}

			$partial_args = array_merge( $partial_args, array(
				'type'                => 'front_page_section',
				'render_callback'     => function( WP_Customize_Partial $partial, $context = array() ) {
					global $post;

					$support = get_theme_support( 'front-page-sections' );
					if ( empty( $support[0]['section_render_callback'] ) || ! is_callable( $support[0]['section_render_callback'] ) ) {
						return false;
					}

					preg_match( '/^front_page_section\[(?P<post_id>-?\d+)\]$/', $partial->id, $matches );

					$post = get_post( $matches['post_id'] );
					setup_postdata( $post );

					return call_user_func( $support[0]['section_render_callback'], $partial, $context );
				},
				'container_inclusive' => true,
				'settings'            => array(),
				'capability'          => 'edit_theme_options',
			) );
		}

		return $partial_args;
	}

	public function loop_start( $wp_query ) {
		if ( $wp_query->is_front_page_with_sections && is_customize_preview() ) {
			echo '<div id="wp-front-page-sections">';
		}
	}

	public function loop_end( $wp_query ) {
		if ( $wp_query->is_front_page_with_sections && is_customize_preview() ) {
			echo '</div>';
		}
	}

	/**
	 * Sanitization callback for lists of IDs.
	 *
	 * @since 4.x.x
	 *
	 * @param string $value Setting value.
	 * @return string Comma-separated list of IDs.
	 */
	public function sanitize_id_list( $value ) {
		return implode( ',', array_unique( array_filter( wp_parse_id_list( $value ) ) ) );
	}
}
