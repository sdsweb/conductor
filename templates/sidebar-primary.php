<?php
/**
 * This is the primary sidebar template used for displaying Conductor Primary Sidebars.
 *
 * Conductor will look for your-theme/conductor/sidebar-primary.php and load that file first if it exists.
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
	// Output the Conductor Primary Sidebar
	if ( conductor_is_active_sidebar( 'primary' ) )
		conductor_get_sidebar( 'primary' );
	// Otherwise load default content
	else
		conductor_get_template_part( 'sidebar-primary', 'default' );
?>