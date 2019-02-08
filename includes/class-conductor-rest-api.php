<?php
/**
 * Conductor REST API
 *
 * @class Conductor_REST_API
 * @author Slocum Studio
 * @version 1.5.2
 * @since 1.5.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_REST_API' ) ) {
	final class Conductor_REST_API {
		/**
		 * @var string
		 */
		public $version = '1.5.2';

		/**
		 * @var string
		 */
		public static $namespace = 'conductor/v1';

		/**
		 * @var string
		 */
		public $pagenum_link = '';

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
		function __construct() {
			// Hooks
			add_action( 'rest_api_init', array( $this, 'rest_api_init' ) ); // REST API Initialization
		}

		/**
		 * This function runs when the REST API is initialized.
		 */
		// TODO: Future: Convert this logic to a WP_REST_Controller instance
		public function rest_api_init() {
			// Grab the Conductor options
			$conductor_options = Conductor_Options::get_options();

			// Bail if the Conductor REST API isn't enabled
			if ( ! $conductor_options['rest']['enabled'] )
				return;

			/*
			 * Register the Conductor Widget query endpoint.
			 *
			 * Parameters:
			 *  - Number: Conductor Widget number (allows for negative number for use in the_widget())
			 *  - Paged (optional): Current page to use for the query
			 *
			 * Note: We're utilizing both the GET (READABLE) and POST (CREATABLE) methods
			 * due to the fact that URLs should be limited to roughly ~2000 characters at most.
			 *
			 * We need to be sure that we are below this limit and POST (CREATABLE) is one way
			 * that we can accomplish this.
			 *
			 * @see https://stackoverflow.com/a/417184
			 */
			register_rest_route( self::$namespace, apply_filters( 'conductor_rest_widget_query_route', '/widget/query/(?P<number>[-]?[\d]+)(?:\/(?P<paged>[\d]+))?\/?', $conductor_options, $this ), apply_filters( 'conductor_rest_widget_query_args', array(
				'methods' => array(
					// GET
					WP_REST_Server::READABLE,
					// POST
					WP_REST_Server::CREATABLE
				),
				'callback' => array( $this, 'widget_query' ),
				array(
					// Arguments
					'args' => array(
						// Number
						'number' => array(
							'required' => true,
							'sanitize_callback' => array( $this, 'int' ),
							'validate_callback' => array( $this, 'is_numeric' )
						),
						// Paged (optional)
						'paged' => array(
							'sanitize_callback' => array( $this, 'absint' ),
							'validate_callback' => array( $this, 'is_numeric' )
						)
					)
				)
			), $conductor_options, $this ) );
		}


		/************
		 * REST API *
		 ************/

		/**
		 * This function runs when a Conductor Widget REST API request is received.
		 *
		 * Note: As of WordPress 4.9.4, URL parameters are not sanitized prior
		 * to the REST API callback function being executed. We're sanitizing them
		 * here again just to be sure.
		 */
		public function widget_query( $data ) {
			// Grab the Conductor options
			$conductor_options = Conductor_Options::get_options();

			// Bail if the Conductor REST API isn't enabled
			if ( ! $conductor_options['rest']['enabled'] )
				return new WP_REST_Response( array(
					'code' => 'conductor_rest_api_disabled',
					'message' => __( 'Something went wrong. Please try again', 'conductor' ),
					'data' => array(
						'status' => 404
					)
				), 404 );

			// Grab the Conductor Widget instance
			$conductor_widget = Conduct_Widget();

			// Grab the widget number
			$widget_number = apply_filters( 'conductor_rest_widget_query_widget_number', ( int ) $data['number'], $data['number'], $data, $conductor_options, $conductor_widget, $this );

			// Grab the paged value (default to 1)
			$paged = apply_filters( 'conductor_rest_widget_query_paged', ( isset( $data['paged'] ) ) ? ( int ) $data['paged'] : 1, $data, $conductor_options, $conductor_widget, $this );

			// Grab a reference to the widget settings (all Conductor Widgets)
			$conductor_widget_settings = apply_filters( 'conductor_rest_widget_query_conductor_widget_settings', $conductor_widget->get_settings(), $widget_number, $paged, $data, $conductor_options, $conductor_widget, $this );

			// Bail if this isn't a valid Conductor Widget
			if ( ! array_key_exists( $widget_number, $conductor_widget_settings ) || ! $conductor_widget->is_valid_instance( $conductor_widget_settings[$widget_number] ) ) {
				return new WP_REST_Response( array(
					'code' => 'conductor_widget_number_invalid',
					'message' => __( 'Invalid Conductor Widget number. Please try again', 'conductor' ),
					'data' => array(
						'status' => 404,
						'number' => $widget_number,
						'widget_id' => $conductor_widget->id
					)
				), 404 );
			}

			// Grab the Conductor Widget instance
			$instance = apply_filters( 'conductor_rest_widget_query_conductor_widget_instance', $conductor_widget_settings[$widget_number], $conductor_widget_settings, $widget_number, $paged, $data, $conductor_options, $conductor_widget, $this );

			// Bail if this Conductor Widget isn't enabled in the Conductor REST API
			if ( ! $conductor_widget->is_rest_api_enabled( $instance, array() ) )
				return new WP_REST_Response( array(
					'code' => 'conductor_widget_rest_api_disabled',
					'message' => __( 'Something went wrong. Please try again', 'conductor' ),
					'data' => array(
						'status' => 404,
						'number' => $widget_number,
						'widget_id' => $conductor_widget->id
					)
				), 404 );

			do_action( 'conductor_rest_widget_query_before', $data, $instance, $widget_number, $paged, $conductor_widget_settings, $conductor_widget, $this );

			// Set the global paged value (for the Conductor Widget Query)
			set_query_var( 'paged', $paged );

			// Set the Conductor Widget number
			$conductor_widget->_set( $widget_number );

			// Hook into "conductor_query_paginate_links_has_permalink_structure"
			add_filter( 'conductor_query_paginate_links_has_permalink_structure', array( $this, 'conductor_query_paginate_links_has_permalink_structure' ), 10, 5 );

			// Hook into "conductor_query_paginate_links_is_front_page"
			add_filter( 'conductor_query_paginate_links_is_front_page', array( $this, 'conductor_query_paginate_links_is_front_page' ), 10, 6 );

			// Hook into "conductor_query_paginate_links_is_single"
			add_filter( 'conductor_query_paginate_links_is_single', array( $this, 'conductor_query_paginate_links_is_single' ), 10, 6 );

			// Hook into "conductor_query_paginate_links_args"
			add_filter( 'conductor_query_paginate_links_args', array( $this, 'conductor_query_paginate_links_args' ), 10, 5 );

			// Grab the Conductor Widget REST data
			$conductor_widget_rest = $conductor_widget->widget_rest( array(), $instance );

			// Remove the "conductor_query_paginate_links_args" hook
			remove_filter( 'conductor_query_paginate_links_args', array( $this, 'conductor_query_paginate_links_args' ) );

			// Remove the "conductor_query_paginate_links_is_single" hook
			remove_filter( 'conductor_query_paginate_links_is_single', array( $this, 'conductor_query_paginate_links_is_single' ) );

			// Remove the "conductor_query_paginate_links_is_front_page" hook
			remove_filter( 'conductor_query_paginate_links_is_front_page', array( $this, 'conductor_query_paginate_links_is_front_page' ) );

			// Remove the "conductor_query_paginate_links_has_permalink_structure" hook
			remove_filter( 'conductor_query_paginate_links_has_permalink_structure', array( $this, 'conductor_query_paginate_links_has_permalink_structure' ) );

			// TODO: Future: Return an error if the paged value is greater than the maximum number of pages?

			// Setup the Conductor REST Widget query
			$conductor_rest_widget_query = apply_filters( 'conductor_rest_widget_query', array(
				'success' => true,
				'data' => array_merge( $conductor_widget_rest, array(
					'number' => $widget_number,
					'widget_id' => $conductor_widget->id
				)  )
			), $instance, $paged, $conductor_widget_settings, $conductor_widget, $this );

			do_action( 'conductor_rest_widget_query_after', $conductor_rest_widget_query, $data, $instance, $widget_number, $paged, $conductor_widget_settings, $conductor_widget, $this );

			return $conductor_rest_widget_query;
		}


		/*************
		 * Conductor *
		 *************/

		/**
		 * This function adjusts the has permalink structure flag for Conductor Widget query paginate_links().
		 */
		public function conductor_query_paginate_links_has_permalink_structure( $has_permalink_structure, $permalink_structure, $query, $echo, $conductor_widget_query ) {
			// Grab the pagenum link from the request (ensure it can be used in a re-direct)
			$pagenum_link = ( isset( $_REQUEST['pagenum_link'] ) ) ? wp_validate_redirect( sanitize_text_field( $_REQUEST['pagenum_link'] ) ) : '';

			// Bail if we don't have a pagenum link
			if ( ! $pagenum_link )
				return $has_permalink_structure;

			// Set the pagenum link on this class
			$this->pagenum_link = $pagenum_link;

			// Grab the pagenum link query arguments
			parse_str( parse_url( $pagenum_link, PHP_URL_QUERY ), $pagenum_link_query_args );

			// If we have a permalink structure, we have pagenum link query arguments, and the preview query argument is set in the pagenum link query arguments
			if ( $has_permalink_structure && ! empty( $pagenum_link_query_args ) && isset( $pagenum_link_query_args['preview'] ) && $pagenum_link_query_args['preview'] )
				// Reset the has permalink structure flag
				$has_permalink_structure = false;

			return $has_permalink_structure;
		}

		/**
		 * This function adjusts the is front page flag for Conductor Widget query paginate_links().
		 */
		public function conductor_query_paginate_links_is_front_page( $is_front_page, $has_permalink_structure, $permalink_structure, $query, $echo, $conductor_widget_query ) {
			// Bail if this is already the front page
			if ( $is_front_page )
				return $is_front_page;

			// Set the is front page flag based on the is front page flag from the request
			$is_front_page = ( isset( $_REQUEST['is_front_page'] ) && $_REQUEST['is_front_page'] );

			// TODO: Future: Pass the post ID in the request and verify if page_on_front matches

			return $is_front_page;
		}

		/**
		 * This function adjusts the is single flag for Conductor Widget query paginate_links().
		 */
		public function conductor_query_paginate_links_is_single( $is_single, $has_permalink_structure, $permalink_structure, $query, $echo, $conductor_widget_query ) {
			// Bail if this is already a single piece of content
			if ( $is_single )
				return $is_single;

			// Set the is single flag based on the is front page flag from the request
			$is_single = ( isset( $_REQUEST['is_single'] ) && $_REQUEST['is_single'] === 'true' );

			// TODO: Future: Pass the post ID in the request and verify

			return $is_single;
		}

		/**
		 * This function adjusts Conductor Widget query paginate_links() arguments.
		 */
		public function conductor_query_paginate_links_args( $args, $query, $echo, $conductor_widget_query, $has_permalink_structure ) {
			// If we don't have a pagenum link on this class
			if ( ! $this->pagenum_link ) {
				// Grab the pagenum link from the request (ensure it can be used in a re-direct)
				$pagenum_link = ( isset( $_REQUEST['pagenum_link'] ) ) ? wp_validate_redirect( sanitize_text_field( $_REQUEST['pagenum_link'] ) ) : '';

				// Set the pagenum link on this class
				$this->pagenum_link = $pagenum_link;
			}

			// Bail if we don't have a pagenum link on this class
			if ( ! $this->pagenum_link )
				return $args;

			// Hook into "get_pagenum_link"
			add_filter( 'get_pagenum_link', array( $this, 'get_pagenum_link' ) );

			// Set the base to the pagenum link (remove the "page" and "paged" query arguments)
			$args['base'] = remove_query_arg( array( 'page', 'paged' ), $this->pagenum_link ) . '%_%';

			// If we don't have a permalink structure and the base argument contains a question mark
			if ( ! $has_permalink_structure && strpos( $args['base'], '?' ) !== false ) {
				// Replace question marks in the format argument with ampersands
				$args['format'] = str_replace( '?', '&', $args['format'] );

				// Remove slashes in the format argument
				$args['format'] = str_replace( '/', '', $args['format'] );
			}

			return $args;
		}

		/**
		 * This function adjusts the pagenum link.
		 */
		public function get_pagenum_link( $pagenum_link ) {
			// Remove this hook
			remove_filter( 'get_pagenum_link', array( $this, 'get_pagenum_link' ) );

			// Bail if we don't have a pagenum link on this class
			if ( ! $this->pagenum_link )
				return $pagenum_link;

			// Set the pagenum link
			$pagenum_link = $this->pagenum_link;

			// TODO: Future: Reset the pagenum link reference on this class

			return $pagenum_link;
		}


		/*************************
		 * REST API Sanitization *
		 *************************/
		/**
		 * This function ensures the value is an absolute integer.
		 */
		public function int( $value, $request, $key ) {
			return ( int ) $value;
		}

		/**
		 * This function ensures the value is an absolute integer.
		 */
		public function absint( $value, $request, $key ) {
			return absint( $value );
		}

		/**
		 * This function determines if the value is numeric.
		 */
		public function is_numeric( $value, $request, $key ) {
			return is_numeric( $value );
		}
	}

	/**
	 * Create an instance of the Conductor_REST_API class.
	 */
	function Conduct_REST_API() {
		return Conductor_REST_API::instance();
	}

	Conduct_REST_API(); // Conduct your content!
}