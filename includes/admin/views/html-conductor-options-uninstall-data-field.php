<?php $conductor_options = Conductor_Admin_Options_Views::$options; // Option values are loaded in the Conductor_Admin_Options_Views Class on instantiation. ?>

<div class="checkbox conductor-checkbox conductor-checkbox-enable" data-label-left="<?php esc_attr_e( 'Yes', 'conductor' ); ?>" data-label-right="<?php esc_attr_e( 'No', 'conductor' ); ?>">
	<input type="checkbox" id="conductor_uninstall_data" name="conductor[uninstall][data]" <?php checked( $conductor_options['uninstall']['data'] ); ?> />
	<label for="conductor_uninstall_data">| | |</label>
</div>
