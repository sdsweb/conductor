<?php
/**
 * Plugin Name: Conductor
 * Plugin URI: https://www.conductorplugin.com/
 * Description: Build content-rich layouts in minutes without code.
 * Version: 1.5.4
 * Author: Slocum Studio
 * Author URI: http://www.slocumstudio.com/
 * Requires at least: 4.4
 * Tested up to: 5.2.2
 * License: GPL2+
 *
 * Text Domain: conductor
 * Domain Path: /languages/
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor' ) ) {
	final class Conductor {
		/**
		 * @var string
		 */
		public static $version = '1.5.4';

		/**
		 * @var Boolean, null by default so that we can cache the Boolean value
		 */
		public static $is_conductor = null;

		/**
		 * @var array
		 */
		public static $conductor_content_layout = array();

		/**
		 * @var array
		 */
		public static $conductor_content_layout_data = array();

		/**
		 * @var array
		 */
		public static $public_post_types = array();

		/**
		 * @var array
		 */
		public static $public_post_types_without_attachments = array();

		/**
		 * @var string
		 */
		public static $capability = 'manage_options';

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
			// Load required assets
			$this->includes();

			// Hooks
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) ); // Plugins Loaded
			add_action( 'widgets_init', array( $this, 'widgets_init' ) ); // Widgets Initialization
		}

		/**
		 * Include required core files used in admin and on the front-end.
		 */
		private function includes() {
			// All
			include_once 'includes/class-conductor-updates.php'; // Conductor Updates Class
			include_once 'includes/class-conductor-options.php'; // Conductor Options Class
			include_once 'includes/conductor-sidebar-functions.php'; // Conductor Sidebar Functions
			include_once 'includes/class-conductor-sidebars.php'; // Conductor Sidebars Class
			include_once 'includes/class-conductor-customizer.php'; // Conductor Customizer Class
			include_once 'includes/class-conductor-toolbar.php'; // Core/Main Conductor Toolbar (Admin Bar) Class
			include_once 'includes/class-conductor-scripts-styles.php'; // Conductor Scripts & Styles Class
			include_once 'includes/conductor-template-hooks.php'; // Conductor Template Hooks
			include_once 'includes/conductor-template-functions.php'; // Conductor Template Functions
			include_once 'includes/class-conductor-rest-api.php'; // Conductor REST API
			include_once 'includes/admin/class-conductor-admin.php'; // Core/Main Conductor Admin Class

			// Admin Only
			if ( is_admin() ) {
				if ( ! ( $conductor_option = get_option( Conductor_Options::$option_name ) ) )
					include_once 'includes/admin/class-conductor-admin-install.php'; // Conductor Install Class

				include_once 'includes/walkers/class-conductor-walker-category-dropdown.php'; // Walker_ConductorCategoryDropdown Class
			}

			// Front-End Only
			if ( ! is_admin() )
				include_once 'includes/class-conductor-template-loader.php'; // Conductor Template Loader Class
		}

		/**
		 * This function runs when plugins are loaded.
		 */
		public function plugins_loaded() {
			// Load the Conductor text domain
			load_plugin_textdomain( 'conductor', false, basename( self::plugin_dir() ) . '/languages/' );
		}

		/**
		 * This function includes and initializes Conductor Widgets.
		 */
		public function widgets_init() {
			// Conductor Widget
			include_once 'includes/widgets/class-conductor-widget.php';
		}


		/********************
		 * Helper Functions *
		 ********************/

		/**
		 * This function returns the plugin url for Conductor without a trailing slash.
		 *
		 * @return string, URL for the Conductor plugin
		 */
		public static function plugin_url() {
			return untrailingslashit( plugins_url( '', __FILE__ ) );
		}

		/**
		 * This function returns the plugin directory for Conductor without a trailing slash.
		 *
		 * @return string, Directory for the Conductor plugin
		 */
		public static function plugin_dir() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * This function returns a reference to this Conductor class file.
		 *
		 * @return string
		 */
		public static function plugin_file() {
			return __FILE__;
		}

		/**
		 * This function returns the template path, without a trailing slash, for which themes should place
		 * their Conductor templates to override default Conductor templates (i.e. your-theme/conductor).
		 *
		 * @return string, Directory for Conductor theme templates
		 */
		public static function theme_template_path() {
			return untrailingslashit( apply_filters( 'conductor_template_path', 'conductor' ) );
		}

		/**
		 * This function returns a boolean result comparing against the current WordPress version.
		 *
		 * @return Boolean
		 */
		public static function wp_version_compare( $version, $operator = '>=' ) {
			global $wp_version;

			return version_compare( $wp_version, $version, $operator );
		}

		/**
		 * This function returns a boolean result determining if our Note plugin exists and is active.
		 * It can be used reliably after the plugins_loaded action, once the PHP class is included.
		 *
		 * @return Boolean
		 */
		public static function is_note_active() {
			return class_exists( 'Note' );
		}

		// TODO: CSS helper function

		/**
		 * This function determines whether or not Conductor has been enabled on the current page.
		 * It also sets up the content layout and content layout data for Conductor::get_conductor_content_layout()
		 * and Conductor::get_conductor_content_layout_data().
		 *
		 * $ignore_default_layout bool, should the default Conductor content layout ('default') be ignored
		 *
		 * @return bool
		 */
		public static function is_conductor( $ignore_default_layout = true ) {
			// Return Conductor status based off of $ignore_default_layout (if we have a content layout)
			if ( ! $ignore_default_layout && self::get_conductor_content_layout() )
				return true;

			// Return Conductor status if it's already been checked and the current request is a Conductor request
			if ( self::$is_conductor !== null )
				return self::$is_conductor;

			// Load Conductor options
			$conductor_options = Conductor_Options::get_options();

			// First check the option
			if ( ! $conductor_options['enabled'] )
				return self::$is_conductor = false;

			$conductor_content_layout = false;

			// Verify Conductor content layouts exist in Conductor Options
			if ( ! empty( $conductor_options['content_layouts'] ) && is_array( $conductor_options['content_layouts'] ) ) {
				// Grab public post types as objects
				self::$public_post_types = get_post_types( array( 'public' => true ), 'objects' );

				// Public Post Types (further filtered to remove those that are not attachments)
				self::$public_post_types_without_attachments = wp_list_filter( self::$public_post_types, array( 'name' => 'attachment' ), 'NOT' );


				// Static Front Page (not blog archive)
				if ( is_front_page() && ! is_home() )
					$conductor_content_layout = array_filter( $conductor_options['content_layouts'], array( get_class(), 'array_filter_content_layout_field_id_is_front_page' ) );

				// Home (Blog)
				if ( is_home() )
					$conductor_content_layout = array_filter( $conductor_options['content_layouts'], array( get_class(), 'array_filter_content_layout_field_id_is_home' ) );

				// Category Archive
				if ( is_category() )
					$conductor_content_layout = array_filter( $conductor_options['content_layouts'], array( get_class(), 'array_filter_content_layout_field_id_is_category' ) );

				// Post Type Archive
				if ( is_post_type_archive() )
					$conductor_content_layout = array_filter( $conductor_options['content_layouts'], array( get_class(), 'array_filter_content_layout_field_id_is_post_type_archive' ) );

				// Singular
				if ( ! is_front_page() && is_singular( array_keys( self::$public_post_types_without_attachments ) ) )
					$conductor_content_layout = array_filter( $conductor_options['content_layouts'], array( get_class(), 'array_filter_content_layout_field_id_is_singular' ) );
			}

			// Make sure we have just the content layout values (array_filter returns an array)
			if ( ! empty( $conductor_content_layout ) && is_array( $conductor_content_layout ) ) {
				$conductor_content_layout = array_values( $conductor_content_layout ); // Reset the key(s)
				$conductor_content_layout = $conductor_content_layout[0];
				$conductor_content_layouts = Conductor_Options::get_content_layouts(); // Grab all registered Conductor content layouts
				$conductor_content_layout_data = ( isset( $conductor_content_layouts[$conductor_content_layout['value']] ) ) ? $conductor_content_layouts[$conductor_content_layout['value']] : array();

				if ( ( isset( $conductor_content_layout['value'] ) && ! empty( $conductor_content_layout['value'] ) ) || ! $ignore_default_layout ) {
					self::$conductor_content_layout = $conductor_content_layout; // Content layout
					self::$conductor_content_layout_data = $conductor_content_layout_data; // Content layout data
					self::$is_conductor = true; // Conductor
				}
				else {
					self::$conductor_content_layout = $conductor_content_layout; // Content layout
					self::$conductor_content_layout_data = $conductor_content_layout_data; // Content layout data
					self::$is_conductor = false; // Conductor
				}
			}
			// Otherwise there is no Conductor content layout for this page
			else
				self::$is_conductor = false;

			// Allow the conditional to be filtered if necessary
			self::$is_conductor = apply_filters( 'conductor_is_conductor', self::$is_conductor, $conductor_content_layout, $conductor_options, $ignore_default_layout );

			return self::$is_conductor;
		}

		/**
		 * This function returns the current Conductor content layout.
		 */
		public static function get_conductor_content_layout() {
			self::$conductor_content_layout = apply_filters( 'conductor_content_layout', self::$conductor_content_layout );

			return self::$conductor_content_layout;
		}

		/**
		 * This function returns the current Conductor content layout data. Alternatively
		 * a content layout can be passed as a parameter and the data
		 * for that layout will be returned.
		 */
		public static function get_conductor_content_layout_data( $content_layout = false ) {
			// Return the current content layout if there wasn't one passed in parameters
			if ( empty( $content_layout ) ) {
				self::$conductor_content_layout_data = apply_filters( 'conductor_content_layout_data', self::$conductor_content_layout_data );
				return self::$conductor_content_layout_data;
			}

			// Otherwise find the content layout data for the layout in parameters
			$content_layout_value = ( is_array( $content_layout ) && isset( $content_layout['value'] ) ) ? $content_layout['value'] : $content_layout;
			$conductor_content_layouts = Conductor_Options::get_content_layouts(); // Grab all registered Conductor content layouts
			$conductor_content_layout_data = ( ! empty( $content_layout_value ) && isset( $conductor_content_layouts[$content_layout_value] ) ) ? $conductor_content_layouts[$content_layout_value] : array();

			$conductor_content_layout_data = apply_filters( 'conductor_content_layout_data', $conductor_content_layout_data );

			return $conductor_content_layout_data;
		}

		/**
		 * This function returns a Conductor content layout sidebar ID prefix.
		 */
		public static function get_conductor_content_layout_sidebar_id_prefix( $sidebar, $content_layout = false ) {
			$content_layout = ( is_array( $content_layout ) ) ? $content_layout : self::get_conductor_content_layout();

			return apply_filters( 'conductor_content_layout_sidebar_id_prefix', 'conductor-' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . '-', $sidebar, $content_layout );
		}

		/**
		 * This function returns the Conductor sidebar suffix.
		 */
		public static function get_conductor_content_layout_sidebar_id_suffix( $sidebar, $content_layout = false ) {
			$content_layout = ( is_array( $content_layout ) ) ? $content_layout : self::get_conductor_content_layout();

			return apply_filters( 'conductor_content_layout_sidebar_id_suffix', '-sidebar', $sidebar, $content_layout );
		}

		/**
		 * This function returns a Conductor content layout sidebar ID.
		 */
		public static function get_conductor_content_layout_sidebar_id( $sidebar, $content_layout = false ) {
			$content_layout = ( is_array( $content_layout ) ) ? $content_layout : self::get_conductor_content_layout();
			$prefix = self::get_conductor_content_layout_sidebar_id_prefix( $sidebar, $content_layout ); // Sidebar Prefix
			$suffix = self::get_conductor_content_layout_sidebar_id_suffix( $sidebar, $content_layout ); // Sidebar Suffix

			return apply_filters( 'conductor_content_layout_sidebar_id', $prefix . $sidebar . $suffix, $sidebar, $content_layout, $prefix, $suffix );
		}


		// TODO: Create a is_rest_api_enabled() function with a filter and utilize this function in all add-on logic and throughout Conductor


		/**********************
		 * Internal Functions *
		 **********************/

		/**
		 * This function is used as a callback for array_filter() to determine whether or not a front page
		 * Conductor content layout has been selected.
		 */
		public static function array_filter_content_layout_field_id_is_front_page( $var ) {
			return ( $var['field_type'] === 'built-in' && $var['field_id'] === 'front_page' );
		}

		/**
		 * This function is used as a callback for array_filter() to determine whether or not a home (blog)
		 * Conductor content layout has been selected.
		 */
		public static function array_filter_content_layout_field_id_is_home( $var ) {
			return ( $var['field_type'] === 'built-in' && $var['field_id'] === 'home' );
		}

		/**
		 * This function is used as a callback for array_filter() to determine whether or not a category archive
		 * Conductor content layout has been selected.
		 */
		public static function array_filter_content_layout_field_id_is_category( $var ) {
			$category = get_queried_object();

			return ( $var['field_type'] === 'category' && ( int ) $var['field_id'] === $category->term_id );
		}

		/**
		 * This function is used as a callback for array_filter() to determine whether or not a post type archive
		 * Conductor content layout has been selected.
		 */
		public static function array_filter_content_layout_field_id_is_post_type_archive( $var ) {
			$post_type = get_queried_object();

			return ( $var['field_type'] === 'post-type' && $var['field_id'] === $post_type->name );
		}

		/**
		 * This function is used as a callback for array_filter() to determine whether or not a singular
		 * Conductor content layout has been selected.
		 */
		public static function array_filter_content_layout_field_id_is_singular( $var ) {
			$singular = get_queried_object();

			return ( isset( self::$public_post_types_without_attachments[$singular->post_type] ) && $var['field_type'] === $singular->post_type && ( int ) $var['field_id'] === $singular->ID );
		}

		/**
		 * This function is used as a callback for array_filter() to determine whether or not a single post
		 * Conductor content layout has been selected.
		 *
		 * TODO: Depreciate in a future version?
		 */
		public static function array_filter_content_layout_field_id_is_post( $var ) {
			$post = get_queried_object();

			return ( $var['field_type'] === 'post' && ( int ) $var['field_id'] === $post->ID );
		}

		/**
		 * This function is used as a callback for array_filter() to determine whether or not a single page
		 * Conductor content layout has been selected.
		 *
		 * TODO: Depreciate in a future version?
		 */
		public static function array_filter_content_layout_field_id_is_page( $var ) {
			$page = get_queried_object();

			return ( $var['field_type'] === 'page' && ( int ) $var['field_id'] === $page->ID );
		}
	}

	/**
	 * Create an instance of the Conductor class.
	 */
	function Conduct() {
		return Conductor::instance();
	}

	Conduct(); // Conduct your content!
}