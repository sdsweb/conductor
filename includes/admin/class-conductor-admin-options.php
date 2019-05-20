<?php
/**
 * Conductor Admin Options
 *
 * @class Conductor_Admin_Options
 * @author Slocum Studio
 * @version 1.5.3
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Admin_Options' ) ) {
	final class Conductor_Admin_Options {
		/**
		 * @var string
		 */
		public $version = '1.5.3';

		/**
		 * @var string
		 */
		public static $menu_page = 'conductor';

		/**
		 * @var string
		 */
		public static $menu_page_prefix = 'toplevel_page_';

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
			// Load required assets
			$this->includes();

			// Hooks
			add_action( 'admin_menu', array( $this, 'admin_menu' ) ); // Set up admin menu item
			add_action( 'admin_menu', array( $this, 'admin_menu_sub_menu' ), 9999 ); // Adjust the main Conductor sub-menu item
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) ); // Load CSS/JavaScript
			add_filter( 'wp_redirect', array( $this, 'wp_redirect' ) ); // Add "hash" (tab) to URL before re-direct
			add_action( 'admin_init', array( $this, 'admin_init' ) ); // Register setting
		}

		/**
		 * Include required core files used in admin and on the front-end.
		 */
		private function includes() {
			include_once 'class-conductor-admin-options-views.php'; // Conductor Admin Options View Controller
		}

		/**
		 * This function creates the admin menu item for Conductor admin functionality
		 */
		public function admin_menu() {
			// Conductor Admin Page (directly after "Settings" which is located at position 80)
			self::$menu_page = add_menu_page( __( 'Conductor', 'conductor' ), __( 'Conductor', 'conductor' ), Conductor::$capability, 'conductor', array( $this, 'render' ), '', '80.01000011' );
		}

		/**
		 * This function adjusts the label on the first Conductor sub-menu item in the admin menu.
		 */
		public function admin_menu_sub_menu() {
			global $submenu;

			// Adjust the first sub-menu item label
			if ( isset( $submenu['conductor'] ) )
				$submenu['conductor'][0][0] = __( 'Options', 'conductor' );
		}

		/**
		 * This function enqueues CSS/JavaScript on the Conductor Options Page.
		 */
		public function admin_enqueue_scripts( $hook ) {
			// Bail if we're not on the conductor page
			if ( $hook !== Conductor_Admin_Options::get_menu_page( false ) )
				return;

			$protocol = is_ssl() ? 'https' : 'http';

			// Stylesheets
			wp_enqueue_style( 'conductor-admin', Conductor::plugin_url() . '/assets/css/conductor-admin.css', false, Conductor::$version );

			// Scripts
			wp_enqueue_script( 'conductor-general-admin', Conductor::plugin_url() . '/assets/js/conductor-general-admin.js', array( 'jquery' ), Conductor::$version );

			wp_enqueue_script( 'conductor-content-layouts', Conductor::plugin_url() . '/assets/js/conductor-options-admin.js', array( 'jquery', 'wp-backbone' ), Conductor::$version );
			wp_localize_script( 'conductor-content-layouts', 'conductor', apply_filters( 'conductor_admin_options_localize', array(
				'customize_url' => add_query_arg( array( 'url' => '' ), wp_customize_url() ),
				// l10n
				'l10n' => array(
					'content_layout_exists' => __( 'A content layout for that content type already exists. Please try again.', 'conductor' ),
					'content_layout_created' => __( 'New content layout was created successfully.', 'conductor' ),
					'no_content_type' => __( 'Please select a content type.', 'conductor' ),
					'category_label_prefix' => __( 'Category -', 'conductor' ),
					'post_type_label_prefix' => __( 'Post Type - ', 'conductor' ),
					'customize_action_with_panel' => __( 'Customizing &#9656;' ),
					'customize_action' => __( 'Customizing', 'conductor' )
				)
			), $hook, $protocol ) );
		}

		/*
		 * This function appends the hash for the current tab based on POST data.
		 */
		public function wp_redirect( $location ) {
			// Append tab "hash" to end of URL
			if ( strpos( $location, Conductor_Options::$option_name ) !== false && isset( $_POST['conductor_options_tab'] ) && $_POST['conductor_options_tab'] )
				$location .= esc_url( $_POST['conductor_options_tab'] );

			return $location;
		}

		/**
		 * This function registers a setting for Conductor and adds setting sections and setting fields.
		 */
		public function admin_init() {
			// Register Setting
			register_setting( Conductor_Options::$option_name, Conductor_Options::$option_name, array( $this, 'sanitize_option' ) );

			// Enable Conductor
			add_settings_section( 'conductor_enable_section', __( 'Enable Conductor', 'conductor' ), array( $this, 'conductor_enable_section' ), Conductor_Options::$option_name . '_general' );
			add_settings_field( 'conductor_enable_field', __( 'Enable Conductor', 'conductor' ), array( $this, 'conductor_enable_field' ), Conductor_Options::$option_name . '_general', 'conductor_enable_section' );

			// Enable Conductor REST API
			add_settings_section( 'conductor_rest_enable_section', __( 'Enable Conductor REST API', 'conductor' ), array( $this, 'conductor_rest_enable_section' ), Conductor_Options::$option_name . '_general' );
			add_settings_field( 'conductor_rest_enable_field', __( 'Enable Conductor REST API', 'conductor' ), array( $this, 'conductor_rest_enable_field' ), Conductor_Options::$option_name . '_general', 'conductor_rest_enable_section' );

			// Content Layouts
			add_settings_section( 'conductor_content_layouts_section', __( 'Conductor Layouts', 'conductor' ), array( $this, 'conductor_content_layouts_section' ), Conductor_Options::$option_name . '_general' );
			add_settings_field( 'conductor_content_layouts_field', __( 'Select a Layout For:', 'conductor' ), array( $this, 'conductor_content_layouts_field' ), Conductor_Options::$option_name . '_general', 'conductor_content_layouts_section' );

			// Conductor Uninstall
			add_settings_section( 'conductor_uninstall_section', __( 'Uninstall', 'conductor' ), array( $this, 'conductor_uninstall_section' ), Conductor_Options::$option_name . '_advanced' );
			add_settings_field( 'conductor_uninstall_data_field', __( 'Uninstall Data', 'conductor' ), array( $this, 'conductor_uninstall_data_field' ), Conductor_Options::$option_name . '_advanced', 'conductor_uninstall_section' );
		}

		/**
		 * This function renders the Conductor Enable Settings Section.
		 */
		public function conductor_enable_section() {
			Conductor_Admin_Options_Views::conductor_enable_section();
		}

		/**
		 * This function renders the Conductor Enable Settings Field.
		 */
		public function conductor_enable_field() {
			Conductor_Admin_Options_Views::conductor_enable_field();
		}

		/**
		 * This function renders the Conductor REST API Enable Settings Section.
		 */
		public function conductor_rest_enable_section() {
			Conductor_Admin_Options_Views::conductor_rest_enable_section();
		}

		/**
		 * This function renders the Conductor REST API Enable Settings Field.
		 */
		public function conductor_rest_enable_field() {
			Conductor_Admin_Options_Views::conductor_rest_enable_field();
		}


		/**
		 * This function renders the Conductor Content Layouts Settings Section.
		 */
		public function conductor_content_layouts_section() {
			Conductor_Admin_Options_Views::conductor_content_layouts_section();
		}

		/**
		 * This function renders the Conductor Content Layouts Settings Field.
		 */
		public function conductor_content_layouts_field() {
			Conductor_Admin_Options_Views::conductor_content_layouts_field();
		}

		/**
		 * This function renders the Conductor Uninstall Settings Section.
		 */
		public function conductor_uninstall_section() {
			Conductor_Admin_Options_Views::conductor_uninstall_section();
		}

		/**
		 * This function renders the Conductor Uninstall Data Settings Field.
		 */
		public function conductor_uninstall_data_field() {
			Conductor_Admin_Options_Views::conductor_uninstall_data_field();
		}

		/**
		 * This function renders the Conductor options page.
		 */
		public function render() {
			// Render the main view
			Conductor_Admin_Options_Views::render();
		}

		/**
		 * This function sanitizes the option values before they are stored in the database.
		 */
		public static function sanitize_option( $input ) {
			// Reset to Defaults
			if ( isset( $input['reset'] ) )
				return Conductor_Options::get_option_defaults();

			// Store the raw input values from the user which will be used in certain validation checks
			$raw_input = $input;

			// Grab Conductor option defaults
			$conductor_option_defaults = Conductor_Options::get_option_defaults();

			// Parse arguments, replacing defaults with user input
			$input = wp_parse_args( $input, Conductor_Options::get_option_defaults() );

			// Enable Conductor
			$input['enabled'] = ( isset( $raw_input['enabled'] ) && $input['enabled'] ) ? true : $conductor_option_defaults['enabled'];

			// Enable Conductor REST API
			$input['rest']['enabled'] = ( isset( $raw_input['rest']['enabled'] ) && $input['rest']['enabled'] ) ? true : false;

			// Content Layouts
			if ( ! empty( $input['content_layouts'] ) && is_array( $input['content_layouts'] ) ) {
				foreach ( $input['content_layouts'] as $id => &$value ) {
					$temp_value = array(); // Store the data temporarily

					// Verify that the content layout data is an array of data
					if ( is_array( $value ) ) {
						$content_type = key( $value ); // Content Type is stored in first key
						$field_name = key( $value[$content_type] ); // Field name is stored in the first key of the $content_type array

						// Grab public post types as objects
						$public_post_types = get_post_types( array( 'public' => true ), 'objects' );

						// Content Type
						switch ( $content_type ) {
							// Built-In
							case 'built-in':
								$built_in_content_types = apply_filters( 'conductor_sanitize_option_built_in_content_types', array(
									'front_page' => __( 'Front Page', 'conductor' ),
									'home' => __( 'Blog', 'conductor' )
								) );

								// Make sure the content type is valid and modify the $temp_value
								if ( array_key_exists( $field_name, $built_in_content_types ) )
									$temp_value = array(
										'field_label' => $built_in_content_types[$field_name],
										'sidebar_name_prefix' => $built_in_content_types[$field_name],
									);
								// Remove this entry as it's not valid
								else
									unset( $input['content_layouts'][$id] );
							break;

							// Category
							case 'category':
								// Make sure the content type is valid and modify the $temp_value
								if ( $category = get_category( $field_name ) )
									$temp_value = array(
										'field_label' => __( 'Category - ', 'conductor' ) . $category->name,
										'sidebar_name_prefix' => $category->name . __( ' - Category', 'conductor' ),
									);
								// Remove this entry as it's not valid
								else
									unset( $input['content_layouts'][$id] );
							break;

							// Post Type
							case 'post-type':
								$post_type = sanitize_text_field( $field_name );

								// Public Custom Post Types (further filtered to remove those that are not built-in, do not have archives, and do not have rewrite rules)
								$public_custom_post_types = wp_list_filter( $public_post_types, array( '_builtin' => true, 'has_archive' => false, 'rewrite' => false ), 'NOT' );

								// Make sure the content type is valid and modify the $temp_value
								if ( array_key_exists( $post_type, $public_custom_post_types ) )
									$temp_value = array(
										'field_label' => __( 'Post Type - ', 'conductor' ) . $public_custom_post_types[$post_type]->labels->singular_name,
										'sidebar_name_prefix' => $public_custom_post_types[$post_type]->labels->singular_name . __( ' - Post Type', 'conductor' ),
									);
								// Remove this entry as it's not valid
								else
									unset( $input['content_layouts'][$id] );
							break;

							/*
							 * All other content types
							 *
							 * Check singular content layouts before allowing "other" content types to be processed
							 */
							default:
								// Public Post Types (further filtered to remove those that are not attachments)
								$public_post_types_without_attachments = wp_list_filter( $public_post_types, array( 'name' => 'attachment' ), 'NOT' );

								// Make sure the content type is valid and modify the $temp_value
								if ( array_key_exists( $content_type, $public_post_types_without_attachments ) ) {
									// Make sure the content type is valid and modify the $temp_value
									if ( $post = get_post( $field_name ) )
										$temp_value = array(
											'field_label' => $public_post_types_without_attachments[$content_type]->labels->singular_name . ' - ' . $post->post_title,
											'sidebar_name_prefix' => $post->post_title . ' - ' . $public_post_types_without_attachments[$content_type]->labels->singular_name,
										);
									// Remove this entry as it's not valid
									else
										unset( $input['content_layouts'][$id] );

									// Break out of the loop so the filter below doesn't run
									break 2;
								}

								/*
								 * Field type, field ID, and value will be set and sanitized below.
								 *
								 * $temp_value should look similar to the following after validation (see above validation for reference):
								 *
								 * $temp_value = array(
								 * 		'field_label' => 'Content Type - Content Title',
								 * 		'sidebar_name_prefix' => 'Content Title - Content Type',
								 * );
								 */
								$temp_value = apply_filters( 'conductor_sanitize_option_other_content_types', $temp_value, $id, $value, $content_type, $field_name, $input, $raw_input );

								// If the $temp_value is empty or the field label isn't set, remove this entry as it's likely not valid
								if ( empty( $temp_value ) || ! isset( $temp_value['field_label'] ) )
									unset( $input['content_layouts'][$id] );
							break;
						}

						// Append the rest of the content layout data
						$temp_value['field_type'] = sanitize_text_field( $content_type );
						$temp_value['field_id'] = sanitize_text_field( $field_name );

						// This value is used in options panel output to determine selected content layout
						$temp_value['value'] = ( $value[$content_type][$field_name] !== 'default' ) ? sanitize_text_field( $value[$content_type][$field_name] ) : false;

						// Assign the $temp_value to the $value
						$value = apply_filters( 'conductor_sanitize_option_content_layout_value', $temp_value, $id, $value, $content_type, $field_name, $input, $raw_input );
					}
					// Content type is not valid
					else
						unset( $input['content_layouts'][$id] );
				}

				// Reset the array keys
				$input['content_layouts'] = array_values( $input['content_layouts'] );
			}
			// Invalid content_layouts passed
			else
				$input['content_layouts'] = $conductor_option_defaults['content_layouts'];

			// Conductor Uninstall
			$input['uninstall']['data'] = ( isset( $raw_input['uninstall']['data'] ) && $input['uninstall']['data'] ) ? true : $conductor_option_defaults['uninstall']['data']; // Remove Conductor data on uninstall (checking isset() here due to the nested arrays)

			$input = apply_filters( 'conductor_sanitize_options', $input, $raw_input );

			return $input;
		}


		/**********************
		 * Internal Functions *
		 **********************/

		/**
		 * This function returns the menu page. The optional $strip_prefix parameter allows the prefix
		 * added by WordPress to be stripped
		 */
		public static function get_menu_page( $strip_prefix = true ) {
			return ( $strip_prefix ) ? str_replace( self::$menu_page_prefix, '', self::$menu_page ) : self::$menu_page;
		}
	}

	/**
	 * Create an instance of the Conductor_Admin_Options class.
	 */
	function Conduct_Admin_Options() {
		return Conductor_Admin_Options::instance();
	}

	Conduct_Admin_Options(); // Conduct your content!
}