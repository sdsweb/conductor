<?php
/**
 * Conductor Admin License Options Views (controller)
 *
 * @class Conductor_Admin_License_Options_Views
 * @author Slocum Studio
 * @version 1.4.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Admin_License_Options_Views' ) ) {
	final class Conductor_Admin_License_Options_Views {
		/**
		 * @var string
		 */
		public $version = '1.4.0';

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
			self::$options = Conductor_Options::get_options( Conductor_Options::$option_name . '_license' );
		}


		/**
		 * This function renders the Conductor License Settings Section.
		 */
		public static function conductor_license_section() {
			include_once 'views/html-conductor-options-license-section.php';
		}

		/**
		 * This function renders the Conductor License Settings Field.
		 */
		public static function conductor_license_field() {
			include_once 'views/html-conductor-options-license-field.php';
		}

		/**
		 * This function renders the Conductor License options page.
		 */
		public static function render() {
			// Render the main view
			include_once 'views/html-conductor-options-license.php';
		}
	}

	/**
	 * Create an instance of the Conductor_Admin_License_Options_Views class.
	 */
	function Conduct_Admin_License_Options_Views() {
		return Conductor_Admin_License_Options_Views::instance();
	}

	Conduct_Admin_License_Options_Views(); // Conduct your content!
}