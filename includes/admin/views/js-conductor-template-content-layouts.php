<script type="text/template" id="tmpl-conductor-content-layout">
	<?php $content_layouts = Conductor_Options::get_content_layouts(); ?>

	<div class="conductor-content-layout-wrap conductor-content-layout-wrap conductor-content-layout-wrap-js conductor-content-layout-{{{ data.field_type }}}-{{{ data.field_id }}} {{{ data.field_id }}} {{{ data.field_type }}}">
		<h4 class="conductor-content-layout-title">
			{{{ data.field_label }}}
			<!-- - a href="{{{ data.edit_link_url }}}" class="conductor-edit-content-layout" data-field-num="{{{ data.field_num }}}">Conduct</a-->
			- <a href="#remove" class="conductor-remove-content-layout" data-field-num="{{{ data.field_num }}}">Remove</a>
		</h4>

		<?php foreach( $content_layouts as $name => $atts ) : ?>
			<div class="conductor-content-layout conductor-content-layout-<?php echo $name; ?>">
				<label class="content-layout-label" data-field-num="{{{ data.field_num }}}">
					<input type="radio" id="conductor_content_layouts_name_<?php echo $name; ?>" name="conductor[content_layouts][{{{ data.field_num }}}][{{{ data.field_type }}}][{{{ data.field_id }}}]" value="<?php echo $name; ?>" <# if ( data.selected === '<?php echo $name; ?>' || ( ! data.selected && <?php echo ( isset( $atts['default'] ) && $atts['default'] ) ? 'true': 'false'; ?> ) ) { #> <?php checked( true ); ?> <# } #> <?php echo ( ! empty( $args ) && isset( $args['customizer']['link'] ) ) ? $args['customizer']['link'] : false; ?> />

					<div class="conductor-content-layout-preview">
						<?php
							if ( isset( $atts['preview_values'] ) )
								vprintf( $atts['preview'], $atts['preview_values'] );
							else
								echo $atts['preview'];
						?>
					</div>
				</label>
			</div>
		<?php endforeach; ?>
	</div>
</script>