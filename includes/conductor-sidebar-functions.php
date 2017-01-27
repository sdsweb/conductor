<?php
/**
 * Conductor Sidebar Functions
 *
 * @author Slocum Studio
 * @version 1.0.0
 * @since 1.3.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * This function registers a Conductor sidebar. It effectively is a wrapper for register_sidebar()
 * but will add the sidebar arguments to Conductor_Sidebars::$registered_sidebars. You should pass
 * an ID parameter in $sidebar_args that is generated from Conductor::get_conductor_content_layout_sidebar_id().
 */
// TODO: In 2.0 consider adding the conductor_sidebars_args filter here instead of before each call to conductor_register_sidebar()
function conductor_register_sidebar( $sidebar_args, $content_layout, $conductor_sidebar_id = false ) {
	// Grab the Conductor Sidebars instance
	$conductor_sidebars = Conduct_Sidebars();

	// Add this sidebar to the list of registered Conductor sidebars
	if ( isset( $sidebar_args['id'] ) && ! in_array( $sidebar_args['id'], $conductor_sidebars->registered_sidebars ) )
		$conductor_sidebars->registered_sidebars[] = $sidebar_args['id'];
	else if ( $conductor_sidebar_id ) {
		$sidebar_id = Conductor::get_conductor_content_layout_sidebar_id( $conductor_sidebar_id, $content_layout );

		// If this sidebar ID doesn't already exist
		if ( ! in_array( $sidebar_id, $conductor_sidebars->registered_sidebars ) )
			$conductor_sidebars->registered_sidebars[] = $sidebar_id;
	}

	// If we have a sidebar ID
	if ( isset( $sidebar_id ) || isset( $sidebar_args['id'] ) ) {
		// Find the sidebar ID
		$the_sidebar_id = ( isset( $sidebar_id ) ) ? $sidebar_id : $sidebar_args['id'];

		// Store the sidebar arguments
		if ( ! array_key_exists( $the_sidebar_id, $conductor_sidebars->registered_sidebars_args ) )
			$conductor_sidebars->registered_sidebars_args[$the_sidebar_id] = $sidebar_args;

		// Generate a content layout ID
		$content_layout_id = $content_layout['field_type'] . '-' . $content_layout['field_id'];

		// Create the entry for this content layout
		if ( ! isset( $conductor_sidebars->registered_content_layout_sidebars[$content_layout_id] ) )
			$conductor_sidebars->registered_content_layout_sidebars[$content_layout_id] = array();

		// Store the sidebar ID on this content layout
		if ( ! in_array( $the_sidebar_id, $conductor_sidebars->registered_content_layout_sidebars[$content_layout_id] ) )
			$conductor_sidebars->registered_content_layout_sidebars[$content_layout_id][] = $the_sidebar_id;
	}


	// Register the sidebar
	register_sidebar( $sidebar_args );
}

