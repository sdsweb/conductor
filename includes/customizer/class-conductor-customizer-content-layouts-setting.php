<?php
/**
 * Conductor Customizer Content Layouts Setting (Customizer functionality)
 *
 * @class Conductor_Customizer_Content_Layouts_Setting
 * @author Slocum Studio
 * @version 1.4.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

// Make sure the Customize Image Control class exists
if ( ! class_exists( 'WP_Customize_Control' ) )
	exit;

if ( ! class_exists( 'Conductor_Customizer_Content_Layouts_Setting' ) ) {
	final class Conductor_Customizer_Content_Layouts_Setting extends WP_Customize_Setting {
		/**
		 * @var string
		 */
		public $version = '1.4.0';

		/**
		 * @var array
		 */
		public $initial_option_value = array();

		/**
		 * @var array
		 */
		public $original_post_value = array();

		/**
		 * @var array
		 */
		public $conductor_post_value = array();

		/**
		 * @var Boolean
		 */
		public $is_customize_save = false;

		/**
		 * Constructor
		 */
		function __construct( $manager, $id, $args ) {
			// Call the parent constructor here
			parent::__construct( $manager, $id, $args );

			// WordPress 4.4+
			if ( Conductor::wp_version_compare( '4.4' ) )
				// Multidimensional aggregated
				if ( $this->is_multidimensional_aggregated )
					// Store a reference the original value for comparison afterwards
					$this->initial_option_value = self::$aggregated_multidimensionals[$this->type][$this->id_data['base']]['root_value'];

			// Hooks
			add_action( 'customize_save', array( $this, 'customize_save' ) ); // Customize Save
			add_action( 'customize_save_after', array( $this, 'customize_save_after' ) );// Customize Save After
		}

		/**
		 * This function sanitizes and configures the post_value to ensure content layouts are set up
		 * correctly for Conductor Options on the Customizer.
		 */
		public function conductor_post_value( $original, $default = null, $value = false ) {
			$value = ( $value ) ? $value : $this->post_value();

			// First make sure the post_value() is not the same as the option
			if ( is_array( $value ) && ! $this->array_diff_assoc_recursive( ( array ) $this->multidimensional_get( $original, $this->id_data['keys'] ), $value ) )
				return $value;

			// Sanitize and configure the new value, pass it through Conductor's sanitize_option function
			$conductor_options = Conductor_Admin_Options::sanitize_option( $this->multidimensional_replace( $original, $this->id_data['keys'], $value ) );

			// Return the new value to the _preview_filter() callback
			return $this->multidimensional_get( $conductor_options, $this->id_data['keys'] );
		}

		/**
		 * This function is used to filter the previewer options to ensure content layouts are set up
		 * correctly for Conductor Options on the Customizer.
		 */
		public function _preview_filter( $original ) {
			// Grab the $_POSTed value
			$post_value = $this->post_value();

			// If we don't have a $_POSTed value just return the original option value
			if ( empty( $post_value ) )
				return $original;

			// Otherwise we have a post value, replace data in the option
			$_preview_filter = $this->multidimensional_replace( $original, $this->id_data['keys'], $this->conductor_post_value( $original ) );

			return $_preview_filter;
		}

		/**
		 * This function sanitizes input data from the Customizer
		 */
		public function sanitize( $value ) {
			// WordPress 4.4+
			if ( Conductor::wp_version_compare( '4.4' ) ) {
				// Since the sanitize function runs multiple times, ensure this logic only fires once (first time)
				if ( empty( $this->conductor_post_value ) ) {
					// Call the parent sanitize value first
					$value = parent::sanitize( $value );

					// Store this value
					$this->original_post_value = $value;

					// Grab the Conductor post value
					$value = $this->conductor_post_value( $this->initial_option_value, array(), $value );

					// Store the new value so we can return it later
					$this->conductor_post_value = $value;
				}
				// Otherwise use the cached value
				else
					$value = $this->conductor_post_value;

				// If this is a Customizer save
				if ( $this->is_customize_save )
					// Use the original value in this case; sanitize_option() function in Conductor_Admin_Options will be called here
					$value = $this->original_post_value;
			}

			return $value;
		}


		/*********
		 * Hooks *
		 *********/

		/**
		 * This function sets a flag during saving of Customizer settings.
		 */
		public function customize_save() {
			// Set the flag
			$this->is_customize_save = true;
		}

		/**
		 * This function resets a flag during saving of Customizer settings.
		 */
		public function customize_save_after() {
			// Reset the flag
			$this->is_customize_save = false;
		}


		/**********************
		 * Internal Functions *
		 **********************/

		/**
		 * This function is used to perform an array_diff on associative arrays recursively.
		 * @see http://www.php.net/manual/en/function.array-diff-assoc.php#111675
		 */
		protected function array_diff_assoc_recursive( $array1, $array2 ) {
			$difference = array();

			// If the first array is empty and the second isn't empty
			if ( empty( $array1 ) && ! empty( $array2 ) )
				// Return the second array as that's the difference
				return $array2;
			// Otherwise if the second array is empty and the first isn't empty
			else if ( empty( $array2 ) && ! empty( $array1 ) )
				// Return the first array as that's the difference
				return $array1;

			foreach ( $array1 as $key => $value ) {
				if ( is_array( $value ) ) {
					if ( ! isset( $array2[$key] ) || ! is_array( $array2[$key] ) ) {
						$difference[$key] = $value;
					}
					else {
						$new_diff = $this->array_diff_assoc_recursive( $value, $array2[$key] );
						if ( ! empty( $new_diff ) )
							$difference[$key] = $new_diff;
					}
				}
				else if ( ! array_key_exists( $key, $array2 ) || $array2[$key] !== $value ) {
					$difference[$key] = $value;
				}
			}

			return $difference;
		}
	}
}