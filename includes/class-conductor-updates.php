<?php
/**
 * Conductor Updates
 *
 * @class Conductor_Updates
 * @author Slocum Studio
 * @version 1.5.4
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Updates' ) ) {
	final class Conductor_Updates {
		/**
		 * @var string
		 */
		public $version = '1.5.4';

		/**
		 * @var string, URL
		 */
		public $url = 'https://conductorplugin.com';

		/**
		 * @var string, Download name
		 */
		public $name = 'Conductor';

		/**
		 * @var string, Author name
		 */
		public $author = 'Slocum Studio';

		/**
		 * @var mixed, Plugin file reference
		 */
		public $plugin_file;

		/**
		 * @var array, Extra API data to send
		 */
		public $api_data = array();

		/**
		 * @var Conductor_Plugin_Updater, Instance of the EDD Software Licensing Plugin Updater class
		 */
		protected $updater;

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
			// Load required assets
			$this->includes();

			/*
			 * Defaults
			 */

			$this->version = $this->get_conductor_version(); // Set the version
			$this->plugin_file = $this->get_conductor_plugin_file(); // Set the plugin file reference
			$this->author = sprintf( _x( '%1$s', 'Plugin author', 'conductor' ), $this->get_author() ); // Translate the author string

			// Loop through args and set the values
			if ( ! empty( $args ) && is_array( $args ) ) {
				$keys = array_keys( get_object_vars( $this ) );

				foreach ( $keys as $key )
					if ( isset( $args[$key] ) )
						$this->$key = $args[$key];
			}

			// Setup API data
			$this->api_data = array(
				'conductor_version' => Conductor::$version
			);

			// Hooks
			add_action( 'admin_init', array( $this, 'admin_init' ) ); // Check for updates
		}

		/**
		 * Include required core files used in admin and on the front-end.
		 */
		private function includes() {
			if ( ! class_exists( 'Conductor_Plugin_Updater' ) )
				include_once 'class-conductor-plugin-updater.php'; // EDD Software Licensing Plugin Updater
		}

		/**
		 * This function checks for plugin updates.
		 */
		public function admin_init() {
			// Initialize the updater
			$this->init_updater();
		}

		/**
		 * This function initializes the updater.
		 */
		public function init_updater() {
			// Fetch the license
			$license = Conductor_Options::get_options( Conductor_Options::$option_name . '_license' );

			// API Data
			$api_data = array(
				'version' => $this->get_version(),
				'license' => $license['key'],
				'item_name' => $this->get_name(),
				'author' => $this->get_author()
			);
			$api_data = ( ! empty( $this->api_data ) ) ? array_merge( $this->api_data, $api_data ) : $api_data;

			// Create and instance of the plugin updater
			$this->updater = new Conductor_Plugin_Updater( $this->get_url(), $this->get_plugin_file(), $api_data );
		}

		/**
		 * This function returns the update url.
		 */
		public function get_name() {
			return $this->name;
		}

		/**
		 * This function returns the update name.
		 */
		public function get_url() {
			return $this->url;
		}

		/**
		 * This function returns the update version.
		 *
		 * @since 1.1.0
		 */
		public function get_version() {
			return $this->version;
		}

		/**
		 * This function returns the Conductor version.
		 *
		 * @since 1.1.0
		 */
		public function get_conductor_version() {
			return Conductor::$version;
		}

		/**
		 * This function returns the author.
		 *
		 * @since 1.1.0
		 */
		public function get_author() {
			return $this->author;
		}

		/**
		 * This function returns a reference to the plugin file.
		 *
		 * @since 1.1.0
		 */
		public function get_plugin_file() {
			return $this->plugin_file;
		}

		/**
		 * This function returns a reference to the Conductor plugin file.
		 *
		 * @since 1.1.0
		 */
		public function get_conductor_plugin_file() {
			return Conductor::plugin_file();
		}
	}

	/**
	 * Create an instance of the Conductor_Updates class.
	 */
	function Conduct_Updates() {
		return Conductor_Updates::instance();
	}

	Conduct_Updates(); // Conduct your content!
}