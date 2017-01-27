<?php
/**
 * Conductor Note Customizer Shim (Customizer functionality)
 *
 * @class Conductor_Note_Widget_Customizer
 * @author Slocum Studio
 * @version 1.0.0
 * @since 1.3.0
 *
 * TODO: Remove in a future version
 */

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Note_Widget_Customizer' ) ) {
	final class Conductor_Note_Widget_Customizer {
		/**
		 * @var string
		 */
		public $version = '1.0.0';

		/**
		 * @var array
		 */
		public $conductor_note_editor_types = array( 'rich_text', 'rich_text_only', 'media' );

		/**
		 * @var array
		 */
		public $conductor_note_localize = array();

		/**
		 * @var array
		 */
		public $note_localize = array();

		/**
		 * @var array
		 */
		public $note_tinymce_localize = array();

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
			// Hooks
			add_action( 'init', array( $this, 'init' ), 9999 ); // Init (late)
			add_action( 'customize_preview_init', array( $this, 'customize_preview_init' ) ); // Customizer Preview Initialization
		}

		/**
		 * This function checks to see if Note is active and sets up Note localization data.
		 */
		function init() {
			// If Note is active
			if ( Conductor::is_note_active() ) {
				// Grab an instance of the Note Customizer class
				$note_customizer = Note_Customizer::instance();

				// Setup (copy) Note localization data
				$this->note_localize = $note_customizer->note_localize;

				// Conductor Note Widget Editor Types
				$this->conductor_note_editor_types = apply_filters( 'conductor_note_editor_types', $this->conductor_note_editor_types, $this );

				// Setup Previewer localization
				$this->conductor_note_localize = array(
					// TinyMCE Config Parameters
					'tinymce' => array(),
					// Widget Settings & Templates
					'widgets' => array(
						'settings' => array(), // Settings for individual widgets
						'templates' => array() // Available widget templates/config
					)
				);

				// Loop through editor types for configuration
				if ( is_array( $this->conductor_note_editor_types ) && ! empty( $this->conductor_note_editor_types ) ) {
					foreach ( $this->conductor_note_editor_types as $editor_type ) {
						// Switch based on editor type
						switch ( $editor_type ) {
							// Rich Text Only
							case 'rich_text_only':
								$settings = array(
									'selector' => '.conductor-note-widget .editor', // Element selector (general; specific selectors are created on initialization in Previewer)
									// Allow filtering of plugins on an array instead of a space separated string
									'plugins' => implode( ' ', array_unique( apply_filters( 'conductor_note_tinymce_plugins', array(
										'wplink',
										'wpview',
										'paste',
										'lists',
										'hr',
										'noteinsert',
										'textcolor'
									), $editor_type, $this ) ) ),
									// Block level elements
									'blocks' => array(
										'note_edit'
									),
									// Custom TinyMCE theme expects separate "rows"
									'toolbar' => apply_filters( 'conductor_note_tinymce_toolbar', array_merge( $this->note_localize['tinymce']['toolbar'], array( 'forecolor' ) ), $editor_type, $this ), // Inherit from Note
									// Alignment Formats
									'formats' => $this->note_localize['tinymce']['formats'], // Inherit from Note
									'theme' => $this->note_localize['tinymce']['theme'], // Inherit from Note
									'inline' => $this->note_localize['tinymce']['inline'], // Inherit from Note
									'relative_urls' => $this->note_localize['tinymce']['relative_urls'], // Inherit from Note
									'convert_urls' => $this->note_localize['tinymce']['convert_urls'], // Inherit from Note
									'browser_spellcheck' => $this->note_localize['tinymce']['browser_spellcheck'], // Inherit from Note
									'entity_encoding' => $this->note_localize['tinymce']['entity_encoding'], // Inherit from Note
									'placeholder' => apply_filters( 'note_tinymce_placeholder', $this->note_localize['tinymce']['placeholder'], $editor_type, $this ) // Inherit from Note
								);
							break;

							// Media
							case 'media':
								// Copy Note localization data
								$settings = $this->note_localize['tinymce'];

								// Add media blocks
								$settings['media_blocks'] = array( 'wp_image' );

								// Reset the placeholder
								$settings['placeholder'] = '';

								// Allow filtering of plugins, toolbar items, and placeholder
								$settings['plugins'] = explode( ' ', $settings['plugins'] );
								$settings['plugins'] = implode( ' ', array_unique( apply_filters( 'conductor_note_tinymce_plugins', $settings['plugins'], $editor_type, $this ) ) );
								$settings['toolbar'] = apply_filters( 'conductor_note_tinymce_toolbar', $settings['toolbar'], $editor_type, $this );
								$settings['placeholder'] = apply_filters( 'conductor_note_tinymce_placeholder', $settings['placeholder'], $editor_type, $this );

								// Adjust selector
								$settings['selector'] = '.conductor-note-widget .editor'; // Element selector (general; specific selectors are created on initialization in Previewer)
							break;

							// Rich Text (Default; inherit from Note)
							default:
								// Copy Note localization data
								$settings = $this->note_localize['tinymce'];

								// Allow filtering of plugins, toolbar items, and placeholder
								$settings['plugins'] = explode( ' ', $settings['plugins'] );
								$settings['plugins'] = implode( ' ', array_unique( apply_filters( 'conductor_note_tinymce_plugins', $settings['plugins'], $editor_type, $this ) ) );
								$settings['toolbar'] = apply_filters( 'conductor_note_tinymce_toolbar', $settings['toolbar'], $editor_type, $this );
								$settings['placeholder'] = apply_filters( 'conductor_note_tinymce_placeholder', $settings['placeholder'], $editor_type, $this );

								// Adjust selector
								$settings['selector'] = '.conductor-note-widget .editor'; // Element selector (general; specific selectors are created on initialization in Previewer)
							break;
						}

						// Add the Note editor type
						$settings['note_type'] = $editor_type;

						// Assign the configuration to the localization data
						$settings = apply_filters( 'conductor_note_editor_settings', $settings, $editor_type, $this );
						$this->conductor_note_localize['tinymce'][$editor_type] = $settings;
					}
				}

				$this->conductor_note_localize = apply_filters( 'conductor_note_localize', $this->conductor_note_localize, $this );
			}
		}

		/**
		 * This function fires on the initialization of the Customizer. We add actions that pertain to the
		 * Customizer preview window here. The actions added here are fired only in the Customizer preview.
		 */
		public function customize_preview_init() {
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) ); // Conductor Content Layout Previewer Scripts/Styles
		}

		/**
		 * This function outputs scripts and styles in the the Customizer preview only.
		 */
		public function wp_enqueue_scripts() {
			global $wp_registered_widgets;

			// Conductor Note Widget Script (only enqueue script if this widget is active; requires Note)
			if ( Conductor::is_note_active() && function_exists( 'Note_Widget' ) && function_exists( 'Conduct_Note_Widget' ) ) {
				$note_widget = Note_Widget(); // Note Widget instance
				$conductor_note_widget = Conduct_Note_Widget(); // Conductor Note Widget instance

				// If a Note Widget is active
				if ( is_active_widget( false, false, $note_widget->id_base, true ) ) {

					// Re-add the widgets key if it doesn't exist (due to filtering above)
					if ( ! isset( $this->conductor_note_localize['widgets'] ) )
						$this->conductor_note_localize['widgets'] = array(
							'settings' => array(), // Settings for individual widgets
							'templates' => array() // Available widget templates/config
						);

					// Setup the template data
					$this->conductor_note_localize['widgets']['templates'] = $conductor_note_widget->templates;

					foreach ( $this->conductor_note_localize['widgets']['templates'] as &$template ) {
						// Template Config
						if ( isset( $template['config'] ) && is_array( $template['config'] ) ) {
							// Count template content areas
							$template_content_areas = count( $template['config'] );

							// Loop through rows (skip row 1 since it's already configured)
							for ( $row = 2; $row <= $conductor_note_widget->max_rows; $row++ ) {
								// Loop through content areas
								for ( $i = 1; $i <= $template_content_areas; $i++ ) {
									$new_content_area = $i + ( $template_content_areas * ( $row - 1 ) );

									// If this config is not already set
									if ( ! isset( $template['config'][$new_content_area] ) )
										$template['config'][$new_content_area] = $template['config'][$i];
								}
							}
						}
					}

					// Find Conductor Note Widgets in sidebars
					// TODO: The following logic will fetch data for all Note Widgets in all sidebars, can we just output data for displayed widgets?
					$sidebars_widgets = wp_get_sidebars_widgets();
					$conductor_note_widget_settings = array();

					if ( is_array( $sidebars_widgets ) )
						// Loop through sidebars
						foreach ( $sidebars_widgets as $sidebar => $widgets ) {
							// Ignore inactive or orphaned
							if ( $sidebar !== 'wp_inactive_widgets' && substr( $sidebar, 0, 16 ) !== 'orphaned_widgets' && is_array( $widgets ) )
								// Loop through widgets
								foreach ( $widgets as $widget ) {
									// Verify that this is a Note Widget
									if ( $note_widget->id_base === _get_widget_id_base( $widget ) ) {
										// Make sure this widget has a callback
										if ( isset( $wp_registered_widgets[$widget] ) ) {
											// Store a reference to this widget object
											$wp_widget = $wp_registered_widgets[$widget];
											$widget_number = $wp_widget['params'][0]['number'];

											// Store a reference to the widget settings (all Note Widgets)
											if ( empty( $conductor_note_widget_settings ) )
												$conductor_note_widget_settings = $note_widget->get_settings();

											// Find this widget in settings
											if ( array_key_exists( $widget_number, $conductor_note_widget_settings ) ) {
												// Widget settings (parse with Conductor Note Widget defaults to prevent PHP warnings and missing setting values)
												$this->conductor_note_localize['widgets']['settings'][$widget_number] = wp_parse_args( ( array ) $conductor_note_widget_settings[$widget_number], $conductor_note_widget->defaults );

												// Store a reference to the widget number
												$this->conductor_note_localize['widgets']['settings'][$widget_number]['widget_number'] = $widget_number;

												// Store a reference to the widget ID
												$this->conductor_note_localize['widgets']['settings'][$widget_number]['widget_id'] = $widget;

												// Store a reference to the sidebar ID
												$this->conductor_note_localize['widgets']['settings'][$widget_number]['sidebar_id'] = $sidebar;
											}
										}
									}
								}
						}

					// Allow for filtering of localization widget data
					$this->conductor_note_localize['widgets'] = apply_filters( 'conductor_note_localize_widgets', $this->conductor_note_localize['widgets'], $this );

					// Conductor Note Widget Previewer script
					wp_enqueue_script( 'conductor-note-widget-preview', Conductor::plugin_url() . '/assets/js/widgets/conductor-note-widget-preview.js', array( 'note' ), Conductor::$version, true );
					wp_localize_script( 'conductor-note-widget-preview', 'conductor_note', $this->conductor_note_localize );

					// Conductor Note TinyMCE Placeholder Plugin
					wp_enqueue_script( 'conductor-note-tinymce-placeholder', Conductor::plugin_url() . '/assets/js/widgets/conductor-note-tinymce-placeholder.js', array( 'note-tinymce' ), Conductor::$version, true );
				}
			}
		}
	}

	/**
	 * Create an instance of the Conductor_Note_Widget_Customizer class.
	 */
	function Conduct_Note_Widget_Customizer() {
		return Conductor_Note_Widget_Customizer::instance();
	}

	Conduct_Note_Widget_Customizer(); // Conduct your content!
}