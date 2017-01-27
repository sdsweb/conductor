<?php
/**
 * Conductor Customizer (Customizer functionality)
 *
 * @class Conductor_Customizer
 * @author Slocum Studio
 * @version 1.4.0
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Customizer' ) ) {
	final class Conductor_Customizer {
		/**
		 * @var string
		 */
		public $version = '1.4.0';

		/**
		 * @var array
		 */
		public $conductor_sidebars_args_localize = array();

		/**
		 * @var array
		 */
		public $old_sidebars_widgets = array();

		/**
		 * @var Conductor, Instance of the class
		 */
		protected static $_instance;

		/**
		 * Function used to create instance of class.
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) )
				self::$_instance = new self();

			return self::$_instance;
		}


		/**
		 * This function sets up all of the actions and filters on instance. It also loads (includes)
		 * the required files and assets.
		 */
		function __construct() {
			// Note Hooks
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) ); // Plugins Loaded

			// Conductor Hooks
			add_filter( 'conductor_widget_admin_localize', array( $this, 'conductor_sidebars_args_localize' ) ); // Conductor Widget Admin Localize
			add_filter( 'conductor_customizer_previewer_localize', array( $this, 'conductor_sidebars_args_localize' ) ); // Conductor Customizer Previewer Localize
			add_action( 'wp_ajax_conductor-update-widget', array( $this, 'wp_ajax_conductor_update_widget' ), 1 ); // Conductor Update Widget AJAX Action

			// Hooks
			add_action( 'wp', array( $this, 'wp' ), 1 ); // WP (early; before core Widgets Customizer logic)
			add_action( 'wp_loaded', array( $this, 'wp_loaded' ), 1 ); // WP Loaded (early; before core Customizer)
			add_action( 'customize_register', array( $this, 'customize_register' ), 0 ); // Customizer Register (before anything else)
			add_action( 'customize_preview_init', array( $this, 'customize_preview_init' ) ); // Customizer Preview Initialization
			add_action( 'customize_controls_print_footer_scripts', array( $this, 'customize_controls_print_footer_scripts' ) ); // Customizer Footer Scripts
		}

		/**
		 * Include required core files used in admin and on the front-end.
		 */
		private function includes() {
			include_once 'customizer/class-conductor-customizer-content-layouts-setting.php'; // Conductor Customizer Content Layouts Setting
			include_once 'customizer/class-conductor-customizer-content-layouts-control.php'; // Conductor Customizer Content Layouts Control
		}

		/**
		 * This function checks to see if Note is active and sets up hooks.
		 */
		public function plugins_loaded() {
			// If Note is active
			if ( Conductor::is_note_active() )
				add_filter( 'note_sidebar_ui_buttons', array( $this, 'note_sidebar_ui_buttons' ) ); // Note Sidebar UI Buttons
		}

		/**
		 * This function adds Conductor Sidebar data to the localized data on the Conductor Widgets.
		 */
		public function conductor_sidebars_args_localize( $localize ) {
			// Create the customizer key if it doesn't exist
			if ( ! isset( $localize['customizer'] ) )
				$localize['customizer'] = array();

			// Setup Conductor sidebars localize data if it doesn't exist yet
			if ( empty( $this->conductor_sidebars_args_localize ) )
				$this->setup_conductor_sidebars_localize_data();

			// Create the customizer sidebars key if it doesn't exist
			if ( ! isset( $localize['customizer']['sidebars'] ) && ! empty( $this->conductor_sidebars_args_localize ) )
				$localize['customizer']['sidebars'] = $this->conductor_sidebars_args_localize;
			// Otherwise merge the customizer sidebars data
			else if ( ! empty( $this->conductor_sidebars_args_localize ) )
				$localize['customizer']['sidebars'] = array_merge_recursive( $localize['customizer']['sidebars'], $this->conductor_sidebars_args_localize );

			return $localize;
		}

		/**
		 * This function is the callback function for the conductor-update-widget AJAX action.
		 */
		public function wp_ajax_conductor_update_widget() {
			global $wp_customize, $wp_registered_widget_updates;

			if ( ! is_user_logged_in() )
				wp_die( 0 );

			check_ajax_referer( 'update-widget', 'nonce' );

			if ( ! current_user_can( 'edit_theme_options' ) )
				wp_die( -1 );

			// Widget data to be returned
			$widget_data = array();

			// Loop through each widget to setup sanitized widget setting data
			if ( isset( $_POST['widgets'] ) ) {
				foreach ( $_POST['widgets'] as &$widget )
					// If the sanitized_widget_setting data is set
					if ( isset( $widget['sanitized_widget_setting'] ) && ! empty( $widget['sanitized_widget_setting'] ) ) {
						// Decode the sanitized widget setting
						$widget['sanitized_widget_setting'] = json_decode( wp_unslash( $widget['sanitized_widget_setting'] ), true );

						// Determine if any of the required parameters are missing
						if ( ! array_key_exists( 'encoded_serialized_instance', $widget['sanitized_widget_setting'] ) || ! array_key_exists( 'is_widget_customizer_js_value', $widget['sanitized_widget_setting'] ) || ! array_key_exists( 'instance_hash_key', $widget['sanitized_widget_setting'] ) ) {
							// Update the sanitized widget setting
							$widget['sanitized_widget_setting'] = $wp_customize->widgets->sanitize_widget_js_instance( $widget['sanitized_widget_setting'] );
						}
					}
			}
			else
				wp_send_json_error( 'missing-widgets' );

			// Unset $widget since it currently is set to [the reference of the] last element in $_POST['widgets']
			unset( $widget );

			do_action( 'load-widgets.php' );
			do_action( 'widgets.php' );
			do_action( 'sidebar_admin_setup' );

			// Loop through each widget to setup sanitized widget setting data
			if ( isset( $_POST['widgets'] ) )
				foreach ( $_POST['widgets'] as $widget_key => $widget ) {
					// Setup the global $_POST values
					$_POST['sanitized_widget_setting'] = $widget['sanitized_widget_setting'];
					$_POST['sanitized_widget_setting']['is_widget_customizer_js_value'] = true; // Set is_widget_customizer_js_value because WP_Customize_Widgets::sanitize_widget_js_instance() expects it to be set
					$_POST['sanitized_widget_setting'] = json_encode( wp_slash( $_POST['sanitized_widget_setting'] ) );

					$_POST['widget-id'] = $widget['widget-id'];
					$_POST['id_base'] = $widget['id_base'];
					$_POST['widget-width'] = $widget['widget-width'];
					$_POST['widget-height'] = $widget['widget-height'];
					$_POST['widget_number'] = $widget['widget_number'];
					$_POST['multi_number'] = $widget['multi_number'];
					$_POST['add_new'] = $widget['add_new'];

					// Grab the widget ID
					$widget_id = ( isset( $widget['widget-id'] ) ) ? $widget['widget-id'] : null;

					// "Update" the widget
					$updated_widget = $wp_customize->widgets->call_widget_update( $widget_id ); // => {instance,form}

					// If we have a valid updated object
					if ( ! is_wp_error( $updated_widget ) ) {
						$form = $updated_widget['form'];
						$instance = $wp_customize->widgets->sanitize_widget_js_instance( $updated_widget['instance'] );
						$widget_data[$widget_key] = compact( 'form', 'instance' );
						$widget_data[$widget_key]['data'] = $widget;

						// Reset the updated flag on the widget control
						foreach ( ( array ) $wp_registered_widget_updates as $id_base => $control )
							if ( $id_base === $widget['id_base'] ) {
								// 0 index on the callback is always the WP_Widget instance
								$control['callback'][0]->updated = false;
								break;
							}
					}
				}

			wp_send_json_success( $widget_data );
		}

		/**
		 * This function sets up Conductor localization data after Previewer filters have been initialized.
		 */
		public function wp() {
			// Setup Conductor sidebars localize data if it doesn't exist yet
			if ( empty( $this->conductor_sidebars_args_localize ) )
				$this->setup_conductor_sidebars_localize_data();
		}

		/**
		 * This function checks to see if a theme is being previewed in the Customizer and attempts
		 * to keep Conductor Sidebars and widgets.
		 */
		public function wp_loaded() {
			global $wp_customize;

			// Bail if the Customizer isn't ready or we're doing AJAX or the theme is active
			if ( ! is_a( $wp_customize, 'WP_Customize_Manager' ) || $wp_customize->doing_ajax() || $wp_customize->is_theme_active() )
				return;

			// Grab the current version of the sidebar widgets
			$this->old_sidebars_widgets = wp_get_sidebars_widgets();

			// Filter the sidebars widgets
			add_filter( 'option_sidebars_widgets', array( $this, 'option_sidebars_widgets' ), 20 ); // After core Customizer
		}

		/**
		 * This function filters the sidebars_widgets option after it is returned from the database.
		 */
		public function option_sidebars_widgets( $value ) {
			global $wp_customize;

			// Bail if the Customizer isn't ready
			if ( ! is_a( $wp_customize, 'WP_Customize_Manager' ) )
				return $value;

			$conductor_sidebars = Conduct_Sidebars(); // Grab the Conductor Sidebars instance

			// Attempt to save Conductor sidebars/widgets
			$value = $conductor_sidebars->pre_update_option_sidebars_widgets( $value, $this->old_sidebars_widgets );

			return $value;
		}

		/**
		 * This function registers sections and settings for use in the Customizer.
		 */
		public function customize_register( $wp_customize ) {
			// Load required assets
			$this->includes();

			/**
			 * Conductor
			 */

			$conductor_option_defaults = Conductor_Options::get_option_defaults();

			// Setting (data is sanitized upon update_option() call using the sanitize function in Conductor_Admin_Options)
			$wp_customize->add_setting(
				new Conductor_Customizer_Content_Layouts_Setting( $wp_customize,
					'conductor[content_layouts]', // IDs can have nested array keys
					array(
						'default' => $conductor_option_defaults['content_layouts'],
						'type' => 'option'
					)
				)
			);

			// Section
			$wp_customize->add_section(
				'conductor_content_layouts',
				array(
					'title' => __( 'Conductor Layouts', 'conductor' ),
					'priority' => 90
				)
			);

			// Control
			$wp_customize->add_control(
				new Conductor_Customizer_Content_Layouts_Control(
					$wp_customize,
					'content_layouts',
					array(
						'label' => __( 'Select a Page to Conduct', 'conductor' ),
						'section' => 'conductor_content_layouts',
						'settings' => 'conductor[content_layouts]',
						'type' => 'conductor_content_layouts' // Used in js controller
					)
				)
			);

			// Register all sidebars for all Conductor content layouts
			$this->register_conductor_sidebars();
		}

		/**
		 * This function fires on the initialization of the Customizer Previewer. We add actions that pertain to the
		 * Customizer preview window here. The actions added here are fired only in the Customizer Previewer.
		 */
		public function customize_preview_init() {
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) ); // Conductor Content Layout Previewer Scripts/Styles
			add_action( 'dynamic_sidebar_params', array( $this, 'dynamic_sidebar_params' ) ); // Filter Dynamic Sidebar Parameters (Conductor Widgets)
			add_filter( 'conductor_query_paginate_links_args', array( $this, 'conductor_query_paginate_links_args' ), 10, 4 ); // Conductor Widget Pagination


			/*
			 * Register all (newly/missing) added Sidebars
			 *
			 * This prevents a case where a new content layout has just been added via the Customizer and the Previewer
			 * does not know about the registered sidebars (i.e. they do not appear in $GLOBALS['wp_registered_sidebars']).
			 */
			$this->register_conductor_sidebars();
		}

		/**
		 * This function outputs scripts and styles in the the Customizer preview only.
		 */
		public function wp_enqueue_scripts() {
			// Dashicons
			wp_enqueue_style( 'dashicons' );

			// Content Layouts Preview Script
			wp_enqueue_script( 'conductor-content-layouts-customizer-control-preview', Conductor::plugin_url() . '/assets/js/conductor-content-layouts-customizer-control-preview.js', array( 'customize-preview-widgets' ), Conductor::$version, true );

			// Content Layouts Preview Stylesheet
			wp_enqueue_style( 'conductor-content-layouts-customizer-control-preview', Conductor::plugin_url() . '/assets/css/conductor-content-layouts-customizer-control-preview.css', ( Conductor::is_conductor() ) ? array( 'conductor', 'buttons' ) : array( 'buttons' ), Conductor::$version );

			// Grab the Conductor Widget instance
			$conductor_widget = Conduct_Widget();

			// Localize the Content Layouts Preview Stylesheet script information
			wp_localize_script( 'conductor-content-layouts-customizer-control-preview', 'conductor', apply_filters( 'conductor_customizer_previewer_localize', array(
				'customizer' => array(
					'sidebars' => array(
						'args' => array(),
						'ids' => array()
					),
					'previewer' => array(
						'options' => $this->conductor_previewer_localize_data()
					)
				),
				'widgets' => array(
					// Conductor Widget
					'conductor' => array(
						'id_base' => $conductor_widget->id_base
					)
				)
			) ) );
		}

		/**
		 * This function prepends input elements to widgets for use in the Previewer JS scripts.
		 */
		public function dynamic_sidebar_params( $params ) {
			// Bail if we're not on a Conductor content layout
			if ( ! Conductor::is_conductor() )
				return $params;

			// Grab the Conductor Sidebars instance
			$conductor_sidebars = Conduct_Sidebars();

			// Grab the current Conductor layout
			$conductor_content_layout = Conductor::get_conductor_content_layout();

			// Generate a content layout ID
			$content_layout_id = $conductor_content_layout['field_type'] . '-' . $conductor_content_layout['field_id'];

			// Grab the reference to the current Conductor content layout sidebar IDs
			$conductor_content_layout_sidebars = ( isset( $conductor_sidebars->registered_content_layout_sidebars[$content_layout_id] ) ) ? $conductor_sidebars->registered_content_layout_sidebars[$content_layout_id] : false;

			// Conductor Sidebar widgets only
			if ( ! empty( $conductor_content_layout_sidebars ) && in_array( $params[0]['id'], $conductor_content_layout_sidebars ) ) {
				$widget_after = '<input type="hidden" name="widget_number" class="widget-number" value="' . esc_attr( $params[1]['number'] ) . '" />'; // Widget Number
				$widget_after .= '<input type="hidden" name="widget_id" class="widget-id" value="' . esc_attr( $params[0]['widget_id'] ) . '" />'; // Widget ID
				$widget_after .= '<input type="hidden" name="sidebar_name" class="sidebar-name" value="' . esc_attr( $params[0]['name'] ) . '" />'; // Sidebar Name
				$widget_after .= '<input type="hidden" name="sidebar_id" class="sidebar-id" value="' . esc_attr( $params[0]['id'] ) . '" />'; // Sidebar ID

				// Modify the 'after_widget' param to include data we'll send to Customizer
				$params[0]['after_widget'] = $widget_after . $params[0]['after_widget'];
			}

			return $params;
		}

		/**
		 * This function filters the Conductor Widget pagination arguments in the Customizer/Previewer
		 * only due to the use of shortlinks for categories and single pages.
		 */
		// TODO: Parse the query_vars property instead of query_string
		public function conductor_query_paginate_links_args( $paginate_links_args, $query, $echo, $conductor_widget_query ) {
			global $wp;

			// Category archives and single pages
			if ( ( is_category() || is_page() ) && ! empty( $wp->query_string ) ) {
				// Permalink structure
				$permalink_structure = get_option( 'permalink_structure' );

				// Parse query variables
				$query_vars = explode( '&', $wp->query_string );

				// Further parsing of query variables
				foreach ( $query_vars as &$query_var )
					$query_var = explode( '=', $query_var ); // [0] is query variable name, [1] is the value

				// Determine if we're on the "short" link or the regular permalink
				if ( $permalink_structure )
					foreach( $query_vars as $query_var )
						// "cat" query variable or "page_id" query variable; "short" link
						if ( is_array( $query_var ) && ( ( is_category() && $query_var[0] === 'cat' ) || ( is_page() && $query_var[0] === 'page_id' ) ) ) {
							$paginate_links_args['format'] = '&paged=%#%';
							break;
						}
			}

			return $paginate_links_args;
		}

		/**
		 * This function outputs an HTML <form> element so we can refresh the Customizer main window as necessary.
		 */
		public function customize_controls_print_footer_scripts() {
			// Conductor Widget Re-Order Template
			self::conductor_widget_reorder_template();
		}


		/********
		 * Note *
		 ********/

		/**
		 * This function adds an "Add Conductor Widget" button to the Note Sidebar UI Buttons.
		 */
		public function note_sidebar_ui_buttons( $buttons ) {
			// Reference "Add Note Widget" button index
			$note_button_index = -1;
			$note_button = $new_note_buttons = array();

			// Loop through each button
			foreach ( $buttons as $index => $button )
				// Find the "Remove Note Sidebar" button
				if ( $button['id'] === 'add-note-widget' ) {
					// Store the index
					$note_button_index = $index;

					// Store the button arguments
					$note_button = $button;

					break;
				}

			// Setup the "Add Conductor Widget" button
			$conductor_note_button = array(
				'id' => 'add-conductor-widget',
				'label' => 'C',
				'title' => __( 'Add Conductor Widget', 'conductor' )
			);

			// Add the "Add Conductor Widget" button (after "Add Note Widget" button)
			if ( $note_button_index && ! empty( $note_button ) ) {
				// "Add Note Widget" Button
				$new_note_buttons[] = $note_button;

				// "Add Conductor Widget" Button
				$new_note_buttons[] = $conductor_note_button;

				// Splice the buttons
				array_splice( $buttons, $note_button_index, 1, $new_note_buttons );
			}

			return $buttons;
		}


		/**********************
		 * Internal Functions *
		 **********************/

		/**
		 * This function registers any newly added/missing Conductor sidebars for use in the Customizer. These sidebars
		 * will be in Conductor Options via the transient that is set on Customizer refresh.
		 */
		public function register_conductor_sidebars() {
			$conductor_options = Conductor_Options::get_options();
			$content_layouts = $conductor_options['content_layouts'];

			// Make sure we have content layouts first
			if ( isset( $content_layouts ) && ! empty( $content_layouts ) )
				foreach ( $content_layouts as $content_layout )
					// Register Content, Primary, and Secondary Sidebars (forcing registration)
					// TODO: Is it possible to keep a consistent order in the Customizer? Sidebars are not registered in order because we have to "fake" some of them on the Customizer; maybe with JS
					Conductor_Sidebars::register_conductor_sidebars( $content_layout, $content_layouts, true );
		}

		/**
		 * This function sets up Conductor sidebars localize data for use in the Customizer and Previewer.
		 */
		public function setup_conductor_sidebars_localize_data() {
			global $wp_customize;

			// Bail if the Customizer isn't ready
			if ( ! is_a( $wp_customize, 'WP_Customize_Manager' ) )
				return;

			// Grab the Conductor Sidebars instance
			$conductor_sidebars = Conduct_Sidebars();

			// Add registered sidebar data
			$this->conductor_sidebars_args_localize['ids'] = $conductor_sidebars->registered_sidebars;

			// Add registered sidebar data for content layouts
			$this->conductor_sidebars_args_localize['content_layouts'] = $conductor_sidebars->registered_content_layout_sidebars;

			// Add base registered sidebar arguments
			$this->conductor_sidebars_args_localize['args'] = $conductor_sidebars->registered_sidebars_args;

			/*
			 * Sidebars
			 */

			// Grab the sidebars widget option
			$sidebars_widgets = get_option( 'sidebars_widgets' );

			// Customizer Section prefix
			$section_prefix = 'sidebar-widgets-';

			// Flags to determine if settings, sections, and controls previously existed
			$customizer_component_flags = array(
				'setting' => false,
				'section' => false,
				'control' => false
			);

			// Loop through each sidebar within this location
			foreach ( $this->conductor_sidebars_args_localize['args'] as $sidebar_id => &$sidebar_args ) {
				// Generate a setting ID
				$setting_id = 'sidebars_widgets[' . $sidebar_id . ']';

				// If the setting doesn't exist
				if ( ! $wp_customize->get_setting( $setting_id ) )
					// Create a mock Customizer Setting
					$wp_customize->add_setting( $setting_id, $wp_customize->widgets->get_setting_args( $setting_id ) );
				// Otherwise set the existed flag
				else
					$customizer_component_flags['setting'] = true;

				// Generate a section ID
				$section_id = $section_prefix . $sidebar_id;

				// If the section doesn't exist (most likely this sidebar is not registered by default for this layout)
				if ( ! ( $customizer_section = $wp_customize->get_section( $section_id ) ) ) {
					// Create a mock Customizer Section
					// TODO: We may need our own class here like Note
					$customizer_section = new WP_Customize_Sidebar_Section( $wp_customize, $section_id, array(
						'id' => $section_id,
						'title' => $sidebar_args['name'],
						'description' => $sidebar_args['description'],
					) );
					$wp_customize->add_section( $customizer_section );
				}
				// Otherwise set the existed flag
				else
					$customizer_component_flags['section'] = true;

				// If the control doesn't exist (most likely this sidebar is not registered by default for this layout)
				if ( ! ( $customizer_control = $wp_customize->get_control( $setting_id ) ) ) {
					// Create a mock Customizer Control
					// TODO: We may need our own class here like Note
					$customizer_control = new WP_Widget_Area_Customize_Control( $wp_customize, $setting_id, array(
						'section' => $section_id,
						'sidebar_id' => $sidebar_id,
						'priority' => 0 // No active widgets
					) );
					$wp_customize->add_control( $customizer_control );
				}
				// Otherwise set the existed flag
				else
					$customizer_component_flags['control'] = true;

				// Customizer data
				$sidebar_args['customizer'] = array(
					// Setting
					'setting' => array(
						'id' => $setting_id,
						'transport' => 'refresh',
						'value' => ( isset( $sidebars_widgets[$sidebar_id] ) ) ? $sidebars_widgets[$sidebar_id] : array()
					),
					// Section
					'section' => array(
						'id' => $section_id,
						'title' => $sidebar_args['name'],
						'description' => $sidebar_args['description'],
						'sidebarId' => $sidebar_id,
						'panel' => 'widgets',
						'active' => false, // By default, Conductor content layout values are empty, this section should be inactive
						'content' => $customizer_section->get_content(), // Grab the section HTML TODO: Use an UnderscoreJS template here
						'type' => 'sidebar'
					),
					// Control
					'control' => array(
						'id' => $setting_id,
						'section' => $section_id,
						'sidebar_id' => $sidebar_id,
						'priority' => 0, // No active widgets
						'settings' => array(
							'default' => $setting_id
						),
						'active' => false, // By default, Conductor content layout values are empty, this control should be inactive
						'content' => $customizer_control->get_content(), // Grab the control HTML TODO: Use an UnderscoreJS template here
						'type' => 'sidebar_widgets'
					)
				);

				// If we're not in the Previewer, remove the mock Settings, Sections, and Controls active
				if ( ( is_customize_preview() && is_admin() ) && ! $customizer_component_flags['setting'] && ! $customizer_component_flags['section'] && ! $customizer_component_flags['control'] ) {
					// Remove Customizer mock setting, section, and control
					$wp_customize->remove_setting( $setting_id );
					$wp_customize->remove_section( $section_id );
					$wp_customize->remove_control( $setting_id );
				}

				// Reset flags to determine if settings, sections, and controls previously existed
				$customizer_component_flags = array(
					'setting' => false,
					'section' => false,
					'control' => false
				);

				/*
				 * Default widgets
				 */

				// Add base registered sidebar default widgets
				$this->conductor_sidebars_args_localize['default_widgets'] = $conductor_sidebars->default_widgets;
			}
		}



		/**
		 * This function sets up Conductor sidebars localize data for use in the Customizer and Previewer.
		 */
		public function conductor_previewer_localize_data() {
			// If this piece of content has a Conductor content layout applied (including the default layout)
			if ( Conductor::is_conductor( false ) ) {
				// Setup localize data
				$data = array(
					'content_sidebar_id' => Conductor::get_conductor_content_layout_sidebar_id( 'content' ),
					'primary_sidebar_id' => Conductor::get_conductor_content_layout_sidebar_id( 'primary' ),
					'secondary_sidebar_id' => Conductor::get_conductor_content_layout_sidebar_id( 'secondary' ),
					'content_layout' => Conductor::get_conductor_content_layout()
				);

				// Conductor sidebar prefix
				$data['content_layout']['sidebar_prefix'] = Conductor::get_conductor_content_layout_sidebar_id_prefix( '', Conductor::get_conductor_content_layout() );

				// Conductor sidebar suffix
				$data['content_layout']['sidebar_suffix'] = Conductor::get_conductor_content_layout_sidebar_id_suffix( '', Conductor::get_conductor_content_layout() );
			}
			// Otherwise if this piece of content does not yet have a Conductor content layout applied
			else {
				// Grab the permalink structure
				$permalink_structure = get_option( 'permalink_structure' );

				// Grab public post types as objects
				$public_post_types = get_post_types( array( 'public' => true ), 'objects' );

				// Public Post Types (further filtered to remove those that are not attachments)
				$public_post_types_without_attachments = wp_list_filter( $public_post_types, array( 'name' => 'attachment' ), 'NOT' );

				// Setup localize data
				$data = array(
					'content_sidebar_id' => false,
					'primary_sidebar_id' => false,
					'secondary_sidebar_id' => false,
					'content_layout' => array(),
					'sidebar_prefix' => false
				);

				// Front Page (Built-In)
				if ( is_front_page() && ! is_home() ) {
					$data['content_layout']['field_label'] = $data['content_layout']['sidebar_name_prefix'] = __( 'Front Page', 'conductor' );
					$data['content_layout']['field_type'] = 'built-in';
					$data['content_layout']['field_id'] = 'front_page';

					// Permalink
					$data['content_layout']['permalink'] = trailingslashit( home_url() );
				}
				// Home/Blog (Built-In)
				else if ( is_home() ) {
					$data['content_layout']['field_label'] = $data['content_layout']['sidebar_name_prefix'] = __( 'Blog', 'conductor' );
					$data['content_layout']['field_type'] = 'built-in';
					$data['content_layout']['field_id'] = 'home';

					// Permalink
					if ( $page_for_posts = get_option( 'page_for_posts' ) )
						$data['content_layout']['permalink'] = ( $permalink_structure ) ? trailingslashit( get_permalink( $page_for_posts ) ) : get_permalink( $page_for_posts );
					else
						$data['content_layout']['permalink'] = trailingslashit( home_url() );
				}
				// Category
				else if ( is_category() ) {
					$category = get_queried_object();

					$data['content_layout']['field_label'] = sprintf( __( 'Category - %1$s', 'conductor' ), $category->name );
					$data['content_layout']['sidebar_name_prefix'] = sprintf( __( '%1$s - Category', 'conductor' ), $category->name );
					$data['content_layout']['field_type'] = 'category';
					$data['content_layout']['field_id'] = $category->term_id;

					// Permalink
					$data['content_layout']['permalink'] = ( $permalink_structure ) ? trailingslashit( get_category_link( $category->term_id ) ) : get_category_link( $category->term_id );
				}
				// Post Type Archive
				else if ( is_post_type_archive() ) {
					$post_type = get_queried_object();

					$data['content_layout']['field_label'] = sprintf( __( 'Post Type - %1$s', 'conductor' ), $post_type->labels->singular_name );
					$data['content_layout']['sidebar_name_prefix'] = sprintf( __( '%1$s - Post Type', 'conductor' ), $post_type->labels->singular_name );
					$data['content_layout']['field_type'] = 'post-type';
					$data['content_layout']['field_id'] = $post_type->name;

					// Permalink
					$data['content_layout']['permalink'] = ( $permalink_structure ) ? trailingslashit( get_post_type_archive_link( $post_type->name ) ) : get_post_type_archive_link( $post_type->name );
				}
				// Singular
				else if ( is_singular( array_keys( $public_post_types_without_attachments ) ) ) {
					$post = get_queried_object();
					$post_title = get_the_title( $post );
					$post_type = $public_post_types_without_attachments[$post->post_type];

					$data['content_layout']['field_label'] = sprintf( __( '%1$s - %2$s', 'conductor' ), $post_type->labels->singular_name, $post_title );
					$data['content_layout']['sidebar_name_prefix'] = sprintf( __( '%1$s - %2$s', 'conductor' ), $post_title, $post_type->labels->singular_name );
					$data['content_layout']['field_type'] = $post->post_type;
					$data['content_layout']['field_id'] = $post->ID;

					// Permalink
					$data['content_layout']['permalink'] = ( $permalink_structure ) ? trailingslashit( get_permalink() ) : get_permalink();
				}

				// No value (i.e. default layout)
				$data['content_layout']['value'] = false;

				// New content layout flag
				$data['content_layout']['new_content_layout'] = true;
			}

			// Allow filtering of options
			$data = apply_filters( 'conductor_customizer_preview_options', $data, Conductor::is_conductor( false ) ); // Legacy (as of version 1.4.0)
			$data = apply_filters( 'conductor_customizer_previewer_options', $data, Conductor::is_conductor( false ) );

			return $data;
		}

		/**
		 * This function outputs the Conductor Widget re-order UnderscoreJS template.
		 */
		public static function conductor_widget_reorder_template() {
		?>
			<script type="text/template" id="tmpl-conductor-widget-reorder" xmlns="http://www.w3.org/1999/html">
				<li class="" data-id="{{ data.id }}" title="{{ data.description }}" tabindex="0">{{ data.name }}</li>
			</script>
		<?php
		}
	}

	/**
	 * Create an instance of the Conductor_Customizer class.
	 */
	function Conduct_Customizer() {
		return Conductor_Customizer::instance();
	}

	Conduct_Customizer(); // Conduct your content!
}