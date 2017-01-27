<?php
/**
 * Conductor Admin Help Views (controller)
 *
 * @class Conductor_Admin_Help_Views
 * @author Slocum Studio
 * @version 1.4.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Admin_Help_Views' ) ) {
	final class Conductor_Admin_Help_Views {
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
		}


		/**
		 * This function renders the Conductor Help options page.
		 */
		public static function render() {
			// Render the main view
			include_once 'views/html-conductor-help.php';
		}
	}

	/**
	 * Create an instance of the Conductor_Admin_Help_Views class.
	 */
	function Conduct_Admin_Help_Views() {
		return Conductor_Admin_Help_Views::instance();
	}

	Conduct_Admin_Help_Views(); // Conduct your content!
}