<?php
/**
 * This is the default content template used for displaying Conductor default content.
 *
 * Conductor will look for your-theme/conductor/content-default.php and load that file first if it exists.
 *
 * @author Slocum Studio
 * @version 1.0.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<section class="conductor-default conductor-default-content">
	<h2 class="conductor-default-title"><?php _e( 'Welcome to Conductor', 'conductor' ); ?></h2>
	<p class="conductor-notice"><?php _e( 'First things first, <strong>your content is safe and sound</strong>. Conductor is enabled on this page, but there aren\'t currently any active widgets.  Add your widgets here to get started with conducting your content. Conductor can easily be disabled on this page via the Customizer or Conductor Options in the Dashboard.', 'conductor' ); ?></p>
</section>