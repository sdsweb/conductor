<?php
/**
 * Conductor Admin Add-Ons
 *
 * @class Conductor_Admin_Add_Ons
 * @author Slocum Studio
 * @version 1.5.4
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Admin_Add_Ons' ) ) {
	final class Conductor_Admin_Add_Ons {
		/**
		 * @var string
		 */
		public $version = '1.5.4';

		/**
		 * @var string
		 */
		public static $sub_menu_page = 'conductor_page_conductor-add-ons';

		/**
		 * @var string
		 */
		public static $sub_menu_page_prefix = 'conductor_page_';

		/**
		 * @var string
		 */
		public static $plugin_action_file = 'plugins.php';

		/**
		 * @var string
		 */
		public static $api_url = '';

		/**
		 * @var array
		 */
		public static $default_api_args = array();

		/**
		 * @var string
		 */
		public static $conductor_license = '';

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

			// Grab the Conductor Updates instance
			$conductor_updates = Conduct_Updates();

			// Setup the license data
			self::$conductor_license = Conductor_Options::get_options( Conductor_Options::$option_name . '_license' );

			// Setup the API URL
			self::$api_url = $conductor_updates->get_url() . '/edd-api/products/';

			// Setup core API arguments
			self::$default_api_args = array(
				'license' => self::$conductor_license['key'],
				'url' => urlencode( trailingslashit( home_url() ) ),
				'conductor_version' => Conductor::$version
			);

			// Hooks
			add_filter( 'plugins_api_args', array( $this, 'plugins_api_args' ), 10, 2 ); // Plugins API - Arguments
			add_action( 'admin_menu', array( $this, 'admin_menu' ) ); // Admin Menu
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) ); // Admin Enqueue Scripts

			// AJAX Hooks
			add_action( 'wp_ajax_conductor-add-ons-install-single', array( $this, 'wp_ajax_conductor_add_ons_install_single' ) ); // Conductor Add-Ons Install Single
			add_action( 'wp_ajax_conductor-add-ons-activate-single', array( $this, 'wp_ajax_conductor_add_ons_activate_single' ) ); // Conductor Add-Ons Activate Single
			add_action( 'wp_ajax_conductor-add-ons-deactivate-single', array( $this, 'wp_ajax_conductor_add_ons_deactivate_single' ) ); // Conductor Add-Ons Deactivate Single
		}

		/**
		 * Include required core files used in admin and on the front-end.
		 */
		private function includes() {
			include_once 'class-conductor-admin-add-ons-views.php'; // Conductor Admin Add-Ons View Controller
		}

		/**
		 * This function determines if the user is installing an add-on and fetches the add-on details.
		 */
		public function plugins_api_args( $args, $action ) {
			// Bail if we aren't fetching plugin information or the Conductor query argument is not set
			if ( $action !== 'plugin_information' || ! isset( $_REQUEST['conductor'] ) )
				return $args;

			// Get add-ons data
			$add_ons_data = self::get_add_ons_data();

			// If we have add-ons data and can create a Conductor Updates instance
			if ( ! empty( $add_ons_data ) && ! is_wp_error( $add_ons_data ) && class_exists( 'Conductor_Updates' ) )
				// Loop through add-ons data
				foreach ( $add_ons_data as $add_on_data )
					// If this we have valid add-on data (basename and name)
					if ( is_array( $add_on_data ) && isset( $add_on_data['basename'] ) && isset( $add_on_data['name'] ) )
						// If this add-on is a valid add-on
						if ( $args->slug === $add_on_data['basename'] ) {
							// If this isn't a free add-on
							if ( ! isset( $add_on_data['free'] ) || ! $add_on_data['free'] ) {
								// Create the Conductor Updates instance
								$add_on_updater = new Conductor_Updates( array(
									'version' => ( isset( $add_on_data['version'] ) && ! empty( $add_on_data['version'] ) ) ? $add_on_data['version'] : '1.0.0', // Default to 1.0.0
									'name' => $add_on_data['name'],
									'plugin_file' => $add_on_data['basename']
								) );

								// Initialize the updater (will hook into plugins_api and fetch the data from the API)
								$add_on_updater->init_updater();
							}

							// Break from the loop
							break;
						}

			return $args;
		}

		/**
		 * This function creates the admin menu item for Conductor admin add-ons functionality
		 */
		public function admin_menu() {
			// Grab the Conductor Admin menu page slug
			$conductor_admin_menu_page = Conductor_Admin_Options::get_menu_page();

			// If the current user has the Conductor capability
			if ( current_user_can( Conductor::$capability ) )
				// Conductor Admin Add-Ons Options Page
				self::$sub_menu_page = add_submenu_page( $conductor_admin_menu_page, __( 'Add-Ons', 'conductor' ), __( 'Add-Ons', 'conductor' ), Conductor::$capability, self::get_sub_menu_page(), array( $this, 'render' ) );

			return self::$sub_menu_page;
		}

		/**
		 * This function enqueues CSS/JavaScript on the Conductor admin add-ons Page.
		 */
		public function admin_enqueue_scripts( $hook ) {
			// Bail if we're not on the Conductor Add-Ons page
			if ( strpos( $hook, self::get_sub_menu_page() ) === false )
				return;

			// Stylesheet
			wp_enqueue_style( 'conductor-admin', Conductor::plugin_url() . '/assets/css/conductor-admin.css', false, Conductor::$version );

			// Scripts
			wp_enqueue_script( 'conductor-general-admin', Conductor::plugin_url() . '/assets/js/conductor-general-admin.js', array( 'jquery' ), Conductor::$version );
			wp_enqueue_script( 'conductor-add-ons', Conductor::plugin_url() . '/assets/js/conductor-add-ons.js', array( 'jquery', 'wp-backbone', 'wp-util' ), Conductor::$version );
			wp_localize_script( 'conductor-add-ons', 'conductor', array(
				// Add-Ons Localization
				'add_ons_l10n' => array(
					// Success
					'success' => __( 'Success!', 'conductor' ),
					// Fail
					'fail' => __( 'An error occurred. Please try again.', 'conductor' )
				)
			) );
		}


		/********
		 * AJAX *
		 ********/

		/**
		 * This function handles the AJAX request for installing a single add-on.
		 */
		public function wp_ajax_conductor_add_ons_install_single() {
			// Generic error message
			$error = __( 'There was an error installing this add-on. Please try again later.', 'conductor' );

			// Install status flags
			$status = array();

			// Check AJAX referrer
			if ( ! check_ajax_referer( sanitize_text_field( $_POST['nonce_action'] ), 'nonce', false ) ) {
				$status['error'] = $error;
				wp_send_json_error( $status );
			}

			// Decode the plugin basename
			$plugin_basename = sanitize_text_field( urldecode( $_POST['plugin_basename'] ) );

			// Default install status flags
			$status = array(
				'install' => 'plugin',
				'plugin_basename' => $plugin_basename,
				'plugin_slug' => sanitize_key( $_POST['plugin_slug'] ),
				'attributes' => array(),
				'status' => array()
			);

			// Return an error if the current user can't install plugins
			if ( ! current_user_can( 'install_plugins' ) ) {
				$status['error'] = __( 'You do not have sufficient permissions to install plugins on this site.', 'conductor' );
				wp_send_json_error( $status );
			}

			// Get add-ons data
			$add_ons_data = self::get_add_ons_data();

			// The add-on data
			$the_add_on_data = array();

			// Flag to determine if this is a valid add-on
			$is_valid_add_on = false;

			// If we have add-ons data
			if ( ! empty( $add_ons_data ) && ! is_wp_error( $add_ons_data ) ) {
				// Loop through add-ons data
				foreach ( $add_ons_data as $add_on_data )
					// If this we have valid add-on data (basename and name)
					if ( is_array( $add_on_data ) && isset( $add_on_data['basename'] ) && $plugin_basename === $add_on_data['basename'] ) {
						// Set the flag
						$is_valid_add_on = true;

						// Set the add-on data
						$the_add_on_data = $add_on_data;

						break;
					}
			}

			// Return an error if the add-on isn't valid
			if ( ! $is_valid_add_on ) {
				$status['error'] = $error;
				wp_send_json_error( $status );
			}

			// Flag to determine if this is a free add-on
			$is_free_add_on = ( ! empty( $the_add_on_data ) && isset( $the_add_on_data['free'] ) && $the_add_on_data['free'] );

			// Include necessary plugin installer functionality
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			// Grab the plugin information
			$api = plugins_api( 'plugin_information', array(
				'slug' => ( $is_free_add_on ) ? $the_add_on_data['slug'] : $plugin_basename,
			) );

			// Return an error if there was an error grabbing plugin information
			if ( is_wp_error( $api ) ) {
				$status['error'] = __( 'There was an error retrieving add-on information. Please try again later.', 'conductor' );
				wp_send_json_error( $status );
			}

			// Install the plugin (use the Automatic Upgrader Skin)
			$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );

			// Return an error if there was an issue installing the plugin
			if ( $upgrader->install( $api->download_link ) !== true ) {
				// If the upgrader result is a WP_Error instance
				if ( $upgrader->result && is_wp_error( $upgrader->result ) )
					$status['error'] = $upgrader->result->get_error_message();
				// Otherwise if the skin result is an error
				else if ( $upgrader->skin->result !== true && is_wp_error( $upgrader->skin->result ) )
					$status['error'] = $upgrader->skin->result->get_error_message();
				// Otherwise if the skin has messages, use the last message
				else {
					$skin_messages = ( method_exists( $upgrader->skin, 'get_upgrade_messages' ) ) ? $upgrader->skin->get_upgrade_messages() : array();

					if ( ! empty( $skin_messages ) )
					$status['error'] = sprintf( '%1$s %2$s <br /><br /> %3$s',
						$error,
						__( 'Additionally the installer returned the following message:', 'conductor' ),
						array_pop( $skin_messages ) );
					else
						$status['error'] = $error;;
				}
				wp_send_json_error( $status );
			}

			// Add a success message
			$status['message'] = sprintf( __( '%1$s installed successfully. Please activate it now.', 'conductor' ), sanitize_text_field( $_POST['plugin_name'] ) );

			// Generate a nonce URL
			$activation_url = wp_nonce_url( self::plugin_action_url( $plugin_basename ), self::wp_nonce_url_plugin_action( $plugin_basename ) );

			// Parse the URL to grab the query arguments
			$activation_url_query_args = array();

			if ( strpos( $activation_url, '?' ) !== false )
				wp_parse_str( substr( html_entity_decode( $activation_url ), ( strpos( $activation_url, '?' ) + 1 ) ), $activation_url_query_args );

			// Populate attribute data
			$status['attributes'] = array(
				'href' => esc_url( $activation_url ),
				'data' => array(
					'nonce' => ( isset( $activation_url_query_args['_wpnonce'] ) ) ? esc_attr( $activation_url_query_args['_wpnonce'] ) : '',
					'nonce-action' => esc_attr( self::wp_nonce_url_plugin_action( $plugin_basename ) ),
					'label' => esc_attr( __( 'Install', 'conductor' ) ),
					'processing-button-label' => __( 'Activating...', 'conductor' ),
					'success-css-class' => esc_attr( 'deactivate-add-on' ),
					'success-button-type' => 'button-secondary', // Deactivate
					'success-label' => esc_attr( __( 'Deactivate', 'conductor' ) ),
					'success-action' => esc_attr( 'deactivate-plugin' )
				)
			);

			// Populate the status data
			$status['status'] = array(
				'css_class' => esc_attr( 'conductor-add-on-status-inactive' ),
				'message' => __( 'Inactive', 'conductor' )
			);

			// If we've made it this far, we should have a successful install
			wp_send_json_success( $status );
		}

		/**
		 * This function handles the AJAX request for activating a single add-on.
		 */
		public function wp_ajax_conductor_add_ons_activate_single() {
			// Generic error message
			$error = __( 'There was an error activating this add-on. This is likely due to a missing required component. Please try again later.', 'conductor' );

			// Check AJAX referrer
			if ( ! check_ajax_referer( sanitize_text_field( $_POST['nonce_action'] ), 'nonce', false ) ) {
				$status['error'] = $error;
				wp_send_json_error( $status );
			}

			// Decode the plugin basename
			$plugin_basename = sanitize_text_field( urldecode( $_POST['plugin_basename'] ) );

			// Install status flags
			$status = array(
				'activate' => 'plugin',
				'plugin_basename' => $plugin_basename,
				'plugin_slug' => sanitize_key( $_POST['plugin_slug'] ),
				'attributes' => array(),
				'status' => array()
			);

			// Return an error if the current user can't activate plugins
			if ( ! current_user_can( 'activate_plugins' ) ) {
				$status['error'] = __( 'You do not have sufficient permissions to activate plugins on this site.', 'conductor' );
				wp_send_json_error( $status );
			}

			// If the add-on isn't activated
			if ( ! is_plugin_active( $plugin_basename ) ) {
				// Activate the plugin
				$result = activate_plugin( $plugin_basename );

				if ( is_wp_error( $result ) ) {
					$status['error'] = $result->get_error_message();
					wp_send_json_error( $status );
				}

				/*
				 * Some add-ons have logic to determine if the necessary components (i.e. other plugins, functionality,
				 * logic, correct versions, etc...) exist. If the necessary components do not exist, the add-on is typically
				 * deactivated. We're looking to see if the add-on this logic and calling it accordingly to determine if this
				 * add-on can truly be activated.
				 */

				// Get add-ons data
				$add_ons_data = self::get_add_ons_data();

				// Add a success message
				$status['message'] = sprintf( __( '%1$s activated.', 'conductor' ), sanitize_text_field( $_POST['plugin_name'] ) );

				// If we have add-ons data
				if ( ! empty( $add_ons_data ) && ! is_wp_error( $add_ons_data ) ) {
					// Loop through add-ons data
					foreach ( $add_ons_data as $add_on_data )
						// If this we have valid add-on data and this is a match
						if ( is_array( $add_on_data ) && isset( $add_on_data['basename'] ) && $plugin_basename === $add_on_data['basename'] ) {
							// If we can grab an instance of this add-on
							if ( isset( $add_on_data['instance'] ) && function_exists( $add_on_data['instance'] ) ) {
								// Grab the add-on instance
								$add_on_instance = $add_on_data['instance']();

								// Grab the check requirements function
								$check_requirements_function = ( isset( $add_on_data['check_requirements_function'] ) && ! empty( $add_on_data['check_requirements_function'] ) ) ? $add_on_data['check_requirements_function'] : 'plugins_loaded';

								// If this add-on has a check requirements function and admin_notices callback
								if ( method_exists( $add_on_instance, $check_requirements_function ) && method_exists( $add_on_instance, 'admin_notices' ) ) {
									// Call the check requirements function
									call_user_func_array( array( $add_on_instance, $check_requirements_function ), array() );

									// If this add-on is no longer active
									if ( ! is_plugin_active( $plugin_basename ) ) {
										ob_start();
											// Call the admin_notices() function
											call_user_func_array( array( $add_on_instance, 'admin_notices' ), array() );

										// Grab the add-on admin notice
										$add_on_admin_notice = ob_get_clean();

										// Strip HTML tags
										$add_on_admin_notice = trim( strip_tags( $add_on_admin_notice ) );

										// Remove the success message
										unset( $status['message'] );

										$status['error'] = ( ! empty( $add_on_admin_notice ) ) ? $add_on_admin_notice :  $error;
										wp_send_json_error( $status );
									}
								}
							}

							// Break out of the loop
							break;
						}
				}
				// Otherwise we don't have add-ons data
				else
					// Adjust the success message (add a note regarding deactivation)
					$status['message'] .= _x( ' Note: Conductor could not fetch add-ons data. If this add-on requires other components that aren\'t found, it may be deactivated upon navigating away from this page.', 'adjusted success message blurb for Conductor add-ons when add-ons data could not be fetched', 'conductor' );
			}

			// Generate a nonce URL
			$deactivation_url = wp_nonce_url( self::plugin_action_url( $plugin_basename, 'deactivate' ), self::wp_nonce_url_plugin_action( $plugin_basename, 'deactivate' ) );

			// Parse the URL to grab the query arguments
			$deactivation_url_query_args = array();

			if ( strpos( $deactivation_url, '?' ) !== false )
				wp_parse_str( substr( html_entity_decode( $deactivation_url ), ( strpos( $deactivation_url, '?' ) + 1 ) ), $deactivation_url_query_args );

			// Populate attribute data
			$status['attributes'] = array(
				'href' => esc_url( $deactivation_url ),
				'data' => array(
					'nonce' => ( isset( $deactivation_url_query_args['_wpnonce'] ) ) ? esc_attr( $deactivation_url_query_args['_wpnonce'] ) : '',
					'nonce-action' => esc_attr( self::wp_nonce_url_plugin_action( $plugin_basename, 'deactivate' ) ),
					'label' => esc_attr( __( 'Deactivate', 'conductor' ) ),
					'processing-button-label' => __( 'Deactivating...', 'conductor' ),
					'success-css-class' => esc_attr( 'activate-add-on' ),
					'success-button-type' => 'button-primary', // Activate
					'success-label' => esc_attr( __( 'Activate', 'conductor' ) ),
					'success-action' => esc_attr( 'activate-plugin' )
				)
			);

			// Populate the status data
			$status['status'] = array(
				'css_class' => esc_attr( 'conductor-add-on-status-active' ),
				'message' => __( 'Active', 'conductor' )
			);

			// If we've made it this far, we should have a successful activation
			wp_send_json_success( $status );
		}

		/**
		 * This function handles the AJAX request for activating a single add-on.
		 */
		public function wp_ajax_conductor_add_ons_deactivate_single() {
			// Generic error message
			$error = __( 'There was an error deactivating this add-on. Please try again later.', 'conductor' );

			// Check AJAX referrer
			if ( ! check_ajax_referer( sanitize_text_field( $_POST['nonce_action'] ), 'nonce', false ) ) {
				$status['error'] = $error;
				wp_send_json_error( $status );
			}

			// Decode the plugin basename
			$plugin_basename = sanitize_text_field( urldecode( $_POST['plugin_basename'] ) );

			// Install status flags
			$status = array(
				'activate' => 'plugin',
				'plugin_basename' => $plugin_basename,
				'plugin_slug' => sanitize_key( $_POST['plugin_slug'] ),
				'attributes' => array(),
				'status' => array()
			);

			// Return an error if the current user can't activate plugins
			if ( ! current_user_can( 'activate_plugins' ) ) {
				$status['error'] = __( 'You do not have sufficient permissions to deactivate plugins on this site.', 'conductor' );
				wp_send_json_error( $status );
			}

			// Validate the add-on
			$result = validate_plugin( $plugin_basename );

			// Return an error if the add-on isn't valid
			if ( is_wp_error( $result ) ) {
				//$status['error'] = $error;
				$status['error'] = sprintf( '%1$s %2$s <br /><br /> %3$s',
					$error,
					__( 'Additionally the following message was returned:', 'conductor' ),
					$result->get_error_message() );
				wp_send_json_error( $status );
			}

			// Deactivate the plugin
			deactivate_plugins( $plugin_basename );

			// Add a success message
			$status['message'] = sprintf( __( '%1$s deactivated.', 'conductor' ), sanitize_text_field( $_POST['plugin_name'] ) );

			// Generate a nonce URL
			$activation_url = wp_nonce_url( self::plugin_action_url( $plugin_basename ), self::wp_nonce_url_plugin_action( $plugin_basename ) );

			// Parse the URL to grab the query arguments
			$activation_url_query_args = array();

			if ( strpos( $activation_url, '?' ) !== false )
				wp_parse_str( substr( html_entity_decode( $activation_url ), ( strpos( $activation_url, '?' ) + 1 ) ), $activation_url_query_args );

			// Populate attribute data
			$status['attributes'] = array(
				'href' => esc_url( $activation_url ),
				'data' => array(
					'nonce' => ( isset( $activation_url_query_args['_wpnonce'] ) ) ? esc_attr( $activation_url_query_args['_wpnonce'] ) : '',
					'nonce-action' => esc_attr( self::wp_nonce_url_plugin_action( $plugin_basename ) ),
					'label' => esc_attr( __( 'Activate', 'conductor' ) ),
					'processing-button-label' => __( 'Activating...', 'conductor' ),
					'success-css-class' => esc_attr( 'deactivate-add-on' ),
					'success-button-type' => 'button-secondary', // Deactivate
					'success-label' => esc_attr( __( 'Deactivate', 'conductor' ) ),
					'success-action' => esc_attr( 'deactivate-plugin' )
				)
			);

			// Populate the status data
			$status['status'] = array(
				'css_class' => esc_attr( 'conductor-add-on-status-inactive' ),
				'message' => __( 'Inactive', 'conductor' )
			);

			// If we've made it this far, we should have a successful activation
			wp_send_json_success( $status );
		}


		/********************
		 * Helper Functions *
		 ********************/

		/**
		 * This function renders the Conductor Add-Ons page.
		 */
		public function render() {
			// Render the main view
			Conductor_Admin_Add_Ons_Views::render();
		}


		/**********************
		 * Internal Functions *
		 **********************/

		/**
		 * This function fetches a list of available add-ons from the Conductor website if the list
		 * is not already stored locally.
		 *
		 * @uses Conductor_Updates
		 */
		public static function maybe_fetch_add_ons_list() {
			// Grab the installed plugins
			$installed_plugins = self::get_installed_plugin_data();

			// Grab the add-on nonces
			$add_on_nonces = self::get_add_on_nonces();

			// Bail if we don't have the add-on nonces
			if ( empty( $add_on_nonces ) ) {
				$add_ons = '<div class="error"><p>' . __( 'There was an error retrieving the add-ons list. Please try again later.', 'conductor' ) . '</p></div>';

				return $add_ons;
			}

			// Fetch the data
			$args = wp_parse_args( array(
				'feed' => 'add-ons',
				'installed_plugins' => urlencode( json_encode( $installed_plugins ) ),
				'add_on_nonces' => urlencode( json_encode( $add_on_nonces ) )
			), self::$default_api_args );

			// Fetch the add-ons
			$add_ons = wp_remote_post( self::$api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $args ) );

			// If we have add-ons HTML
			if ( ! empty( $add_ons ) && ! is_wp_error( $add_ons ) )
				// Retrieve the request body
				$add_ons = wp_remote_retrieve_body( $add_ons );
			// Otherwise there was an error
			else
				$add_ons = '<div class="error"><p>' . __( 'There was an error retrieving the add-ons list. Please try again later.', 'conductor' ) . '</p></div>';

			return $add_ons;
		}

		/**
		 * This function outputs the list of add-ons.
		 */
		public static function display_add_ons_list() {
			echo self::maybe_fetch_add_ons_list();
		}

		/**
		 * This function grabs data for installed plugins.
		 */
		public static function get_installed_plugin_data() {
			// Keep track of installed plugin data
			$installed_plugin_data = array();

			// Get plugins
			$plugins = get_plugins();

			// Loop through plugins
			foreach ( $plugins as $plugin_basename => $plugin ) {
				// Flag to determine if this plugin is active
				$is_plugin_active = is_plugin_active( $plugin_basename );

				// Add this plugin to installed plugin data
				$installed_plugin_data[] = array(
					'plugin_basename' => $plugin_basename,
					'name' => $plugin['Name'],
					'is_active' => ( $is_plugin_active ),
					'activation_url' => ( ! $is_plugin_active ) ? wp_nonce_url( self::plugin_action_url( $plugin_basename ), self::wp_nonce_url_plugin_action( $plugin_basename ) ) : false,
					'deactivation_url' => ( $is_plugin_active ) ? wp_nonce_url( self::plugin_action_url( $plugin_basename, 'deactivate' ), self::wp_nonce_url_plugin_action( $plugin_basename, 'deactivate' ) ) : false
				);
			}

			return $installed_plugin_data;
		}

		/**
		 * This function returns a plugins "action" (activate/deactivate) URL.
		 */
		public static function plugin_action_url( $plugin_basename, $action = 'activate' ) {
			// Sanitize the action (default to activate)
			if ( ! in_array( $action, array( 'activate', 'deactivate', 'install' ) ) )
				$action = 'activate';

			return esc_html( add_query_arg( array(
				'plugin' => $plugin_basename,
				'action' => $action
			),
			self::$plugin_action_file ) );
		}

		/**
		 * This function returns an action name for nonces.
		 */
		public static function wp_nonce_url_plugin_action( $plugin_basename, $action = 'activate' ) {
			// Sanitize the action (default to activate)
			if ( ! in_array( $action, array( 'activate', 'deactivate', 'install' ) ) )
				$action = 'activate';

			return $action . '-plugin_' . $plugin_basename;
		}

		/**
		 * This function fetches add-ons data.
		 */
		public static function get_add_ons_data() {
			// Fetch the data
			$args = wp_parse_args( array(
				'feed' => 'add-ons-data'
			), self::$default_api_args );

			// Fetch the add-ons
			$add_ons_data = wp_remote_post( self::$api_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $args ) );

			// If we have add-ons HTML
			if ( ! empty( $add_ons_data ) && ! is_wp_error( $add_ons_data ) )
				// Retrieve the request body
				$add_ons_data = json_decode( wp_remote_retrieve_body( $add_ons_data ), true );

			return $add_ons_data;
		}

		/**
		 * This function creates nonces for installing add-ons.
		 */
		public static function get_add_on_nonces( $action = 'install' ) {
			// Add-on nonces
			$add_on_nonces = array();

			// Fetch the add-ons
			$add_ons_data = self::get_add_ons_data();

			// If we have add-ons data
			if ( ! empty( $add_ons_data ) && ! is_wp_error( $add_ons_data ) ) {
				// Loop through add-ons
				foreach ( $add_ons_data as $add_on_data )
					// If this we have valid add-on data
					if ( is_array( $add_on_data ) && isset( $add_on_data['basename'] ) )
						// Add this add-on nonce to the list
						$add_on_nonces[$add_on_data['slug']] = wp_create_nonce( self::wp_nonce_url_plugin_action( $add_on_data['basename'], $action ) );
			}

			return $add_on_nonces;
		}

		/**
		 * This function returns the sub-menu page. The optional $strip_prefix parameter allows the prefix
		 * added by WordPress to be stripped
		 */
		public static function get_sub_menu_page( $strip_prefix = true ) {
			return ( $strip_prefix ) ? str_replace( self::$sub_menu_page_prefix, '', self::$sub_menu_page ) : self::$sub_menu_page;
		}
	}

	/**
	 * Create an instance of the Conductor_Admin_Add_Ons class.
	 */
	function Conduct_Admin_Add_Ons() {
		return Conductor_Admin_Add_Ons::instance();
	}

	Conduct_Admin_Add_Ons(); // Conduct your content!
}