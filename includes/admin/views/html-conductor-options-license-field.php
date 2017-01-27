<?php $conductor_license_options = Conductor_Admin_License_Options_Views::$options; // Option values are loaded in the Conductor_Admin_License_Options_Views Class on instantiation. ?>

<?php
	/*
	 * Determine license status and license key input box CSS classes
	 */
	$conductor_license_key_classes = array();

	// Valid
	if ( ! empty( $conductor_license_options['key'] ) && $conductor_license_options['status'] === 'valid' ) {
		$conductor_license_key_classes[] = 'has-license';
		$conductor_license_key_classes[] = 'active';
	}
	// Invalid
	else if ( ! empty( $conductor_license_options['key'] ) && $conductor_license_options['status'] === 'invalid' ) {
		$conductor_license_key_classes[] = 'has-license';
		$conductor_license_key_classes[] = 'inactive';
	}
	// No License
	else if ( empty( $conductor_license_options['key'] ) )
		$conductor_license_key_classes[] = 'no-license';

	$conductor_license_key_classes = implode(' ', $conductor_license_key_classes );
?>

<div class="input conductor-input conductor-input-license-key <?php echo $conductor_license_key_classes; ?>">
	<input type="text" id="conductor_license_key" name="conductor_license[key]" class="large-text <?php echo $conductor_license_key_classes; ?>" value="<?php echo esc_attr( $conductor_license_options['key'] ); ?>" autocomplete="off" />
</div>
