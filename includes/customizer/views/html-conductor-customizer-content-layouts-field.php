<?php
	$conductor_options = Conductor_Options::get_options();

	include_once Conductor::plugin_dir() . '/includes/admin/views/js-conductor-template-content-layouts.php'; // Underscore.js Template
?>

<div class="conductor-content-layouts-container">
	<?php include_once Conductor::plugin_dir() . '/includes/admin/views/html-conductor-content-layouts-controls.php'; // Conductor Content Layouts Controls ?>

	<div class="conductor-content-layouts">
		<?php
			// Output existing content layouts
			if ( ! empty( $conductor_options['content_layouts'] ) )
				foreach ( $conductor_options['content_layouts'] as $content_layout_id => $content_layout )
					include Conductor::plugin_dir() . '/includes/admin/views/html-conductor-template-content-layouts.php';
		?>
	</div>

	<?php
		// Output Conductor help
		include_once 'html-conductor-customizer-content-layouts-help.php';
	?>
</div>