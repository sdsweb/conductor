<div id="conductor-sidebar" class="sidebar">
	<?php do_action( 'conductor_admin_options_sidebar_before' ); ?>

	<div class="yt-subscribe conductor-widget">
		<div class="g-ytsubscribe" data-channel="slocumstudio" data-layout="default"></div>
		<script src="https://apis.google.com/js/plusone.js"></script>
	</div>

	<a href="https://twitter.com/slocumstudio" class="twitter-follow-button" data-show-count="false" data-size="large" data-dnt="true">Follow @slocumstudio</a>
	<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>

	<br />
	<br />

	<div class="slocum-studio conductor-widget">
		<?php printf( __( 'Brought to you by <a href="%1$s" target="_blank">Slocum Studio</a>', 'conductor' ), 'https://conductorplugin.com/?utm_source=conductor&utm_medium=link&utm_content=conductor-sidebar-branding&utm_campaign=conductor' ); ?>
	</div>

	<?php do_action( 'conductor_admin_options_sidebar_after' ); ?>
</div>