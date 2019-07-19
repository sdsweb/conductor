<?php
// TODO: Tidy up Comments and functions
/**
 * Conductor Widget Default Query - Default class used for querying content within Conductor.
 *
 * @class Conductor_Widget_Default_Query
 * @author Slocum Studio
 * @version 1.5.4
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Widget_Default_Query' ) ) {
	class Conductor_Widget_Default_Query extends Conductor_Widget_Query {
		/**
		 * @var string
		 */
		public $version = '1.5.4';

		/**
		 * @var WP_Widget, Conductor Widget
		 */
		public $widget = false;

		/**
		 * @var array, WP_Widget Instance (widget settings)
		 */
		public $widget_instance = false;

		/**
		 * @var mixed, Content query
		 */
		public $query = null;

		/**
		 * @var string, Query type (single or many)
		 */
		public $query_type = 'single';

		/**
		 * @var string, Post Not In (current query)
		 */
		public static $query_post__not_in = array();

		/**
		 * @var mixed, Instance of Query used for output
		 */
		public $output = null;

		/**
		 * @var WP_Post, Current post
		 */
		public $post = null;


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
			// Parent Constructor
			parent::__construct( $args );

			// Hooks (get_class() refers to this class, static methods)
			add_filter( 'found_posts', array( get_class(), 'found_posts' ), 10, 2 ); // Filter the found posts

			return $this;
		}

		/**
		 * This function is used to create a query and return results from that query.
		 * @uses WP_Query
		 */
		public function query( $type = false ) {
			// Use class query_type if no $type was passed
			if ( ! $type )
				$type = $this->query_type;

			// Single Content Piece
			if ( $type === 'single' ) {
				global $post;

				// Sets up post data
				setup_postdata( $post );
				
				// Store a reference to the current global $post
				$this->global_post = $post;

				// Fetch the post
				$this->query = get_post( $this->widget_instance['post_id'] );

				// Store this post in the global (for now)
				$post = $this->query;
			}
			// Many Content Pieces
			else if ( $type === 'many' ) {
				global $wp_query;

				$max_num_pages = 1;
				$post_counts = false;
				$paged = 1;

				/**
				 * Set up query arguments
				 */
				$query_args = array(
					'ignore_sticky_posts' => true,
					'post_type' => $this->widget_instance['query_args']['post_type'],
					'cat' => $this->widget_instance['query_args']['cat'],
					'orderby' => $this->widget_instance['query_args']['orderby'],
					'order' => $this->widget_instance['query_args']['order'],
					'_conductor' => array()
				);

				/*
				 * Calculate correct posts_per_page and offset
				 */

				// If no posts_per_page value is set, or posts_per_page and max_num_posts values are even, use the max_num_posts
				if ( empty( $this->widget_instance['query_args']['posts_per_page'] ) || ( ( int ) $this->widget_instance['query_args']['posts_per_page'] === ( int ) $this->widget_instance['query_args']['max_num_posts'] ) ) {
					// If max_num_posts is empty, we need to show all of the posts
					if ( $this->widget_instance['query_args']['max_num_posts'] === '' ) {
						$post_counts = wp_count_posts( $this->widget_instance['query_args']['post_type'] );
						$query_args['posts_per_page'] = ( int ) $post_counts->publish;
					}
					// Otherwise posts_per_page can be set to max_num_posts
					else
						$query_args['posts_per_page'] = ( int ) $this->widget_instance['query_args']['max_num_posts'];

					$query_args['offset'] = ( $this->widget_instance['query_args']['offset'] > 1 ) ? ( $this->widget_instance['query_args']['offset'] - 1 ) : 0;
				}
				// Otherwise posts_per_page is set, calculate the correct number of posts_per_page
				else {
					$query_args['posts_per_page'] = $this->widget_instance['query_args']['posts_per_page'];

					// Get the "true" paged query variable from the main query (defaulting to 1)
					$paged = $query_args['paged'] = ( int ) get_query_var( 'paged' );

					// Use the paged query var if set
					if ( empty( $query_args['paged'] ) && isset( $wp_query->query['paged'] ) )
						$paged = $query_args['paged'] = ( int ) $wp_query->query['paged'];
					// Single post uses "page" instead of "paged"
					else if ( is_single() && ( int ) get_query_var( 'page' ) )
						$paged = $query_args['paged'] = ( int ) get_query_var( 'page' );
					// Otherwise assume page 1
					else if ( empty( $query_args['paged'] ) )
						$paged = $query_args['paged'] = 1;

					// Determine the correct offset
					$offset = ( $this->widget_instance['query_args']['offset'] > 1 ) ? ( $this->widget_instance['query_args']['offset'] - 1 ) : 0;

					// Get the "true" posts found on the last page and offset
					if ( $this->widget_instance['query_args']['max_num_posts'] === '' ) {
						// We need the total post count (including offset)
						$post_counts = wp_count_posts( $this->widget_instance['query_args']['post_type'] );
						$last_page_posts = $post_counts->publish - ( $query_args['posts_per_page'] * ( $query_args['paged'] - 1 ) + $offset );
					}
					else {
						$last_page_posts = ( int ) $this->widget_instance['query_args']['max_num_posts'] - ( $query_args['posts_per_page'] * ( $query_args['paged'] - 1 ) );
					}

					// "Last" page
					if ( $last_page_posts && $last_page_posts < $query_args['posts_per_page'] ) {
						// Calculate the correct offset on "last" page (using post type publish count)
						if ( $post_counts )
								$query_args['offset'] = ( $post_counts->publish - $last_page_posts );
						// Calculate the correct offset on "last" page (using other query arguments)
						else
							if ( $offset )
								$query_args['offset'] = ( $offset + ( $query_args['posts_per_page'] * ( $query_args['paged'] - 1 ) ) );
							else
								//$query_args['offset'] = ( $query_args['posts_per_page'] * ( $query_args['paged'] - 1 ) + $last_page_posts ) - 1;
								$query_args['offset'] = ( $query_args['posts_per_page'] * ( $query_args['paged'] - 1 ) );

						// paged is not needed here since we're on the last page
						unset( $query_args['paged'] );

						// Adjust posts per page to match the number of posts found on the last page
						$query_args['posts_per_page'] = $last_page_posts;

						// Set the "last_page" flag in the query
						$query_args['_conductor'] += array( 'last_page' => true );
					}

					// Calculate the "true" offset (if necessary), only if offset isn't already set (i.e. we're not on the last page)
					if ( $offset && ! isset( $query_args['offset'] ) ) {
						// If we're paged, but not on the "last" page
						if ( $paged > 1 )
							$query_args['offset'] = $offset + ( $query_args['posts_per_page'] * ( $query_args['paged'] - 1 ) );
						// "First" page
						else
							$query_args['offset'] = $offset;
					}

					// Determine if the user has gone beyond the max number of pages
					$max_num_pages = ( $this->widget_instance['query_args']['max_num_posts'] === '' ) ? ceil( ( $post_counts->publish - $offset ) / ( int ) $this->widget_instance['query_args']['posts_per_page'] ) : ceil( $this->widget_instance['query_args']['max_num_posts'] / ( int ) $this->widget_instance['query_args']['posts_per_page'] ) ;

					if ( $max_num_pages < $paged ) {
						$post_counts = ( $this->widget_instance['query_args']['max_num_posts'] !== '' ) ? wp_count_posts( $this->widget_instance['query_args']['post_type'] ) : $post_counts;

						// Set the posts per page
						$query_args['posts_per_page'] = $this->widget_instance['query_args']['posts_per_page'];

						// "Fake" the offset
						$query_args['offset'] = $post_counts->publish;
					}
				}

				// If posts should be excluded (and none to be included)
				if ( ! empty( $this->widget_instance['query_args']['post__not_in'] ) && empty( $this->widget_instance['query_args']['post__in'] ) ) {
					$query_args['post__not_in'] = explode( ',', $this->widget_instance['query_args']['post__not_in'] );

					// We need the total post count
					if ( ! $post_counts )
						$post_counts = wp_count_posts( $this->widget_instance['query_args']['post_type'] );

					// If max_num_posts is smaller than the total number of found posts
					if ( $this->widget_instance['query_args']['max_num_posts'] !== '' && ( int ) $this->widget_instance['query_args']['max_num_posts'] < $post_counts->publish ) {
						/*
						 * Now we need to determine if any posts are actually going to be excluded in this query.
						 *
						 * Run a custom query and determine if any posts were excluded in our query or not (the excluded posts could come after max_num_posts, or they may not even be excluded in this query, so we have to check)
						 */

						$temp_query_args = $query_args; // Create temporary query args
						$temp_query_args['offset'] = ( $this->widget_instance['offset'] - 1 ); // Reset offset
						$temp_query_args['posts_per_page'] = ( int ) $this->widget_instance['query_args']['max_num_posts']; // Reset posts per page to max num posts
						$temp_query_args['no_found_rows'] = true; // We don't need a total count here
						$temp_query_args['fields'] = 'ids'; // Only return IDs
						unset( $temp_query_args['paged'] ); // We don't need paged
						unset( $temp_query_args['post__not_in'] ); // Remove excluded posts because we need to check if the requested excluded posts are in this set of returned posts

						$temp_exclude_query = new WP_Query( $temp_query_args );

						// If we have results, loop through and determine if any of our excluded IDs were excluded
						if ( ! empty( $temp_exclude_query->posts ) )
							foreach ( $temp_exclude_query->posts as $post_id )
								// Post was found in the excluded list
								if ( in_array( $post_id, $query_args['post__not_in'] ) )
									self::$query_post__not_in[] = $post_id;

						// "Last" page
						if ( isset( $query_args['paged'] ) && ( int ) ( $query_args['paged'] * $query_args['posts_per_page'] ) === ( int ) $this->widget_instance['query_args']['max_num_posts'] ) {
							// Adjust posts per page only if we have posts that were excluded
							if ( ! empty( self::$query_post__not_in ) )
								$query_args['posts_per_page'] = count( self::$query_post__not_in ); // Reset posts per page

							// Set the "last_page" flag in the query
							$query_args['_conductor'] += array( 'last_page' => true );

							// Set the "last_page_caller" flag in the query
							$query_args['_conductor'] += array( 'last_page_caller' => 'post__not_in' );
						}
					}
				}

				// If posts should be included
				if ( ! empty( $this->widget_instance['query_args']['post__in'] ) ) {
					$query_args['post__in'] = ( strpos( $this->widget_instance['query_args']['post__in'], ',' ) !== false ) ? explode( ',', $this->widget_instance['query_args']['post__in'] ) : ( array ) $this->widget_instance['query_args']['post__in'];
					$query_args['post_type'] = get_post_types( array( 'public' => true ), 'names' ); // All post types, TODO: Do we need to specify post types here?
					$query_args['orderby'] = 'post__in'; // Order by order of posts specified by user
					unset( $query_args['post__not_in'] ); // Ignore excluded posts
					unset( $query_args['offset'] ); // Ignore offset
					//unset( $query_args['posts_per_page'] ); // Ignore posts per page

					// TODO: Add functionality for last_page (if necessary)
				}

				// Set a custom parameter so that we know when a Conductor query is executed (used for found_posts filter)
				$query_args['_conductor'] += array(
					'instance' => $this->widget_instance,
					'max_num_pages' => $max_num_pages,
					'paged' => $paged
				);

				// Allow filtering of query arguments
				$this->query_args = apply_filters( 'conductor_query_args', $query_args, $type, $this->widget_instance, $this );

				// Create the query
				$this->query = new WP_Query( $this->query_args );
			}
			// Other Types
			else
				$this->query = apply_filters( 'conductor_query_other_types', $this->query, $type, $this->widget_instance, $this );
		}

		/**
		 * This function is used to retrieve the current query (with results)
		 */
		public function get_query() {
			return $this->query;
		}

		/**
		 * This function is used to determine if the current query has posts (uses query class have_posts() if exists).
		 */
		public function have_posts() {
			// Use the query object's default have_posts() method if it exists
			if ( method_exists( $this->query, 'have_posts' ) )
				return $this->query->have_posts();

			return apply_filters( 'conductor_query_have_posts', false, $this );
		}

		/**
		 * This function is used to move to the next post within the current query. (uses query class next_post() if exists).
		 */
		public function next_post() {
			// Use the query object's default next_post() method if it exists
			if ( method_exists( $this->query, 'next_post' ) )
				$this->query->next_post();

			do_action( 'conductor_query_next_posts', $this->query->post, $this ); // Legacy
			do_action( 'conductor_query_next_post', $this->query->post, $this );

			return $this->query->post;
		}

		/**
		 * This function is used to move to the next post within the current query (uses query class the_post() if exists
		 * and will also set global $post data).
		 */
		public function the_post() {
			// Use the query object's default the_post() method if it exists
			if ( method_exists( $this->query, 'the_post' ) )
				$this->query->the_post();

			do_action( 'conductor_query_the_post', $this->query->post, $this );

			return $this->query->post;
		}

		/**
		 * This function is used to fetch the current post in the query.
		 */
		public function get_current_post( $single = false ) {
			// Single Content Pieces
			if ( $single )
				$post = $this->get_query(); // WP_Post Object
			// Multiple Content Pieces
			else
				$post = $this->get_query()->post; // WP_Post Object

			return apply_filters( 'conductor_query_current_post', $post, $single, $this );
		}

		/**
		 * This function determines if the current query has pagination.
		 */
		public function has_pagination() {
			$has_pagination = false;

			/*
			 * Pagination checks:
			 *
			 * - Make sure posts_per_page is not empty,
			 * - Make sure posts_per_page does not equal max_num_pages,
			 * - Make sure found_posts is greater than posts_per_page,
			 * - Or if we're on a Conductor query on the last page
			 */
			if ( ! empty( $this->widget_instance['query_args']['posts_per_page'] ) && ( $this->widget_instance['query_args']['posts_per_page'] !== ( int ) $this->widget_instance['query_args']['max_num_posts'] ) && ( $this->query->found_posts > $this->widget_instance['query_args']['posts_per_page'] || ( isset( $this->query->query['_conductor'] ) && ! empty( $this->query->query['_conductor'] ) && isset( $this->query->query['_conductor']['last_page'] ) && $this->query->query['_conductor']['last_page'] ) ) )
				$has_pagination = true;

			return apply_filters( 'conductor_query_has_pagination', $has_pagination, $this );
		}

		/**
		 * This function returns or will echo pagination for a query depending on parameters.
		 * TODO: Optimize this function
		 */
		public function get_pagination_links( $query = false, $echo = true ) {
			global $page;
			// Use class query if no $query was passed
			if ( empty( $query ) )
				$query = $this->query;

			// Grab the permalink structure
			$permalink_structure = get_option( 'permalink_structure' );

			// Flag to determine if there is a permalink structure
			$has_permalink_structure = apply_filters( 'conductor_query_paginate_links_has_permalink_structure', ( ! is_preview() && $permalink_structure ), $permalink_structure, $query, $echo, $this );

			// Paginate links arguments
			$paginate_links_args = array(
				//'base' => get_pagenum_link() . '%_%', // %_% will be replaced with format below
				'format' => ( $has_permalink_structure ) ? 'page/%#%/' : '?paged=%#%', // %#% will be replaced with page number
				'current' => max( 1, ( ( $_conductor = $query->get( '_conductor' ) ) && isset( $_conductor['last_page'] ) && $_conductor['last_page'] ) ? $query->max_num_pages : $query->get( 'paged' ) ), // Get whichever is the max out of 1 and the current page count
				'total' => $query->max_num_pages, // Get total number of pages in current query
				'next_text' => __(' Next &#8594;', 'conductor' ),
				'prev_text' => __( '&#8592; Previous', 'conductor' ),
				'type' => ( ! $echo ) ? 'array' : 'list'  // Output this as an array or unordered list
			);

			// Front page
			if ( apply_filters( 'conductor_query_paginate_links_is_front_page', is_front_page(), $has_permalink_structure, $paginate_links_args, $query, $echo, $this ) )
				$paginate_links_args['format'] = ( $has_permalink_structure ) ? 'page/%#%/' : '/?paged=%#%';

			// Single post uses "page" instead of "paged"
			if ( apply_filters( 'conductor_query_paginate_links_is_single', is_single(), $has_permalink_structure, $paginate_links_args, $query, $echo, $this ) )
				$paginate_links_args['format'] = ( $has_permalink_structure ) ? '%#%/' : '?page=%#%'; // %#% will be replaced with page number

			// If we don't have a permalink structure or this is a preview and this widget has AJAX enabled
			if ( ( ! $has_permalink_structure || is_preview() ) && $this->widget->has_ajax( $this->widget_instance, array() ) ) {
				// Add the base argument to the paginate links arguments (remove the "page" and "paged" query arguments)
				$paginate_links_args['base'] = html_entity_decode( remove_query_arg( array( 'page', 'paged' ), html_entity_decode( ( is_preview() ) ? esc_url( get_permalink() ) : get_pagenum_link() ) ) ) . '%_%';

				// Replace question marks in the format argument with ampersands
				$paginate_links_args['format'] = ( ! $permalink_structure ) ? str_replace( '?', '&', $paginate_links_args['format'] ) : $paginate_links_args['format'];
			}

			// If we have a permalink structure and we're paged
			if ( $has_permalink_structure && ( is_paged() || $page > 1 ) )
				// Add the base argument to the paginate links arguments (remove the "page" and "paged" query arguments)
				$paginate_links_args['base'] = html_entity_decode( remove_query_arg( array( 'page', 'paged' ), html_entity_decode( ( is_preview() ) ? esc_url( get_permalink() ) : get_pagenum_link() ) ) ) . '%_%';

			$paginate_links_args = apply_filters( 'conductor_query_paginate_links_args', $paginate_links_args, $query, $echo, $this, $has_permalink_structure );

			$paginate_links = paginate_links( $paginate_links_args );

			if ( $echo )
				echo $paginate_links;
			else
				return $paginate_links;
		}

		/**
		 * This function alters the number of "found_posts" on Conductor Widget queries only (query args
		 * contain _conductor key).
		 */
		public static function found_posts( $found_posts, $query ) {
			// Grab the original found posts value
			$orig_found_posts = $found_posts;

			// Only on Conductor Widget queries
			if ( $_conductor = $query->get( '_conductor' ) ) {
				// Test for "excluded" posts early
				if ( ! empty( self::$query_post__not_in ) ) {
					// "Last" page
					if ( isset( $_conductor['last_page'] ) && $_conductor['last_page'] && ! empty( self::$query_post__not_in ) )
						if ( $_conductor['instance']['query_args']['max_num_posts'] !== '' && $_conductor['instance']['query_args']['max_num_posts'] < $found_posts )
							$found_posts = ( int ) ( $query->get( 'posts_per_page' ) * ceil( $_conductor['instance']['query_args']['max_num_posts'] / $_conductor['instance']['query_args']['posts_per_page'] ) );
						else
							$found_posts = ( int ) ( $query->get( 'posts_per_page' ) *ceil( $found_posts / $_conductor['instance']['query_args']['posts_per_page'] ) );
					// All other pages
					else
						if ( $_conductor['instance']['query_args']['max_num_posts'] !== '' && $found_posts > ( int ) $_conductor['instance']['query_args']['max_num_posts'] )
							$found_posts = ( int ) $_conductor['instance']['query_args']['max_num_posts'] - count( self::$query_post__not_in );
						else
							$found_posts -= count( self::$query_post__not_in );

					return $found_posts;
				}

				// Subtract offset from $found_posts
				$found_posts -= ( ( int ) $_conductor['instance']['query_args']['offset'] - 1 );

				// TODO: Need to calculate this correctly for the "last" page?

				// If $found_posts is greater than the maximum number of requested posts
				if ( $_conductor['instance']['query_args']['max_num_posts'] !== '' && $found_posts > ( int ) $_conductor['instance']['query_args']['max_num_posts'] )
					$found_posts = $_conductor['instance']['query_args']['max_num_posts'];

				// If post__in count the number of posts
				if ( ! empty( $_conductor['instance']['query_args']['post__in'] ) )
					$found_posts = ( strpos( $_conductor['instance']['query_args']['post__in'], ',' ) !== false ) ? count( explode( ',', $_conductor['instance']['query_args']['post__in'] ) ) : 1;

				// "Last page" (we have to fake this value in order for max_num_pages to work correctly)
				if ( isset( $_conductor['last_page'] ) && $_conductor['last_page'] ) {
					// TODO: Add functionality for last_page for post__in
					//if ( $_conductor['instance']['max_num_posts'] !== '' && ( int ) $_conductor['instance']['query_args']['max_num_posts'] > $_conductor['instance']['query_args']['posts_per_page'] )
						// Only if the posts_per_page does not divide evenly into max_num_posts
						//if ( ( int ) $_conductor['instance']['query_args']['max_num_posts'] % $_conductor['instance']['query_args']['posts_per_page'] ) {
							//$found_posts -= ceil( ( int ) $_conductor['instance']['query_args']['max_num_posts'] / $_conductor['instance']['query_args']['posts_per_page'] );
						//}
					if ( $_conductor['instance']['query_args']['max_num_posts'] !== '' && $_conductor['instance']['query_args']['max_num_posts'] < $found_posts )
						$found_posts = ( int ) ( $query->get( 'posts_per_page' ) * ceil( $_conductor['instance']['query_args']['max_num_posts'] / $_conductor['instance']['query_args']['posts_per_page'] ) );
					else
						$found_posts = ( int ) ( $query->get( 'posts_per_page' ) *ceil( $found_posts / $_conductor['instance']['query_args']['posts_per_page'] ) );
				}

				$found_posts = apply_filters( 'conductor_query_found_posts', $found_posts, $query, $orig_found_posts );
			}

			return $found_posts;
		}

		/**
		 * This function gets the excerpt of a specific post ID or object.
		 * @see https://pippinsplugins.com/a-better-wordpress-excerpt-by-id-function/
		 */
		public function get_excerpt_by_id( $post, $length = 55, $tags = array(), $extra = '...' ) {
			// Get the post object of the passed ID
			if( is_int( $post ) )
				$post = get_post( $post );
			else if( ! is_object( $post ) )
				return false;

			// Only return the password form if excerpt length is greater than 0
			if ( $length && post_password_required( $post ) )
				return get_the_password_form( $post );

			// Allowed HTML tags in excerpt
			$tags = apply_filters( 'conductor_widget_excerpt_allowable_tags', ( array ) $tags, $post );
			$tags = implode( '', $tags );

			// TODO: Make this an option on the widget
			$the_excerpt = ( has_excerpt( $post->ID ) ) ? $post->post_excerpt : $post->post_content;
			$the_excerpt = strip_shortcodes( strip_tags( $the_excerpt, $tags ) );
			$words_array = preg_split( "/[\n\r\t ]+/", $the_excerpt, $length + 1, PREG_SPLIT_NO_EMPTY );
			$sep = ' ';

			if ( count( $words_array ) > $length ) {
				array_pop( $words_array );
				$the_excerpt = implode( $sep, $words_array );
				$the_excerpt .= $extra;
			}
			else
				$the_excerpt = implode( $sep, $words_array );

			return apply_filters( 'the_content', $the_excerpt );
		}

		/**
		 * This function returns the content of a specific post ID or object. The functionality is virtually identical
		 * to get_the_content() in core except it can be used outside of "The Loop" and some bits of functionality
		 * were removed as they were not needed (global variables, $more, some teaser functionality, read more).
		 *
		 * Retrieve the post content.
		 *
		 */
		public function get_content_by_id( $post, $strip_teaser = false ) {
			// Get the post object of the passed ID
			if( is_int( $post ) )
				$post = get_post( $post );
			else if( ! is_object( $post ) )
				return false;

			$output = '';
			$has_teaser = false;

			// If post password required and it doesn't match the cookie.
			if ( post_password_required( $post ) )
				return get_the_password_form( $post );

			$content = $post->post_content;
			if ( preg_match( '/<!--more(.*?)?-->/', $content, $matches ) ) {
				$content = explode( $matches[0], $content, 2 );

				$has_teaser = true;
			} else {
				$content = array( $content );
			}

			if ( false !== strpos( $post->post_content, '<!--noteaser-->' ) )
				$strip_teaser = true;

			$teaser = $content[0];

			if ( $strip_teaser && $has_teaser )
				$teaser = '';

			$output .= $teaser;

			$output = apply_filters( 'the_content', $output );
			$output = str_replace( ']]>', ']]&gt;', $output );

			return $output;
		}

		/**
		 * This function returns CSS classes for use in this widget.
		 */
		public function get_wrapper_css_classes( $post, $instance, $widget ) {
			return implode( ' ', get_post_class( $widget->get_css_classes( $instance ), $post->ID ) );
		}

		/************************
		 * Output Functionality *
		 ************************/

		/**
		 * This function outputs the featured image for Conductor Widgets.
		 */
		public function conductor_widget_featured_image( $post, $instance, $widget, $query ) {
			// Find the featured image output element data
			$priority = $instance['output_elements']['featured_image'];
			$output = $instance['output'][$priority];

			do_action( 'conductor_widget_featured_image_before', $post, $instance );

			if ( has_post_thumbnail( $post->ID ) ) :
		?>
				<div class="thumbnail post-thumbnail featured-image <?php echo ( ! $output['link'] ) ? 'no-link' : false; ?>">
					<?php
						// Output desired featured image size
						if ( ! empty( $instance['post_thumbnails_size'] ) )
							$conductor_thumbnail_size = $instance['post_thumbnails_size'];
						else
							$conductor_thumbnail_size = ( $instance['widget_size'] !== 'small' ) ? $instance['widget_size'] : 'thumbnail';

						$conductor_thumbnail_size = apply_filters( 'conductor_widget_featured_image_size', $conductor_thumbnail_size, $instance, $post, $widget, $query, $this ); // TODO: Future: Add $widget, $query, $this parameters to other add-ons

						// Link featured image to post
						if ( $output['link'] ) :
					?>
							<a href="<?php echo get_permalink( $post->ID ); ?>">
								<?php echo get_the_post_thumbnail( $post->ID, $conductor_thumbnail_size ); ?>
							</a>
					<?php
						// Just output the featured image
						else:
							echo get_the_post_thumbnail( $post->ID, $conductor_thumbnail_size );
						endif;
					?>
				</div>
		<?php
			endif;

			do_action( 'conductor_widget_featured_image_after', $post, $instance );
		}

		/**
		 * This function outputs the post title for Conductor Widgets.
		 */
		public function conductor_widget_post_title( $post, $instance, $widget, $query ) {
			// Find the post title output element data
			$priority = $instance['output_elements']['post_title'];
			$output = $instance['output'][$priority];

			do_action( 'conductor_widget_post_title_before', $post, $instance );

			$link = ( ! $output['link'] ) ? ' no-link' : false;
		?>
			<h2 class="<?php echo apply_filters( 'conductor_widget_post_title_css_classes', 'post-title entry-title' . $link, $output ); ?>">
				<?php
					// Link post title to post
					if ( $output['link'] ) :
				?>
						<a href="<?php echo get_permalink( $post->ID ); ?>">
							<?php echo get_the_title( $post->ID ); ?>
						</a>
				<?php
					// Just output the post title
					else:
						echo get_the_title( $post->ID );
					endif;
				?>
			</h2>
		<?php
			do_action( 'conductor_widget_post_title_after', $post, $instance );
		}

		/**
		 * This function outputs the author byline for Conductor Widgets.
		 */
		public function conductor_widget_author_byline( $post, $instance, $widget, $query ) {
			do_action( 'conductor_widget_author_byline_before', $post, $instance );
		?>
			<p class="post-author"><?php printf( __( 'Posted by <a href="%1$s">%2$s</a> on %3$s', 'conductor' ) , get_author_posts_url( get_the_author_meta( 'ID' , $post->post_author ) ), get_the_author_meta( 'display_name', $post->post_author ), get_the_time( 'F jS, Y', $post ) ); ?></p>
		<?php
			do_action( 'conductor_widget_author_byline_after', $post, $instance );
		}

		/**
		 * This function outputs the post content for Conductor Widgets.
		 */
		public function conductor_widget_post_content( $post, $instance, $widget, $query ) {
			do_action( 'conductor_widget_post_content_before', $post, $instance );

			// Determine which type of content to output
			switch ( $instance['content_display_type'] ) {
				// Excerpt - the_excerpt()
				case 'excerpt':
					echo $this->get_excerpt_by_id( $post, $instance['excerpt_length'] );
				break;

				// the_content()
				case 'content':
				default:
					echo $this->get_content_by_id( $post );
				break;
			}

			do_action( 'conductor_widget_post_content_after', $post, $instance );
		}

		/**
		 * This function outputs the read more link for Conductor Widgets.
		 */
		public function conductor_widget_read_more( $post, $instance, $widget, $query ) {
			// Find the read more output element data
			$priority = $instance['output_elements']['read_more'];
			$output = $instance['output'][$priority];

			do_action( 'conductor_widget_read_more_before', $post, $instance );

			// Link read more to post
			if ( $output['link'] ) :
		?>
				<a class="more read-more more-link" href="<?php echo get_permalink( $post->ID ); ?>">
					<?php echo $output['label']; ?>
				</a>
		<?php
			// Just output the read more
			else:
				echo $output['label'];
			endif;

			do_action( 'conductor_widget_read_more_after', $post, $instance );
		}
	}

	/**
	 * Create an instance of the Conductor_Widget_Query class.
	 */
	function Conduct_Widget_Default_Query() {
		return Conductor_Widget_Default_Query::instance();
	}

	Conduct_Widget_Default_Query(); // Conduct your content!
}