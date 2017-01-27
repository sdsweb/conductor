<div class="wrap about-wrap">
	<?php
		global $_wp_admin_css_colors;

		// Output styles to match selected admin color scheme
		if ( ( $user_admin_color = get_user_option( 'admin_color' ) ) && isset( $_wp_admin_css_colors[$user_admin_color] ) && Conductor::wp_version_compare( '3.8' ) ) :
	?>
		<style type="text/css" scoped>
			/* Checkboxes */
			.conductor-checkbox:before {
				background: <?php echo $_wp_admin_css_colors[$user_admin_color]->colors[2]; ?>;
			}

			/* Content Layouts */
			.conductor-content-layout:hover .conductor-content-layout-preview,
			.conductor-content-layout input[type=radio]:checked + .conductor-content-layout-preview {
				border: 1px solid <?php echo $_wp_admin_css_colors[$user_admin_color]->colors[2]; ?>;
			}

			.conductor-content-layout:hover .conductor-content-layout-preview  .col,
			.conductor-content-layout input[type=radio]:checked + .conductor-content-layout-preview .col {
				color: #fff;
				background: <?php echo $_wp_admin_css_colors[$user_admin_color]->colors[2]; ?>;
			}

			.conductor-content-layout:hover .conductor-content-layout-preview  .col-sidebar,
			.conductor-content-layout input[type=radio]:checked + .conductor-content-layout-preview .col-sidebar {
				color: #fff;
				background: <?php echo $_wp_admin_css_colors[$user_admin_color]->colors[3]; ?>;
			}
		</style>
	<?php
		endif;
	?>

	<h1><?php _e( 'Conductor Options', 'conductor' ); ?></h1>

	<!--div class="about-text conductor-about-text">
		<?php // _e( 'Welcome to Conductor!', 'conductor' ); ?>
	</div-->

	<?php do_action( 'conductor_options_notifications' ); ?>

	<?php
		settings_errors( 'general' ); // General Settings Errors
		settings_errors( Conductor_Options::$option_name ); // Conductor Settings Errors
	?>

	<h3 class="nav-tab-wrapper conductor-nav-tab-wrapper conductor-options-tab-wrap">
		<a href="#general" id="general-tab" class="nav-tab conductor-tab nav-tab-active"><?php _e( 'General', 'conductor' ); ?></a>
		<a href="#advanced" id="advanced-tab" class="nav-tab conductor-tab"><?php _e( 'Advanced', 'conductor' ); ?></a>
		<?php do_action( 'conductor_options_navigation_tabs' ); // Hook for extending tabs ?>
	</h3>

	<form method="post" action="options.php" enctype="multipart/form-data" id="conductor-form">
		<?php settings_fields( Conductor_Options::$option_name ); ?>
		<input type="hidden" name="conductor_options_tab" id="conductor_options_tab" value="" />

		<div id="general-tab-content" class="conductor-tab-content conductor-tab-content-active">
			<?php
				/**
				 * Conductor General Settings
				 */
				do_settings_sections( Conductor_Options::$option_name . '_general' );
			?>
		</div>

		<div id="advanced-tab-content" class="conductor-tab-content">
			<?php
				/**
				 * Conductor Advanced Settings
				 */
				do_settings_sections( Conductor_Options::$option_name . '_advanced' );
			?>
		</div>

		<?php do_action( 'conductor_options_settings' ); // Hook for extending settings ?>

		<p class="submit">
			<?php submit_button( __( 'Save Options', 'conductor' ), 'primary', 'submit', false ); ?>
			<?php submit_button( __( 'Restore Defaults', 'conductor' ), 'secondary', 'conductor[reset]', false ); ?>
		</p>
	</form>

	<?php include_once 'html-conductor-options-sidebar.php'; ?>
</div>