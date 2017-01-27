<?php
/**
 * Conductor Install
 *
 * @class Conductor_Admin_Install
 * @author Slocum Studio
 * @version 1.4.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Admin_Install' ) ) {
	final class Conductor_Admin_Install {
		/**
		 * @var string
		 */
		public $version = '1.4.0';

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
			add_action( 'admin_init', array( $this, 'admin_init' ) ); // Add Conductor option
		}

		/**
		 * This function creates the Conductor option in the database upon install.
		 */
		public function admin_init() {
			add_option( Conductor_Options::$option_name, Conductor_Options::get_option_defaults() );
		}
	}

	/**
	 * Create an instance of the Conductor_Admin_Install class.
	 */
	function Conduct_Admin_Install() {
		return Conductor_Admin_Install::instance();
	}

	Conduct_Admin_Install(); // Conduct your content!
}