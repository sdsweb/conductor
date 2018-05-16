<?php
/**
 * Conductor Options
 *
 * @class Conductor_Options
 * @author Slocum Studio
 * @version 1.5.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Options' ) ) {
	final class Conductor_Options {
		/**
		 * @var string
		 */
		public $version = '1.5.0';

		/**
		 * @var string
		 */
		public static $option_name = 'conductor';

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
		 * This function returns the current option values for Conductor.
		 */
		public static function get_options( $option_name = false ) {
			// If an option name is passed, return that value otherwise default to Conductor options
			if ( $option_name )
				return wp_parse_args( get_option( $option_name ), Conductor_Options::get_option_defaults( $option_name ) );

			return wp_parse_args( get_option( Conductor_Options::$option_name ), Conductor_Options::get_option_defaults() );
		}

		/**
		 * This function returns the default option values for Conductor.
		 */
		public static function get_option_defaults( $option_name = false ) {
			$defaults = false;

			// If we have an option name
			if ( $option_name ) {
				// Switch based on the option name
				switch ( $option_name ) {
					// Conductor License
					case Conductor_Options::$option_name . '_license':
						$defaults = array(
							'key' => false,
							'status' => false
						);
					break;
				}

				$defaults = apply_filters( 'conductor_options_defaults_' . $option_name, $defaults, $option_name );
			}
			// Otherwise we don't have an option name
			else {
				$defaults = array(
					// Enabled
					'enabled' => true,
					// Content Layouts
					'content_layouts' => array(),
					// REST API
					'rest' => array(
						// Enabled
						'enabled' => true
					),
					// Uninstall
					'uninstall' => array(
						'data' => true // Should Conductor data be removed upon uninstall?
					)
				);
			}

			return apply_filters( 'conductor_options_defaults', $defaults, $option_name );
		}


		/**
		 * This function registers all content layouts available in Conductor.
		 */
		// TODO: Allow content layouts to be created from a template file using WordPress header logic - @see https://codex.wordpress.org/File_Header
		public static function get_content_layouts() {
			$content_layouts = array(
				'default' => array( // Name used in saved option
					'label' => __( 'Default', 'conductor' ), // Label on options panel
					'preview' => '<div class="cols cols-1 cols-default"><div class="col col-content" title="%1$s"><span class="label">%1$s</span></div></div>', // Preview on options panel (required; %1$s is replaced with values below on options panel if specified)
					'preview_values' => array( __( 'Disable', 'conductor' ) ),
					'default' => true
				),
				'cols-1' => array( // Full Width
					'label' => __( 'Full Width', 'conductor' ),
					'preview' => '<div class="cols cols-1"><div class="col col-content">&nbsp;</div></div>',
				),
				'cols-2' => array( // Content Left, Primary Sidebar Right
					'label' => __( 'Content Left', 'conductor' ),
					'preview' => '<div class="cols cols-2"><div class="col col-content">&nbsp;</div><div class="col col-sidebar">&nbsp;</div></div>'
				),
				'cols-2-r' => array( // Content Right, Primary Sidebar Left
					'label' => __( 'Content Right', 'conductor' ),
					'preview' => '<div class="cols cols-2 cols-2-r"><div class="col col-sidebar">&nbsp;</div><div class="col col-content">&nbsp;</div></div>'
				),
				'cols-3' => array( // Content Left, Primary Sidebar Middle, Secondary Sidebar Right
					'label' => __( 'Content, Sidebar, Sidebar', 'conductor' ),
					'preview' => '<div class="cols-3"><div class="col col-content">&nbsp;</div><div class="col col-sidebar">&nbsp;</div><div class="col col-sidebar col-sidebar-secondary">&nbsp;</div></div>'
				),
				'cols-3-m' => array( // Primary Sidebar Left, Content Middle, Secondary Sidebar Right
					'label' => __( 'Sidebar, Content, Sidebar', 'conductor' ),
					'preview' => '<div class="cols cols-3 cols-3-m"><div class="col col-sidebar">&nbsp;</div><div class="col col-content">&nbsp;</div><div class="col col-sidebar col-sidebar-secondary">&nbsp;</div></div>'
				),
				'cols-3-r' => array( // Primary Sidebar Left, Secondary Sidebar Middle, Content Right
					'label' => __( 'Sidebar, Sidebar, Content', 'conductor' ),
					'preview' => '<div class="cols cols-3 cols-3-r"><div class="col col-sidebar">&nbsp;</div><div class="col col-sidebar col-sidebar-secondary">&nbsp;</div><div class="col col-content">&nbsp;</div></div>'
				)
			);

			return apply_filters( 'conductor_content_layouts', $content_layouts );
		}
	}

	/**
	 * Create an instance of the Conductor_Options class.
	 */
	function Conduct_Options() {
		return Conductor_Options::instance();
	}

	Conduct_Options(); // Conduct your content!
}