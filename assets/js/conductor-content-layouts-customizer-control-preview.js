/**
 * Conductor Content Layouts Customizer Control Preview (used only in the previewer of the Customizer)
 */
var conductor = conductor || {};
// TODO: BackboneJS

( function ( wp, $ ) {
	"use strict";

	// Bail if the customizer isn't initialized
	if ( ! wp || ! wp.customize ) {
		return;
	}

	var api = wp.customize, WidgetPreview;

	// Conductor Content Layouts Preview
	api.conductorCustomizerPreview = {
		content_layout: {}, // Populated using jQuery.extend() on document ready
		content_sidebar_id: {}, // Populated using jQuery.extend() on document ready
		primary_sidebar_id: {}, // Populated using jQuery.extend() on document ready
		secondary_sidebar_id: {}, // Populated using jQuery.extend() on document ready
		preview: null,

		init: function () {
			var self = this,
				// TODO: BackboneJS/Underscores templates
				conductor_ui = {
					// Controls above Conductor columns
					/*controls:
						'<div class="conductor-ui wp-core-ui conductor-ui-core conductor-cf">' +
							'<div class="conductor-ui-inner conductor-cf">' +
								'<h4 class="conductor-ui-title">Conductor</h4>' +
								'<span class="conductor-ui-button conductor-button conductor-add-widget button-primary" title="Add a Widget">' +
									'<span>+ Widget</span>' +
								'</span>' +
								'<span class="conductor-ui-button conductor-button conductor-add-conductor  button-primary" title="Add a Conductor Widget">' +
									'<span>+ Conductor</span>' +
								'</span>' +
							'</div>' +
						'</div>',*/
					// Widget edit button (before/after widgets within Conductor columns)
					widget_edit_buttons: {
						before: {
							title: '<div class="conductor-ui conductor-ui-widget conductor-cf">' +
								'<span class="conductor-ui-button conductor-button conductor-edit-widget" title="Customize this Widget">' +
									'<span class="dashicons dashicons-edit"></span>' +
								'</span>' +
							'</div>',
							no_title: '<div class="conductor-ui conductor-ui-widget conductor-ui-widget-no-title conductor-cf">' +
								'<span class="conductor-ui-button conductor-button conductor-edit-widget" title="Customize this Widget">' +
									'<span class="dashicons dashicons-edit"></span>' +
								'</span>' +
							'</div>'
						},
						// TODO: Not currently used, maybe use this in the future?
						after:
							'<div class="conductor-ui conductor-ui-widget conductor-ui-widget-last conductor-cf">' +
								'<span class="conductor-ui-button conductor-button conductor-edit-widget" title="Customize this Widget">' +
								'<span class="dashicons dashicons-edit"></span>' +
								'</span>' +
							'</div>'
					}
				};


			// Set the document jQuery reference
			this.$document = $( document );

			// When the previewer is active
			this.preview.bind( 'active', function() {
				var $conductor_inner = $( '.conductor-inner' );

				// Send the updated Conductor Customizer data to the Customizer
				if ( conductor.customizer && conductor.customizer.sidebars ) {
					// Send Customizer information over
					self.preview.send( 'conductor-sidebars', conductor.customizer.sidebars );
				}

				// Send content layout information over to the Customizer
				self.preview.send( 'conductor-content-layout', self.content_layout );

				/*
				 * Conductor Controls
				 */

				// Append the custom widget controls to Conductor sections
				// TODO: This logic will likely only work currently with Conductor "core" sidebars, but it should work with all sidebars registered through Conductor ( i.e. a theme/plugin that registers a new sidebar using conductor_register_sidebar() )
				$conductor_inner.each( function() {
					var $this = $( this ),
						$conductor_parent = $this.parents( '.conductor-content, .conductor-sidebar'),
						conductor_ui =
						'<div class="conductor-ui wp-core-ui conductor-ui-core conductor-cf">' +
							'<div class="conductor-ui-inner conductor-cf">';

					// Content
					if ( $conductor_parent.hasClass( 'conductor-content' ) ) {
						conductor_ui += '<h4 class="conductor-ui-title">' + self.content_layout.sidebar_name_prefix + ' - Content</h4>';
					}
					// Primary Sidebar
					else if ( $conductor_parent.hasClass( 'conductor-primary-sidebar' ) ) {
						conductor_ui += '<h4 class="conductor-ui-title">' + self.content_layout.sidebar_name_prefix + ' - Primary</h4>';
					}
					// Secondary Sidebar
					else if ( $conductor_parent.hasClass( 'conductor-secondary-sidebar' ) ) {
						conductor_ui += '<h4 class="conductor-ui-title">' + self.content_layout.sidebar_name_prefix + ' - Secondary</h4>';
					}

					conductor_ui += '<span class="conductor-ui-button conductor-button conductor-add-widget button-primary" title="Add a Widget">' +
									'<span>+ Widget</span>' +
								'</span>' +
								'<span class="conductor-ui-button conductor-button conductor-add-conductor  button-primary" title="Add a Conductor Widget">' +
									'<span>+ Conductor</span>' +
								'</span>' +
							'</div>' +
						'</div>';

					$this.before( conductor_ui );
				} );

				// Conductor Core Control Buttons
				// TODO: This logic will likely only work currently with Conductor "core" sidebars, but it should work with all sidebars registered through Conductor ( i.e. a theme/plugin that registers a new sidebar using conductor_register_sidebar() )
				self.$document.on( 'touch click', '.conductor-ui-core .conductor-button', function( e ) {
					var $this = $( this ), $conductor_parent = $this.parents( '.conductor-content, .conductor-sidebar' );

					// Content
					if ( $conductor_parent.hasClass( 'conductor-content') ) {
						// Send add widget data
						self.preview.send( ( $this.hasClass( 'conductor-add-conductor' ) ) ? 'conductor-add-conductor-widget' : 'conductor-add-widget', self.content_sidebar_id );
					}
					// Primary Sidebar
					else if ( $conductor_parent.hasClass( 'conductor-primary-sidebar' ) ) {
						// Send add widget data
						self.preview.send( ( $this.hasClass( 'conductor-add-conductor' ) ) ? 'conductor-add-conductor-widget' : 'conductor-add-widget', self.primary_sidebar_id );
					}
					// Secondary Sidebar
					else if ( $conductor_parent.hasClass( 'conductor-secondary-sidebar' ) ) {
						// Send add widget data
						self.preview.send( ( $this.hasClass( 'conductor-add-conductor' ) ) ? 'conductor-add-conductor-widget' : 'conductor-add-widget', self.secondary_sidebar_id );
					}
				} );


				/*
				 * Conductor Widget Edit Buttons
				 */

				// Append the custom widget controls to Conductor sections (not default sections or Note Widgets)
				$conductor_inner.children( ':not(.conductor-default):not(.conductor-clear):not(.note-widget)' ).each( function() {
					var $this = $( this ),
						$children = $this.children(),
						widget_title_exists = ( $children.filter( '.widget-title' ).length );

					// Set the display property on widgets (ignoring Conductor single/flexbox Widgets)
					if ( ! $this.hasClass( 'conductor-widget-flex' ) && ! $this.hasClass( 'conductor-widget-single-wrap' ) && ! $this.hasClass( 'conductor-widget-single-flexbox-wrap' ) && $this.css( 'display' ) !== 'block' ) {
						$this.css( {
							display: 'inline-block',
							width: '100%'
						} );

						// CSS adjustments (allow Conductor UI to appear correctly on Conductor Widgets without widget titles)
						$this.addClass( 'conductor-widget-has-ui' );
					}

					// Conductor Widgets
					if ( $this.hasClass( 'conductor-widget' ) || $this.hasClass( 'conductor-widget-single-flexbox-wrap' ) ) {
						// Conductor Widgets - Single
						if ( $children.hasClass( 'conductor-widget-single' ) ) {
							// Widget title exists
							if ( widget_title_exists ) {
								$children.addClass( 'conductor-widget-has-ui' ).filter( '.conductor-widget-single' ).prepend( conductor_ui.widget_edit_buttons.before.no_title );
							}
							// No widget title exists
							else {
								$children.addClass( 'conductor-widget-has-ui' ).prepend( conductor_ui.widget_edit_buttons.before.no_title );
							}
						}
						// Conductor Widgets - Flexbox
						else if ( $this.hasClass( 'conductor-widget-single-flexbox-wrap' ) && $children.hasClass( 'conductor-widget-single-wrap' ) ) {
							// Widget title exists
							if ( widget_title_exists ) {
								$children.find( '.conductor-widget' ).addClass( 'conductor-widget-has-ui' ).prepend( conductor_ui.widget_edit_buttons.before.no_title );
							}
							// No widget title exists
							else {
								$children.find( '.conductor-widget' ).addClass( 'conductor-widget-has-ui' ).prepend( conductor_ui.widget_edit_buttons.before.no_title );
							}
						}
						// Conductor Widgets - Many (not empty)
						else if ( $children.length ) {
							// Widget title exists
							if ( widget_title_exists ) {
								$this.addClass( 'conductor-widget-has-ui' ).prepend( conductor_ui.widget_edit_buttons.before.no_title );
							}
							// No widget title exists
							else {
								$this.addClass( 'conductor-widget-has-ui' ).prepend( conductor_ui.widget_edit_buttons.before.no_title );
							}
						}
					}
					// All Other Widgets
					else {
						// Widget title exists
						if ( widget_title_exists ) {
							$this.addClass( 'conductor-widget-has-ui' ).prepend( conductor_ui.widget_edit_buttons.before.title );
						}
						// No widget title exists
						else {
							$this.addClass( 'conductor-widget-has-ui' ).prepend( conductor_ui.widget_edit_buttons.before.no_title );
						}
					}
				} );

				// Conductor Edit Control Buttons
				self.$document.on( 'touch click', '.conductor-ui-widget .conductor-edit-widget', function( e ) {
					var $this = $( this ),
						$this_parent = $this.parent(),
						$conductor_parent = $this.parents( '.conductor-content, .conductor-sidebar' ),
						$widget = $this_parent.parents( '.conductor-widget-has-ui' ),
						$widget_id = ( ! $widget.find( '.widget-id' ).length ) ? $widget.parent().find( '.widget-id' ) : $widget.find( '.widget-id' ),
						widget_id = $widget_id.val();

					// Content
					if ( $conductor_parent.hasClass( 'conductor-content' ) ) {
						// Send edit widget data
						self.preview.send( 'conductor-edit-widget' , { sidebar_id: self.content_sidebar_id, widget_id: widget_id } );
					}
					// Primary Sidebar
					else if ( $conductor_parent.hasClass( 'conductor-primary-sidebar' ) ) {
						// Send edit widget data
						self.preview.send( 'conductor-edit-widget' , { sidebar_id: self.primary_sidebar_id, widget_id: widget_id } );
					}
					// Secondary Sidebar
					else if ( $conductor_parent.hasClass( 'conductor-secondary-sidebar' ) ) {
						// Send edit widget data
						self.preview.send( 'conductor-edit-widget' , { sidebar_id: self.secondary_sidebar_id, widget_id: widget_id } );
					}
				} );

				// Conductor Edit Control Buttons - Mouseenter
				self.$document.on( 'mouseenter', '.conductor-ui-widget .dashicons', function( e ) {
					var $this = $( this ),
						$parent = $this.parents( '.conductor-widget-has-ui' );

					$parent.addClass( 'conductor-edit-border' );
				} );

				// Conductor Edit Control Buttons - Mouseout
				self.$document.on( 'mouseout', '.conductor-ui-widget .dashicons', function( e ) {
					var $this = $( this ),
						$parent = $this.parents( '.conductor-widget-has-ui' );

					$parent.removeClass( 'conductor-edit-border' );
				} );

				/*
				 * Note UI Buttons
				 */

				// Note Add Conductor Widget Button
				self.$document.on( 'touch click', '.note-add-conductor-widget-button', function( event ) {
					var $this = $( this ),
						$sidebar = $this.parents( '.note-sidebar' ),
						sidebar_id = $sidebar.attr( 'data-note-sidebar-id' );

					// If the Conductor localize data exists
					if ( conductor.widgets && conductor.widgets.conductor && conductor.widgets.conductor.id_base ) {
						// Send the 'note-add-note-widget' data to the Customizer
						self.preview.send( 'note-add-note-widget', {
							sidebar_id: ( $sidebar.attr( 'data-note-sidebar' ) === 'true' ) ? note.sidebars.args[sidebar_id].customizer.section.sidebarId : sidebar_id,
							widget_id: conductor.widgets.conductor.id_base
						} );
					}
				} );
			} );
		}
	};

	/**
	 * Capture the instance of the Preview since it is private
	 */
	WidgetPreview = api.Preview;
	api.Preview = WidgetPreview.extend( {
		initialize: function( params, options ) {
			api.conductorCustomizerPreview.preview = this;
			WidgetPreview.prototype.initialize.call( this, params, options );
		}
	} );

	// Document ready
	$( function () {
		var settings = conductor.customizer.previewer.options;

		if ( ! settings ) {
			return;
		}

		$.extend( api.conductorCustomizerPreview, settings );

		api.conductorCustomizerPreview.init();
	} );
} )( window.wp, jQuery );