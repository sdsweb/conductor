<?php
/**
 * This is the default secondary sidebar template used for displaying Conductor Secondary Sidebar default content.
 *
 * Conductor will look for your-theme/conductor/sidebar-secondary-default.php and load that file first if it exists.
 *
 * @author Slocum Studio
 * @version 1.0.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<section class="conductor-default conductor-default-sidebar conductor-default-secondary-sidebar">
	<h3 class="conductor-default-title"><?php _e( 'Conductor Secondary Sidebar', 'conductor' ); ?></h3>
	<p class="conductor-notice"><?php _e( 'Conduct your content. Add your widgets here to get started.', 'conductor' ); ?></p>
</section>