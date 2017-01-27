<?php
/**
 * Conductor Template Functions
 *
 * @author Slocum Studio
 * @version 1.3.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * This function locates and loads templates based on arguments. Optionally an array of data can be passed
 * that will be extract()ed and the template will have access to the $data. If data is passed, WordPress
 * global variables can be included as well. The template file can also be required once if necessary.
 *
 * Verify if the file exists in the theme first, then load the plugin template if necessary.
 */
function conductor_get_template_part( $slug, $name = '', $data = array(), $wp_globals = false, $require_once = false ) {
	$template = '';
	$templates = array();

	// Find the more specific template in the theme first
	if ( $name ) {
		//$templates[] = $slug . '-' . $name . '.php';
		$templates[] = Conductor::theme_template_path() . '/' . $slug . '-' . $name . '.php';
		$template = locate_template( $templates );

		// Find the more specific template in Conductor if it was not found in the theme
		if ( ! $template && file_exists( Conductor::plugin_dir() . '/templates/' . $slug . '-' . $name . '.php' ) )
			$template = Conductor::plugin_dir() . '/templates/' . $slug . '-' . $name . '.php';
	}

	// Find the more generic template in the theme if the more specific template doesn't exist
	if ( ! $template ) {
		$templates = array(); // Reset templates array first
		//$templates[] = $slug . '.php';
		$templates[] = Conductor::theme_template_path() . '/' . $slug . '.php';
		$template = locate_template( $templates );

		// Find the more generic template in Conductor if it was not found in the theme
		if ( ! $template && file_exists( Conductor::plugin_dir() . '/templates/' . $slug . '.php' ) )
			$template = Conductor::plugin_dir() . '/templates/' . $slug . '.php';
	}

	// conductor_get_template_part filter
	$template = apply_filters( 'conductor_get_template_part', $template, $slug, $name, $data, $wp_globals, $require_once );

	// Finally, if we have a template, lets load it
	if ( $template ) {
		// If data was passed we have to require() the files
		// TODO: Is there any reason we can't use load_template here? We may not be able to with data being passed to the template, but we should use it if we can
		if ( is_array( $data ) && ! empty( $data ) ) {
			$data = apply_filters( 'conductor_get_template_part_data', $data, $slug, $name );

			// WordPress Globals
			// TODO: Include query variables like load_template()
			if ( $wp_globals )
				global $posts, $post, $wp_did_header, $wp_query, $wp_rewrite, $wpdb, $wp_version, $wp, $id, $comment, $user_ID;

			// Extract the data for use in the template
			extract( $data, EXTR_SKIP ); // Skip collisions
			unset( $data ); // We don't need the $data var anymore

			// Require Once
			if ( $require_once )
				require_once $template;
			// Require
			else
				require $template;

		}
		// Otherwise we can load_template()
		else
			load_template( $template, $require_once );
	}
}

/**
 * This function determines if the current content layout has a particular sidebar. Optionally
 * pass a content layout to verify that layout instead of the current Conductor layout.
 */
function conductor_content_layout_has_sidebar( $sidebar, $content_layout = false ) {
	$has_sidebar = false;
	$content_layout = ( is_array( $content_layout ) ) ? $content_layout : Conductor::get_conductor_content_layout();
	$conductor_content_layout_data = Conductor::get_conductor_content_layout_data( $content_layout );
	$conductor_content_layout_has_sidebars = ( isset( $conductor_content_layout_data['has_sidebars'] ) ) ? $conductor_content_layout_data['has_sidebars'] : array();

	// Conductor content layout data
	if ( ! empty( $conductor_content_layout_has_sidebars ) && in_array( $sidebar, $conductor_content_layout_has_sidebars ) )
		$has_sidebar = true;
	// Default/fallback
	else {
		// Switch between sidebars
		switch ( $sidebar ) {
			// Primary Sidebar
			case 'primary':
				// 2 or 3 Columns
				if ( strpos( $content_layout['value'], 'cols-2' ) !== false || strpos( $content_layout['value'], 'cols-3' ) !== false )
					$has_sidebar = true;
			break;
			// Secondary Sidebar
			case 'secondary':
				// 3 Column
				if ( strpos( $content_layout['value'], 'cols-3' ) !== false )
					$has_sidebar = true;
			break;
		}
	}

	return apply_filters( 'conductor_content_layout_has_sidebar', $has_sidebar, $sidebar, $content_layout );
}

