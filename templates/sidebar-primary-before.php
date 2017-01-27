<?php
/**
 * This is the content wrapper template used for displaying the opening primary sidebar wrapper element.
 *
 * Conductor will look for your-theme/conductor/sidebar-primary-before.php and load that file first if it exists.
 *
 * @author Slocum Studio
 * @version 1.0.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<?php echo apply_filters( 'conductor_primary_sidebar_element_before', '<div class="conductor-primary-sidebar conductor-sidebar conductor-cf ' . Conductor::get_conductor_content_layout_sidebar_id( 'primary' ) . '" data-sidebar-id="' . Conductor::get_conductor_content_layout_sidebar_id( 'primary' ) . '"><div class="conductor-inner conductor-cf">', Conductor::get_conductor_content_layout() ); ?>