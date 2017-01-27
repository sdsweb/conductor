<?php
/**
 * This is the content wrapper template used for displaying the main closing content wrapper element.
 *
 * Conductor will look for your-theme/conductor/content-wrapper-after.php and load that file first if it exists.
 *
 * @author Slocum Studio
 * @version 1.1.1
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<?php echo apply_filters( 'conductor_content_wrapper_element_after', '<div class="conductor-cf conductor-clear"></div></div>', Conductor::get_conductor_content_layout() ); ?>