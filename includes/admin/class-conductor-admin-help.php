<?php
/**
 * Conductor Admin Help
 *
 * @class Conductor_Admin_Help
 * @author Slocum Studio
 * @version 1.4.4
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Admin_Help' ) ) {
	final class Conductor_Admin_Help {
		/**
		 * @var string
		 */
		public $version = '1.4.4';

		/**
		 * @var string
		 */
		public static $sub_menu_page = 'conductor_page_conductor-help';

		/**
		 * @var string
		 */
		public static $sub_menu_page_prefix = 'conductor_page_';

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
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 9999 ); // Set up admin sub-menu item (Late)
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) ); // Load CSS/JavaScript
		}

		/**
		 * Include required core files used in admin and on the front-end.
		 */
		private function includes() {
			include_once 'class-conductor-admin-help-views.php'; // Conductor Admin Help View Controller
		}

		/**
		 * This function creates the admin menu item for Conductor admin help functionality.
		 */
		public function admin_menu() {
			// Grab the Conductor Admin menu page slug
			$conductor_admin_menu_page = Conductor_Admin_Options::get_menu_page();

			// If the current user has the Conductor capability
			if ( current_user_can( Conductor::$capability ) )
				// Conductor Admin Help Page
				self::$sub_menu_page = add_submenu_page( $conductor_admin_menu_page, __( 'Help', 'conductor' ), __( 'Help', 'conductor' ), Conductor::$capability, self::get_sub_menu_page(), array( $this, 'render' ) );

			return self::$sub_menu_page;
		}

		/**
		 * This function enqueues CSS/JavaScript on the Conductor Options Page.
		 */
		public function admin_enqueue_scripts( $hook ) {
			// Bail if we're not on the Conductor Help page
			if ( strpos( $hook, self::get_sub_menu_page() ) === false )
				return;

			// Stylesheets
			wp_enqueue_style( 'conductor-admin', Conductor::plugin_url() . '/assets/css/conductor-admin.css', false, Conductor::$version );

			// Scripts
			wp_enqueue_script( 'conductor-general-admin', Conductor::plugin_url() . '/assets/js/conductor-general-admin.js', array( 'jquery' ), Conductor::$version );
		}


		/**
		 * This function renders the Conductor options page.
		 */
		public function render() {
			// Render the main view
			Conductor_Admin_Help_Views::render();
		}

		/**
		 * This function returns helpful debug information.
		 *
		 * Some functionality below copyright 2013, Andrew Norcross, http://andrewnorcross.com/
		 * - @see https://github.com/norcross/system-snapshot-report
		 */
		public static function get_snapshot_details() {
			// call WP database
			global $wpdb;

			//$browser = get_browser( null, true );
			$theme_data = wp_get_theme();
			$front_page = get_option( 'page_on_front' );
			$page_for_posts = get_option( 'page_for_posts' );
			$mu_plugins = get_mu_plugins();
			$plugins = get_plugins();
			$active_plugins = get_option( 'active_plugins', array() );
			$nt_plugins = is_multisite() ? wp_get_active_network_plugins() : array();
			$ms_sites = is_multisite() ? wp_get_sites() : null;

			$snapshot = array(
				// Browser
				'browser_info' => array(
					'label' => __( 'Browser Info:', 'conductor' ),
					'value' => ''
				),
				/*'browser' => array(
					'label' => __( 'Browser:', 'conductor' ),
					'value' => $browser['browser']
				),
				'browser_version' => array(
					'label' => __( 'Browser Version:', 'conductor' ),
					'value' => $browser['version']
				),
				'browser_platform' => array(
					'label' => __( 'Platform (Operating System):', 'conductor' ),
					'value' => $browser['platform']
				),*/
				'browser_user_agent' => array(
					'label' => __( 'User Agent:', 'conductor' ),
					'value' => $_SERVER['HTTP_USER_AGENT']
				),
				// Theme
				'theme' => array(
					'label' => __( 'Theme:', 'conductor' ),
					'value' => $theme_data->Name . ' ' . $theme_data->Version
				),
				// Other
				'front_page' => array(
					'label' => __( 'Front Page:', 'conductor' ),
					'value' => $front_page ? get_the_title( $front_page ).' (ID# '.$front_page.')'.'' : __( 'n/a', 'conductor' )
				),
				'page_for_posts' => array(
					'label' => __( 'Page for Posts:', 'conductor' ),
					'value' => $front_page ? get_the_title( $page_for_posts ).' (ID# '.$page_for_posts.')'.'' : __( 'n/a', 'conductor' )
				),
				'display_errors' => array(
					'label' => __( 'Display Errors:', 'conductor' ),
					'value' => ini_get( 'display_errors' ) != false ? __( 'On', 'conductor' ) : __( 'Off', 'conductor' )
				),
				'jquery_version' => array(
					'label' => __( 'jQuery Version:', 'conductor' ),
					'value' => wp_script_is( 'jquery', 'registered' ) ? $GLOBALS['wp_scripts']->registered['jquery']->ver : __( 'n/a', 'conductor' )
				),
				'php_session' => array(
					'label' => __( 'PHP Session:', 'conductor' ),
					'value' => isset( $_SESSION ) ? __( 'Enabled', 'conductor' ) : __( 'Disabled', 'conductor' )
				),
				'php_cookies' => array(
					'label' => __( 'Use Cookies:', 'conductor' ),
					'value' => ini_get( 'session.use_cookies' ) ? __( 'On', 'conductor' ) : __( 'Off', 'conductor' )
				),
				'php_cookies_only' => array(
					'label' => __( 'Use Cookies Only:', 'conductor' ),
					'value' => ini_get( 'session.use_only_cookies' ) ? __( 'On', 'conductor' ) : __( 'Off', 'conductor' )
				),
				'php_fsockopen' => array(
					'label' => __( 'fsockopen() Support:', 'conductor' ),
					'value' => function_exists( 'fsockopen' ) ? __( 'Your server supports fsockopen.', 'conductor' ) : __( 'Your server does not support fsockopen.', 'conductor' )
				),
				'php_curl' => array(
					'label' => __( 'cURL Support:', 'conductor' ),
					'value' => function_exists( 'curl_init' ) ? __( 'Your server supports cURL.', 'conductor' ) : __( 'Your server does not support cURL.', 'conductor' )
				),
				'php_soap_client' => array(
					'label' => __( 'SOAP Client Support:', 'conductor' ),
					'value' => class_exists( 'SoapClient' ) ? __( 'Your server has the SOAP Client enabled.', 'conductor' ) : __( 'Your server does not have the SOAP Client enabled.', 'conductor' )
				),
				'php_suhosin' => array(
					'label' => __( 'SUHOSIN Support:', 'conductor' ),
					'value' => extension_loaded( 'suhosin' ) ? __( 'Your server has SUHOSIN installed.', 'conductor' ) : __( 'Your server does not have SUHOSIN installed.', 'conductor' )
				),
				'php_open_ssl' => array(
					'label' => __( 'OpenSSL Support:', 'conductor' ),
					'value' => extension_loaded('openssl') ? __( 'Your server has OpenSSL installed.', 'conductor' ) : __( 'Your server does not have OpenSSL installed.', 'conductor' )
				),
				'php_version' => array(
					'label' => __( 'PHP Version:', 'conductor' ),
					'value' => PHP_VERSION
				),
				'mysql_version' => array(
					'label' => __( 'MySQL Version:', 'conductor' ),
					'value' => $wpdb->db_version()
				),
				'server_software' => array(
					'label' => __( 'Server Software:', 'conductor' ),
					'value' => $_SERVER['SERVER_SOFTWARE']
				),
				'php_memory_limit' => array(
					'label' => __( 'PHP Memory Limit:', 'conductor' ),
					'value' => ini_get( 'memory_limit' )
				),
				'php_upload_max_size' => array(
					'label' => __( 'PHP Maximum Upload Size:', 'conductor' ),
					'value' => ini_get( 'upload_max_filesize' )
				),
				'php_post_max_size' => array(
					'label' => __( 'PHP Maximum Post Size:', 'conductor' ),
					'value' => ini_get( 'post_max_size' )
				),
				'php_max_execution_time' => array(
					'label' => __( 'PHP Maximum Execution Time:', 'conductor' ),
					'value' => ini_get( 'max_execution_time' )
				),
				'php_max_input_vars' => array(
					'label' => __( 'PHP Maximum Input Variables:', 'conductor' ),
					'value' => ini_get( 'max_input_vars' )
				),
				'php_session_name' => array(
					'label' => __( 'PHP Session Name:', 'conductor' ),
					'value' => esc_html( ini_get( 'session.name' ) )
				),
				'php_cookie_path' => array(
					'label' => __( 'PHP Cookie Path:', 'conductor' ),
					'value' => esc_html( ini_get( 'session.cookie_path' ) )
				),
				'php_save_path' => array(
					'label' => __( 'PHP Save Path:', 'conductor' ),
					'value' => esc_html( ini_get( 'session.save_path' ) )
				),
				// WordPress
				'wp_site_url' => array(
					'label' => __( 'Site URL:', 'conductor' ),
					'value' => site_url()
				),
				'wp_home_url' => array(
					'label' => __( 'Home URL:', 'conductor' ),
					'value' => home_url()
				),
				'wp_version' => array(
					'label' => __( 'WordPress Version:', 'conductor' ),
					'value' => get_bloginfo( 'version' )
				),
				'wp_permalink_structure' => array(
					'label' => __( 'Permalink Structure:', 'conductor' ),
					'value' => get_option( 'permalink_structure' )
				),
				'wp_post_types' => array(
					'label' => __( 'Post Types:', 'conductor' ),
					'value' => implode( ', ', get_post_types( '', 'names' ) )
				),
				'wp_post_stati' => array(
					'label' => __( 'Post Stati:', 'conductor' ),
					'value' => implode( ', ', get_post_stati() )
				),
				'wp_user_count' => array(
					'label' => __( 'User Count:', 'conductor' ),
					'value' => count( get_users() )
				),
				'wp_memory_limit' => array(
					'label' => __( 'Memory Limit:', 'conductor' ),
					'value' => WP_MEMORY_LIMIT
				),
				'wp_prefix' => array(
					'label' => __( 'Database Prefix:', 'conductor' ),
					'value' => $wpdb->base_prefix
				),
				'wp_prefix_length' => array(
					'label' => __( 'Prefix Length:', 'conductor' ),
					'value' => strlen( $wpdb->prefix ) < 16 ? __( 'Acceptable', 'conductor' ) : __( 'Too Long', 'conductor' )
				),
				'wp_is_multisite' => array(
					'label' => __( 'Multisite:', 'conductor' ),
					'value' => is_multisite() ? __( 'Yes', 'conductor' ) : __( 'No', 'conductor' )
				),
				'wp_is_safe_mode' => array(
					'label' => __( 'Safe Mode:', 'conductor' ),
					'value' => is_multisite() ? __( 'Yes', 'conductor' ) : __( 'No', 'conductor' )
				),
				'wp_is_wp_debug' => array(
					'label' => __( 'WP DEBUG:', 'conductor' ),
					'value' => defined( 'WP_DEBUG' ) ? WP_DEBUG ? __( 'Enabled', 'conductor' ) : __( 'Disabled', 'conductor' ) : __( 'Not Set', 'conductor' )
				)
			);

			if ( is_multisite() ) {
				$snapshot['wp_multisite_total'] = array(
					'label' => __( 'Total Sites:', 'conductor' ),
					'value' => get_blog_count()
				);

				$snapshot['wp_multisite_base'] = array(
					'label' => __( 'Base Site:', 'conductor' ),
					'value' => $ms_sites[0]['domain']
				);

				$snapshot['wp_multisite_all'] = array(
					'label' => __( 'All Sites:', 'conductor' ),
					'value' => ''
				);

				foreach ( $ms_sites as $site_index => $site )
					if ( $site['path'] != '/' )
						$snapshot['wp_multisite_all_' . $site_index] =array(
							'label' => sprintf( __( 'Site %1$s:', 'conductor' ), $site_index ),
							'value' => $site['domain'] . $site['path']
						);
			}

			if ( $plugins && $mu_plugins )
				$snapshot['wp_total_plugin_count'] = array(
					'label' => __( 'Total Plugins:', 'conductor' ),
					'value' => ( count( $plugins ) + count( $mu_plugins ) + count( $nt_plugins ) )
				);

			// output must-use plugins
			if ( $mu_plugins ) {
				$snapshot['wp_must_use_plugins'] = array(
					'label' => __( 'Must-Use Plugins:', 'conductor' ),
					'value' => ''
				);

				foreach ( $mu_plugins as $mu_path => $mu_plugin )
					$snapshot['wp_must_use_plugin_' . $mu_path] = array(
						'label' => $mu_plugin['Name'],
						'value' => $mu_plugin['Version']
					);
			}

			// if multisite, grab active network as well
			if ( is_multisite() ) {
				$snapshot['wp_multisite_network_active'] = array(
					'label' => sprintf( __( 'Network Active Plugins (%1$s):', 'conductor' ), count( $nt_plugins ) ),
					'value' => ''
				);

				foreach ( $nt_plugins as $plugin_path ) {
					if ( array_key_exists( $plugin_path, $nt_plugins ) )
						continue;

					$plugin = get_plugin_data( $plugin_path );

					$snapshot['wp_multisite_network_active_' . $plugin_path] = array(
						'label' => $plugin['Name'],
						'value' => $plugin['Version']
					);
				}
			}

			// output active plugins
			if ( $plugins ) {
				$snapshot['wp_active_plugins'] = array(
					'label' => sprintf( __( 'Active Plugins (%1$s):', 'conductor' ), count( $active_plugins ) ),
					'value' => ''
				);

				foreach ( $plugins as $plugin_path => $plugin ) {
					if ( ! in_array( $plugin_path, $active_plugins ) )
						continue;

					$snapshot['wp_active_plugins_' . $plugin_path] = array(
						'label' => $plugin['Name'],
						'value' => $plugin['Version']
					);
				}
			}

			// output inactive plugins
			if ( $plugins ) {
				$snapshot['wp_inactive_plugins'] = array(
					'label' => sprintf( __( 'Inactive Plugins (%1$s):', 'conductor' ), ( count( $plugins ) - count( $active_plugins ) ) ),
					'value' => ''
				);

				foreach ( $plugins as $plugin_path => $plugin ) {
					if ( in_array( $plugin_path, $active_plugins ) )
						continue;

					$snapshot['wp_wp_inactive_plugins_' . $plugin_path] = array(
						'label' => $plugin['Name'],
						'value' => $plugin['Version']
					);
				}
			}

			return $snapshot;
		}

		/**********************
		 * Internal Functions *
		 **********************/

		/**
		 * This function returns the sub-menu page. The optional $strip_prefix parameter allows the prefix
		 * added by WordPress to be stripped
		 */
		public static function get_sub_menu_page( $strip_prefix = true ) {
			return ( $strip_prefix ) ? str_replace( self::$sub_menu_page_prefix, '', self::$sub_menu_page ) : self::$sub_menu_page;
		}
	}

	/**
	 * Create an instance of the Conductor_Admin_Help class.
	 */
	function Conduct_Admin_Help() {
		return Conductor_Admin_Help::instance();
	}

	Conduct_Admin_Help(); // Conduct your content!
}