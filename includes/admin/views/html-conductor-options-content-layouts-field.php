<?php
	$conductor_options = Conductor_Admin_Options_Views::$options; // Option values are loaded in the Conductor_Admin_Options_Views Class on instantiation.

	include_once 'js-conductor-template-content-layouts.php'; // Underscore.js Template
?>

<div class="conductor-content-layouts-container">
	<?php include_once 'html-conductor-content-layouts-controls.php'; // Conductor Content Layouts Controls ?>

	<div class="conductor-content-layouts">
		<?php
		// Output existing content layouts
		if ( ! empty( $conductor_options['content_layouts'] ) )
			foreach ( $conductor_options['content_layouts'] as $content_layout_id => $content_layout )
				include 'html-conductor-template-content-layouts.php';
		?>
	</div>

	<?php
		// Output Conductor help
		if ( empty( $conductor_options['content_layouts'] ) )
			include_once 'html-conductor-content-layouts-help.php';
	?>
</div>