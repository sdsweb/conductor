<?php
/**
 * This is the content wrapper template used for displaying the closing secondary sidebar wrapper element.
 *
 * Conductor will look for your-theme/conductor/sidebar-secondary-after.php and load that file first if it exists.
 *
 * @author Slocum Studio
 * @version 1.1.1
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<?php echo apply_filters( 'conductor_secondary_sidebar_element_after', '<div class="conductor-cf conductor-clear"></div></div></div>', Conductor::get_conductor_content_layout() ); ?>