/**
 * This function determines if the current content layout sidebar is active. Optionally
 * pass a content layout to verify that layout instead of the current Conductor layout.
 */
function conductor_is_active_sidebar( $sidebar, $content_layout = false ) {
	$content_layout = ( is_array( $content_layout  ) ) ? $content_layout : Conductor::get_conductor_content_layout();
	$sidebar_id = Conductor::get_conductor_content_layout_sidebar_id( $sidebar );
	$active_sidebar = false;

	if ( is_active_sidebar( $sidebar_id ) )
		$active_sidebar = true;

	// Legacy (as of version 1.4.0)
	$active_sidebar = apply_filters( 'conductor_is_sidebar_active', $active_sidebar, $sidebar, $content_layout );

	return apply_filters( 'conductor_is_active_sidebar', $active_sidebar, $sidebar, $sidebar_id, $content_layout );
}

/**
 * This function outputs Conductor Sidebars and contains actions before and after sidebar output.
 *
 * @uses dynamic_sidebar()
 */
// TODO
function conductor_get_sidebar( $sidebar, $content_layout = false ) {
	$content_layout = ( is_array( $content_layout  ) ) ? $content_layout : Conductor::get_conductor_content_layout();
	$sidebar_id = Conductor::get_conductor_content_layout_sidebar_id( $sidebar );

	// TODO: These hooks may not be necessary since dynamic_sidebar() contains its own before/after hooks

	// 'conductor_' .$sidebar . '_dynamic_sidebar_before' hook
	do_action( 'conductor_dynamic_sidebar_before', $sidebar_id, $content_layout );
	do_action( 'conductor_' . $sidebar . '_dynamic_sidebar_before', $sidebar_id, $content_layout );

	// Output the sidebar
	dynamic_sidebar( $sidebar_id );

	// 'conductor_' .$sidebar . '_dynamic_sidebar_after' hook
	do_action( 'conductor_' . $sidebar . '_dynamic_sidebar_after', $sidebar_id, $content_layout );
	do_action( 'conductor_dynamic_sidebar_after', $sidebar_id, $content_layout );
}


/**
 * Main Wrapper Elements
 */

/**
 * This function loads the template which outputs the main opening content wrapper element.
 */
function conductor_content_wrapper_before() {
	conductor_get_template_part( 'content', 'wrapper-before' );
}

/**
 * This function loads the template which outputs the main closing content wrapper element.
 */
function conductor_content_wrapper_after() {
	conductor_get_template_part( 'content', 'wrapper-after' );
}


/**
 * Content Wrapper Elements
 */

/**
 * This function loads the template which outputs the opening content wrapper element.
 */
function conductor_content_before() {
	conductor_get_template_part( 'content', 'before' );
}

/**
 * This function loads the template which outputs the closing content wrapper element.
 */
function conductor_content_after() {
	conductor_get_template_part( 'content', 'after' );
}


/**
 * Primary Sidebar Wrapper Elements
 */

/**
 * This function loads the template which outputs the opening primary sidebar wrapper element.
 */
function conductor_primary_sidebar_before() {
	conductor_get_template_part( 'sidebar-primary', 'before' );
}

/**
 * This function loads the template which outputs the closing primary sidebar wrapper element.
 */
function conductor_primary_sidebar_after() {
	conductor_get_template_part( 'sidebar-primary', 'after' );
}


/**
 * Secondary Sidebar Wrapper Elements
 */

/**
 * This function loads the template which outputs the opening secondary sidebar wrapper element.
 */
function conductor_secondary_sidebar_before() {
	conductor_get_template_part( 'sidebar-secondary', 'before' );
}

/**
 * This function loads the template which outputs the closing secondary sidebar wrapper element.
 */
function conductor_secondary_sidebar_after() {
	conductor_get_template_part( 'sidebar-secondary', 'after' );
}