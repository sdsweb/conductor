<?php
/**
 * Conductor Admin
 *
 * @class Conductor_Admin
 * @author Slocum Studio
 * @version 1.4.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Admin' ) ) {
	final class Conductor_Admin {
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
			// Load required assets
			$this->includes();
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		private function includes() {
			include_once 'class-conductor-admin-options.php'; // Conductor Admin Options
			include_once 'class-conductor-admin-license-options.php'; // Conductor Admin License Options
			include_once 'class-conductor-admin-add-ons.php'; // Conductor Admin Add-Ons
			include_once 'class-conductor-admin-help.php'; // Conductor Admin Help
		}
	}

	/**
	 * Create an instance of the Conductor_Admin class.
	 */
	function Conduct_Admin() {
		return Conductor_Admin::instance();
	}

	Conduct_Admin(); // Conduct your content!
}