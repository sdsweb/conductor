<?php
/**
 * Conductor Template Loader
 *
 * @class Conductor_Template_Loader
 * @author Slocum Studio
 * @version 1.5.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Template_Loader' ) ) {
	final class Conductor_Template_Loader {
		/**
		 * @var string
		 */
		public $version = '1.5.0';

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
			add_action( 'wp', array( $this, 'wp' ) ); // WP
			add_filter( 'template_include', array( $this, 'template_include' ), 20 ); // Load Conductor Templates
			add_filter( 'body_class', array( $this, 'body_class' ) ); // Add Body Classes
		}


		/**
		 * This function determines if we're on a Conductor content layout that is paginated but was previously
		 * flagged as a 404 template. It then removes the 404 flags if necessary.
		 */
		public function wp() {
			global $wp_query;

			// Only if we're on a 404 request
			if ( is_404() ) {
				// Parse the query variables again
				$wp_query->parse_query();

				// Determine if this is a Conductor content layout on a paged archive request only
				if ( is_paged() && ( is_archive() || is_home() ) && Conductor::is_conductor() ) {
					$wp_query->is_404 = false;
					status_header( 200 ); // Legit request

					// Remove the nocache headers
					add_filter( 'nocache_headers', array( $this, 'nocache_headers' ) );
				}
				// Otherwise set the 404 flags again
				else
					$wp_query->set_404();
			}
		}

		/**
		 * This function removes the nocache headers if necessary.
		 */
		public function nocache_headers( $headers ) {
			return array();
		}

		/**
		 * This function loads Conductor templates on the front end.
		 *
		 * It determines whether or not a Conductor page is being loaded and which template to load from
		 * the plugin/theme assets. Themes can override default Conductor templates by creating a 'conductor'
		 * directory and placing template files there.
		 */
		public function template_include( $template ) {
			// Verify that a Conductor page is being requested
			if ( Conductor::is_conductor() ) {
				$conductor_content_layout_data = Conductor::get_conductor_content_layout_data();
				$conductor_content_layout_template = ( isset( $conductor_content_layout_data['template'] ) && $conductor_content_layout_data['template'] !== 'conductor.php' ) ? $conductor_content_layout_data['template'] : false;
				$orig_template = $template; // Reference to the original requested template
				$file = 'conductor.php';
				$templates = array();

				// Setup core template files
				$templates[] = $file;
				$templates[] = Conductor::theme_template_path() . '/' . $file; // Theme

				// If this layout has specified a template add it to the templates list
				if ( $conductor_content_layout_template ) {
					$templates[] = $conductor_content_layout_template;
					$templates[] = Conductor::theme_template_path() . '/' . $conductor_content_layout_template; // Theme
				}

				// Verify if the file exists in the theme first, then load the plugin template if necessary
				$template = locate_template( $templates );

				// If we don't have a template yet
				if ( ! $template ) {
					// Check to see if the content layout template exists in Conductor
					if ( $conductor_content_layout_template && file_exists( Conductor::plugin_dir() . '/templates/' . $conductor_content_layout_template ) )
						$template = Conductor::plugin_dir() . '/templates/' . $conductor_content_layout_template;
					// Conductor fallback template
					else
						$template = Conductor::plugin_dir() . '/templates/' . $file; // Conductor
				}

				// conductor_template_include filter
				$template = apply_filters( 'conductor_template_include', $template, $templates, $orig_template );

				// If we don't have a template at this point, use the original as a fallback
				if ( ! $template )
					$template = $orig_template;

				// TODO: Follow the WordPress template hierarchy structure
			}

			return $template;
		}

		/**
		 * This function adds conductor CSS classes to the <body> element.
		 */
		public function body_class( $classes ) {
			// Verify that a Conductor page is being requested
			if ( Conductor::is_conductor() ) {
				$conductor_content_layout = Conductor::get_conductor_content_layout(); // Grab this Conductor content layout
				$conductor_content_layout_data = Conductor::get_conductor_content_layout_data();
				$conductor_content_layout_body_class = ( isset( $conductor_content_layout_data['body_class'] ) ) ? $conductor_content_layout_data['body_class'] : false;

				$template = get_option( 'template' ); // Parent theme
				$stylesheet = get_option( 'stylesheet' ); // Child theme

				// Base CSS classes
				$css_classes = array(
					'conductor',
					'conductor-' . $conductor_content_layout['value'],
					$template,
					$stylesheet
				);

				// Custom CSS classes
				if ( $conductor_content_layout_body_class && is_string( $conductor_content_layout_body_class ) )
					$css_classes[] = $conductor_content_layout_body_class;
				else if ( $conductor_content_layout_body_class && is_array( $conductor_content_layout_body_class ) )
					$css_classes = array_merge( $css_classes, $conductor_content_layout_body_class );

				// Sanitize CSS classes
				$css_classes = array_map( 'sanitize_html_class', $css_classes );

				// Ensure we have unique CSS classes (no empty values)
				$css_classes = array_unique( array_values( array_filter( $css_classes ) ) );


				$classes['conductor'] = esc_attr( implode( ' ', $css_classes ) );
			}

			return $classes;
		}
	}

	/**
	 * Create an instance of the Conductor_Template_Loader class.
	 */
	function Conduct_Template_Loader() {
		return Conductor_Template_Loader::instance();
	}

	Conduct_Template_Loader(); // Conduct your content!
}