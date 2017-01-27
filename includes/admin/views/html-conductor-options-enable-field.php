<?php $conductor_options = Conductor_Admin_Options_Views::$options; // Option values are loaded in the Conductor_Admin_Options_Views Class on instantiation. ?>

<div class="checkbox conductor-checkbox conductor-checkbox-enable" data-label-left="<?php esc_attr_e( 'On', 'conductor' ); ?>" data-label-right="<?php esc_attr_e( 'Off', 'conductor' ); ?>">
	<input type="checkbox" id="conductor_enabled" name="conductor[enabled]" <?php checked( $conductor_options['enabled'] ); ?> />
	<label for="conductor_enabled">| | |</label>
</div>
