<?php
/**
 * Conductor Template Hooks
 *
 * Functions are located in conductor-template-functions.php
 *
 * @author Slocum Studio
 * @version 1.0.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Conductor Content Wrappers
 */

// Main wrapper elements
add_action( 'conductor_content_wrapper_before', 'conductor_content_wrapper_before' );
add_action( 'conductor_content_wrapper_after', 'conductor_content_wrapper_after' );

// Content wrapper elements
add_action( 'conductor_content_before', 'conductor_content_before' );
add_action( 'conductor_content_after', 'conductor_content_after' );

// Primary sidebar wrapper elements
add_action( 'conductor_primary_sidebar_before', 'conductor_primary_sidebar_before' );
add_action( 'conductor_primary_sidebar_after', 'conductor_primary_sidebar_after' );

// Secondary sidebar wrapper elements
add_action( 'conductor_secondary_sidebar_before', 'conductor_secondary_sidebar_before' );
add_action( 'conductor_secondary_sidebar_after', 'conductor_secondary_sidebar_after' );