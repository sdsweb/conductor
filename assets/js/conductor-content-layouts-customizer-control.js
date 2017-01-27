/**
 * Conductor Content Layouts Customizer Control
 */
var conductor = conductor || {};

( function ( wp, $, conductor_content_layouts_customizer ) {
	"use strict";

	var api = wp.customize, OldPreviewer, OldPreviewFrame;

	// Defaults
	if ( conductor.hasOwnProperty( 'customizer' ) && conductor.customizer ) {
		$.extend( conductor.customizer, {
			controls: {},
			views: {}
		} );
	}
	else {
		conductor.customizer = conductor.customizer || {
			controls: {},
			views: {}
		};
	}

	api.Conductor = api.Conductor || {};

	// Conductor Customizer
	api.Conductor.Customizer = {
		previewer_refresh_flag: false,
		// Get the Previewer refresh flag
		disallowPreviewerRefresh: function() {
			return this.previewer_refresh_flag;
		},
		// Set the Previewer refresh flag
		setPreviewerRefreshFlag: function( value ) {
			this.previewer_refresh_flag = value;
		}
	};

	// Conductor Content Layouts Controller
	api.conductorContentLayoutsControl = api.Control.extend( {
		// When the customizer is "ready"
		ready: function() {
			var self = this, window_events = {};

			// Document ready
			$( function() {
				// Store references to DOM objects
				self.$window = $( window ); // Window
				self.$body = $( 'body' ); // Body Element
				self.$customize_sidebar_header = self.$body.find( '.wp-full-overlay-header' ); // Customizer Sidebar Header
				self.$customize_sidebar_content = self.$body.find( '.wp-full-overlay-sidebar-content' ); // Customizer Sidebar Content
				self.$customize_theme_controls = self.$customize_sidebar_content.find( '#customize-theme-controls' ); // Theme Controls
				self.$widgets_panel = self.$customize_theme_controls.find( '#accordion-panel-widgets' ); // Widgets Panel

				// Store a reference to the current major WordPress version (major version number only)
				self.wp_major_version = parseInt( conductor.customizer.wp_version, 10 );

				// Store a reference to the current WordPress version (major and minor version numbers only)
				self.wp_version = parseFloat( conductor.customizer.wp_version );

				// Instantiate a new Controls View
				self.views['controls_view'] = new self.controls_view( {
					self: self
				} );

				// Instantiate a new View
				self.views['view'] = new self.view( {
					self: self,
					previewer: api.Conductor.Previewer
				} );
			} );

			// Listen for the "conductor-sidebars" event from the Previewer
			api.Conductor.Previewer.bind( 'conductor-sidebars', function( data ) {
				var conductor_widget_reorder_tmpl = wp.template( 'conductor-widget-reorder' );

				// Loop through Conductor Customizer sidebar data
				_.each( data, function( value, key ) {
					// Determine if the sidebar argument data from the Previewer is different
					if ( key === 'args' ) {
						// Loop through sidebar arguments
						_.each( value, function( args, sidebar_id ) {
							// If this sidebar doesn't exist, add it now
							if ( _.isUndefined( conductor.customizer.sidebars.args[sidebar_id] ) ) {
								conductor.customizer.sidebars.args[sidebar_id] = args;
							}
							// Otherwise if the data from the Previewer is different
							else if ( ! _.isEqual( conductor.customizer.sidebars.args[sidebar_id], args ) ) {
								_.extend( conductor.customizer.sidebars.args[sidebar_id], args );
							}

							// Add the template data
							conductor.customizer.sidebars.args[sidebar_id].customizer.widget_reorder_template = conductor_widget_reorder_tmpl( {
								id: args.id,
								description: args.description,
								name: args.name
							} );
						} );
					}
					// Determine if other data from the Previewer is different
					else {
						// If this sidebar doesn't exist, add it now
						if ( _.isUndefined( conductor.customizer.sidebars[key] ) ) {
							conductor.customizer.sidebars[key] = value;
						}
						// Otherwise if the data from the Previewer is different
						else if ( ! _.isEqual( conductor.customizer.sidebars[key], value ) ) {
							_.extend( conductor.customizer.sidebars[key], value );
						}
					}
				} );
			} );

			// Listen for the "conductor-content-layout" event from the Previewer and trigger the same event name on this controller's main Backbone view to setup content layout data
			api.Conductor.Previewer.bind( 'conductor-content-layout', function( content_layout ) {
				var content_layout_id = content_layout['field_type'] + '-' + content_layout['field_id'],
					conductor_sidebars_args = conductor.customizer.sidebars.args,
					content_layout_sidebars = conductor.customizer.sidebars.content_layouts[content_layout_id];

				// Set the content layout (pass self to the callback)
				self.setPreviewerContentLayout( content_layout, self );

				// Hide the loading spinner in the controls view
				self.views.controls_view.conductor_content_layout_controls_view.$( '.conductor-spinner' ).css( 'display', 'none' );

				// Determine if any "missing" sidebars should be registered for this content layout
				if ( ! _.isUndefined ( content_layout_sidebars ) ) {
					// Loop through content layout sidebars
					_.each( content_layout_sidebars, function( sidebar_id ) {
						// If we have sidebar arguments for this sidebar
						if ( ! _.isUndefined ( conductor_sidebars_args[sidebar_id] ) ) {
							// If this sidebar is not already registered, register it now
							if ( ! api.control( conductor_sidebars_args[sidebar_id].customizer.control.id ) ) {
								// "Register" the new sidebar for use in the Customizer
								self.registerConductorSidebar( sidebar_id, conductor_sidebars_args[sidebar_id] );
							}
						}
					} );
				}
			} );

			/*
			 * Conductor Previewer UI
			 */

			// Bind the conductor-add-conductor-widget to the previewer
			api.Conductor.Previewer.bind( 'conductor-add-conductor-widget', function( sidebar_id ) {
				self.addConductorWidget( sidebar_id, self ); // Pass self to the callback
			} );

			// Bind the conductor-add-widget to the previewer
			api.Conductor.Previewer.bind( 'conductor-add-widget', function( sidebar_id ) {
				self.addWidget( sidebar_id, self ); // Pass self to the callback
			} );

			// Bind the conductor-edit-widget to the previewer
			api.Conductor.Previewer.bind( 'conductor-edit-widget', function( data ) {
				self.editWidget( data, self ); // Pass self to the callback
			} );
		},
		$window: false,
		$body: false,
		$customize_theme_controls: false,
		$widgets_panel: false,
		$customize_theme_controls_content : false,
		$customize_theme_controls_header : false,
		sidebars_no_widgets: [],
		default_widget_complete_callbacks: 0,
		wp_version: 0, // Reference to the current WordPress version (float; e.g. 4.1 or 4.0; does not contain minor version at this time)
		wp_major_version: 0, // Reference to the current major WordPress version (int, i.e. 4 or 5)
		// This function is triggered when the previewer window has loaded
		setPreviewerContentLayout: function( content_layout, self ) {
			self.conductor_content_layout = content_layout;

			self.views.view.trigger( 'conductor-content-layout', content_layout );

			// TODO: Edge case: Conductor Layouts can open unexpectedly if the user closes a panel right as the previewer sends this data
			// Open the Conductor section
			// Commented for WordPress 4.3 due to Sections behaving more like Panels in the Customizer
			/*if ( ! $( '.accordion-section' ).hasClass( 'open' ) ) {
				self.$customize_theme_controls.find( '.accordion-section[id$="conductor_content_layouts"] .accordion-section-title' ).trigger( 'click' );
			}*/
		},
		// This function is triggered when the user would like to add a Conductor widget to a specific sidebar
		addConductorWidget: function( sidebar_id, self ) {
			// In WordPress 4.1 and above the process has been simplified for us
			if ( self.wp_version > 4 ) {
				var prefix = 'sidebar-widgets-',
					sidebar_section = api.section( prefix + sidebar_id );

				// Make sure this sidebar exists
				if ( typeof sidebar_section === 'undefined' ) {
					return;
				}

				// Open the Customizer sidebar first
				self.openCustomizerSidebar( self.$body );

				// Expand the section for this sidebar (if it's not already open)
				if ( ! sidebar_section.expanded() ) {
					// Collapse all sections
					api.section.each( function ( section ) {
						// Section is currently expanded
						if ( section.expanded() ) {
							// Collapse this section
							section.collapse( { duration: 0 } ); // Hide immediately
						}
					} );

					// Collapse all panels
					api.panel.each( function ( panel ) {
						// Panel is currently expanded
						if ( panel.expanded() && panel.id !== sidebar_section.panel() ) {
							// Collapse this panel
							panel.collapse( { duration: 0 } ); // Hide immediately
						}
					} );

					sidebar_section.expand( {
						// Open the "Add a Widget" panel (if it's not already open)
						completeCallback: function() {
							// Trigger a click on the "Add a Widget" button
							sidebar_section.container.find( '.add-new-widget' ).trigger( 'click' );

							// Add a Conductor Widget (api.Widgets.availableWidgetsPanel is a Backbone View)
							api.Widgets.availableWidgetsPanel.$( '.widget-tpl[data-widget-id^="conductor-widget"]' ).trigger( 'click' );
					} } );
				}
				// Sidebar section is already open
				else {
					var widgets_panel = api.panel( 'widgets' );

					// Open the Widgets Panel (if it's not already open)
					if ( ! widgets_panel.expanded() ) {
						widgets_panel.expand();
					}

					// Open the "Add a Widget" panel (if it's not already open)
					if ( ! self.$body.hasClass( 'adding-widget' ) ) {
						sidebar_section.container.find( '.add-new-widget' ).trigger( 'click' );

						// Add a Conductor Widget (api.Widgets.availableWidgetsPanel is a Backbone View)
						api.Widgets.availableWidgetsPanel.$( '.widget-tpl[data-widget-id^="conductor-widget"]' ).trigger( 'click' );
					}
					// "Add a Widget" panel is already open
					else {
						// Just add a Conductor Widget (api.Widgets.availableWidgetsPanel is a Backbone View)
						api.Widgets.availableWidgetsPanel.$( '.widget-tpl[data-widget-id^="conductor-widget"]' ).trigger( 'click' );

					}
				}
			}
			// WordPress 4.0 and below
			else {
				var $sidebar_panel = ( self.wp_major_version >= 4 ) ? self.$widgets_panel.find( '.accordion-section[id$="' + sidebar_id + '"]' ) : self.$customize_theme_controls.find( '.accordion-section[id$="' + sidebar_id + '"]' );

				// Open the Customizer sidebar first
				self.openCustomizerSidebar( self.$body );

				// If WordPress 4.0 and up
				if ( self.wp_major_version >= 4 ) {
					// Open the Widgets panel
					self.openCustomizerWidgetsPanel( self.$widgets_panel );
				}

				// Sidebar Panel
				if ( $sidebar_panel.length ) {
					// Open the Sidebar Panel (if it's not already open)
					if ( ! $sidebar_panel.hasClass( 'open' ) ) {
						$sidebar_panel.find( '.accordion-section-title' ).trigger( 'click' );
					}

					// Open the "Add a Widget" panel (if it's not already open)
					if ( ! self.$body.hasClass( 'adding-widget' ) ) {
						$sidebar_panel.find( '.add-new-widget' ).trigger( 'click' );
					}

					// Add a Conductor Widget (api.Widgets.availableWidgetsPanel is a Backbone View)
					api.Widgets.availableWidgetsPanel.$( '.widget-tpl[data-widget-id^="conductor-widget"]' ).trigger( 'click' );

					// Scroll to sidebar panel in Customizer sidebar (wait for other animations to finish first)
					this.scrollCustomizerSidebar( $sidebar_panel, self );
				}
			}
		},
		// This function is triggered when the user would like to add any widget to a specific sidebar
		addWidget: function( sidebar_id, self ) {
			// In WordPress 4.1 and above the process has been simplified for us
			if ( self.wp_version > 4 ) {
				var prefix = 'sidebar-widgets-',
					sidebar_section = api.section( prefix + sidebar_id );

				// Make sure this sidebar exists
				if ( typeof sidebar_section === 'undefined' ) {
					return;
				}

				// Open the Customizer sidebar first
				self.openCustomizerSidebar( self.$body );

				// Expand the section for this sidebar (if it's not already open)
				if ( ! sidebar_section.expanded() ) {
					// Collapse all sections
					api.section.each( function ( section ) {
						// Section is currently expanded
						if ( section.expanded() ) {
							// Collapse this section
							section.collapse( { duration: 0 } ); // Hide immediately
						}
					} );

					// Collapse all panels
					api.panel.each( function ( panel ) {
						// Panel is currently expanded
						if ( panel.expanded() && panel.id !== sidebar_section.panel() ) {
							// Collapse this panel
							panel.collapse( { duration: 0 } ); // Hide immediately
						}
					} );

					sidebar_section.expand( {
						// Open the "Add a Widget" panel (if it's not already open)
						completeCallback: function() {
							// Trigger a click on the "Add a Widget" button
							sidebar_section.container.find( '.add-new-widget' ).trigger( 'click' );
					} } );
				}
				// Sidebar section is already open
				else {
					var widgets_panel = api.panel( 'widgets' );

					// Open the Widgets Panel (if it's not already open)
					if ( ! widgets_panel.expanded() ) {
						widgets_panel.expand();
					}

					// Just open the "Add a Widget" panel (if it's not already open)
					if ( ! self.$body.hasClass( 'adding-widget' ) ) {
						sidebar_section.container.find( '.add-new-widget' ).trigger( 'click' );
					}
				}
			}
			// WordPress 4.0 and below
			else {
				var $sidebar_panel = ( self.wp_major_version >= 4 ) ? self.$widgets_panel.find( '.accordion-section[id$="' + sidebar_id + '"]' ) : self.$customize_theme_controls.find( '.accordion-section[id$="' + sidebar_id + '"]' );

				// Open the Customizer sidebar first
				self.openCustomizerSidebar( self.$body );

				// If WordPress 4.0 and up
				if ( self.wp_major_version >= 4 ) {
					// Open the Widgets panel
					self.openCustomizerWidgetsPanel( self.$widgets_panel );
				}

				// Sidebar Panel
				if ( $sidebar_panel.length ) {
					// Open the Sidebar Panel (if it's not already open)
					if ( ! $sidebar_panel.hasClass( 'open' ) ) {
						$sidebar_panel.find( '.accordion-section-title' ).trigger( 'click' );
					}

					// Open the "Add a Widget" panel (if it's not already open)
					if ( ! self.$body.hasClass( 'adding-widget' ) ) {
						$sidebar_panel.find( '.add-new-widget' ).trigger( 'click' );
					}

					// Scroll to sidebar panel in Customizer sidebar (wait for other animations to finish)
					this.scrollCustomizerSidebar( $sidebar_panel, self );
				}
			}
		},
		/*
		 * This function adds a default widget to a sidebar. It uses logic similar to core's
		 * implementation for adding a widget to a sidebar, however we're creating a new ConductorWidgetControl control
		 * instead of a default WidgetControl to ensure we only have 1 AJAX call for all of our default widgets.
		 *
		 * License: GPLv2 or later
		 * Copyright: WordPress, http://wordpress.org/
		 *
		 * @see api.Widgets.SidebarControl.addWidget()
		 * @see https://github.com/WordPress/WordPress/blob/4.2-branch/wp-admin/js/customize-widgets.js#L1765-L1892
		 *
		 * We've used core's implementation as a base and modified it to suit our needs.
		 */
		addDefaultWidget: function( default_widget, sidebar_control ) {
			var self = sidebar_control, controlHtml, $widget,
				widgetId = default_widget.widget_id_base,
				controlType = 'widget_form',
				conductorControlType = 'conductor_widget_form', // ConductorWidgetControl
				controlContainer, controlConstructor,
				widgetIdMatches,
				parsedWidgetId = {
					number: null,
					id_base: null
				},
				widgetNumber, widgetIdBase, widget,
				settingId, isExistingWidget, widgetFormControl, sidebarWidgets, settingArgs, setting;

			// Parse the Widget ID
			widgetIdMatches = widgetId.match( /^(.+)-(\d+)$/ );
			if ( widgetIdMatches ) {
				parsedWidgetId.id_base = widgetIdMatches[1];
				parsedWidgetId.number = parseInt( widgetIdMatches[2], 10 );
			} else {
				// likely an old single widget
				parsedWidgetId.id_base = widgetId;
			}

			widgetNumber = parsedWidgetId.number;
			widgetIdBase = parsedWidgetId.id_base;
			widget = api.Widgets.availableWidgets.findWhere( { id_base: widgetIdBase } );


			if ( ! widget ) {
				return false;
			}

			if ( widgetNumber && ! widget.get( 'is_multi' ) ) {
				return false;
			}

			// Set up new multi widget
			if ( widget.get( 'is_multi' ) && ! widgetNumber ) {
				widget.set( 'multi_number', widget.get( 'multi_number' ) + 1 );
				widgetNumber = widget.get( 'multi_number' );
			}

			controlHtml = $.trim( $( '#widget-tpl-' + widget.get( 'id' ) ).html() );
			if ( widget.get( 'is_multi' ) ) {
				controlHtml = controlHtml.replace( /<[^<>]+>/g, function( m ) {
					return m.replace( /__i__|%i%/g, widgetNumber );
				} );
			} else {
				widget.set( 'is_disabled', true ); // Prevent single widget from being added again now
			}

			$widget = $( controlHtml );

			controlContainer = $( '<li/>' )
				.addClass( 'customize-control' )
				.addClass( 'customize-control-' + controlType )
				.append( $widget );

			// Remove icon which is visible inside the panel
			controlContainer.find( '> .widget-icon' ).remove();

			if ( widget.get( 'is_multi' ) ) {
				controlContainer.find( 'input[name="widget_number"]' ).val( widgetNumber );
				controlContainer.find( 'input[name="multi_number"]' ).val( widgetNumber );
			}

			widgetId = controlContainer.find( '[name="widget-id"]' ).val();

			settingId = 'widget_' + widget.get( 'id_base' );
			if ( widget.get( 'is_multi' ) ) {
				settingId += '[' + widgetNumber + ']';
			}
			controlContainer.attr( 'id', 'customize-control-' + settingId.replace( /\]/g, '' ).replace( /\[/g, '-' ) );

			// Only create setting if it doesn't already exist (if we're adding a pre-existing inactive widget)
			isExistingWidget = api.has( settingId );
			if ( ! isExistingWidget ) {
				settingArgs = {
					transport: 'refresh',
					previewer: self.setting.previewer
				};
				setting = api.create( settingId, settingId, '', settingArgs );
				setting.set( default_widget.widget_settings ); // mark dirty, changing from '' to default widget settings
			}

			controlConstructor = api.controlConstructor[conductorControlType];
			widgetFormControl = new controlConstructor( settingId, {
				params: {
					settings: {
						'default': settingId
					},
					content: controlContainer,
					sidebar_id: self.params.sidebar_id,
					widget_id: widgetId,
					widget_id_base: widget.get( 'id_base' ),
					type: controlType,
					is_new: ! isExistingWidget,
					width: widget.get( 'width' ),
					height: widget.get( 'height' ),
					is_wide: widget.get( 'is_wide' )
				},
				previewer: self.setting.previewer
			} );
			api.control.add( settingId, widgetFormControl );

			// Make sure widget is removed from the other sidebars
			api.each( function( otherSetting ) {
				if ( otherSetting.id === self.setting.id ) {
					return;
				}

				if ( 0 !== otherSetting.id.indexOf( 'sidebars_widgets[' ) ) {
					return;
				}

				var otherSidebarWidgets = otherSetting().slice(),
					i = _.indexOf( otherSidebarWidgets, widgetId );

				if ( -1 !== i ) {
					otherSidebarWidgets.splice( i );
					otherSetting( otherSidebarWidgets );
				}
			} );

			// Add widget to this sidebar
			sidebarWidgets = self.setting().slice();
			if ( -1 === _.indexOf( sidebarWidgets, widgetId ) ) {
				sidebarWidgets.push( widgetId );
				self.setting( sidebarWidgets );
			}

			return widgetFormControl;
		},
		/*
		 * This function adds a default widget to a sidebar and updates the widget. Upon updateWidget completion, change
		 * all ConductorWidgetControl controls to default WidgetControl controls constructors to ensure they function as
		 * default widgets (we no longer need ConductorWidgetControl).
		 *
		 * @see addDefaultWidget() above
		 */
		addDefaultWidgetToSidebar: function( default_widget, sidebar_control ) {
			var self = this,
				widget_control;

			// Add the widget
			widget_control = self.addDefaultWidget( default_widget, sidebar_control );
			//widget_control = sidebar_control.addWidget( default_widget.widget_id_base );

			// Temporarily unbind the preview event (to prevent Previewer refreshes)
			widget_control.setting.unbind( widget_control.setting.preview );

			// Increase the default widget callback count
			self.default_widget_complete_callbacks++;

			// Adjust the setting
			widget_control.updateWidget( {
				complete: function() {
					var setting_preview = this.setting.preview;

					// Decrease the callback count
					self.default_widget_complete_callbacks--;

					// Reset the refresh flag to allow Previewer refreshes
					if ( api.Conductor.Customizer.disallowPreviewerRefresh() ) {
						api.Conductor.Customizer.setPreviewerRefreshFlag( false );
					}

					// If this is the last callback, trigger the set event with the correct value
					if ( ! self.default_widget_complete_callbacks ) {
						// Re-bind the preview event on all widgets
						_.each( this.controls, function ( control, key ) {
							control.setting.bind( setting_preview );
						} );

						// This triggers the "change" event on the Customizer API
						self.set( self.views.view.setting );
					}
				}
			} );
		},
		// This function is triggered when the user would like to edit a widget within a specific sidebar
		editWidget: function( data, self ) {
			// In WordPress 4.1 and above the process has been simplified for us
			if ( self.wp_version > 4 ) {
				var prefix = 'sidebar-widgets-',
					sidebar_section = api.section( prefix + data.sidebar_id ),
					form_control = api.Widgets.getWidgetFormControlForWidget( data.widget_id ); //  Grab the form control for the particular widget

				// Make sure this sidebar exists
				if ( typeof sidebar_section === 'undefined' ) {
					return;
				}

				// Open the Customizer sidebar first
				self.openCustomizerSidebar( self.$body );

				// Expand the section for this sidebar (if it's not already open)
				if ( ! sidebar_section.expanded() ) {
					// Collapse all sections
					api.section.each( function ( section ) {
						// Section is currently expanded
						if ( section.expanded() ) {
							// Collapse this section
							section.collapse( { duration: 0 } ); // Hide immediately
						}
					} );

					// Collapse all panels
					api.panel.each( function ( panel ) {
						// Panel is currently expanded
						if ( panel.expanded() && panel.id !== sidebar_section.panel() ) {
							// Collapse this panel
							panel.collapse( { duration: 0 } ); // Hide immediately
						}
					} );

					sidebar_section.expand( {
						// Open the "Add a Widget" panel (if it's not already open)
						completeCallback: function() {
							// If we have a form control and it's not currently open
							if ( form_control && ! form_control.expanded() ) {
								// Expand the form control
								form_control.expand( {
									duration: 0, // Open immediately (no animation)
									// On completion
									completeCallback: function() {
										// Set a timeout to ensure the input element can be focused properly after all expansion events
										setTimeout( function() {
											// Select the first input element (title)
											form_control.container.find( 'input:first' ).focus();
										}, 100 ); // 100ms delay
									}
								} );
							}
							// Otherwise just focus the first input element (title)
							else if ( form_control ) {
								// Select the first input element (title)
								form_control.container.find( 'input:first' ).focus();
							}
					} } );
				}
				// Sidebar section is already open
				else {
					var widgets_panel = api.panel( 'widgets' );

					// Open the Widgets Panel (if it's not already open)
					if ( ! widgets_panel.expanded() ) {
						widgets_panel.expand();
					}

					// If we have a form control and it's not currently open
					if ( form_control && ! form_control.expanded() ) {
						// Expand the form control
						form_control.expand( {
							duration: 0, // Open immediately (no animation)
							// On completion
							completeCallback: function() {
								// Set a timeout to ensure the input element can be focused properly after all expansion events
								setTimeout( function() {
									// Select the first input element (title)
									form_control.container.find( 'input:first' ).focus();
								}, 100 ); // 100ms delay
							}
						} );
					}
					// Otherwise just focus the first input element (title)
					else if ( form_control ) {
						// Select the first input element (title)
						form_control.container.find( 'input:first' ).focus();
					}
				}
			}
			// WordPress 4.0 and below
			else {
				var $sidebar_panel = ( self.wp_major_version >= 4 ) ? self.$widgets_panel.find( '.accordion-section[id$="' + data.sidebar_id + '"]' ) : self.$customize_theme_controls.find( '.accordion-section[id$="' + data.sidebar_id + '"]' );

				// Open the Customizer sidebar first
				self.openCustomizerSidebar( self.$body );

				// If WordPress 4.0 and up
				if ( self.wp_major_version >= 4 ) {
					// Open the Widgets panel
					self.openCustomizerWidgetsPanel( self.$widgets_panel );
				}

				// Sidebar Panel
				if ( $sidebar_panel.length ) {
					// Find the correct widget (first list item is the description of the widget area)
					var $widget = $sidebar_panel.find( '.accordion-section-content .customize-control-widget_form:eq(' + data.widget + ')' );

					if ( $widget.length ) {
						// Open the Sidebar Panel (if it's not already open)
						if ( ! $sidebar_panel.hasClass( 'open' ) ) {
							$sidebar_panel.find( '.accordion-section-title' ).trigger( 'click' );
						}

						// Open the widget for editing (if it's not already open)
						if ( ! $widget.hasClass( 'expanded' ) ) {
							$widget.find( '.widget-top' ).trigger( 'click' );
						}

						// Scroll to sidebar panel in Customizer sidebar (wait for other animations to finish)
						this.scrollCustomizerSidebar( $sidebar_panel, self );
					}
				}
			}
		},
		// Open the Customizer sidebar if it's not already open
		openCustomizerSidebar: function( $body ) {
			if ( $body.children( '.wp-full-overlay' ).hasClass( 'collapsed' ) ) {
				$( '.collapse-sidebar' ).trigger( 'click' );
			}
		},
		// Open the Customizer widgets panel if it's not already open
		openCustomizerWidgetsPanel: function( $widgets_panel ) {
			if ( ! $widgets_panel.hasClass( 'current-panel' ) ) {
				$widgets_panel.find( '.accordion-section-title' ).trigger( 'click' );
			}
		},
		// Scroll to the sidebar panel in the Customizer sidebar (wait for other animations to finish)
		scrollCustomizerSidebar: function( $sidebar_panel, self ) {
			setTimeout( function() {
				self.$customize_sidebar_content.scrollTop( 0 );

				self.$customize_sidebar_content.animate( {
					scrollTop: $sidebar_panel.offset().top - self.$customize_sidebar_header.height()
				}, 100 );
			}, 400 ); // 400ms ensures that most (if not all) other animations have completed
		},
		// "Register" a sidebar within the Customizer
		registerConductorSidebar: function( sidebar_id, sidebar_args ) {
			var sidebar_section_priority = -1,
				sidebar_section_prefix = 'sidebar-widgets-',
				is_setting_dirty = false,
				// Customizer data
				conductor_customizer_setting = sidebar_args.customizer.setting,
				conductor_customizer_section = sidebar_args.customizer.section,
				conductor_customizer_control = sidebar_args.customizer.control,
				// Customizer Setting
				setting = {
					id: conductor_customizer_setting.id,
					transport: conductor_customizer_setting.transport,
					value: conductor_customizer_setting.value,
					dirty: conductor_customizer_setting.dirty
				},
				// Customizer Section
				section = {
					active: conductor_customizer_section.active,
					content: conductor_customizer_section.content,
					panel: conductor_customizer_section.panel,
					sidebarId: conductor_customizer_section.sidebarId,
					title: conductor_customizer_section.title,
					description: conductor_customizer_section.description,
					customizeAction: ( conductor_customizer_section.panel ) ? conductor.l10n.customize_action_with_panel + ' ' + wp.customize.panel( 'widgets' ).params.title : customize_action_with_panel,
					type: conductor_customizer_section.type,
					instanceNumber: _.size( api.settings.sections ),
					priority: -1
				},
				// Customizer Control
				control = {
					active: conductor_customizer_control.active,
					content: conductor_customizer_control.content,
					section: conductor_customizer_control.section,
					sidebar_id: conductor_customizer_control.sidebar_id,
					priority: conductor_customizer_control.priority,
					settings: conductor_customizer_control.settings,
					type: conductor_customizer_control.type,
					instanceNumber: _.size( api.settings.controls )
				},
				customizer_setting_value = [],
				sectionConstructor, customizer_section,
				controlConstructor, customizer_control;

			// Generate the correct priority for this sidebar section
			api.section.each( function ( section ) {
				var priority = section.priority();

				// Sidebar section
				if ( section.id.indexOf( sidebar_section_prefix ) !== -1 && priority > sidebar_section_priority ) {
					sidebar_section_priority = priority;
				}
			} );

			// Increase the priority by 1 to make sure there are no conflicts
			sidebar_section_priority++;

			// Set the priority on the section object
			section.priority = sidebar_section_priority;

			// Add our sidebar to the list of registered sidebars (omitting our 'customizer' key)
			api.Widgets.registeredSidebars.add( _.omit( sidebar_args, 'customizer' ) );


			/*
			 * Customizer Setting
			 */

			// Add setting data to api.settings.settings
			api.settings.settings[setting.id] = {
				transport: setting.transport,
				value: setting.value
			};

			// Add Customizer setting (value will be an empty array if there are no widgets previously assigned)
			api.create( setting.id, setting.id, setting.value, {
				transport: setting.transport,
				previewer: api.previewer,
				dirty: !! setting.dirty
			} );

			// If there is a difference in setting values
			if ( _.difference( customizer_setting_value, setting.value ).length ) {
				// set() the setting (make it _dirty)
				api( setting.id ).set( customizer_setting_value );
			}
			// Otherwise, no difference, but let's check the indexes just to be sure
			else {
				// Loop through each of the setting values (we know they will both contain the same values)
				_.each( setting.value, function( value, index ) {
					// Only if the setting is not already dirty and the indexes do not match
					if ( ! is_setting_dirty && index !== customizer_setting_value.indexOf( value ) ) {
						// set() the setting (make it _dirty)
						api( setting.id ).set( customizer_setting_value );

						// Set the dirty flag
						is_setting_dirty = true;
					}
				} );
			}


			/*
			 * Customizer Section
			 */

			// Add section data to api.settings.sections
			api.settings.sections[conductor_customizer_section.id] = section;

			// Determine the correct constructor (should be sidebar constructor in our case; fallback to default section)
			sectionConstructor = api.sectionConstructor[section.type] || api.Section;

			// Create the section
			customizer_section = new sectionConstructor( conductor_customizer_section.id, {
				params: section
			} );

			// Add the section
			api.section.add( conductor_customizer_section.id, customizer_section );


			/*
			 * Customizer Control
			 */

			// Add control data to api.settings.controls
			api.settings.controls[conductor_customizer_control.id] = control;

			// Determine the correct constructor (should be sidebar constructor in our case; fallback to default control)
			controlConstructor = api.controlConstructor[control.type] || api.Control;

			// Create the control
			customizer_control = new controlConstructor( conductor_customizer_control.id, {
				params: control,
				previewer: api.previewer
			} );

			// Add the control
			api.control.add( conductor_customizer_control.id, customizer_control );

			// Loop through controls
			api.control.each( function( control ) {
				// Widget form controls only
				if ( control.params && control.params.type === 'widget_form' ) {
					// Find the re-order element and add the new sidebar element
					control.container.find( '.widget-area-select' ).append( sidebar_args.customizer.widget_reorder_template );
				}
			} );
		},
		// Register Conductor default widgets for Conductor Sidebars
		registerConductorDefaultWidgets: function( default_widgets, content_layout_sidebars ) {
			var self = this, sidebars_added = false,
				conductor_content_layout = this.views.view.getContentLayout(),
				sidebar_prefix = conductor_content_layout.sidebar_prefix,
				sidebar_suffix = conductor_content_layout.sidebar_suffix,
				conductor_sidebar_id, sidebar_control, is_sidebar_empty = null, sidebar_index = -1;

			// Loop through default widgets
			_.each( default_widgets, function( default_widget ) {
				// Create a Conductor Sidebar ID
				conductor_sidebar_id = sidebar_prefix + default_widget.sidebar_id + sidebar_suffix;

				// If we have a sidebar
				if ( content_layout_sidebars.indexOf( conductor_sidebar_id ) !== -1 ) {
					// Determine if we're switching sidebar controls
					if ( ! sidebar_control || sidebar_control.id !== 'sidebars_widgets[' + conductor_sidebar_id + ']' ) {
						// Grab the sidebar control
						sidebar_control = api.control( 'sidebars_widgets[' + conductor_sidebar_id + ']' );

						// If this sidebar doesn't exist in cache yet
						if ( self.sidebars_no_widgets.indexOf( conductor_sidebar_id ) === -1 ) {
							// Reset the empty sidebar flag
							is_sidebar_empty = null;
						}
					}

					// If we have a sidebar control
					if ( sidebar_control ) {
						// Determine if this sidebar is empty and cache the value
						is_sidebar_empty = ( is_sidebar_empty === null ) ? ! sidebar_control.setting().length : is_sidebar_empty;

						// Store this value in cache
						if ( ( sidebar_index = self.sidebars_no_widgets.indexOf( conductor_sidebar_id ) ) === -1 && is_sidebar_empty ) {
							self.sidebars_no_widgets.push( conductor_sidebar_id );
						}
						// Otherwise this sidebar is "empty" if it's stored in sidebars_no_widgets (set the flag)
						else if ( ! is_sidebar_empty && sidebar_index !== -1 ) {
							is_sidebar_empty = true;
						}

						// If the sidebar is/was empty (checking cached value)
						if ( is_sidebar_empty ) {
							// Add default widget
							self.addDefaultWidgetToSidebar( default_widget, sidebar_control );

							sidebars_added = true;
						}
					}
				}
			} );

			return sidebars_added;
		},
		// Determine if the content layout value matches the default widget value
		isDefaultWidgetContentLayoutMatch: function( value, default_widget ) {
			// Matches
			if ( default_widget['matches'] && value.indexOf( default_widget.content_layout ) !== -1 ) {
				return true;
			}
			// Equals
			else if ( value === default_widget.content_layout ) {
				return true;
			}

			return false;
		},
		conductor_content_layout: {},
		views: {},
		// The main view of this controller (Conductor Content Layouts)
		view: Backbone.View.extend( {
			self: false,
			el: '.conductor-content-layouts',
			collection: conductor['content-layouts'].views['content-layout'].prototype.collection,
			content_layout: {},
			setting: false,
			initialize: function( options ) {
				var conductor_content_layouts_view = conductor['content-layouts'].ready.views['content-layouts'].controls.controller_view;

				// Set self property to the Control if passed
				this.self = options.self || false;

				/*
				 * Once the rendering is complete on the Conductor Content Layouts view we send the settings to the
				 * previewer. This is to ensure that any newly added DOM elements are accounted for.
				 */
				this.listenTo( conductor_content_layouts_view, 'render:complete', this.setContentLayoutsControlVal );

				// When the content layout in the Previewer has changed, set the content layout
				this.listenTo( this, 'conductor-content-layout', this.setContentLayout );

				// When the content layout in the Previewer has changed, refresh the list
				this.listenTo( this, 'conductor-content-layout', this.refreshContentLayouts );
			},
			// Event hashes for removing/switching content layouts
			events : {
				'click .conductor-remove-content-layout': 'setContentLayoutsControlVal',
				'click .conductor-content-layout input': 'setContentLayoutsControlVal'
			},
			// Set the content layout
			setContentLayout: function( content_layout ) {
				this.content_layout = content_layout;
			},
			// Get the content layout
			getContentLayout: function() {
				return this.content_layout;
			},
			// Send the updated Conductor Content Layout settings to the Previewer
			setContentLayoutsControlVal: function( event ) {
				var self = this,
					sidebars_added = false,
					api_saved_state = api.state( 'saved' ),
					$current_target = ( event ) ? $( event.currentTarget ) : false,
					old_setting_val = this.self.get(),
					setting = false,
					selected_content_layout_value = false,
					current_content_layout = this.getContentLayout(),
					current_content_layout_id = current_content_layout.field_type + '-' + current_content_layout.field_id,
					conductor_content_layout = this.getContentLayout(),
					sidebar_prefix = conductor_content_layout.sidebar_prefix,
					sidebar_suffix = conductor_content_layout.sidebar_suffix,
					content_layout_sidebars = [],
					content_layout_has_default_widgets = false,
					conductor_sidebar_id,
					sidebar_index = -1;

				// If we have input values that are checked
				if ( typeof this !== 'undefined' && this.$( 'input:checked' ).length ) {
					// Fetch the new setting value
					setting = self.setting = this.$( 'input:checked' ).formParams( true, 'conductor[content_layouts]' ); // Parse settings

					// If we have an event and a target
					if ( event && $current_target ) {
						// Store the content layout selection
						selected_content_layout_value = $current_target.val();

						// Loop through default widgets
						// TODO: Optimize this logic
						_.each( conductor.customizer.sidebars.default_widgets, function( default_widgets, content_layout ) {
							// Set the refresh flag if it isn't set yet to prevent Previewer refreshes
							if ( ! api.Conductor.Customizer.disallowPreviewerRefresh() ) {
								api.Conductor.Customizer.setPreviewerRefreshFlag( true );
							}

							// Loop through individual default widgets registered to this content layout
							_.each( default_widgets, function( default_widget ) {
								// Determine if the selected content layout value matches the default widget value
								if ( self.self.isDefaultWidgetContentLayoutMatch( selected_content_layout_value, default_widget ) ) {
									// Set the flag
									if ( ! content_layout_has_default_widgets ) {
										content_layout_has_default_widgets = true;
									}

									// Create a Conductor Sidebar ID
									conductor_sidebar_id = sidebar_prefix + default_widget.sidebar_id + sidebar_suffix;

									// Store this value in cache (if it is a valid sidebar for this content layout and it doesn't exist already)
									if ( conductor.customizer.sidebars.content_layouts[current_content_layout_id].indexOf( conductor_sidebar_id ) !== -1 && content_layout_sidebars.indexOf( conductor_sidebar_id ) === -1 ) {
										// Store a reference to this sidebar ID
										content_layout_sidebars.push( conductor_sidebar_id );
									}
								}
							} );

							// Loop through individual default widgets registered to this content layout (using _.find() to stop execution once we've found a match)
							_.find( default_widgets, function( default_widget ) {
								// Determine if the selected content layout value matches the default widget value
								if ( self.self.isDefaultWidgetContentLayoutMatch( selected_content_layout_value, default_widget ) ) {

									// Register the default widgets
									sidebars_added = self.self.registerConductorDefaultWidgets( default_widgets, conductor.customizer.sidebars.content_layouts[current_content_layout_id] );

									return true;
								}

								return false;
							} );
						} );

						// Loop through content layout sidebars
						_.each( content_layout_sidebars, function( sidebar_id ) {
							// Remove the sidebars from sidebars_no_widgets now that widgets have been added
							if ( ( sidebar_index = self.self.sidebars_no_widgets.indexOf( sidebar_id ) ) !== -1 ) {
								self.self.sidebars_no_widgets.splice( sidebar_index, 1 );
							}
						} );

						// Reset the refresh flag to allow Previewer refreshes
						if ( ! sidebars_added || ! content_layout_has_default_widgets && api.Conductor.Customizer.disallowPreviewerRefresh() ) {
							api.Conductor.Customizer.setPreviewerRefreshFlag( false );
						}
					}

					/*
					 * If we do not have any default widgets, we can set the setting value now. However,
					 * if there are default widgets, we have to wait until the AJAX requests are made to
					 * update the widget forms. In this case, we'll set the setting value after all of those
					 * AJAX requests have finished. @see this.self.addDefaultWidgetToSidebar()
					 */

					// If we don't have default widgets, we can set the setting value now
					if ( ! sidebars_added || ! content_layout_has_default_widgets ) {
						// This triggers the "change" event on the Customizer API
						this.self.set( setting );
					}
					// Otherwise we'll trigger the loading-initiated event on the Previewer for end-user feedback
					else {
						api.Conductor.Previewer.send( 'loading-initiated' );
					}
				}
				// Otherwise we have no content layout data yet
				else {
					// TODO: Remove Conductor Sidebar default widgets before change state is triggered?

					// This triggers the "change" event on the Customizer API
					this.self.set( {} ); // No data
				}

				// Adjust the saved state on removal
				if ( $current_target && $current_target.hasClass( 'conductor-remove-content-layout' ) ) {
					api_saved_state.set( false );
					api.state.trigger( 'change', api_saved_state ); // trigger the saved flag
				}

				// Show the loading spinner (only if the setting has changed)
				if ( setting && ! _.isEqual( setting, old_setting_val ) ) {
					this.self.views.controls_view.conductor_content_layout_controls_view.$( '.conductor-spinner' ).css( 'display', 'inline-block' );
				}
			},
			// Determine which Conductor Content Layout to display (if any) or show the help section
			refreshContentLayouts: function( content_layout ) {
				var $content_layouts = this.$( '.conductor-content-layout-wrap' ),
					$content_layouts_help = this.$el.parent().find( '.conductor-content-layouts-help' );

				// Show the correct content layout if we have one
				if ( content_layout ) {
					// Loop through all Conductor content layouts
					$content_layouts.each( function() {
						var $this = $( this ), conductor_rendered = $this.data( 'conductor:rendered' );

						// If this layout has the correct CSS class and is not already rendered
						if ( $this.hasClass( 'conductor-content-layout-' + content_layout.field_type + '-' + content_layout.field_id ) && ! conductor_rendered ) {
							$this.addClass( 'open' ).stop().slideDown( function() {
								$this.css( 'height', 'auto' ).data( 'conductor:rendered', true ); // so that the .accordion-section-content won't overflow
							} );
						}
						// Otherwise this layout does not have the correct CSS class and is rendered (we need to slide it up)
						else if ( ! $this.hasClass( 'conductor-content-layout-' + content_layout.field_type + '-' + content_layout.field_id ) || ! conductor_rendered ) {
							$this.stop().slideUp( function() {
								$this.removeClass( 'open' ).data( 'conductor:rendered', false )
							} );
						}
					} );

					// Check if a layout is currently being shown and if not show the help
					if ( ! $content_layouts.filter( 'open' ) ) {
						$content_layouts_help.stop().slideDown(); // Show help
					}
					// Otherwise hide the help
					else {
						$content_layouts_help.stop().slideUp(); // Hide help
					}
				}
				// No content layout, show the help section
				else {
					$content_layouts.stop().slideUp().data( 'conductor:rendered', false );
					$content_layouts_help.stop().slideDown();
				}
			}
		} ),
		// The Conductor Content Layout Controls view
		controls_view: Backbone.View.extend( {
			self: false,
			el: '.conductor-content-layouts-controls',
			collection: conductor['content-layouts'].views['content-layout'].prototype.collection, // TODO: Store this in view.options
			model: false,
			conductor_content_layout_controls_view: {},
			initialize: function( options ) {
				var conductor_content_layout_controls_view = conductor['content-layouts'].ready.views['content-layouts'].controls;

				this.conductor_content_layout_controls_view = conductor_content_layout_controls_view;

				// Set self property to the Control if passed
				this.self = options.self || false;

				// Redirect the previewer when a new content layout has been added
				this.listenTo( this.collection, 'add', this.redirectPreviewer );

				/*
				 * Listen to the Conductor Content Layout Controls view for when the show message flag event is triggered.
				 * We're determining whether or not the message should be shown in the Customizer.
				 */
				this.listenTo( conductor_content_layout_controls_view, 'show-message-flag', this.maybeSetShowMessageFlag );
			},
			// Event hash to redirect the Previewer when a new content layout has been added
			events : {
				'click .conductor-content-layouts-add' : function( event ) {
					this.maybeAddContentLayout( event );
					this.redirectPreviewer( event );
				}
			},
			// Maybe add a content layout (if it doesn't exist)
			maybeAddContentLayout: function( event ) {
				// Prevent default
				event.preventDefault();

				// New content layout and no value in content types dropdown (the current page in the Previewer is used for the model settings)
				if ( ! this.$( '.conductor-content-types' ).val() && this.self.conductor_content_layout.hasOwnProperty( 'new_content_layout' ) ) {
					// Determine if this content layout exists
					if ( ! this.conductor_content_layout_controls_view.$( '.conductor-content-layout-' + this.self.conductor_content_layout.field_type + '-' + this.self.conductor_content_layout.field_id ).length ) {
						// Add the new layout to the collection which will fire the render method in the controller view
						this.conductor_content_layout_controls_view.addContentLayout( this.self.conductor_content_layout );

						// Reset current model
						this.conductor_content_layout_controls_view.current_model = {};

						// Show new content layout message
						this.conductor_content_layout_controls_view.showMessage( conductor_content_layouts_customizer.l10n.content_layout_created, 'content-layout-created' );

						// Show the loading spinner
						this.conductor_content_layout_controls_view.$( '.conductor-spinner' ).css( 'display', 'inline-block' );
					}
				}
				// New content layout and content type is selected
				else if ( this.$( '.conductor-content-types' ).val() ) {
					var $content_types = this.conductor_content_layout_controls_view.$( '.conductor-content-types-select' ), $content_types_selected = $content_types.find( 'option:selected' ),
						content_type = {
							id: $content_types.val(),
							name: $content_types_selected.text(),
							type: $content_types_selected.attr( 'data-content-type' ),
							permalink: $content_types_selected.attr( 'data-permalink' )
						},
						model = {};

					// Validate data
					if ( ! content_type.id.length || ! content_type.name.length || ! content_type.type.length ) {
						this.conductor_content_layout_controls_view.showMessage( conductor_content_layouts_customizer.l10n.no_content_type, 'no-content-type', 'error' );

						return false;
					}

					// Check to make sure this choice doesn't already exist
					if ( ! this.conductor_content_layout_controls_view.controller_view.$( '.conductor-content-layout-' + content_type.type + '-' + content_type.id ).length ) {
						model = {
							field_id: content_type.id,
							field_name: content_type.name,
							field_type: content_type.type,
							permalink: content_type.permalink
						};

						// Add the new layout to the collection which will fire the render method in the controller view
						this.conductor_content_layout_controls_view.addContentLayout( model, content_type );

						// Reset current model
						this.conductor_content_layout_controls_view.current_model = {};

						// Show new content layout message
						this.conductor_content_layout_controls_view.showMessage( conductor_content_layouts_customizer.l10n.content_layout_created, 'content-layout-created' );

						// Show the loading spinner
						this.conductor_content_layout_controls_view.$( '.conductor-spinner' ).css( 'display', 'inline-block' );
					}
					// Content layout exists, need to re-direct Previewer
					else {
						$content_types_selected = this.$( '.conductor-content-types-select option:selected' );

						var permalink = $content_types_selected.attr( 'data-permalink' );

						// If the current Previewer URL doesn't match the permalink for this content layout
						if ( api.Conductor.Previewer.previewUrl() !== permalink ) {
							// Re-direct the Previewer
							this.redirectPreviewerURL( permalink );

							// Show the loading spinner
							this.conductor_content_layout_controls_view.$( '.conductor-spinner' ).css( 'display', 'inline-block' );
						}
					}
				}
				// Edge Case: Conductor does not have all of the correct content layout information from the Previewer
				else if ( ! this.$( '.conductor-content-types' ).val() && ! this.self.conductor_content_layout.hasOwnProperty( 'field_id' ) ) {
					// TODO: Provide a warning/error message to the user visually
					console.log( 'Conductor did not receive event data. Please exit the Customizer and try again.' );
				}
			},
			// Re-direct the Previewer to the new model's permalink
			redirectPreviewer: function( model ) {
				if ( model && model.hasOwnProperty( 'attributes' ) ) {
					api.Conductor.Previewer.previewUrl( model.get( 'permalink' ) );
				}
			},
			// Re-direct the Previewer to a specific URL
			redirectPreviewerURL: function( url ) {
				api.Conductor.Previewer.previewUrl( url );
			},
			// This function determines whether or not the show_message_flag on the main Conductor Content Layout Controls view should be set to false based on context of message being shown
			maybeSetShowMessageFlag: function ( view, content, context, type, duration ) {
				var $content_types = this.$( '.conductor-content-types-select' ), $content_types_selected = $content_types.find( 'option:selected' ),
					permalink = $content_types_selected.attr( 'data-permalink' ),
					self = this;

				// Make sure the Previewer is not already at this URL
				if ( api.Conductor.Previewer.previewUrl() !== permalink ) {
					switch ( context ) {
						// Content Layout Exists
						case 'content-layout-exists':
							view.setShowMessageFlag( false ); // Set the flag to false

							this.redirectPreviewerURL( permalink );
						break;
						// No Content Type Selected
						case 'no-content-type':
							view.setShowMessageFlag( false ); // Set the flag to false
						break;
					}
				}
				// User is most likely adding a new content layout that will refresh the Customizer
				else {
					switch( context ) {
						// No Content Type Selected
						case 'no-content-type':
							view.setShowMessageFlag( false ); // Set the flag to false
						break;
					}
				}
			}
		} ),
		// This function fires when the user has changed any aspect of Conductor content layouts
		set: function( setting ) {
			// Set the value to ensure the controller knows
			this.setting.set( setting );
		},
		// This function returns the current setting value for Conductor content layouts
		get: function( setting ) {
			return this.setting();
		}
	} );

	// Conductor Widget Control
	api.Widgets.ConductorWidgetControl = api.Widgets.WidgetControl.extend( {
		controls: [], // Reference to all api.Widgets.ConductorWidgetControl widget controls
		$inputs: [], // Reference to all api.Widgets.ConductorWidgetControl widget control input elements
		$widgetRoots: [], // Reference to all api.Widgets.ConductorWidgetControl widget control widget root elements
		$widgetContents: [], // Reference to all api.Widgets.ConductorWidgetControl widget control widget content elements
		// Initialize
		initialize: function ( id, options ) {
			var self = this,
				$widgetRoot,
				$widgetContent;

			// Call WidgetControl initialize() early
			api.Widgets.WidgetControl.prototype.initialize.call( self, id, options );

			// Add this control to the collection
			self.controls.push( self );

			// Add widget root and content elements to the collection
			$widgetRoot = self.container.find( '.widget:first' );
			self.$widgetRoots.push( $widgetRoot );
			$widgetContent = $widgetRoot.find( '.widget-content:first' );
			self.$widgetContents.push( $widgetContent );

			// Add this controls input elements to the collection
			self.$inputs.push( self._getInputs( $widgetContent ) );
		},
		/*
		 * This function updates Conductor default widgets. It uses logic similar to core's
		 * implementation for updating a widget, however we're only making 1 AJAX call instead
		 * of individual calls for each widget.
		 *
		 * License: GPLv2 or later
		 * Copyright: WordPress, http://wordpress.org/
		 *
		 * @see api.Widgets.SidebarControl.updateWidget()
		 * @see https://github.com/WordPress/WordPress/blob/4.2-branch/wp-admin/js/customize-widgets.js#L999-L1183
		 *
		 * We've used core's implementation as a base and modified it to suit our needs.
		 */
		// Update Widget (_.debounce()d 250ms)
		updateWidget: _.debounce( function( args ) {
			var self = this, completeCallback, $widgetContent,
				updateNumber, params = { widgets: [] }, $inputs, processing, jqxhr, isChanged, customized = {};

			args = $.extend( {
				instance: null,
				complete: null,
				ignoreActiveElement: false
			}, args );

			completeCallback = args.complete;

			// API processing state
			processing = api.state( 'processing' );
			processing( processing() + 1 );

			// Loop through widgets
			_.each( self.controls, function ( control, key ) {
				// Add the widget data and store the index
				var widget_index = params['widgets'].push( {
					'sanitized_widget_setting': JSON.stringify( control.setting() )
				} );
				widget_index--; // Make sure this is the reference to the array key and not the length

				$widgetContent = self.$widgetContents[key];

				// Loop through input siblings
				$widgetContent.find( '~ :input' ).each( function() {
					var $this = $( this );
					params['widgets'][widget_index][$this.attr( 'name' )] = $this.val();
				} );

				// Remove a previous error message
				$widgetContent.find( '.widget-error' ).remove();

				control.container.addClass( 'widget-form-loading' );
				control.container.addClass( 'previewer-loading' );

				if ( ! control.liveUpdateMode ) {
					control.container.addClass( 'widget-form-disabled' );
				}
			} );

			// We also have to send the customized data (but just the keys for widgets)
			api.each( function ( value, key ) {
				// Widgets only
				if ( value._dirty && key.indexOf( 'widget_' ) === 0 ) {
					customized[key] = {};
				}
			} );
			params['customized'] = JSON.stringify( customized );

			$.extend( params, {
				action: 'conductor-update-widget',
				wp_customize: 'on',
				nonce: api.Widgets.data.nonce,
				theme: api.settings.theme.stylesheet
			} );

			if ( this._previousUpdateRequest ) {
				this._previousUpdateRequest.abort();
			}
			jqxhr = $.post( wp.ajax.settings.url, params );
			this._previousUpdateRequest = jqxhr;

			jqxhr.done( function( r ) {
				var message, sanitizedForm,	$sanitizedInputs, hasSameInputsInResponse,
					isLiveUpdateAborted = false;

				// Check if the user is logged out.
				if ( '0' === r ) {
					api.previewer.preview.iframe.hide();
					api.previewer.login().done( function() {
						self.updateWidget( args );
						api.previewer.preview.iframe.show();
					} );
					return;
				}

				// Check for cheaters.
				if ( '-1' === r ) {
					api.previewer.cheatin();
					return;
				}

				if ( r.success ) {
					// Loop through each widget in data
					_.each( r.data, function( widget, key ) {
						sanitizedForm = $( '<div>' + widget.form + '</div>' );
						$sanitizedInputs = self._getInputs( sanitizedForm );
						hasSameInputsInResponse = self._getInputsSignature( self.$inputs[key] ) === self._getInputsSignature( $sanitizedInputs );

						// Restore live update mode if sanitized fields are now aligned with the existing fields
						if ( hasSameInputsInResponse && ! self.controls[key].liveUpdateMode ) {
							self.controls[key].liveUpdateMode = true;
							self.controls[key].container.removeClass( 'widget-form-disabled' );
							self.controls[key].container.find( 'input[name="savewidget"]' ).hide();
						}

						self.$widgetContents[key].html( widget.form );

						self.controls[key].container.removeClass( 'widget-form-disabled' );

						$( document ).trigger( 'widget-updated', [ self.$widgetRoots[key] ] );

						/**
						 * If the old instance is identical to the new one, there is nothing new
						 * needing to be rendered, and so we can preempt the event for the
						 * preview finishing loading.
						 */
						isChanged = ! isLiveUpdateAborted && ! _( self.controls[key].setting() ).isEqual( widget.instance );
						if ( isChanged ) {
							self.controls[key].isWidgetUpdating = true; // suppress triggering another updateWidget
							self.controls[key].setting( widget.instance );
							self.controls[key].isWidgetUpdating = false;
						} else {
							// no change was made, so stop the spinner now instead of when the preview would updates
							self.controls[key].container.removeClass( 'previewer-loading' );
						}

						if ( completeCallback ) {
							completeCallback.call( self, null, { noChange: ! isChanged, ajaxFinished: true } );
						}
					} );
				}
				else {
					// General error message
					message = conductor_content_layouts_customizer.l10n.error;

					if ( r.data && r.data.message ) {
						message = r.data.message;
					}

					if ( completeCallback ) {
						completeCallback.call( self, message );
					} else {
						// Loop through all widget content elements in the collection
						_.each( self.$widgetContents, function( $widgetContent ) {
							$widgetContent.prepend( '<p class="widget-error"><strong>' + message + '</strong></p>' );
						} );
					}
				}
			} );

			jqxhr.fail( function( jqXHR, textStatus ) {
				if ( completeCallback ) {
					completeCallback.call( self, textStatus );
				}
			} );

			jqxhr.always( function() {
				// Loop through widgets
				_.each( self.controls, function ( control ) {
					// Remove widget loading CSS class
					control.container.removeClass( 'widget-form-loading' );

					// Adjust the updateWidget callback back to the normal widget control updateWidget method
					control.updateWidget = api.Widgets.WidgetControl.prototype.updateWidget;
				} );

				// Loop through all inputs in the collection
				_.each( self.$inputs, function( $inputs ) {
					$inputs.each( function() {
						$( this ).removeData( 'state' + updateNumber );
					} );
				} );

				processing( processing() - 1 );
			} );
		}, 250 )
	} );


	// Add custom Conductor Content Layouts Controller to control constructor (extending what is already there)
	$.extend( api.controlConstructor, {
		conductor_content_layouts: api.conductorContentLayoutsControl,
		conductor_widget_form: api.Widgets.ConductorWidgetControl
	} );

	/**
	 * Capture the instance of the Previewer since it is private
	 */
	OldPreviewer = api.Previewer;
	api.Previewer = OldPreviewer.extend( {
		initialize: function( params, options ) {
			// Store a reference to the Previewer
			api.Conductor.Previewer = this;

			// Initialize the old Previewer
			OldPreviewer.prototype.initialize.call( this, params, options );
		},
		// Refresh
		refresh: function() {
			// Refresh only if the Conductor Previewer refresh flag isn't set
			if ( ! api.Conductor.Customizer.disallowPreviewerRefresh() ) {
				// Refresh the old previewer
				OldPreviewer.prototype.refresh.call( this );
			}
		}
	} );

	/**
	 * Capture the instance of the Preview since it is private
	 */
	OldPreviewFrame = api.PreviewFrame;
	api.PreviewFrame = OldPreviewFrame.extend( {
		initialize: function( params, options ) {
			api.Conductor.PreviewFrame = this;
			OldPreviewFrame.prototype.initialize.call( this, params, options );
		}
	} );

	// Also store the custom Conductor Content Layouts Controller locally in "conductor"
	conductor.customizer.controls['content-layouts'] = api.conductorContentLayoutsControl;
} )( wp, jQuery, conductor_content_layouts_customizer );