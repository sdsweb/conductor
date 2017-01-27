<?php
/**
 * Conductor Note Widget (Enhancements)
 *
 * @class Conductor_Note_Widget
 * @author Slocum Studio
 * @version 1.2.1
 * @since 1.2.0
 */

// TODO: Create a function to output each content area instead of having 3 function calls/wrappers to do it
// $widget->template_column( $number, $row, $instance );

// Bail if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

if ( ! class_exists( 'Conductor_Note_Widget' ) ) {
	final class Conductor_Note_Widget {
		/**
		 * @var string
		 */
		public $version = '1.2.1';

		/**
		 * @var array, Conductor Note Widget defaults
		 */
		public $defaults = array();

		/**
		 * @var array, Conductor Note Widget template configuration
		 */
		public $templates = array();

		/**
		 * @var string
		 */
		public $default_note_template = 'standard';

		/**
		 * @var int
		 */
		public $max_rows = 10;

		/**
		 * @var int
		 */
		public $max_content_areas = 1;

		/**
		 * @var string, directory location of template files within theme template directory or Conductor template directory
		 */
		public $base_template_dir = 'widgets/note';

		/**
		 * @var Conductor_Note_Widget, Instance of the class
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
		 * This function sets up all of the actions and filters on instance. It also initializes widget options
		 * including class name, description, width/height, and creates an instance of the widget
		 */
		function __construct() {
			// Load required assets
			$this->includes();

			// Hooks
			// TODO: Hooks are currently run multiple times due to each widget instance, we should either verify if the hook has been added, move these to another file, or set callback functions to static
			add_action( 'init', array( $this, 'init' ) ); // Init
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) ); // Enqueue admin scripts

			add_filter( 'note_tinymce_plugins', array( $this, 'note_tinymce_plugins' ) ); // Note TinyMCE Plugins
			add_filter( 'note_tinymce_toolbar', array( $this, 'note_tinymce_toolbar' ) ); // Note TinyMCE Toolbar
			add_filter( 'note_widget_widget_options', array( $this, 'note_widget_widget_options' ) ); // Note Widget Options
			add_action( 'note_widget_defaults', array( $this, 'note_widget_defaults' ), 10, 2 ); // Note Widget Defaults
			add_action( 'note_widget_settings_content_before', array( $this, 'note_widget_settings_content_before' ), 10, 2 ); // Note Widget Settings before content
			add_filter( 'note_widget_update', array( $this, 'note_widget_update' ), 10, 3 ); // Note Widget Update
			add_filter( 'note_widget_instance', array( $this, 'note_widget_instance' ), 10, 3 ); // Note Widget Instance

			add_action( 'note_widget_before', array( $this, 'note_widget_before' ), 1, 3 ); // Note Widget Output (early)
			add_action( 'note_widget_after', array( $this, 'note_widget_after' ), 9999, 3 ); // Note Widget Output After (late)
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		private function includes() {
			// TODO
		}

		/**
		 * This function sets up properties on this class and allows other plugins and themes
		 * to adjust those properties by filtering.
		 */
		// TODO: Since this class is constructed on widgets_init, other themes/plugins can already
		// TODO: filter these as long as their filters happen before widgets_init (move these back
		// TODO: to the __construct() method in a future version).
		public function init() {
			// Set up the default widget settings
			$this->defaults = apply_filters( 'conductor_note_widget_defaults', array(
				'template' => $this->default_note_template, // Widget Template
				'content_areas' => array(), // Widget content
				'rows' => 1 // Number of rows
			), $this );

			// Maximum number of rows
			$this->max_rows = ( int ) apply_filters( 'conductor_note_widget_max_rows', $this->max_rows, $this );

			/*
			 * Set up the default widget templates.
			 *
			 * Valid types:
			 * - rich_text - Default type (if not specified)
			 * - rich_text - Rich text only (no media)
			 * - rich_text_only - no media
			 * - media - Media (will include rich text once image has been removed from editor)
			 *
			 * Format:
			 *	'template-id' => array( // ID for the template (unique)
			 *		'label' => __( 'Template Label', 'conductor' ), // Label for the template
			 *		'template' => 'template', // Template name for this template (optional; without .php suffix; the widget will search through $this->base_template_dir and theme assets first, then load the fallback)
			 *		'placeholder' => '<p>Placeholder</p>', // Global placeholder text/html for this template (this placeholder will be used if an individual config does not specify a placeholder property)
			 *		'config' => array( // Customizer Previewer Configuration (array key to start at 1, not 0, and is a string)
			 *			'1' => array( // First content area
			 *				'type' => 'rich_text_only', // Type for this content area (optional)
			 *				// Placeholder Content (optional)
			 *				'placeholder' => '<p>Placeholder</p>', // Content area placeholder text/html for this template (optional)
			 *			)
			 *		)
			 *	)
			 */
			// TODO: Placeholder/default content could be set in the template possibly
			$this->templates = apply_filters( 'conductor_note_widget_templates', array(
				// 2 Columns
				'2-col' => array(
					// Label
					'label' => __( '2 Columns', 'conductor' ),
					// Placeholder Content
					'placeholder' => '<h2>Heading 2</h2>
						<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed eros tortor, molestie eget tortor sit amet, feugiat semper ante. Aliquam a pellentesque purus, quis vulputate lacus.</p>',
					// Customizer Previewer Configuration
					'config' => array(
						// Column 1 (Content Area)
						'1' => array(),
						// Column 2 (Content Area)
						'2' => array()
					)
				),
				// 2 Columns - Media Left/Content Right
				'2-col-media-content' => array(
					// Label
					'label' => __( '2 Columns - Media Left/Content Right', 'conductor' ),
					// Template
					'template' => '2-col',
					// Placeholder Content
					'placeholder' => '<h2>Heading 2</h2>
						<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed eros tortor, molestie eget tortor sit amet, feugiat semper ante. Aliquam a pellentesque purus, quis vulputate lacus.</p>',
					// Customizer Previewer Configuration
					'config' => array(
						// Column 1 (Content Area)
						'1' => array(
							'type' => 'media', // Media Only (no text)
							// Placeholder Content
							'placeholder' => ''
						),
						// Column 2 (Content Area)
						'2' => array()
					)
				),
				// 2 Columns - Content Left/Media Right
				'2-col-content-media' => array(
					// Label
					'label' => __( '2 Columns - Content Left/Media Right', 'conductor' ),
					// Template
					'template' => '2-col',
					// Placeholder Content
					'placeholder' => '<h2>Heading 2</h2>
						<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed eros tortor, molestie eget tortor sit amet, feugiat semper ante. Aliquam a pellentesque purus, quis vulputate lacus.</p>',
					// Customizer Previewer Configuration
					'config' => array(
						// Column 1 (Content Area)
						'1' => array(),
						// Column 2 (Content Area)
						'2' => array(
							'type' => 'media', // Media Only (no text)
							// Placeholder Content
							'placeholder' => ''
						)
					)
				),
				// 3 Columns
				'3-col' => array(
					// Label
					'label' => __( '3 Columns', 'conductor' ),
					// Placeholder Content
					'placeholder' => '<h2>Heading 2</h2>
								<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed eros tortor, molestie eget tortor sit amet, feugiat semper ante. Aliquam a pellentesque purus, quis vulputate lacus.</p>',
					// Customizer Previewer Configuration
					'config' => array(
						// Column 1 (Content Area)
						'1' => array(),
						// Column 2 (Content Area)
						'2' => array(),
						// Column 3 (Content Area)
						'3' => array()
					)
				),
				// 4 Columns
				'4-col' => array(
					// Label
					'label' => __( '4 Columns', 'conductor' ),
					// Placeholder Content
					'placeholder' => '<h2>Heading 2</h2>
								<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed eros tortor, molestie eget tortor sit amet, feugiat semper ante. Aliquam a pellentesque purus, quis vulputate lacus.</p>',
					// Customizer Previewer Configuration
					'config' => array(
						// Column 1 (Content Area)
						'1' => array(),
						// Column 2 (Content Area)
						'2' => array(),
						// Column 3 (Content Area)
						'3' => array(),
						// Column 4 (Content Area)
						'4' => array()
					)
				),
				// 5 Columns
				'5-col' => array(
					// Label
					'label' => __( '5 Columns', 'conductor' ),
					// Placeholder Content
					'placeholder' => '<h2>Heading 2</h2>
								<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed eros tortor, molestie eget tortor sit amet, feugiat semper ante. Aliquam a pellentesque purus, quis vulputate lacus.</p>',
					// Customizer Previewer Configuration
					'config' => array(
						// Column 1 (Content Area)
						'1' => array(),
						// Column 2 (Content Area)
						'2' => array(),
						// Column 3 (Content Area)
						'3' => array(),
						// Column 4 (Content Area)
						'4' => array(),
						// Column 5 (Content Area)
						'5' => array()
					)
				),
				// 6 Columns
				'6-col' => array(
					// Label
					'label' => __( '6 Columns', 'conductor' ),
					// Placeholder Content
					'placeholder' => '<h2>Heading 2</h2>
								<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed eros tortor, molestie eget tortor sit amet, feugiat semper ante. Aliquam a pellentesque purus, quis vulputate lacus.</p>',
					// Customizer Previewer Configuration
					'config' => array(
						// Column 1 (Content Area)
						'1' => array(),
						// Column 2 (Content Area)
						'2' => array(),
						// Column 3 (Content Area)
						'3' => array(),
						// Column 4 (Content Area)
						'4' => array(),
						// Column 5 (Content Area)
						'5' => array(),
						// Column 6 (Content Area)
						'6' => array()
					)
				)
			), $this );

			// Determine the maximum number of content areas
			if ( ! empty( $this->templates ) )
				foreach ( $this->templates as $template ) {
					// Count the number of content areas for this template
					$template_content_areas = ( isset( $template['config'] ) && is_array( $template['config'] ) ) ? count( $template['config'] ) : 0;

					if ( $template_content_areas > $this->max_content_areas )
						$this->max_content_areas = $template_content_areas;
				}

			// Allow for filtering of the base template directory
			$this->base_template_dir = apply_filters( 'conductor_note_widget_base_template_dir', $this->base_template_dir, $this);
		}

		/*
		 * This function enqueues the necessary styles associated with this widget on admin.
		 */
		public function admin_enqueue_scripts( $hook ) {
			// Only on Widgets Admin Page
			if ( $hook === 'widgets.php' ) {
				// Conductor Note Widget Admin
				wp_enqueue_script( 'conductor-note-widget-admin', Conductor::plugin_url() . '/assets/js/widgets/conductor-note-widget-admin.js', ( ! $this->is_customizer() ) ? array( 'conductor-widget-admin' ) : array( 'conductor-widget-admin', 'note-customizer' ) );

				// Localize the Conductor Note Widget admin script information
				wp_localize_script( 'conductor-note-widget-admin', 'conductor_note', apply_filters( 'conductor_note_widget_admin_localize', array(
						'default_note_template' => $this->default_note_template
					) )
				);
			}
		}

		/**
		 * This function adds plugins to Note TinyMCE configurations.
		 */
		function note_tinymce_plugins( $plugins ) {
			// Conductor Note TinyMCE Placeholder Plugin
			$plugins[] = 'conductornoteplaceholder';

			return $plugins;
		}

		/**
		 * This function adds toolbar items to Note TinyMCE configurations.
		 */
		function note_tinymce_toolbar( $items ) {
			// TODO
			// TinyMCE Foreground Color
			//$items[] = 'forecolor';

			return $items;
		}

		/**
		 * This function adjusts Note Widget options to add the Conductor Note Widget to the
		 * CSS classes (classname).
		 */
		function note_widget_widget_options( $widget_options ) {
			// Add Conductor Note Widget CSS Class
			$widget_options['classname'] .= ' conductor-note-widget';

			return $widget_options;
		}

		/**
		 * This function adjusts Note Widget defaults.
		 */
		function note_widget_defaults( $defaults, $widget ) {
			// Merge Note Widget defaults with Conductor Note Widget defaults
			$defaults = array_merge( $defaults, $this->defaults );

			return $defaults;
		}

		/**
		 * This function outputs settings within Note Widgets before the content setting.
		 */
		public function note_widget_settings_content_before( $instance, $widget ) {
			// Merge Note Widget instance with Conductor Note Widget defaults to prevent PHP notices and missing setting values
			$instance = wp_parse_args( ( array ) $instance, $this->defaults );
		?>
			<?php do_action( 'note_widget_settings_template_before', $instance, $widget ); ?>
			<?php do_action( 'conductor_note_widget_settings_template_before', $instance, $widget ); ?>

			<p class="conductor-note-template">
				<?php // Widget Template ?>
				<label for="<?php echo $widget->get_field_id( 'template' ); ?>"><strong><?php _e( 'Display Layout', 'conductor' ); ?></strong></label>
				<br />
				<select name="<?php echo $widget->get_field_name( 'template' ); ?>" id="<?php echo $widget->get_field_id( 'template' ); ?>" class="conductor-note-template conductor-select">
					<option value=""><?php _e( '&mdash; Select &mdash;', 'conductor' ); ?></option>
					<option value="<?php echo esc_attr( $this->default_note_template ); ?>" <?php selected( $instance['template'], $this->default_note_template ); ?>><?php _e( 'Standard', 'conductor' ); ?></option>

					<?php
						// If we have templates
						if ( ! empty( $this->templates ) ) :
							// Loop through each template
							foreach ( $this->templates as $template_id => $template ) :
								// Sanitize Template ID
								$template_id = esc_attr( sanitize_text_field( $template_id ) );

								// Determine Template Label (fallback to ID)
								$template_label = ( isset( $template['label'] ) && ! empty( $template['label'] ) ) ? $template['label'] : $template_id;
					?>
						<option value="<?php echo $template_id; ?>" <?php selected( $instance['template'], $template_id ); ?>><?php echo $template_label; ?></option>
					<?php
							endforeach;
						endif;
					?>
				</select>
				<small class="description note-description"><?php _e( 'Select a layout for the Note widget to display.', 'conductor' ); ?></small>
			</p>

			<?php do_action( 'conductor_note_widget_settings_template_after', $instance, $widget ); ?>
			<?php do_action( 'note_widget_settings_template_after', $instance, $widget ); ?>

			<?php do_action( 'note_widget_settings_rows_before', $instance, $widget ); ?>
			<?php do_action( 'conductor_note_widget_settings_rows_before', $instance, $widget ); ?>

			<p class="conductor-note-rows <?php echo ( empty( $instance['template'] ) || $instance['template'] === $this->default_note_template ) ? 'conductor-hidden' : false; ?>">
				<?php // Widget Rows ?>
				<label for="<?php echo $widget->get_field_id( 'rows' ); ?>"><strong><?php _e( 'Number of Rows', 'conductor' ); ?></strong></label>
				<br />
				<select name="<?php echo $widget->get_field_name( 'rows' ); ?>" id="<?php echo $widget->get_field_id( 'rows' ); ?>" class="conductor-note-rows conductor-select">
					<option value=""><?php _e( '&mdash; Select &mdash;', 'conductor' ); ?></option>

					<?php
						// Loop through rows
						for ( $i = 1; $i <= $this->max_rows; $i++ ) :
					?>
						<option value="<?php echo $i; ?>" <?php selected( $instance['rows'], $i ); ?>><?php echo $i; ?></option>
					<?php
						endfor;
					?>
				</select>
				<small class="description note-description"><?php _e( 'Select the number of rows to display.', 'conductor' ); ?></small>
			</p>

			<?php do_action( 'conductor_note_widget_settings_rows_after', $instance, $widget ); ?>
			<?php do_action( 'note_widget_settings_rows_after', $instance, $widget ); ?>

			<?php do_action( 'note_widget_settings_conductor_content_before', $instance, $widget ); ?>
			<?php do_action( 'conductor_note_widget_settings_content_before', $instance, $widget ); ?>

			<div class="conductor-widget-setting conductor-widget-content">
				<?php // Widget Content ?>
				<?php
					// If we have content areas, output the correct amount of textareas
					if ( $this->max_content_areas ) :
						// Loop through rows
						for ( $row = 1; $row <= $this->max_rows; $row++ ) :
							// Loop through content areas
							for ( $i = 1; $i <= $this->max_content_areas; $i++ ) :
								$content_area_num = $i + ( $this->max_content_areas * ( $row - 1 ) );
								// TODO: conductor-hidden or note-hidden
				?>
					<textarea class="conductor-input conductor-hidden conductor-content conductor-content-<?php echo $content_area_num; ?> conductor-content-<?php echo $row; ?>-<?php echo $i; ?>" id="<?php echo $widget->get_field_id( 'content-area-' . $content_area_num ); ?>" name="<?php echo $widget->get_field_name( 'content_area][' . $content_area_num ); ?>" rows="16" cols="20"><?php echo ( isset( $instance['content_areas'][$content_area_num] ) ) ? $instance['content_areas'][$content_area_num] : false; ?></textarea>
				<?php
							endfor;
						endfor;
					endif;
				?>
			</div>

			<?php do_action( 'conductor_note_widget_settings_content_after', $instance, $widget ); ?>
			<?php do_action( 'note_widget_settings_conductor_content_after', $instance, $widget ); ?>
		<?php
		}

		/**
		 * This function sanitizes values on Note Widget updates (save).
		 */
		public function note_widget_update( $new_instance, $old_instance, $widget ) {
			// Widget Template
			$new_instance['template'] = ( ! empty( $new_instance['template'] ) ) ? sanitize_text_field( $new_instance['template'] ) : $this->default_note_template; // Widget Template
			$new_instance['template'] = ( ! empty( $new_instance['template'] ) && $this->is_valid_template( $new_instance['template'] ) ) ? $new_instance['template'] : $this->default_note_template; // Further sanitization of Widget Template

			// Widget Rows
			$new_instance['rows'] = ( ! empty( $new_instance['rows'] ) ) ? ( int ) $new_instance['rows'] : $this->defaults['rows']; // Widget Rows

			// Widget Content
			// TODO: Sanitize based on type of content area in $this->templates and $new_instance['template']?
			if ( is_array( $new_instance['content_area'] ) && ! empty( $new_instance['content_area'] ) )
				// Loop through content areas
				foreach ( $new_instance['content_area'] as &$content_area )
					$content_area = ( ! empty( $content_area ) ) ? $this->sanitize_widget_content( $content_area ) : false; // Widget Content - Sanitize as post_content; Fake a Post ID

			// Widget Content (further sanitization)
			if ( is_array( $new_instance['content_area'] ) && ! empty( $new_instance['content_area'] ) )
				// Loop through content areas
				foreach ( $new_instance['content_area'] as $number => &$content_area ) {
					// Placeholder
					$placeholder = $this->get_template_placeholder( ( $number + 1 ), 0, $new_instance ); // Fetch the template's placeholder

					// Values for direct comparison
					$compare_placeholder = $this->sanitize_widget_content( $placeholder, 'compare' );
					$compare_content = $this->sanitize_widget_content( $content_area, 'compare' );

					$content_area = ( ! empty( $content_area ) && $compare_content !== $compare_placeholder ) ? $content_area : false;
				}

			// Widget Content (store in correct location)
			$new_instance['content_areas'] = $new_instance['content_area'];
			unset( $new_instance['content_area'] );

			return apply_filters( 'conductor_note_widget_update', $new_instance, $old_instance, $widget, $this );
		}

		/**
		 * This function adjusts Note Widget instances to make sure Conductor Note Widget
		 * defaults exist.
		 */
		function note_widget_instance( $instance, $args, $widget ) {
			// Merge Note Widget instance with Conductor Note Widget defaults to prevent PHP notices and missing setting values
			$instance = wp_parse_args( ( array ) $instance, $this->defaults );

			return $instance;
		}

		/**
		 * This function determines whether or not Conductor Note Widget templates should be
		 * output instead of standard Note Widget content.
		 */
		function note_widget_before( $instance, $args, $widget ) {
			// Check to see if we have a valid template
			if ( $this->is_valid_template( $instance['template'] ) ) {
				// Grab the Note Widget instance
				$note_widget = Note_Widget();

				// Remove Note Widget output
				remove_action( 'note_widget', array( get_class( $note_widget ), 'note_widget' ), 10, 3 ); // Note Widget Output

				// Add Conductor Note Widget output
				add_action( 'note_widget', array( $this, 'note_widget' ), 10, 3 ); // Note Widget Output
			}
		}

		/**
		 * This function outputs custom Note Widget templates.
		 */
		public function note_widget( $instance, $args, $widget ) {
			extract( $args ); // $before_widget, $after_widget, $before_title, $after_title

			do_action( 'conductor_note_widget_before', $instance, $args, $widget, $this );

			// Widget Title
			do_action( 'conductor_note_widget_title_before', $instance, $args, $widget, $this );
			$this->widget_title( $before_title, $after_title, $instance );
			do_action( 'conductor_note_widget_title_after', $instance, $args, $widget, $this );
			?>

			<div class="note-wrapper conductor-note-wrapper <?php echo esc_attr( $widget->get_css_classes( $instance ) ); ?> <?php echo esc_attr( $this->get_css_classes( $instance ) ); ?>">
				<?php
					$template = $instance['template'];

					// Customizer Placeholder
					if ( $this->is_customize_preview() && ! $this->is_valid_template( $instance['template'] ) )
						$template = 'placeholder';

					// Loop through rows on the instance
					if ( $instance['rows'] )
						for ( $i = 1; $i <= $instance['rows']; $i++ )
							// Load Widget Template
							$this->load_template( $this->get_template( $template ), $template, $i, $instance, $args, $widget );
				?>
			</div>

			<?php
			do_action( 'conductor_note_widget_after', $instance, $args, $widget, $this );
		}


		/**
		 * This function resets Note Widget output.
		 */
		function note_widget_after( $instance, $args, $widget ) {
			// Check to see if we have a valid template
			if ( $this->is_valid_template( $instance['template'] ) ) {
				// Grab the Note Widget instance
				$note_widget = Note_Widget();

				// Add Note Widget output
				add_action( 'note_widget', array( get_class( $note_widget ), 'note_widget' ), 10, 3 ); // Note Widget Output

				// Remove Conductor Note Widget output
				remove_action( 'note_widget', array( $this, 'note_widget' ), 10, 3 ); // Note Widget Output
			}
		}


		/**
		 * ------------------
		 * Internal Functions
		 * ------------------
		 */

		/**
		 * This function generates CSS classes for widget output.
		 */
		public function get_css_classes( $instance ) {
			$classes = array( 'conductor-note-widget-wrapper', $instance['template'], 'conductor-note-widget-' . $instance['template'] );

			// Custom CSS Classes (Note_Widget::get_css_classes() handles custom CSS classes)
			//if ( ! empty( $instance['css_class'] ) )
			//	$classes[] = str_replace( '.', '', $instance['css_class'] );

			$classes = apply_filters( 'conductor_note_widget_css_classes', $classes, $instance, $this );

			return implode( ' ', $classes );
		}

		/**
		 * This function generates CSS classes for widget title output.
		 */
		public function get_widget_title_css_classes( $instance ) {
			$classes = array( 'conductor-note-widget-title' );

			// Custom CSS Classes
			if ( ! empty( $instance['css_class'] ) )
				$classes[] = str_replace( '.', '', $instance['css_class'] );

			$classes = apply_filters( 'conductor_note_widget_title_css_classes', $classes, $instance, $this );

			return implode( ' ', $classes );
		}

		/**
		 * This function gets the widget title.
		 */
		public function widget_title( $before_title, $after_title, $instance ) {
			$before_title = str_replace( 'class="', 'class="' . $this->get_widget_title_css_classes( $instance ) . ' ', $before_title );

			// Widget Title
			if ( ! empty( $instance['title'] ) && ( ! isset( $instance['hide_title'] ) || ( isset( $instance['hide_title'] ) && ! $instance['hide_title'] ) ) )
				// TODO: As of 1.2.1 "$this->id_base" below causes a PHP notice because id_base doesn't exist
				echo $before_title . apply_filters( 'widget_title', $instance['title'], $instance, $this->id_base, $this ) . $after_title;
		}

		/**
		 * This function validates the selected widget template by checking if the template exists
		 * in template configuration.
		 */
		function is_valid_template( $template_id ) {
			// Does this template id exist in templates?
			return array_key_exists( $template_id, $this->templates );
		}

		/**
		 * This function returns the correct template name for the selected template. It will use
		 * the template ID as the fallback template name.
		 */
		function get_template( $template_id ) {
			// Does this template id exist in templates?
			if ( $this->is_valid_template( $template_id ) && isset( $this->templates[$template_id]['template'] ) && ! empty( $this->templates[$template_id]['template'] ) )
				$template_id = $this->templates[$template_id]['template'];

			// Return the template
			return $template_id;
		}

		/**
		 * This function loads a template for display in the widget.
		 */
		public function load_template( $template_name, $template, $row, $instance, $args, $widget ) {
			// Data to pass to the template (will be extract()ed for use in the template)
			$data = array(
				'instance' => $instance, // Widget Instance
				'args' => $args, // Widget Args
				'note_widget' => $widget, // Note Widget
				'widget' => $this, // Widget
				'template' => $template, // Template
				'row' => $row // Row
			);


			do_action( 'conductor_note_widget_' . $template_name . '_before', $template_name, $template, $data, $instance, $args, $widget, $this );

			// Get the Conductor template part
			conductor_get_template_part( $this->base_template_dir . '/' . $template_name, '', $data );

			do_action( 'conductor_note_widget_' . $template_name . '_after', $template_name, $template, $data, $instance, $args, $widget, $this );
		}

		/**
		 * This function generates CSS classes for widget template output based on context.
		 */
		public function get_template_css_class( $number, $context, $row, $instance ) {
			$orig_number = $number;
			$classes = array();
			$template = ( $this->is_valid_template( $instance['template'] ) ) ? $this->templates[$instance['template']] : false; // Fetch the current template
			$template_content_areas = ( $template && isset( $template['config'] ) && is_array( $template['config'] ) ) ? count( $template['config'] ) : 0;

			// Determine the correct number
			if ( $context !== 'row' && $row !== 1 && $template_content_areas )
				$number = $number + ( $template_content_areas * ( $row - 1 ) );

			// Switch based on context
			switch ( $context ) {
				// Row
				case 'row':
					//$classes[] = 'row';
					//$classes[] = 'row-' . $number . '-col';
					$classes[] = 'conductor-row';
					$classes[] = 'conductor-row-' . $number . '-columns';
					$classes[] = 'conductor-flex';
					$classes[] = 'conductor-flex-' . $number . '-columns';
					$classes[] = 'conductor-' . $number . '-columns';
				break;

				// Column
				case 'column':
					//$classes[] = 'col';
					//$classes[] = 'col-' . $number;
					$classes[] = 'conductor-col';
					$classes[] = 'conductor-col-' . $number;

					// Previewer only
					if ( $this->is_customize_preview() ) {
						//$classes[] = 'col-has-editor';
						$classes[] = 'conductor-col-has-editor';
						//$classes[] = 'col-editor-' . $number;
						$classes[] = 'conductor-col-editor-' . $number;

						// Media
						if ( $this->is_valid_template( $instance['template'] ) ) {
							// Template
							$template = $this->templates[$instance['template']];

							if ( isset( $template['config'] ) && isset( $template['config'][$orig_number] ) && isset( $template['config'][$orig_number]['type'] ) && $template['config'][$orig_number]['type'] === 'media' ) {
								$classes[] = 'conductor-col-editor-media';
								$classes[] = 'conductor-col-editor-media-' . $number;
							}
						}
					}
				break;

				// Content
				case 'content':
					// Previewer only
					if ( $this->is_customize_preview() ) {
						$classes[] = 'editor';
						$classes[] = 'editor-' . $number;

						// Placeholder
						if ( empty( $instance['content_areas'][$number] ) ) {
							$classes[] = 'editor-placeholder';
							$classes[] = 'editor-placeholder-' . $number;
							$classes[] = 'note-has-placeholder';
							$classes[] = 'note-has-placeholder-' . $number;
						}

						// Media
						if ( $this->is_valid_template( $instance['template'] ) ) {
							// Template
							$template = $this->templates[$instance['template']];

							if ( isset( $template['config'] ) && isset( $template['config'][$orig_number] ) && isset( $template['config'][$orig_number]['type'] ) && $template['config'][$orig_number]['type'] === 'media' ) {
								$classes[] = 'editor-media';
								$classes[] = 'editor-media-' . $number;
								$classes[] = 'note-editor-media';
								$classes[] = 'note-editor-media-' . $number;

								// Placeholder
								if ( empty( $instance['content_areas'][$number] ) ) {
									$classes[] = 'editor-media-placeholder';
									$classes[] = 'editor-media-placeholder-' . $number;
									$classes[] = 'note-media-placeholder';
									$classes[] = 'note-media-placeholder-' . $number;
								}
							}
						}
					}
					// Front end
					else {
						$classes[] = 'conductor-note-content';
						$classes[] = 'conductor-note-content-wrap';
					}
				break;
			}

			$classes = apply_filters( 'conductor_note_widget_template_css_classes', $classes, $number, $orig_number, $row, $context, $instance, $this );

			return implode( ' ', $classes );
		}


		/**
		 * This function outputs a CSS class attribute with classes for widget template
		 * based on context.
		 */
		public function template_css_class( $number, $context, $row, $instance = false ) {
			echo 'class="' . esc_attr( $this->get_template_css_class( $number, $context, $row, $instance ) ) . '"';
		}

		/**
		 * This function fetches template placeholder content based on the content area number index.
		 * It will fetch a global placeholder on the template if set.
		 */
		public function get_template_placeholder( $number, $row, $instance ) {
			// Placeholder
			$template = ( $this->is_valid_template( $instance['template'] ) ) ? $this->templates[$instance['template']] : false; // Fetch the current template
			$placeholder = ( $template && isset( $template['config'][$number]['placeholder'] ) ) ? $template['config'][$number]['placeholder'] : false; // Fetch this configuration placeholder
			$placeholder = ( $template && $placeholder === false && isset( $template['placeholder'] ) ) ? $template['placeholder'] : $placeholder; // Fetch the template's placeholder
			$placeholder = $this->sanitize_widget_content( $placeholder, 'placeholder' ); // Sanitize the placeholder

			return $placeholder;
		}

		/**
		 * This function outputs template placeholder content based on the content area number index.
		 */
		public function template_placeholder( $number, $row, $instance ) {
			echo $this->get_template_placeholder( $number, $row, $instance );
		}

		/**
		 * This function fetches template content based on the content area number index.
		 */
		public function get_template_content( $number, $row, $instance ) {
			$template = ( $this->is_valid_template( $instance['template'] ) ) ? $this->templates[$instance['template']] : false; // Fetch the current template
			$template_content_areas = ( $template && isset( $template['config'] ) && is_array( $template['config'] ) ) ? count( $template['config'] ) : 0;

			// Placeholder
			$placeholder = $this->get_template_placeholder( $number, $row, $instance ); // Fetch the template's placeholder

			// Determine the correct number for content
			if ( ! in_array( $row, array( 0, 1 ) ) && $template_content_areas )
				//$number = $number + $template_content_areas + ( ( $row - 1 ) - 1 );
				$number = $number + ( $template_content_areas * ( $row - 1 ) );

			// Content (already sanitized)
			$content = do_shortcode( $instance['content_areas'][$number] );

			return ( ! $this->is_customize_preview() || ! empty( $content ) ) ? $content : $placeholder;
		}


		/**
		 * This function outputs template content based on the content area number index.
		 */
		public function template_content( $number, $row, $instance ) {
			echo $this->get_template_content( $number, $row, $instance );
		}

		/**
		 * This function sanitizes widget content. Allows for a context to determine sanitization method.
		 */
		public function sanitize_widget_content( $content, $context = 'content' ) {
			// Switch based on context
			switch ( $context ) {
				// Compare (expects sanitized $content)
				case 'compare':
					// Remove all tabs and newlines
					$content = preg_replace( "/\t|[\r?\n]/", '', $content );

					// Remove Note placeholder CSS class
					$content = preg_replace( '/ class=\"note-placeholder\"/', '', $content );
				break;

				// Sanitized Compare (sanitize for direct comparison)
				case 'sanitized_compare':
					// Sanitize as post_content; Fake a Post ID
					$content = wp_unslash( sanitize_post_field( 'post_content', $content, 0, 'db' ) );

					// Remove all tabs and newlines
					$content = preg_replace( "/\t|[\r?\n]/", '', $content );
				break;

				// Placeholder
				case 'placeholder':
					// Sanitize as post_content; Fake a Post ID
					$content = wp_unslash( sanitize_post_field( 'post_content', $content, 0, 'db' ) );

					// Remove all tabs
					$content = preg_replace( "/\t/", '', $content );

					// Find all single newlines and add an extra (TinyMCE does this with content)
					$content = preg_replace( "([\r?\n]{1})", "\n\n", $content );
				break;

				// Content (default)
				default:
					// Sanitize as post_content; Fake a Post ID
					$content = wp_unslash( sanitize_post_field( 'post_content', $content, 0, 'db' ) );
				break;
			}

			return $content;
		}


		/**
		 * ----------------
		 * Helper Functions
		 * ----------------
		 */

		/**
		 * This function determines if we're currently in the Customizer.
		 */
		public function is_customizer() {
			return did_action( 'customize_controls_init' );
		}

		/**
		 * This function determines we're currently being previewed in the Customizer.
		 */
		public function is_customize_preview() {
			$is_gte_wp_4 = Conductor::wp_version_compare( '4.0' );

			// Less than 4.0
			if ( ! $is_gte_wp_4 ) {
				global $wp_customize;

				return is_a( $wp_customize, 'WP_Customize_Manager' ) && $wp_customize->is_preview();
			}
			// 4.0 or greater
			else
				return is_customize_preview();
		}
	}

	/**
	 * Create an instance of the Conductor_Note_Widget class.
	 */
	function Conduct_Note_Widget() {
		return Conductor_Note_Widget::instance();
	}

	Conduct_Note_Widget(); // Conduct your content!
}