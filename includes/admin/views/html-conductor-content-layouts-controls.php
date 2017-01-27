<?php
	global $wpdb;

	// Grab public post types as objects
	$public_post_types = get_post_types( array( 'public' => true ), 'objects' );
?>

<div class="conductor-content-layouts-controls">
	<div id="setting-error-conductor-content-layouts" class="settings-error"></div>

	<select id="conductor_content_types" name="conductor_content_types" class="conductor-content-types conductor-content-types-select conductor-select">
		<option value=""><?php _e( 'Select a Content Type', 'conductor' ); ?></option>

		<?php do_action( 'conductor_content_types_before' ); ?>

		<optgroup label="<?php esc_attr_e( 'Built-In', 'conductor' ); ?>">
			<option value="front_page" data-content-type="built-in" data-permalink="<?php echo esc_attr( trailingslashit( home_url() ) ); ?>"><?php _e( 'Front Page', 'conductor' ); ?></option>
			<option value="home" data-content-type="built-in" data-permalink="<?php echo esc_attr( ( $page_for_posts = get_option( 'page_for_posts' ) ) ? get_permalink( get_option( 'page_for_posts' ) ) : trailingslashit( home_url() ) ); ?>"><?php _e( 'Blog', 'conductor' ); ?></option>
			<?php do_action( 'conductor_content_types_built_in' ); ?>
		</optgroup>

		<optgroup label="<?php esc_attr_e( 'Category Archive', 'conductor' ); ?>">
			<?php
				$category_args = array(
					'orderby' => 'name',
					'order' => 'ASC',
					'show_count' => false,
					'hide_empty'  => false,
					'child_of' => false,
					'selected' => false,
					'hierarchical' => true,
					'depth' => 0,
					'hide_if_empty' => false,
					'walker' => new Walker_ConductorCategoryDropdown
				);

				// Fetch the categories (custom Walker adds the data-content-type attribute)
				echo walk_category_dropdown_tree( get_categories( $category_args ), $category_args['depth'], $category_args );
			?>
		</optgroup>

		<?php
			// Public Custom Post Types (further filtered to remove those that are not built-in, do not have archives, and do not have rewrite rules)
			$public_custom_post_types = wp_list_filter( $public_post_types, array( '_builtin' => true, 'has_archive' => false, 'rewrite' => false ), 'NOT' );

			if ( ! empty( $public_custom_post_types ) ) :
		?>
			<optgroup label="<?php esc_attr_e( 'Post Type Archive', 'conductor' ); ?>">
				<?php foreach ( $public_custom_post_types as $public_custom_post_type ) : ?>
					<option value="<?php echo esc_attr( $public_custom_post_type->name ); ?>" data-content-type="post-type" data-permalink="<?php echo esc_attr( get_post_type_archive_link( $public_custom_post_type->name ) ); ?>"><?php echo $public_custom_post_type->labels->singular_name; ?></option>
				<?php endforeach; ?>
			</optgroup>
		<?php
			endif;
		?>

		<?php
			// Public Post Types (further filtered to remove those that are not attachments)
			$public_post_types_without_attachments = wp_list_filter( $public_post_types, array( 'name' => 'attachment' ), 'NOT' );

			// Loop through post types
			if ( ! empty( $public_post_types_without_attachments ) )
				foreach ( $public_post_types_without_attachments as $post_type => $post_type_obj ) :
					// Custom SQL query used to fetch custom post type post data
					$posts = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT SQL_CALC_FOUND_ROWS $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_name FROM $wpdb->posts WHERE 1=1 AND $wpdb->posts.post_type = '%s' AND $wpdb->posts.post_status = 'publish' ORDER BY $wpdb->posts.post_title ASC LIMIT 0, %d", $post_type, wp_count_posts( $post_type )->publish
						)
					);

					// If there are posts within this custom post type
					if ( ! empty( $posts ) ) :
						// Switch based on post type
						switch ( $post_type ) {
							// Post
							case 'post':
								$permalink_query_arg = 'p';
							break;

							// Page
							case 'page':
								$permalink_query_arg = 'page_id';
							break;

							// Default
							default:
								$permalink_query_arg = $post_type;
							break;
						}
		?>
						<optgroup label="<?php echo esc_attr( sprintf( __( 'Single %1$s', 'conductor' ), $post_type_obj->labels->singular_name ) ); ?>">
							<?php
								foreach ( $posts as $post ) :
									// Switch based on post type
									switch ( $post_type ) {
										// Post
										case 'post':
											$permalink_query_arg_value = $post->ID;
										break;

										// Page
										case 'page':
											$permalink_query_arg_value = $post->ID;
										break;

										// Default
										default:
											$permalink_query_arg_value = $post->post_name;
										break;
									}
							?>
								<option value="<?php echo esc_attr( $post->ID ); ?>" data-content-type="<?php echo esc_attr( $post_type ); ?>" data-permalink="<?php esc_attr_e( sprintf( '%1$s?%2$s=%3$s', trailingslashit( home_url() ), $permalink_query_arg, $permalink_query_arg_value ), 'conductor' ); ?>"><?php echo ( $post->post_title === '' ) ? sprintf( __( '#%d (no title)', 'conductor' ), $post->ID ) : $post->post_title; ?></option>
							<?php
								endforeach;
							?>
						</optgroup>
		<?php
					endif;
				endforeach;
		?>

		<?php do_action( 'conductor_content_types_after', $public_post_types, $public_custom_post_types ); ?>
	</select>

	<br />

	<input type="button" id="conductor_content_layouts_add" class="button-primary conductor-content-layouts-add" name="conductor[content_layouts][add]" value="<?php esc_attr_e( 'Conduct this Content Type', 'conductor' ); ?>" />
	<img src="<?php echo admin_url( '/images/spinner.gif' ); ?>" class="conductor-spinner" alt="<?php esc_attr_e( 'Loading...', 'conductor' ); ?>" title="<?php esc_attr_e( 'Loading...', 'conductor' ); ?>" style="<?php echo ( ! is_customize_preview() ) ? 'display: none;' : false; ?>" />
</div>