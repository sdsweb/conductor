<?php
// TODO: Tidy up Comments and functions, Add all default widget query functions to this class
/**
 * Conductor Query - Standard class for querying content within Conductor.
 *
 * Developers should extend this class to create their own query functionality for add-ons.
 *
 * @class Conductor_Widget_Query
 * @author Slocum Studio
 * @version 1.5.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Widget_Query' ) ) {
	class Conductor_Widget_Query {
		/**
		 * @var string
		 */
		public $version = '1.5.0';

		/**
		 * @var WP_Widget, Conductor widget instance
		 */
		public $widget = false;

		/**
		 * @var array, WP_Widget Instance (widget settings)
		 */
		public $widget_instance = false;

		/**
		 * @var array
		 */
		public $query_args = array();

		/**
		 * @var mixed, Content query
		 */
		public $query = null;

		/**
		 * @var string, Query type (single or many)
		 */
		public $query_type = 'single';

		/**
		 * @var mixed, Instance of Query used for output
		 */
		public $output = null;

		/**
		 * @var WP_Post, Current post
		 */
		public $post = null;

		/**
		 * @var WP_Post, Global post
		 */
		public $global_post = null;

		/**
		 * @var array, List of actions/filters this class has added/created
		 */
		public $hooks = array();

		/**
		 * @var int
		 */
		public $display_content_args_count = 0;

		/**
		 * @var int
		 */
		public $skip_display_content_hooks = false;


		/**
		 * @var Conductor, Instance of the class
		 */
		protected static $_instance;

		/**
		 * Function used to create instance of class.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) )
				self::$_instance = new self();

			return self::$_instance;
		}


		/**
		 * This function sets up all of the actions and filters on instance. It also loads (includes)
		 * the required files and assets.
		 */
		function __construct( $args = array() ) {
			// Populate properties
			$keys = array_keys( get_class_vars( __CLASS__ ) );
			foreach ( $keys as $key ) {
				if ( isset( $args[$key] ) )
					$this->$key = $args[$key];
			}

			// TODO: which vars should the user not be able to customize?
			$this->query = null;

			// If we have a widget instance
			if ( ! empty ( $this->widget_instance ) ) {
				$this->query( $this->query_type ); // Query content piece(s)

				// If we're not skipping display content hooks
				if ( ! property_exists( $this, 'skip_display_content_hooks' ) || ! $this->skip_display_content_hooks ) {
					// Output Hooks
					$this->hooks['conductor_widget_display_content_' . $this->widget->number] = array();

					// Opening Wrapper Elements
					add_action( 'conductor_widget_display_content_' . $this->widget->number, array( $this, 'conductor_widget_wrapper' ), 1, $this->display_content_args_count );
					add_action( 'conductor_widget_display_content_' . $this->widget->number, array( $this, 'conductor_widget_content_wrapper' ), 2, $this->display_content_args_count );

					$this->hooks['conductor_widget_display_content_' . $this->widget->number] += array(
						1 => array( get_class(), 'conductor_widget_wrapper' ), // Static callback
						2 => array( get_class(), 'conductor_widget_content_wrapper' ) // Static callback
					);

					// Sortable Elements
					if ( isset( $this->widget_instance['output'] ) && ! empty( $this->widget_instance['output'] ) )
						// Loop through sortable elements
						foreach ( $this->widget_instance['output'] as $priority => $element ) {
							// Grab the output element callback
							$callback = $element['callback'];

							// Array callback
							// Only add this action if the callback exists, it's callable, and the element is visible
							if ( is_array( $callback ) && method_exists( $callback[0], $callback[1] ) && $element['visible'] ) {
								add_action( 'conductor_widget_display_content_' . $this->widget->number, array( $callback[0], $callback[1] ), $priority, $this->display_content_args_count );

								$this->hooks['conductor_widget_display_content_' . $this->widget->number] += array( $priority => array( $callback[0], $callback[1] ) );

								do_action( 'conductor_widget_query_add_display_content', $element, $priority, $this->widget->number, $this->display_content_args_count, $this );
								do_action( 'conductor_widget_query_add_display_content_' . $this->widget->number, $element, $priority, $this->widget->number, $this->display_content_args_count, $this );

							}
							// String/other callbacks within this class
							// Only add this action if the callback exists, it's callable, and the element is visible
							else if ( ! is_array( $callback ) && method_exists( $this, $callback ) && is_callable( array( $this, $callback ) ) && $element['visible'] ) {
								add_action( 'conductor_widget_display_content_' . $this->widget->number, array( $this, $callback ), $priority, $this->display_content_args_count );

								$this->hooks['conductor_widget_display_content_' . $this->widget->number] += array( $priority => array( get_class(), $callback ) );

								do_action( 'conductor_widget_query_add_display_content', $element, $priority, $this->widget->number, $this->display_content_args_count, $this );
								do_action( 'conductor_widget_query_add_display_content_' . $this->widget->number, $element, $priority, $this->widget->number, $this->display_content_args_count, $this );
							}

							// String/other callbacks outside of this class
							// Only add this action if the callback exists, it's callable, and the element is visible
							else if ( ! is_array( $callback ) && function_exists( $callback ) && is_callable( $callback ) && $element['visible'] ) {
								add_action( 'conductor_widget_display_content_' . $this->widget->number, $callback, $priority, $this->display_content_args_count );

								$this->hooks['conductor_widget_display_content_' . $this->widget->number] += array( $priority => $callback );

								do_action( 'conductor_widget_query_add_display_content', $element, $priority, $this->widget->number, $this->display_content_args_count, $this );
								do_action( 'conductor_widget_query_add_display_content_' . $this->widget->number, $element, $priority, $this->widget->number, $this->display_content_args_count, $this );
							}
						}

					// Closing Wrapper Elements
					add_action( 'conductor_widget_display_content_' . $this->widget->number, array( $this, 'conductor_widget_content_wrapper_close' ), 999, $this->display_content_args_count );
					add_action( 'conductor_widget_display_content_' . $this->widget->number, array( $this, 'conductor_widget_wrapper_close' ), 1000, $this->display_content_args_count );

					$this->hooks['conductor_widget_display_content_' . $this->widget->number] += array(
						999 => array( get_class(), 'conductor_widget_content_wrapper_close' ), // Static callback
						1000 => array( get_class(), 'conductor_widget_wrapper_close' ) // Static callback
					);

					// Sort the hooks by key
					ksort( $this->hooks['conductor_widget_display_content_' . $this->widget->number] );
				}
			}
		}

		/**
		 * This function is used to create a query and return results from that query.
		 */
		public function query( $query_type ) {
			die( 'function Conductor_Widget_Query::query() must be over-ridden in a sub-class.' );
		}

		/**
		 * This function is used to retrieve the current query arguments.
		 */
		public function get_query_args() {
			return $this->query_args;
		}

		/**
		 * This function is used to retrieve the current query.
		 */
		public function get_query() {
			return $this->query;
		}

		/**
		 * This function is used to determine if the current query has posts (uses query class have_posts() if exists).
		 */
		public function have_posts() {
			die( 'function Conductor_Widget_Query::have_posts() must be over-ridden in a sub-class.' );
		}

		/**
		 * This function is used to move to the next post within the current query (uses query class next_post() if exists).
		 */
		public function next_post() {
			die( 'function Conductor_Widget_Query::next_post() must be over-ridden in a sub-class.' );
		}

		/**
		 * This function is used to move to the next post within the current query (uses query class the_post() if exists
		 * and will also set global $post data).
		 */
		public function the_post() {
			die( 'function Conductor_Widget_Query::the_post() must be over-ridden in a sub-class.' );
		}

		/**
		 * This function is used to fetch the current post in the query.
		 */
		public function get_current_post( $single = false ) {
			die( 'function Conductor_Widget_Query::get_current_post() must be over-ridden in a sub-class.' );
		}

		/**
		 * TODO: Comment this function; also do we need this?
		 */
		public function current_post() {}

		/**
		 * This function resets the global $post variable using data stored on the class.
		 */
		public function reset_global_post() {
			global $post;

			// If we have a global $post reference
			if ( $this->global_post ) {
				// Reset/restore the global $post
				$post = $this->global_post;

				// Clear the global $post reference
				$this->global_post = null;
			}
		}

		/**
		 * This function determines if the current query has pagination.
		 */
		public function has_pagination() {
			return apply_filters( 'conductor_query_has_pagination', false, $this );
		}

		/**
		 * This function returns or will echo pagination for a query depending on parameters.
		 */
		public function get_pagination_links() {
			die( 'function Conductor_Widget_Query::get_pagination_links() must be over-ridden in a sub-class.' );
		}

		/**
		 * This function alters the number of "found_posts" on Conductor Widget queries only.
		 */
		//public function found_posts() {}

		/**
		 * This function returns CSS classes for use in this widget.
		 */
		public function get_wrapper_css_classes( $post, $instance, $widget ) {
			return $widget->get_css_classes( $instance );
		}

		/**
		 * This function returns the HTML element name used for the main wrapper elements.
		 */
		public function get_wrapper_html_element( $post, $instance, $widget, $query ) {
			return apply_filters( 'conductor_widget_wrapper_html_element', 'div', $post, $instance, $widget, $query, $this );
		}

		/**
		 * This function returns the HTML element name used for content wrapper elements.
		 */
		public function get_content_wrapper_html_element( $post, $instance, $widget, $query ) {
			return apply_filters( 'conductor_widget_content_wrapper_html_element', 'section', $post, $instance, $widget, $query, $this );
		}


		/************************
		 * Output Functionality *
		 ************************/

		/*
		 * These functions are used for fallback functionality and to give developers a general idea
		 * of what their custom query classes should check for and output.
		 */

		/**
		 * This function outputs the opening wrapper for Conductor Widgets.
		 */
		public function conductor_widget_wrapper( $post, $instance, $widget, $query ) {
		?>
			<<?php echo $this->get_wrapper_html_element( $post, $instance, $widget, $query  ); ?> class="<?php echo apply_filters( 'conductor_widget_wrapper_css_classes', $this->get_wrapper_css_classes( $post, $instance, $widget ), $post, $instance, $widget, $query ); ?>">
		<?php
			do_action( 'conductor_widget_output_before', $post, $instance );
		}

		/**
		 * This function outputs the opening content wrapper for Conductor Widgets.
		 */
		public function conductor_widget_content_wrapper( $post, $instance, $widget, $query ) {
		?>
			<<?php echo $this->get_content_wrapper_html_element( $post, $instance, $widget, $query ); ?> class="<?php echo apply_filters( 'conductor_widget_content_wrapper_css_classes', 'content post-content conductor-cf', $post, $instance, $widget, $query ); ?><?php echo ( has_post_thumbnail( $post->ID ) ) ? ' has-post-thumbnail content-has-post-thumbnail' : false; ?>">
		<?php
		}

		/**
		 * This function outputs the featured image for Conductor Widgets.
		 */
		public function conductor_widget_featured_image( $post, $instance, $widget, $query ) {
			//if( has_post_thumbnail( $post->ID ) ) : // Featured Image
				do_action( 'conductor_widget_featured_image_before', $post, $instance );
				do_action( 'conductor_widget_featured_image_after', $post, $instance );
			//endif;
		}

		/**
		 * This function outputs the post title for Conductor Widgets.
		 */
		public function conductor_widget_post_title( $post, $instance, $widget, $query ) {
			do_action( 'conductor_widget_post_title_before', $post, $instance );
			do_action( 'conductor_widget_post_title_after', $post, $instance );
		}

		/**
		 * This function outputs the author byline for Conductor Widgets.
		 */
		public function conductor_widget_author_byline( $post, $instance, $widget, $query ) {
			do_action( 'conductor_widget_author_byline_before', $post, $instance );
			do_action( 'conductor_widget_author_byline_after', $post, $instance );
		}

		/**
		 * This function outputs the post content for Conductor Widgets.
		 */
		public function conductor_widget_post_content( $post, $instance, $widget, $query ) {
			do_action( 'conductor_widget_post_content_before', $post, $instance );
			do_action( 'conductor_widget_post_content_after', $post, $instance );
		}

		/**
		 * This function outputs the read more link for Conductor Widgets.
		 */
		public function conductor_widget_read_more( $post, $instance, $widget, $query ) {
			do_action( 'conductor_widget_read_more_before', $post, $instance );
			do_action( 'conductor_widget_read_more_after', $post, $instance );
		}

		/**
		 * This function outputs the closing content wrapper for Conductor Widgets.
		 */
		public function conductor_widget_content_wrapper_close( $post, $instance, $widget, $query ) {
		?>
			</<?php echo $this->get_content_wrapper_html_element( $post, $instance, $widget, $query ); ?>>
		<?php
		}

		/**
		 * This function outputs the closing wrapper for Conductor Widgets.
		 */
		public function conductor_widget_wrapper_close( $post, $instance, $widget, $query ) {
			do_action( 'conductor_widget_output_after', $post, $instance );
		?>
			</<?php echo $this->get_wrapper_html_element( $post, $instance, $widget, $query ); ?>>
		<?php
		}
	}

	// TODO: Do we need to create an instance?
	/**
	 * Create an instance of the Conductor_Widget_Query class.
	 */
	/*function Conduct_Widget_Query() {
		return Conductor_Widget_Query::instance();
	}

	Conduct_Widget_Query(); // Conduct your content!*/
}