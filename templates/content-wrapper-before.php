<?php
/**
 * This is the content wrapper template used for displaying the main opening content wrapper element.
 *
 * Conductor will look for your-theme/conductor/content-wrapper-before.php and load that file first if it exists.
 *
 * @author Slocum Studio
 * @version 1.0.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<?php echo apply_filters( 'conductor_content_wrapper_element_before', '<div class="conductor-container container conductor-cf">', Conductor::get_conductor_content_layout() ); ?>