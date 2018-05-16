<?php
/**
 * Conductor Scripts & Styles
 *
 * @class Conductor_Options
 * @author Slocum Studio
 * @version 1.5.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Scripts_Styles' ) ) {
	final class Conductor_Scripts_Styles {
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
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) ); // Enqueue Scripts & Styles (front-end)
		}


		/**
		 * This function enqueues scripts & styles on the front-end for Conductor.
		 */
		// TODO: Minify/consolidate all scripts
		public function wp_enqueue_scripts() {
			// Conductor Content Layout Styles
			if ( Conductor::is_conductor() )
				wp_enqueue_style( 'conductor', Conductor::plugin_url() . '/assets/css/conductor.css', false, Conductor::$version );

			// Conductor Widget
			if ( function_exists( 'Conduct_Widget' ) ) {
				// Grab the Conductor Widget instance
				$conductor_widget = Conduct_Widget();

				// If at least one Conductor Widget is active
				if ( is_active_widget( false, false, $conductor_widget->id_base ) ) {
					// Conductor Flexbox
					wp_enqueue_style( 'conductor-flexbox', Conductor::plugin_url() . '/assets/css/conductor-flexbox.css', false, Conductor::$version );

					// Conductor Widget
					wp_enqueue_style( 'conductor-widget', Conductor::plugin_url() . '/assets/css/widgets/conductor-widget.css', array( 'conductor-flexbox', 'dashicons' ), Conductor::$version );
				}
			}
		}
	}

	/**
	 * Create an instance of the Conductor_Scripts_Styles class.
	 */
	function Conduct_Scripts_Styles() {
		return Conductor_Scripts_Styles::instance();
	}

	Conduct_Scripts_Styles(); // Conduct your content!
}