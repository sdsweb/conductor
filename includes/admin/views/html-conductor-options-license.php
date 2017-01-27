<?php $conductor_license_options = Conductor_Admin_License_Options_Views::$options; // Option values are loaded in the Conductor_Admin_License_Options_Views Class on instantiation. ?>

<div class="wrap about-wrap">
	<h1><?php _e( 'Conductor License', 'conductor' ); ?></h1>

	<!--div class="about-text conductor-about-text">
		<?php //_e( 'Activate Conductor....', 'conductor' ); ?>
	</div-->

	<?php do_action( 'conductor_options_notifications' ); ?>

	<?php
		settings_errors( 'general' ); // General Settings Errors
		settings_errors( Conductor_Options::$option_name . '_license' ); // Conductor License Settings Errors
	?>

	<?php do_action( 'conductor_options_license_form_before' ); ?>

	<form method="post" action="options.php" enctype="multipart/form-data" id="conductor-form" class="conductor-license-options-form">
		<?php
			settings_fields( Conductor_Options::$option_name . '_license' );

			/**
			 * Conductor License Settings
			 */
			do_settings_sections( Conductor_Options::$option_name . '_license' );
		?>

		<p class="submit">
			<?php
				// Valid license exists
				if ( ( ! empty( $conductor_license_options['key'] ) && $conductor_license_options['status'] === 'valid' ) )
					submit_button( __( 'Update License(s)', 'conductor' ), 'primary', 'submit', false );
				// No valid license exists
				else
					submit_button( __( 'Activate License(s)', 'conductor' ), 'primary', 'submit', false );
			?>
			<?php submit_button( __( 'Deactivate License(s)', 'conductor' ), 'secondary', Conductor_Options::$option_name . '_license[deactivate]', false ); ?>
		</p>
	</form>

	<?php do_action( 'conductor_options_license_form_after' ); ?>

	<?php include_once 'html-conductor-options-sidebar.php'; ?>
</div>