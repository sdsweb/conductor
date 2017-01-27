<div class="wrap about-wrap">
	<h1><?php _e( 'Conductor Add-Ons', 'conductor' ); ?></h1>

	<?php do_action( 'conductor_options_notifications' ); ?>

	<div id="conductor-form" class="conductor-form-large conductor-form-full-width conductor-form-add-ons">
		<?php Conductor_Admin_Add_Ons::display_add_ons_list(); // Display Conductor Add-Ons ?>
	</div>

	<?php // include_once 'html-conductor-options-sidebar.php'; ?>
</div>