<p>
	<?php
		printf( __( 'Use this option to enable or disable the Conductor <a href="%1$s" target="_blank">WordPress REST API</a>.', 'conductor' ), esc_url( 'http://v2.wp-api.org/' ) );
	?>
</p>

<p>
	<em>
		<?php _e( 'Please Note: The Conductor REST API is required for Conductor Widget AJAX requests to function. If the Conductor REST API is turned off, the "Enable AJAX" setting in Conductor Widgets will be disabled. This may affect Conductor add-on functionality as well.', 'conductor' ); ?>
	</em>
</p>
