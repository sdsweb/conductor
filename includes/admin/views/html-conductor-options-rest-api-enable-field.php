<?php $conductor_options = Conductor_Admin_Options_Views::$options; // Option values are loaded in the Conductor_Admin_Options_Views Class on instantiation. ?>

<div class="checkbox conductor-checkbox conductor-checkbox-rest-api-enable" data-label-left="<?php esc_attr_e( 'On', 'conductor' ); ?>" data-label-right="<?php esc_attr_e( 'Off', 'conductor' ); ?>">
	<input type="checkbox" id="conductor_rest_api_enabled" name="conductor[rest][enabled]" <?php checked( $conductor_options['rest']['enabled'] ); ?> />
	<label for="conductor_rest_api_enabled">| | |</label>
</div>


<?php // checked( ( isset( $conductor_options['rest']['enabled'] ) ) ? $conductor_options['rest']['enabled'] : true ); ?>