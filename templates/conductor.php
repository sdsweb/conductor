<?php
/**
 * This is the core template used for displaying Conductor elements.
 *
 * Conductor will look for your-theme/conductor/conductor.php and load that file first if it exists.
 *
 * @author Slocum Studio
 * @version 1.0.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

get_header( 'conductor' ); ?>

<?php
	/**
	 * conductor_content_wrapper_before hook
	 */
	do_action( 'conductor_content_wrapper_before', Conductor::get_conductor_content_layout() );
?>

	<?php
		/**
		 * Conductor Content
		 */

		// conductor_content_before hook
		do_action( 'conductor_content_before', Conductor::get_conductor_content_layout() );

		// Conductor Content
		conductor_get_template_part( 'content' );

		// conductor_content_after hook
		do_action( 'conductor_content_after', Conductor::get_conductor_content_layout() );
	?>

	<?php
		/**
		 * Conductor Primary Sidebar
		 */
		if ( conductor_content_layout_has_sidebar( 'primary' ) ) {
			// conductor_primary_sidebar_before hook
			do_action( 'conductor_primary_sidebar_before', Conductor::get_conductor_content_layout() );

			// Conductor Primary Sidebar
			conductor_get_template_part( 'sidebar', 'primary' );

			// conductor_primary_sidebar_after hook
			do_action( 'conductor_primary_sidebar_after', Conductor::get_conductor_content_layout() );
		}
	?>

	<?php
		/**
		 * Conductor Secondary Sidebar
		 */
		if ( conductor_content_layout_has_sidebar( 'secondary' ) ) {
			// conductor_secondary_sidebar_before hook
			do_action( 'conductor_secondary_sidebar_before', Conductor::get_conductor_content_layout() );

			// Conductor Secondary Sidebar
			conductor_get_template_part( 'sidebar', 'secondary' );

			// conductor_secondary_sidebar_after hook
			do_action( 'conductor_secondary_sidebar_after', Conductor::get_conductor_content_layout() );
		}
	?>

<?php
	/**
	 * conductor_content_wrapper_after hook
	 */
	do_action( 'conductor_content_wrapper_after', Conductor::get_conductor_content_layout() );
?>


<?php get_footer( 'conductor' ); ?>