<?php
/**
 * Conductor Sidebars
 *
 * @class Conductor_Options
 * @author Slocum Studio
 * @version 1.4.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Sidebars' ) ) {
	final class Conductor_Sidebars {
		/**
		 * @var string
		 */
		public $version = '1.4.0';

		/**
		 * @var array
		 */
		protected $_sidebars_widgets = array();

		/**
		 * @var array
		 */
		public $registered_sidebars = array();

		/**
		 * @var array
		 */
		public $registered_content_layout_sidebars = array();

		/**
		 * @var array
		 */
		public $registered_sidebars_args = array();

		/**
		 * @var array
		 */
		public $default_widgets = array();

		/**
		 * @var array
		 */
		public $sidebar_has_widgets = array();

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
			//$this->includes();

			// Hooks
			add_action( 'widgets_init', array( $this, 'widgets_init' ) ); // Register Sidebars
			add_action( 'after_switch_theme', array( $this, 'after_switch_theme' ), 1, 2 ); // After Switch Theme (keep Conductor widgets)
			add_action( 'updated_option', array( $this, 'updated_option' ), 10, 3 ); // Conductor option updates
		}

		/**
		 * Include required core files used in admin and on the front-end.
		 */
		private function includes() {
		}

		/**
		 * This function registers sidebars based on the Conductor content layouts that have been created.
		 */
		public static function widgets_init() {
			$conductor_options = Conductor_Options::get_options();
			$content_layouts = $conductor_options['content_layouts'];

			// Verify that there are content layouts
			if ( ! empty( $content_layouts ) )
				// Loop through content layouts
				foreach ( $content_layouts as $content_layout )
					// A content layout has been selected
					if ( ! empty( $content_layout['value'] ) )
						// Register Content, Primary, and Secondary sidebars
						self::register_conductor_sidebars( $content_layout, $content_layouts );
		}

		/**
		 * This function fires when the user switches their theme and attempts to carry over Conductor sidebars/widgets
		 * to the new theme.
		 */
		public function after_switch_theme( $old_theme_name, $old_theme = false ) {
			// $old_theme will be empty if the old theme does not exist
			if ( ! empty( $old_theme ) ) {
				$this->_sidebars_widgets = get_option( 'sidebars_widgets', array() ); // Get the current sidebar widgets
				add_filter( 'pre_update_option_sidebars_widgets', array( $this, 'pre_update_option_sidebars_widgets' ), 10, 2 );
			}
		}

		/**
		 * This function filters the sidebars_widgets option before it is saved in the database.
		 */
		// TODO: Should we look for Conductor widgets elsewhere? (i.e. Conductor widget exists in "footer" sidebar, should we carry that over if the new theme has the same "footer" sidebar?)
		public function pre_update_option_sidebars_widgets( $value, $old_value ) {
			// Make sure we have an old value that is an array
			if ( ! empty( $old_value ) && is_array( $old_value ) ) {
				global $wp_registered_sidebars; // Contains the most up-to-date list of registered sidebars at this point

				$conductor_sidebars = $this->find_conductor_sidebar_ids( $wp_registered_sidebars );
				$wp_inactive_widgets = $value['wp_inactive_widgets'];
				$wp_orphaned_widgets = $this->find_orphaned_widgets_sidebar_ids( $value );

				// If we have Conductor sidebars
				if ( ! empty( $conductor_sidebars ) ) {
					// Loop through each Conductor sidebar
					foreach ( $conductor_sidebars as $key ) {
						// Sidebar existed in previous theme
						if ( isset( $old_value[$key] ) ) {
							$sidebar_widgets = $old_value[$key];

							// Loop through the widgets
							foreach ( $sidebar_widgets as $widget_id => $widget ) {
								// Determine if any of the widgets are "inactive"
								foreach ( $wp_inactive_widgets as $inactive_widget_id => $inactive_widget ) {
									if ( $inactive_widget === $widget ) {
										unset( $value['wp_inactive_widgets'][$inactive_widget_id] );
										break; // We don't need to loop any further
									}
								}

								// Determine if any of the widgets are "orphaned"
								foreach ( $wp_orphaned_widgets as $orphaned_sidebar_id => $orphaned_widget )
									if ( is_array( $orphaned_widget ) && ! empty( $orphaned_widget ) && in_array( $widget, $orphaned_widget ) ) {
										unset( $value[$orphaned_sidebar_id] );
										break; // We don't need to loop any further
									}
							}

							// Carry this sidebar to the new theme
							$value[$key] = $sidebar_widgets;
						}
					}

					// Reset the array keys for inactive widgets
					$value['wp_inactive_widgets'] = array_values( $value['wp_inactive_widgets'] );
				}
			}

			return $value;
		}

		/**
		 * This function looks to see when the Conductor option has been updated and adds default
		 * Conductor Widgets to newly registered Conductor content layout sidebars.
		 */
		// TODO: This logic only needs to fire outside of the Customizer and not upon saving in the Customizer
		public function updated_option( $option, $old_value, $value ) {
			// Bail if this is not Conductor, no content layouts exist, or there are no registered default widgets
			if ( $option !== Conductor_Options::$option_name || ( ! isset( $value['content_layouts'] ) || empty( $value['content_layouts'] ) ) || empty( $this->default_widgets ) )
				return;

			// Loop through content layouts
			foreach ( $value['content_layouts'] as $id => $content_layout ) {
				// Loop through default widgets to see if we have a match
				foreach ( $this->default_widgets as $content_layout_id => $default_widgets ) {
					// Loop through the default widgets within this content layout to see if there are any matches
					foreach ( $default_widgets as $default_widget ) {
						// If we have a content layout match, move forward
						if ( $this->is_conductor_content_layout_match( $default_widget, $content_layout, $content_layout_id ) ) {
							// Setup the content layout data
							$conductor_content_layout = array(
								// Flag to determine whether or not this is a new content layout
								'new' => false,
								// Reference to the old value data
								'old_value' => array(),
								// Reference to the new value data
								'new_value' => array()
							);

							// Check to see if this layout exists at the same index first
							if ( isset( $old_value['content_layouts'][$id] ) && ! empty( $old_value['content_layouts'][$id] ) && $this->validate_conductor_content_layout_data( $content_layout, $old_value['content_layouts'][$id] ) ) {
								// Store a reference to the old value data
								$conductor_content_layout['old_value'] = $old_value['content_layouts'][$id];

								// Store a reference to the new value data
								$conductor_content_layout['new_value'] = $content_layout;
							}
							// Otherwise we have to look for it in the old value data
							else {
								// Loop through previous content layouts
								foreach ( $old_value['content_layouts'] as $old_content_layout_id => $old_content_layout )
									// If we have a match (field ID and type)
									if ( $this->validate_conductor_content_layout_data( $content_layout, $old_content_layout ) ) {
										// Store a reference to the old value data
										$conductor_content_layout['old_value'] = $old_content_layout;

										// Store a reference to the new value data
										$conductor_content_layout['new_value'] = $content_layout;

										// Break out of this loop
										break;
									}

								// If we don't have an old value reference by now, this must be a new layout
								if ( empty( $conductor_content_layout['old_value'] ) ) {
									// Store a reference to the new value data
									$conductor_content_layout['new_value'] = $content_layout;

									// Set the new flag
									$conductor_content_layout['new'] = true;
								}
							}

							// If we have content layout data, we likely have sidebars to register
							if ( ! empty( $conductor_content_layout['new_value'] ) )
								// New content layout or an existing layout that has switched to the matching content layout
								if ( $conductor_content_layout['new'] || ( $conductor_content_layout['new_value']['value'] !== $conductor_content_layout['old_value']['value'] ) )
									// Add default widgets to this Conductor content layout
									self::add_widgets_to_content_layout_sidebars( $conductor_content_layout['new_value'], $value['content_layouts'], $default_widgets );

							// Break out of the loop for widgets
							break;
						}
					}
				}
			}
		}

		/**************************
		 * Internal Functionality *
		 **************************/

		/**
		 * This function registers Conductor sidebars based on parameters. The last parameter can be used to force
		 * the registration of sidebars, even if the current content layout does not support them at the time of rendering.
		 *
		 * TODO: In 2.0 consider adding "conductor-" prefix to before_widget wrapper (backwards compat?)
		 * TODO: Need to escape attribute values
		 */
		public static function register_conductor_sidebars( $content_layout, $content_layouts, $force_registration = false ) {
			// Get the sidebar name prefix for this layout
			// TODO: Pass the prefix to a filter?
			$sidebar_name_prefix = ( isset( $content_layout['sidebar_name_prefix'] ) ) ? $content_layout['sidebar_name_prefix'] : $content_layout['field_label'];

			// Conductor content layout data
			$conductor_content_layout_data = Conductor::get_conductor_content_layout_data( $content_layout );
			$sidebar_name_suffixes = ( isset( $conductor_content_layout_data['sidebar_name_suffixes'] ) ) ? $conductor_content_layout_data['sidebar_name_suffixes'] : array();

			/*
			 * Content Area Sidebar
			 */
			$sidebar_args = array(
				'name' => ( isset( $sidebar_name_suffixes['content'] ) ) ? $sidebar_name_prefix . ' - ' . $sidebar_name_suffixes['content'] : sprintf( __( '%1$s - Content', 'conductor' ), $sidebar_name_prefix ),
				'id' => Conductor::get_conductor_content_layout_sidebar_id( 'content', $content_layout ),
				'description' => sprintf( __( 'This widget area is the content widget area for %1$s.', 'conductor' ), $sidebar_name_prefix ),
				'before_widget' => '<div id="' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . '-widget-%1$s" class="widget ' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . ' ' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . '-widget content-sidebar content-sidebar-widget %2$s">',
				'after_widget' => '</div>',
				'before_title' => '<h3 class="widgettitle widget-title ' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . '-widget-title content-sidebar-widget-title">',
				'after_title' => '</h3>',
			);
			$sidebar_args = apply_filters( 'conductor_sidebar_args', $sidebar_args, 'content', $content_layout, $content_layouts );

			conductor_register_sidebar( $sidebar_args, $content_layout );

			/*
			 * Primary Sidebar
			 */
			if ( conductor_content_layout_has_sidebar( 'primary', $content_layout ) || $force_registration ) {
				$sidebar_args = array(
					'name' => ( isset( $sidebar_name_suffixes['primary'] ) ) ? $sidebar_name_prefix . ' - ' . $sidebar_name_suffixes['primary'] : sprintf( __( '%1$s - Primary', 'conductor' ), $sidebar_name_prefix ),
					'id' => Conductor::get_conductor_content_layout_sidebar_id( 'primary', $content_layout ),
					'description' => sprintf( __( 'This widget area is the primary widget area for %1$s.', 'conductor' ), $sidebar_name_prefix ),
					'before_widget' => '<div id="' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . '-widget-%1$s" class="widget ' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . ' ' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . '-widget primary-sidebar primary-sidebar-widget %2$s">',
					'after_widget' => '</div>',
					'before_title' => '<h3 class="widgettitle widget-title ' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . '-widget-title primary-sidebar-widget-title">',
					'after_title' => '</h3>',
				);
				$sidebar_args = apply_filters( 'conductor_sidebar_args', $sidebar_args, 'primary', $content_layout, $content_layouts );

				conductor_register_sidebar( $sidebar_args, $content_layout );
			}

			/*
			 * Secondary Sidebar
			 */
			if ( conductor_content_layout_has_sidebar( 'secondary', $content_layout ) || $force_registration ) {
				$sidebar_args = array(
					'name' => ( isset( $sidebar_name_suffixes['secondary'] ) ) ? $sidebar_name_prefix . ' - ' . $sidebar_name_suffixes['secondary'] : sprintf( __( '%1$s - Secondary', 'conductor' ), $sidebar_name_prefix ),
					'id' => Conductor::get_conductor_content_layout_sidebar_id( 'secondary', $content_layout ),
					'description' => sprintf( __( 'This widget area is the secondary widget area for %1$s.', 'conductor' ), $sidebar_name_prefix ),
					'before_widget' => '<div id="' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . '-widget-%1$s" class="widget ' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . ' ' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . '-widget secondary-sidebar secondary-sidebar-widget %2$s">',
					'after_widget' => '</div>',
					'before_title' => '<h3 class="widgettitle widget-title ' . $content_layout['field_type'] . '-' . $content_layout['field_id'] . '-widget-title secondary-sidebar-widget-title">',
					'after_title' => '</h3>',
				);
				$sidebar_args = apply_filters( 'conductor_sidebar_args', $sidebar_args, 'secondary', $content_layout, $content_layouts );

				conductor_register_sidebar( $sidebar_args, $content_layout );
			}

			// conductor_register_sidebars hook
			// TODO: Pass $sidebar_name_prefix here
			do_action( 'conductor_register_sidebars', $content_layout, $content_layouts, $force_registration );
		}

		/**
		 * This function is used to determine Conductor Sidebars based on the $sidebars_widgets parameter. If
		 * $sidebars_widgets is not passed, get_option( 'sidebars_widgets' ) is used as a fallback.
		 */
		public function find_conductor_sidebar_ids( $sidebars_widgets = false ) {
			$keys = array();

			if ( empty( $sidebars_widgets ) )
				$sidebars_widgets = get_option( 'sidebars_widgets' );

			$sidebars_widgets_keys = array_keys( $sidebars_widgets );

			if ( ! empty( $sidebars_widgets_keys ) )
				foreach ( $sidebars_widgets_keys as $key )
					if ( strpos( $key, 'conductor' ) === 0 )
						$keys[] = $key;

			return $keys;
		}

		/**
		 * This function is used as a callback for array_filter() to determine inactive Conductor widgets.
		 */
		public function array_filter_find_conductor_widgets( $var ) {
			// Grab the Conductor Widget instance
			$conductor_widget = Conduct_Widget();

			return $conductor_widget->get_id_base( $var ) === $conductor_widget->id_base;
		}

		/**
		 * This function is used to find all orphaned widgets during a theme switch.
		 */
		public function find_orphaned_widgets_sidebar_ids( $sidebars_widgets = false ) {
			$orphans = array();

			if ( empty( $sidebars_widgets ) )
				$sidebars_widgets = get_option( 'sidebars_widgets', array() );

			foreach ( $sidebars_widgets as $key => $widgets )
				if ( strpos( $key, 'orphaned_widgets' ) === 0 )
					$orphans[$key] = $widgets;

			return $orphans;
		}

		/**
		 * This function allows default widgets to be registered for a Conductor content layout.
		 * These widgets will be added to sidebars within a specific content layout based on
		 * parameters and only if the sidebars are currently empty when the content_layout is selected.
		 *
		 *  * $args = array(
		 * 		'id' // A unique ID for this widget TODO: Not currently utilized
		 * 		'content_layout' // Conductor content layout ID (Conductor_Options::get_content_layouts())
		 *		'matches' // Does the content layout need to match the 'content_layout'; otherwise a strict check will be made
		 * 		'sidebar_id' // Typically 'content', 'primary', or 'secondary' for normal Conductor content layouts
		 * 		'widget_id_base' // Widget ID Base, must match the widget id_base (i.e. 'conductor-widget')
		 * 		'widget_settings' // An array of settings that will be passed to the widget upon creation
		 * 		'widget_position' // Position to register this widget in (will default to end of current sidebar widgets at the time of registration) TODO: Not currently utilized
		 * )
		 *
		 */
		// TODO: Logic here to determine if there's a conflict with a widget's passed in position
		// TODO: Currently widget_position is not taken into account, widgets are added to sidebars in the order they are registered within Conductor
		// TODO: Remove "registered" widgets from layouts once the layout is reverted?
		public static function register_default_sidebar_widget( $args ) {
			// Grab the Conductor Sidebars instance
			$conductor_sidebars = Conduct_Sidebars();

			// Parse arguments
			$defaults = array(
				'id' => '', // A unique ID for this widget TODO: Not currently utilized
				'content_layout' => false, // Conductor content layout ID (Conductor_Options::get_content_layouts())
				'matches' => false, // Does the content layout need to match the 'content_layout'; otherwise a strict check will be made
				'sidebar_id' => '', // Typically 'content', 'primary', or 'secondary' for normal Conductor content layouts
				'widget_id_base' => '', // Widget ID Base, must match the widget id_base (i.e. 'conductor-widget')
				'widget_settings' => array(), // An array of settings that will be passed to the widget upon creation
				'widget_position' => 0 // Position to register this widget in (will default to end of current sidebar widgets at the time of registration) TODO: Not currently utilized
			);

			$args = wp_parse_args( $args, $defaults );

			// Setup the array if it hasn't been created
			if ( ! isset( $conductor_sidebars->default_widgets[$args['content_layout']] ) )
				$conductor_sidebars->default_widgets[$args['content_layout']] = array();

			// Add the default widget data to the array
			$conductor_sidebars->default_widgets[$args['content_layout']][] = $args;
		}

		/**
		 * This function adds widgets to Conductor content layout sidebars that have been
		 * registered with Conductor_Sidebars::register_default_sidebar_widget().
		 */
		public static function add_widgets_to_content_layout_sidebars( $content_layout, $content_layouts, $default_widgets ) {
			global $wp_widget_factory;

			// Grab the Conductor Sidebars instance
			$conductor_sidebars = Conduct_Sidebars();

			$content_layout_sidebar_prefix = Conductor::get_conductor_content_layout_sidebar_id_prefix( '', $content_layout );
			$content_layout_sidebar_suffix = Conductor::get_conductor_content_layout_sidebar_id_suffix( '', $content_layout );
			$sidebars_widgets = wp_get_sidebars_widgets();
			$widget_controls = array();
			$widget_settings = array();
			$widget_multi_numbers = array();

			// Register any newly "missing" Conductor sidebars (these sidebars will be registered by Conductor on subsequent page loads but are not currently registered at the time of this execution)
			self::register_conductor_sidebars( $content_layout, $content_layouts, true );

			// If we have registered sidebars for this content layout
			if ( $content_layout_sidebars = $conductor_sidebars->get_conductor_content_layout_sidebars( $content_layout ) ) {
				// Include necessary widget functionality
				if ( ! function_exists( 'next_widget_id_number' ) )
					include_once ABSPATH . 'wp-admin/includes/widgets.php';

				// Loop through content layout sidebars
				foreach ( $content_layout_sidebars as $sidebar_id ) {
					// Only if the sidebar widgets do not exist, or they are empty
					if ( ( isset( $conductor_sidebars->sidebar_has_widgets[$sidebar_id] ) && ! $conductor_sidebars->sidebar_has_widgets[$sidebar_id] ) || ( ! isset( $sidebars_widgets[$sidebar_id] ) || empty( $sidebars_widgets[$sidebar_id] ) ) ) {
						// Store in cache
						$conductor_sidebars->sidebar_has_widgets[$sidebar_id] = false;

						// Conductor sidebar ID
						$conductor_sidebar_id = str_replace( array( $content_layout_sidebar_prefix, $content_layout_sidebar_suffix ), '', $sidebar_id );

						// Loop through default widgets
						foreach ( $default_widgets as $default_widget ) {
							// If this widget sidebar ID matches the Conductor sidebar ID
							if ( $default_widget['sidebar_id'] === $conductor_sidebar_id ) {
								$id_base = $default_widget['widget_id_base'];
								$widget_control = array();

								// Find the correct control based on the widget id_base
								foreach ( $wp_widget_factory->widgets as $widget ) {
									// If we have a match on the id_base
									if ( $widget->id_base === $id_base ) {
										// Store a reference to the widget control
										$widget_control = $widget;

										// Break out of the loop
										break;
									}
								}

								// Make sure we have a control for this widget
								if ( ! empty ( $widget_control ) ) {
									// Setup the widget settings data array if necessary
									$widget_settings[$id_base] = ( isset( $widget_settings[$id_base] ) && is_array( $widget_settings[$id_base] ) ) ? $widget_settings[$id_base] : array();

									// Setup the multi number value
									if ( isset( $widget_multi_numbers[$id_base] ) && ! empty( $widget_multi_numbers[$id_base] ) )
										// Increase the current value
										$widget_multi_numbers[$id_base]++;
									else
										// Set the current value to the next ID number
										$widget_multi_numbers[$id_base] = next_widget_id_number( $id_base );

									// Add the update control to the list of controls we'll call updates to
									$widget_controls[$id_base] = $widget_control;

									// Setup all widget data
									$multi_number = $widget_multi_numbers[$id_base];
									$widget_id = $id_base . '-' . $multi_number; // Create a widget ID
									$sidebars_widgets[$sidebar_id][] = $widget_id; // Add the widget to the sidebar TODO: determine widget position in sidebar
									$widget_settings[$id_base][$multi_number] =  $default_widget['widget_settings']; // Add this widget's settings to the array
								}
							}
						}
					}
					// Otherwise this sidebar has widgets
					else
						// Store in cache
						$conductor_sidebars->sidebar_has_widgets[$sidebar_id] = true;
				}

				/*
				 * Loop through widget update controls
				 *
				 * Normally we could call the widget's update_callback by using global $wp_registered_widget_updates,
				 * however, that callback only allows for one widget's settings to be updated (@see https://github.com/WordPress/WordPress/blob/4.2-branch/wp-includes/widgets.php#L412).
				 * We're taking the logic that is found in WP_Widget::update_callback() and making sure each of our new
				 * widget settings are added to the instance settings and then we're saving the settings through
				 * WP_Widget::save_settings().
				 */
				foreach ( $widget_controls as $widget_id_base => $widget_control ) {
					// If we have settings for this widget
					if ( isset( $widget_settings[$widget_id_base] ) ) {
						// Grab all instance settings for this widget
						$all_instances = $widget_control->get_settings();

						// Loop through widget settings (@see https://github.com/WordPress/WordPress/blob/4.2-branch/wp-includes/widgets.php#L345-L414)
						foreach ( $widget_settings[$widget_id_base] as $number => $new_instance ) {
							$new_instance = stripslashes_deep( $new_instance );
							$widget_control->_set( $number );
							$old_instance = isset( $all_instances[$number] ) ? $all_instances[$number] : array();
							$instance = $widget_control->update( $new_instance, $old_instance );

							/**
							 * Filter a widget's settings before saving.
							 *
							 * Returning false will effectively short-circuit the widget's ability
							 * to update settings.
							 *
							 * @see https://github.com/WordPress/WordPress/blob/4.2-branch/wp-includes/widgets.php#L407
							 *
							 * @since 2.8.0
							 *
							 * @param array     $instance     The current widget instance's settings.
							 * @param array     $new_instance Array of new widget settings.
							 * @param array     $old_instance Array of old widget settings.
							 * @param WP_Widget $widget_control         The current widget instance.
							 */
							$instance = apply_filters( 'widget_update_callback', $instance, $new_instance, $old_instance, $widget_control );
							if ( false !== $instance )
								$all_instances[$number] = $instance;
						}

						// Save this widget's settings
						$widget_control->save_settings( $all_instances );
						$widget_control->updated = true;
					}
				}

				// Save the sidebar widgets (save settings to the database)
				wp_set_sidebars_widgets( $sidebars_widgets );
			}
		}

		/**
		 * This function validates data between two Conductor content layout arrays to see if they
		 * are one in the same.
		 */
		public function validate_conductor_content_layout_data( $new_layout, $old_layout ) {
			// Validate by field ID and type
			return $new_layout['field_id'] === $old_layout['field_id'] && $new_layout['field_type'] === $old_layout['field_type'];
		}

		/**
		 * This function determines if a default widget matches a content layout value.
		 */
		public function is_conductor_content_layout_match( $default_widget, $content_layout, $content_layout_id ) {
			// Matches
			if ( $default_widget['matches'] && strpos( $content_layout['value'], $content_layout_id ) !== false )
				return true;
			// Equals
			else if ( $content_layout['value'] === $content_layout_id )
				return true;

			return false;
		}

		/**
		 * This function returns the sidebar IDs for a content layout.
		 */
		public function get_conductor_content_layout_sidebars( $content_layout ) {
			// Grab the Conductor Sidebars instance
			$conductor_sidebars = Conduct_Sidebars();
			$content_layout_sidebar_prefix = Conductor::get_conductor_content_layout_sidebar_id_prefix( '', $content_layout );
			$content_layout_sidebar_regex = '/^' . $content_layout_sidebar_prefix . '/';

			return preg_grep( $content_layout_sidebar_regex, $conductor_sidebars->registered_sidebars );
		}
	}

	/**
	 * Create an instance of the Conductor_Sidebars class.
	 */
	function Conduct_Sidebars() {
		return Conductor_Sidebars::instance();
	}

	Conduct_Sidebars(); // Conduct your content!
}