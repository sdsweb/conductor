<?php
/**
 * This is the secondary sidebar template used for displaying Conductor Secondary Sidebars.
 *
 * Conductor will look for your-theme/conductor/sidebar-secondary.php and load that file first if it exists.
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
	if ( conductor_is_active_sidebar( 'secondary' ) )
		conductor_get_sidebar( 'secondary' );
	// Otherwise load default content
	else
		conductor_get_template_part( 'sidebar-secondary', 'default' );
?>