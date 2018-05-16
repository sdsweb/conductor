<?php
/**
 * Conductor Admin Views (controller)
 *
 * @class Conductor_Admin_Options_Views
 * @author Slocum Studio
 * @version 1.5.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if( ! class_exists( 'Conductor_Admin_Options_Views' ) ) {
	final class Conductor_Admin_Options_Views {
		/**
		 * @var string
		 */
		public $version = '1.5.0';

		/**
		 * @var array
		 */
		public static $options = false;
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
			// Load options
			self::$options = Conductor_Options::get_options();
		}


		/**
		 * This function renders the Conductor Enable Settings Section.
		 */
		public static function conductor_enable_section() {
			include_once 'views/html-conductor-options-enable-section.php';
		}

		/**
		 * This function renders the Conductor Enable Settings Field.
		 */
		public static function conductor_enable_field() {
			include_once 'views/html-conductor-options-enable-field.php';
		}
		
		/**
		 * This function renders the Conductor REST API Enable Settings Section.
		 */
		public static function conductor_rest_enable_section() {
			include_once 'views/html-conductor-options-rest-api-enable-section.php';
		}

		/**
		 * This function renders the Conductor REST API Enable Settings Field.
		 */
		public static function conductor_rest_enable_field() {
			include_once 'views/html-conductor-options-rest-api-enable-field.php';
		}

		/**
		 * This function renders the Conductor Uninstall Settings Section.
		 */
		public static function conductor_uninstall_section() {
			include_once 'views/html-conductor-options-uninstall-section.php';
		}

		/**
		 * This function renders the Conductor Uninstall Data Settings Field.
		 */
		public static function conductor_uninstall_data_field() {
			include_once 'views/html-conductor-options-uninstall-data-field.php';
		}

		/**
		 * This function renders the Conductor Content Layouts Settings Section.
		 */
		public static function conductor_content_layouts_section() {
			include_once 'views/html-conductor-options-content-layouts-section.php';
		}

		/**
		 * This function renders the Conductor Content Layouts Settings Field.
		 */
		public static function conductor_content_layouts_field() {
			include_once 'views/html-conductor-options-content-layouts-field.php';
		}

		/**
		 * This function renders the Conductor options page.
		 */
		public static function render() {
			// Render the main view
			include_once 'views/html-conductor-options.php';
		}
	}

	/**
	 * Create an instance of the Conductor_Admin_Options_Views class.
	 */
	function Conduct_Admin_Options_Views() {
		return Conductor_Admin_Options_Views::instance();
	}

	Conduct_Admin_Options_Views(); // Conduct your content!
}