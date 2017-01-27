<div class="wrap about-wrap">
	<h1><?php _e( 'Conductor Help', 'conductor' ); ?></h1>

	<!--div class="about-text conductor-about-text">
		<?php //_e( 'Having trouble using Conductor?', 'conductor' ); ?>
	</div-->

	<?php do_action( 'conductor_options_notifications' ); ?>

	<div id="conductor-form">
		<h3><?php _e( 'Documentation', 'conductor' ); ?></h3>
		<p><?php printf( __( '<a href="%1$s" target="_blank">Click here to download the User Guide</a>', 'conductor' ), esc_url( 'https://slocumthemes.com/conductor-userguide.pdf' ) ); ?></p>

		<h3><?php _e( 'Contact Us', 'conductor' ); ?></h3>
		<p><?php printf( __( '<a href="%1$s" target="_blank">Click here to contact us</a>', 'conductor' ), esc_url( 'https://conductorplugin.com/contact/?utm_source=conductor&utm_medium=link&utm_content=conductor-help&utm_campaign=conductor' ) ); ?></p>

		<h3><?php _e( 'WordPress Snapshot', 'conductor' ); ?></h3>
		<p><?php _e( 'The following information can be helpful to us when an issue with Conductor may arise. If a support tech requests a "snapshot", please send us this information by copying and pasting it into the support ticket.', 'conductor' ); ?></p>

		<?php
			// WordPress Snapshot Details
			$conductor_wp_snapshot = Conductor_Admin_Help::get_snapshot_details();

			if ( ! empty( $conductor_wp_snapshot ) ) :
		?>
			<textarea class="conductor-wp-snapshot" rows="20" cols="86" onclick="this.focus(); this.select()" readonly="readonly">
				<?php
					foreach ( $conductor_wp_snapshot as $snapshot_key => $snapshot_item ) {
						echo ( ! empty( $snapshot_item['value'] ) ) ? $snapshot_item['label'] : "\n" . $snapshot_item['label'];
						echo ( empty( $snapshot_item['value'] ) ) ? "\n" . '----------' : false;
						echo ( ! empty( $snapshot_item['value'] ) ) ? ' ' .$snapshot_item['value'] : false;
						echo "\n";
					}
				?>
			</textarea>
		<?php
			endif;
		?>
	</div>

	<?php include_once 'html-conductor-options-sidebar.php'; ?>
</div>