<?php
/**
 * Customize API: WP_Customize_Post_Collection_Control class
 *
 * @package WordPress
 * @subpackage Customize
 * @since 4.x.x
 */

/**
 * Customize Post Collection Control class.
 *
 * @since 4.x.x
 *
 * @see WP_Customize_Control
 */
class WP_Customize_Post_Collection_Control extends WP_Customize_Control {
	/**
	 * Control type.
	 *
	 * @since 4.x.x
	 * @var string
	 */
	public $type = 'post_collection';

	/**
	 * Post types that can be added as sections.
	 *
	 * @since 4.x.x
	 * @var array
	 */
	public $post_types = array( 'page', 'post' );

	/**
	 * Whether to include the front page in the post collection.
	 *
	 * @since 4.x.x
	 * @var bool
	 */
	public $include_front_page = false;

	/**
	 * Labels.
	 *
	 * @since 4.x.x
	 * @access public
	 * @var array
	 */
	public $labels = array();

	/**
	 * Constructor.
	 *
	 * @since 4.x.x
	 *
	 * @param WP_Customize_Manager $manager Customizer bootstrap instance.
	 * @param string               $id      Control ID.
	 * @param array                $args    Optional. Arguments to override class property defaults.
	 */
	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );

		$this->labels = wp_parse_args( $this->labels, array(
			'addPost'                => __( 'Add Post' ),
			'addPosts'               => __( 'Add Posts' ),
			'clearResults'           => __( 'Clear Results' ),
			'moveUp'                 => __( 'Move up' ),
			'moveDown'               => __( 'Move down' ),
			'movedUp'                => __( 'Post moved up' ),
			'movedDown'              => __( 'Post moved down' ),
			'removePost'             => __( 'Remove Post' ),
			'searchPosts'            => __( 'Search Posts' ),
			'searchPostsPlaceholder' => __( 'Search posts&hellip;' ),
		) );
	}

	/**
	 * Enqueue control related scripts/styles.
	 *
	 * @since 4.x.x
	 */
	public function enqueue() {
		wp_enqueue_style( 'customize-post-collection' );
		wp_enqueue_script( 'customize-post-collection' );

		add_action( 'customize_controls_print_footer_scripts', array( 'WP_Customize_Post_Collection_Control', 'print_templates' ) );
	}

	/**
	 * Refresh the parameters passed to the JavaScript via JSON.
	 *
	 * @since 4.x.x
	 * @uses WP_Customize_Control::to_json()
	 */
	public function to_json() {
		parent::to_json();

		$this->json['labels']            = $this->labels;
		$this->json['posts']            = $this->get_posts();
		$this->json['postTypes']        = $this->post_types;
		$this->json['includeFrontPage'] = $this->include_front_page;
		$this->json['searchNonce']      = wp_create_nonce( 'find-posts' );
	}

	/**
	 * Don't render any content for this control from PHP.
	 *
	 * @since 4.x.x
	 *
	 * @see WP_Customize_Post_Collection_Control::content_template()
	 */
	public function render_content() {}

	/**
	 * An Underscore (JS) template for this control's content (but not its container).
	 *
	 * @see WP_Customize_Control::print_template()
	 *
	 * @since 4.x.x
	 */
	protected function content_template() {
		?>
		<label>
			<# if ( data.label ) { #>
				<span class="customize-control-title">{{ data.label }}</span>
			<# } #>
			<# if ( data.description ) { #>
				<span class="description customize-control-description">{{{ data.description }}}</span>
			<# } #>
		</label>
		<?php
	}

	/**
	 * Print JavaScript templates in the Customizer footer.
	 *
	 * @since 4.x.x
	 */
	public static function print_templates() {
		?>
		<script type="text/html" id="tmpl-wp-item">
			<div class="wp-item-header">
				<h4 class="wp-item-title"><span>{{ data.title }}</span></h4>

				<# if ( data.showDeleteButton ) { #>
					<button type="button" class="wp-item-delete button-link">
						<span class="screen-reader-text">{{ data.labels.removePost }}</span>
					</button>
				<# } #>

				<div class="wp-reorder-nav is-active">
					<button class="move-item-down" tabindex="0">{{ data.labels.moveDown }}</button>
					<button class="move-item-up" tabindex="0">{{ data.labels.moveUp }}</button>
				</div>
			</div>
		</script>

		<script type="text/html" id="tmpl-customize-drawer-title">
			<button type="button" class="customize-section-back" tabindex="-1">
				<span class="screen-reader-text"><?php _e( 'Back' ); ?></span>
			</button>
			<h3>
				<span class="customize-action">
					<?php
					/* translators: &#9656; is the unicode right-pointing triangle, and %s is the control label in the Customizer */
					printf( __( 'Customizing &#9656; %s' ), '{{ data.customizeAction }}' );
					?>
				</span>
				{{ data.title }}
			</h3>
		</script>

		<script type="text/html" id="tmpl-search-group">
			<label class="screen-reader-text" for="search-group-field">{{ data.labels.searchPosts }}</label>
			<input type="text" id="search-group-field" placeholder="{{{ data.labels.searchPostsPlaceholder }}}" class="search-group-field">
			<div class="search-icon" aria-hidden="true"></div>
			<button type="button" class="clear-results"><span class="screen-reader-text">{{ data.labels.clearResults }}</span></button>
		</script>

		<script type="text/html" id="tmpl-search-result">
			<span class="search-results-item-type">{{ data.type }}</span>
			<span class="search-results-item-title">{{ data.title }}</span>

			<button type="button" class="search-results-item-add button-link">
				<span class="screen-reader-text">{{ data.labels.addPost }}</span>
			</button>
		</script>
		<?php
	}

	/**
	 * Retrieve posts.
	 *
	 * @since 4.x.x
	 *
	 * @return array
	 */
	protected function get_posts() {
		$data     = array();
		$value    = $this->value();
		$post_ids = array_filter( array_map( 'absint', explode( ',', $value ) ) );

		if ( $this->include_front_page ) {
			$front_page = get_option( 'page_on_front' );
			if ( ! in_array( $front_page, $post_ids ) ) {
				array_unshift( $post_ids, $front_page );
			}
		}

		if ( ! empty( $post_ids ) ) {
			$posts = get_posts( array(
				'post_type'      => $this->post_types,
				'post_status'    => 'any',
				'post__in'       => $post_ids,
				'orderby'        => 'post__in',
				'posts_per_page' => 20,
			) );
		}

		if ( ! empty( $posts ) ) {
			$i = 0;
			foreach ( $posts as $post ) {
				$data[] = array(
					'id'    => $post->ID,
					'title' => $post->post_title,
					'order' => ++$i,
				);
			}
		}

		return $data;
	}
}
