<?php
/**
 * Conductor Uninstall
 *
 * @author Slocum Studio
 * @version 1.0.0
 * @since 1.0.0
 */

// Bail if not actually uninstalling
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit;

/**
 * Includes
 */
include_once 'conductor.php'; // Conductor Plugin
include_once 'includes/class-conductor-options.php'; // Conductor Options
include_once 'includes/conductor-template-functions.php'; // Conductor Template Functions
include_once 'includes/widgets/class-conductor-widget.php'; // Conductor Widget

/**
 * Uninstall
 */

// Fetch Conductor options
$conductor_options = Conductor_Options::get_options();

// Remove Conductor data upon uninstall
if ( $conductor_options['uninstall']['data'] ) {
	// Widgets grouped by sidebar
	$sidebars_widgets = wp_get_sidebars_widgets();

	if ( empty( $sidebars_widgets ) )
		$sidebars_widgets = wp_get_widget_defaults();

	// Do we have Conductor content layouts?
	if ( ! empty( $conductor_options['content_layouts'] ) ) {
		foreach ( $conductor_options['content_layouts'] as $id => $content_layout ) {
			// Content Sidebar
			$sidebar_id = Conductor::get_conductor_content_layout_sidebar_id( 'content', $content_layout );

			if ( isset( $sidebars_widgets[$sidebar_id] ) )
				unset( $sidebars_widgets[$sidebar_id] );


			// Primary Sidebar
			$sidebar_id = Conductor::get_conductor_content_layout_sidebar_id( 'primary', $content_layout );

			if ( conductor_content_layout_has_sidebar( 'primary', $content_layout ) && isset( $sidebars_widgets[$sidebar_id] ) )
				unset( $sidebars_widgets[$sidebar_id] );


			// Secondary Sidebar
			$sidebar_id = Conductor::get_conductor_content_layout_sidebar_id( 'secondary', $content_layout );

			if ( conductor_content_layout_has_sidebar( 'secondary', $content_layout ) && isset( $sidebars_widgets[$sidebar_id] ) )
				unset( $sidebars_widgets[$sidebar_id] );

			// TODO: Allow for sidebar/widget data registered elsewhere (i.e. theme/plugin) to be removed as well
		}

		// Update the sidebars/widgets
		wp_set_sidebars_widgets( $sidebars_widgets );
	}

	// Grab an instance of the Conductor Widget and remove the settings
	$conductor_widget = Conduct_Widget();
	delete_option( $conductor_widget->option_name );

	// Delete the Conductor option
	delete_option( Conductor_Options::$option_name );
}