/**
 * This function allows default widgets to be registered for a Conductor content layout.
 * These widgets will be added to sidebars within a specific content layout based on
 * parameters and only if the sidebars are currently empty. It also contains specific logic
 * to register Conductor widgets.
 *
 * $args = array(
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
function conductor_register_default_sidebar_widget( $args ) {
	// Grab Conductor Widget instance
	$conductor_widget = Conduct_Widget();

	// If we have a specific widget type passed
	if ( isset( $args['widget_id_base'] ) ) {
		// Switch based on the widget type
		switch ( $args['widget_id_base'] ) {
			// Conductor Widgets
			case $conductor_widget->id_base:
				// Grab Conductor Widget defaults
				$conductor_widget_defaults = $conductor_widget->defaults;

				// If we have widget settings
				if ( isset( $args['widget_settings'] ) && ! empty( $args['widget_settings'] ) ) {
					// Loop through widget settings (we need to parse array values)
					foreach ( $args['widget_settings'] as $widget_setting_id => &$widget_setting ) {
						// Array settings (skipping output elements for this time around)
						if ( is_array( $widget_setting ) && $widget_setting_id !== 'output' && isset( $conductor_widget_defaults[$widget_setting_id] ) )
							$widget_setting = wp_parse_args( $widget_setting, $conductor_widget_defaults[$widget_setting_id] );
					}

					// Loop through widget settings output elements
					if ( isset( $args['widget_settings']['output'] ) ) {
						// Reference to output elements for this widget
						$output_elements = array();

						// Loop through the passed in widget settings
						foreach ( $args['widget_settings']['output'] as $priority => $output_element ) {
							$default_output_element_priority = 0;
							$default_output_element_data = array();
							$priority_int = ( int ) $priority;
							$output_element_id = ( $priority_int ) ? $output_element['id'] : $priority;

							// Find the output element in defaults
							foreach ( $conductor_widget_defaults['output'] as $default_priority => $default_output_element )
								// If we have a match
								if ( $default_output_element['id'] === $output_element_id ) {
									$default_output_element_priority = $default_priority;
									$default_output_element_data = $default_output_element;
									break;
								}

							// If we found a match in the defaults
							if ( $default_output_element_priority && ! empty( $default_output_element_data ) ) {
								// If the priority is an integer
								if ( $priority_int )
									$output_elements[$priority_int] = wp_parse_args( $output_element, $default_output_element_data );
								else
									$output_elements[$default_output_element_priority] = wp_parse_args( $output_element, $default_output_element_data );
							}
							// Otherwise this is a new output element
							else if ( $priority_int )
								$output_elements[$priority_int] = wp_parse_args( $output_element, $default_output_element_data );
						}

						// Loop through the Conductor Widget default output elements (to find any missing that weren't included in the passed in widget settings)
						foreach ( $conductor_widget_defaults['output'] as $default_priority => $default_output_element ) {
							// Flag to determine this missing element
							$is_missing_element = true;

							// Loop through current set of output elements
							foreach ( $output_elements as $priority => $output_element )
								// If we have a match
								if ( $default_output_element['id'] === $output_element['id'] ) {
									// Set the flag
									$is_missing_element = false;

									break;
								}

							// If we have a missing element
							if ( $is_missing_element ) {
								// If an element already exists at this default priority add it at the next priority (+10 increments)
								if ( isset( $output_elements[$default_priority] ) ) {
									$next_priority = $default_priority;

									// Loop through to find the next priority
									while ( isset( $output_elements[$next_priority] ) )
										$next_priority += 10;

									$output_elements[$next_priority] = $default_output_element;
								}
								// Add this element to the output elements
								else
									$output_elements[$default_priority] = $default_output_element;
							}
						}

						// Sort the output data by priority
						ksort( $output_elements, SORT_NUMERIC );

						$args['widget_settings']['output'] = $output_elements;
					}

					// Conductor expects the data to be serialized
					$args['widget_settings']['output'] = ( isset( $args['widget_settings']['output'] ) ) ? json_encode( $args['widget_settings']['output'] ) : json_encode( $conductor_widget_defaults['output'] );

					// If we have query arguments
					if ( isset( $args['widget_settings']['query_args'] ) )
						// Conductor expects query arguments to exist directly in widget settings (instead of under the 'query_args' parameter
						foreach ( $args['widget_settings']['query_args'] as $id => $output )
							// Only if this value wasn't already set
							if ( ! isset( $args['widget_settings'][$id] ) )
								$args['widget_settings'][$id] = $output;

					// Parse settings with default values
					$args['widget_settings'] = wp_parse_args( $args['widget_settings'], $conductor_widget_defaults );
				}
				// Otherwise use the defaults for widget settings
				else {
					// Set widget settings to Conductor Widget defaults
					$args['widget_settings'] = $conductor_widget_defaults;

					// Conductor expects the data to be serialized
					$args['widget_settings']['output'] = json_encode( $args['widget_settings']['output'] );

					// Conductor expects query arguments to exist directly in widget settings (instead of under the 'query_args' parameter
					foreach ( $args['widget_settings']['query_args'] as $id => $output )
						// Only if this value wasn't already set
						if ( ! isset( $args['widget_settings'][$id] ) )
							$args['widget_settings'][$id] = $output;
				}

				// Register the Conductor default sidebar widget
				Conductor_Sidebars::register_default_sidebar_widget( $args );
			break;
			// All other widgets (no specific logic)
			default:
				// Register the Conductor default sidebar widget
				Conductor_Sidebars::register_default_sidebar_widget( $args );
			break;
		}
	}
	// Otherwise just register the widget
	else
		// Register the Conductor default sidebar widget
		Conductor_Sidebars::register_default_sidebar_widget( $args );
}