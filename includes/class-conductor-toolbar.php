<?php
/**
 * Conductor Toolbar (Admin Bar)
 *
 * @class Conductor_Toolbar
 * @author Slocum Studio
 * @version 1.4.4
 * @since 1.0.0
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Toolbar' ) ) {
	final class Conductor_Toolbar {
		/**
		 * @var string
		 */
		public $version = '1.4.4';

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
		function __construct( ) {
			// Hooks
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 9999 ); // Admin Bar Menu (late)
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu_help' ), 99999 ); // Admin Bar Menu (later)
			add_action( 'wp_before_admin_bar_render', array( $this, 'wp_before_admin_bar_render' ) ); // Before Admin Bar Render
		}

		/**
		 * This function runs when the admin bar is initialized and adds a Conductor node.
		 */
		public function admin_bar_menu( $wp_admin_bar ) {
			// Bail if the current user is not an administrator
			if ( ! current_user_can( Conductor::$capability ) )
				return;

			// Grab the Conductor Admin menu page slug
			$conductor_admin_menu_page = Conductor_Admin_Options::get_menu_page();

			// Conduct your content/conduct this page menu
			$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			// Conductor parent menu
			$wp_admin_bar->add_menu( array(
				'id' => $conductor_admin_menu_page,
				'title' => __( '<span class="ab-icon dashicons dashicons-admin-generic conductor-icon"></span> Conductor', 'conductor' ),
				'href' => ( is_admin() ) ? wp_customize_url() : add_query_arg( 'url', urlencode( $current_url ), wp_customize_url() ),
				'meta' => array(
					'class' => 'conductor conductor-parent'
				)
			) );

			$wp_admin_bar->add_menu( array(
				'id' => 'conductor-conduct',
				'parent' => $conductor_admin_menu_page,
				'title' => ( is_admin() ) ? __( 'Conduct Your Content', 'conductor' ) : __( 'Conduct This Page', 'conductor' ),
				'href' => ( is_admin() ) ? wp_customize_url() : add_query_arg( 'url', urlencode( $current_url ), wp_customize_url() ),
				'meta' => array(
					'class' => 'conductor conductor-child conductor-conduct hide-if-no-customize'
				)
			) );

			// Options menu
			$wp_admin_bar->add_menu( array(
				'id' => Conductor_Admin_Options::get_menu_page() . '-options',
				'parent' => $conductor_admin_menu_page,
				'title' => __( 'Options', 'conductor' ),
				'href' => admin_url( add_query_arg( 'page', $conductor_admin_menu_page, 'admin.php' ) ),
				'meta' => array(
					'class' => 'conductor conductor-child ' . Conductor_Admin_Options::get_menu_page() . '-options'
				)
			) );

			// Addons menu
			$wp_admin_bar->add_menu( array(
				'id' => Conductor_Admin_Add_Ons::get_sub_menu_page(),
				'parent' => $conductor_admin_menu_page,
				'title' => __( 'Add-Ons', 'conductor' ),
				'href' => admin_url( add_query_arg( 'page', Conductor_Admin_Add_Ons::get_sub_menu_page(), 'admin.php' ) ),
				'meta' => array(
					'class' => 'conductor conductor-child ' . Conductor_Admin_Add_Ons::get_sub_menu_page()
				)
			) );
		}

		/**
		 * This function runs when the admin bar is initialized and adds a Conductor Help node. It ensures
		 * that the Conductor Help node will be added as the last child node of the parent Conductor node.
		 */
		public function admin_bar_menu_help( $wp_admin_bar ) {
			// Bail if the current user is not an administrator
			if ( ! current_user_can( Conductor::$capability ) )
				return;

			// Grab the Conductor Admin menu page slug
			$conductor_admin_menu_page = Conductor_Admin_Options::get_menu_page();

			// Help menu
			$wp_admin_bar->add_menu( array(
				'id' => Conductor_Admin_Help::get_sub_menu_page(),
				'parent' => $conductor_admin_menu_page,
				'title' => __( 'Help', 'conductor' ),
				'href' => admin_url( add_query_arg( 'page', Conductor_Admin_Help::get_sub_menu_page(), 'admin.php' ) ),
				'meta' => array(
					'class' => 'conductor conductor-child ' . Conductor_Admin_Help::get_sub_menu_page()
				)
			) );
		}

		/**
		 * This function runs before the admin bar is rendered and outputs styles for the Conductor item.
		 */
		public function wp_before_admin_bar_render() {
			?>
			<style type="text/css">
				#wpadminbar .conductor-parent > a {
					font-size: 14px;
				}
			</style>
		<?php
		}
	}

	/**
	 * Create an instance of the Conductor_Toolbar class.
	 */
	function Conduct_Toolbar() {
		return Conductor_Toolbar::instance();
	}

	Conduct_Toolbar(); // Conduct your content!
}