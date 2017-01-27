<?php
/**
 * This is the content template used for displaying Conductor Content Sidebars.
 *
 * Conductor will look for your-theme/conductor/content.php and load that file first if it exists.
 *
 * @author Slocum Studio
 * @version 1.0.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<?php
	// Output the Conductor Content Sidebar
	if ( conductor_is_active_sidebar( 'content' ) )
		conductor_get_sidebar( 'content' );
	// Otherwise load default content
	else
		conductor_get_template_part( 'content', 'default' );
?>