/**
 * Conductor Widget
 */
// TODO: Future: Comment all functions properly
// TODO: Future: Trigger Conductor specific events for all actions within a widget
// TODO: Future: Minify

var conductor = conductor || {}, wp = wp || {};

( function ( wp, $ ) {
	"use strict";

	var api = ( conductor.hasOwnProperty( 'is_customizer' ) && conductor.is_customizer === '1' ) ? wp.customize : {}, // Customizer API
		conductor_widget_displays = conductor.widgets.conductor.displays || {};

	// Defaults
	if ( conductor.hasOwnProperty( 'widgets' ) && ! conductor.widgets.hasOwnProperty( 'conductor' ) ) {
		$.extend( conductor.widgets, {
			conductor: {}
		} );
	}
	else {
		conductor.widgets = conductor.widgets || {
			conductor: {}
		};
	}

	/**
	 * This function determines which Conductor widget options to display.
	 */
	conductor.widgets.conductor.conductorRenderWidgetOptions = function ( selected, widget_parent ) {
		// Feature one
		if ( selected.val() === '' ) {
			// Hide the feature many elements
			widget_parent.find( '.conductor-feature-many:not(.conductor-content-type-field)' ).hide();

			// Start a new thread (delay 1ms)
			setTimeout( function( ) {
				// Show the feature one elements
				widget_parent.find( '.conductor-feature-one:not(.conductor-content-type-field)' ).show();
			}, 1);
		}
		// Feature many
		else if ( selected.val() === 'true' ) {
			// Hide the feature one elements
			widget_parent.find( '.conductor-feature-one:not(.conductor-content-type-field)' ).hide();

			// Start a new thread (delay 1ms)
			setTimeout( function( ) {
				// Show the feature many elements
				widget_parent.find( '.conductor-feature-many:not(.conductor-content-type-field)' ).show();
			}, 1);
		}

		// Remove "hidden" class from the feature many and feature one elements
		widget_parent.find( '.conductor-feature-many, .conductor-feature-one' ).removeClass( 'conductor-hidden' );
	};

	/**
	 * Accordion functionality taken from WP Core and modified for Conductor (/wp-admin/js/accordion.js).
	 */
	conductor.widgets.conductor.accordionSwitch = function ( el ) {
		var section = el.closest( '.conductor-accordion-section' ),
			content = section.find( '.conductor-accordion-section-content' ),
			widget = el.closest( '.widget' ),
			accordion_sections = {};

		if ( section.hasClass( 'cannot-expand' ) )
			return;

		if ( section.hasClass( 'open' ) ) {
			section.toggleClass( 'open' );
			content.slideToggle( 150, function() {
				accordion_sections = conductor.widgets.conductor.findOpenContentSections( widget, accordion_sections );

				widget.data( 'conductor-accordion-sections', accordion_sections );
			} );
		}
		else {
			content.slideToggle( 150, function() {
				accordion_sections = conductor.widgets.conductor.findOpenContentSections( widget, accordion_sections );

				widget.data( 'conductor-accordion-sections', accordion_sections );
			}  );
			section.toggleClass( 'open' );
		}
	};

	/**
	 * This function finds content sections within a widget that are currently open.
	 */
	conductor.widgets.conductor.findOpenContentSections = function( widget, accordion_sections ) {
		widget.find( '.conductor-section .conductor-accordion-section-content:visible' ).each( function() {
			var conductor_section = $( this ).parents( '.conductor-section' ).attr( 'data-conductor-section' );
			accordion_sections[conductor_section] = '.' + conductor_section;
		} );

		return accordion_sections;
	};

	/**
	 * This function initializes the accordion content areas.
	 */
	conductor.widgets.conductor.accordionInit = function( $widgets ) {
		$widgets.each( function() {
			var $this = $( this ), widget_data = $this.data( 'conductor-accordion-sections' );

			// Loop through object above
			if ( widget_data ) {
				$this.find( '.conductor-section .conductor-accordion-section-content' ).slideUp( 0 ).parents( '.conductor-section' ).removeClass( 'open' );

				$.each( widget_data, function( i, val ) {
					$this.find( val + ' .conductor-accordion-section-content' ).slideDown( 0 ).parents( '.conductor-section' ).addClass( 'open' );
				} );
			}
		} );

		// Hide the content in the sections that aren't ".open" (usually first page load)
		$( '.conductor-section:not(.open)' ).find( '.conductor-accordion-section-content' ).slideUp( 0 );
	};

	/**
	 * This function sets the widget's content type based on user selection.
	 */
	conductor.widgets.conductor.setContentType = function( $this, type ) {
		var $widget_parent = $this.parents( '.widget' ); // Get widget instance

		// Trigger the change event because this is a hidden input element
		$this.parents( '.conductor-accordion-section-content' ).find( '.conductor-content-type' ).val( type ).trigger( 'change' );

		// Trigger an event on the widget parent element
		$widget_parent.trigger( 'conductor-widget:set-content-type', [
			type,
			$this
		] );
	};

	/**
	 * This function determines if an element should be displayed based upon the current query type and widget size (display).
	 */
	conductor.widgets.conductor.renderElement = function ( $el, $widget_parent, $widget_size, data ) {
		// Bail if we don't have any data
		if ( ! data || ! data.hasOwnProperty( 'display' ) || ! data.display.hasOwnProperty( 'config' ) || ! data.display.config.hasOwnProperty( 'customize' ) ) {
			// Hide the element before we bail
			$el.addClass( 'conductor-hidden' );

			return;
		}

		var data_attribute_support = ( data.display.config.customize[data.display.supports] ) ? _.keys( data.display.config.customize[data.display.supports] ) : [],
			data_attribute_support_flag = false;

		// Data attribute customize support
		if ( $widget_size.data( 'conductor-customize-' + data.display.supports ) ) {
			// Loop through all secondary data attribute support properties
			_.find( data_attribute_support, function ( property ) {
				if ( $widget_size.data( 'conductor-customize-' + property + '-' + data.display.supports ) ) {
					// Set the data attribute support flag
					data_attribute_support_flag = true;

					// Break from loop
					return true;
				}

				return false;
			} );

			// If this display has config properties for single and many queries
			if ( data_attribute_support_flag ) {
				// Switch based on feature type
				switch ( data.feature_type ) {
					// Single
					case '':
						// If this display supports single querying column customization
						if ( $widget_size.data( 'conductor-customize-single-' + data.display.supports ) ) {
							// Show the column range input
							$el.removeClass( 'conductor-hidden' );
						}
						// If this display doesn't support single querying column customization
						else if ( $widget_size.data( 'conductor-customize-single-' + data.display.supports ) === false ) {
							// Hide the column range input
							$el.addClass( 'conductor-hidden' );
						}
					break;

					// Many
					case 'true':
						// If this display supports many querying column customization
						if ( $widget_size.data( 'conductor-customize-many-' + data.display.supports ) ) {
							// Show the column range input
							$el.removeClass( 'conductor-hidden' );
						}
						// If this display doesn't support many querying column customization
						else if ( $widget_size.data( 'conductor-customize-many' + data.display.supports ) === false ) {
							// Hide the column range input
							$el.addClass( 'conductor-hidden' );
						}
					break;
				}
			}
			// Otherwise just show the column range input
			else {
				$el.removeClass( 'conductor-hidden' );
			}
		}
		else {
			$el.addClass( 'conductor-hidden' );
		}

		// Trigger an event on the widget parent element
		$widget_parent.trigger( 'conductor-widget:render-element', [
			$widget_parent,
			$widget_size,
			data
		] );
	};

	/**
	 * Document Ready
	 */
	$( function() {
		var $document = $( document ),
			$sidebars = $( 'div.widgets-sortables' ),
			$widgets = $( '.widget', '#widgets-right' );

		// On content piece change
		$document.on( 'change', '.conductor-feature-content-pieces .conductor-select-feature-type', function() {
			var $selected = $( ':selected', this ), // Get selected choice
				$widget_parent = $selected.parents( '.widget' ); // Get widget instance

			// Render the Conductor Widget options
			conductor.widgets.conductor.conductorRenderWidgetOptions( $selected, $widget_parent );

			// Trigger change event on content type
			$widget_parent.find( '.conductor-content-type' ).trigger( 'change' );
		} );

		// On content type change
		$document.on( 'change', '.conductor-content-type', function( event ) {
			var $this = $( this ),
				value = $this.val(),
				$widget_parent = $this.parents( '.widget' ), // Get widget instance
				$feature_type = $widget_parent.find( '.conductor-select-feature-type' );

			// If a content type is selected
			if ( value ) {
				var feature_type_class = '',
					content_type_class = 'conductor-content-type-' + value,
					feature_type_val = $feature_type.val();

				// Feature one
				if ( feature_type_val === '' ) {
					feature_type_class = 'conductor-feature-one';
				}
				// Feature many
				else if ( feature_type_val === 'true' ) {
					feature_type_class = 'conductor-feature-many';
				}

				// Show correct fields
				// TODO: use filter() or other functionality instead (optimize)
				$widget_parent.find( '.conductor-content-type-field' ).each( function() {
					var $this = $( this );

					// Show correct elements
					if ( $this.hasClass( feature_type_class ) && $this.hasClass( content_type_class ) ) {
						// Show this element and remove the "hidden" CSS class
						$this.show().removeClass( 'conductor-hidden' );
					}
					else {
						// Hide this element and remove the "hidden" CSS class
						$this.hide().removeClass( 'conductor-hidden' );
					}
				} );

				// If featuring one, reset the post_id value
				if ( feature_type_class === 'conductor-feature-one' ) {
					$widget_parent.find( '.conductor-content-type-' + value + ' select' ).trigger( 'change' );
				}
			}
		} );

		// Feature one select
		$document.on( 'change', '.featured-one-select', function( event ) {
			var $this = $( this ),
			$widget_parent = $this.parents( '.widget' ); // Get widget instance

			// Create our own "debounce" function to ensure the correct data is passed in some browsers (FireFox/Safari)
			var updatePostID = _.debounce( function() {
				$widget_parent.find( '.conductor-post-id' ).val( $this.val() ).trigger( 'change' );
			}, 250 );

			updatePostID(); // Call the "debounced" function
		} );

		// Accordion (Expand/Collapse on click)
		$document.on( 'click', '.conductor-section .conductor-accordion-section-title', function( event ) {
			// Prevent default
			event.preventDefault();

			conductor.widgets.conductor.accordionSwitch( $( this ) );
		} );

		// Accordion Init
		conductor.widgets.conductor.accordionInit( $widgets );

		// Conductor numbers, only allow numerical characters into input boxes
		$document.on( 'keyup', '.conductor-number', function( event ) {
			var $this = $( this ), numeric_value = $this.val().replace( /[^0-9]/g, '' );

			if ( $this.val() != numeric_value ) {
				$this.val( numeric_value );
			}
		} );

		// On widget update (WordPress 3.9 & up)
		$document.on( 'widget-updated', function () {
			$widgets = $( '.widget', '#widgets-right' ); // Update $widgets

			conductor.widgets.conductor.accordionInit( $widgets );
		} );

		// Set content type
		$document.on( 'change', '.conductor-select-feature-type, .conductor-select-content-type', function( event ) {
			var $this = $( this ),
				$selected = $( ':selected', this ),
				value = $this.val(),
				$widget_parent = $this.parents( '.widget' ), // Get widget instance
				$widget_size = $widget_parent.find( '.conductor-widget-size-value:checked' ), // Current widget size
				widget_size = $widget_size.val(),
				$conductor_columns = $widget_parent.find( '.conductor-columns' ), // Columns
				display_config = ( widget_size && conductor_widget_displays && conductor_widget_displays[widget_size] && _.isObject( conductor_widget_displays[widget_size] ) ) ? conductor_widget_displays[widget_size] : false;

			// Feature Many/Feature One dropdown
			if ( $this.hasClass( 'conductor-select-feature-type' ) ) {
				$selected = $( ':selected', $this.parents( '.conductor-feature-content-pieces' ).find( '.conductor-select-content-type' ) );

				// Flexbox columns
				conductor.widgets.conductor.renderElement( $conductor_columns, $widget_parent, $widget_size, {
					display: {
						supports: 'columns',
						config: ( display_config ) ? display_config : {}
					},
					feature_type: value
				} );
			}

			// Start a new thread; delay 1ms
			setTimeout( function() {
				// Set the content type
				conductor.widgets.conductor.setContentType( $this, $selected.attr( 'data-type' ) );
			}, 1 );
		} );


		/*
		 * Flexbox
		 */

		// On flexbox column change (jQuery "input" event)
		$document.on( 'input', '.conductor-flexbox-columns-range', function() {
			var $this = $( this );

			// Adjust the value
			$this.next( '.conductor-flexbox-columns-value' ).html( $this.val() );
		} );

		// On widget size change
		$document.on( 'change', '.conductor-widget-size-value', function( event ) {
			var $this = $( this ),
				value = $this.val(),
				display_config = ( value && conductor_widget_displays && conductor_widget_displays[value] && _.isObject( conductor_widget_displays[value] ) ) ? conductor_widget_displays[value]: false,
				$widget_parent = $this.parents( '.widget' ), // Get widget instance
				conductor_output = $widget_parent.data( 'conductor-output' ), // Output elements
				feature_type = $widget_parent.find( '.conductor-select-feature-type' ).val(), // Feature many or single
				$conductor_columns = $widget_parent.find( '.conductor-columns' ), // Columns
				$conductor_columns_range = $conductor_columns.find( '.conductor-flexbox-columns-range'), // Columns range
				$featured_image_size = $widget_parent.find( '.conductor-post-thumbnails-size .conductor-select' ); // Featured Image Size

			// Flexbox columns
			conductor.widgets.conductor.renderElement( $conductor_columns, $widget_parent, $this, {
				display: {
					supports: 'columns',
					config: ( display_config ) ? display_config : {}
				},
				feature_type: feature_type
			} );

			// If we have a value and the value has a display configuration
			if ( display_config ) {
				// If there are defaults
				if ( display_config.defaults ) {
					// Flexbox columns (if visible)
					// TODO: $conductor_columns.is( ':visible' ) ?
					if ( display_config.defaults.hasOwnProperty( 'columns' )  ) {
						// Default columns value (also trigger the jQuery 'input' event)
						$conductor_columns_range.val( parseInt( display_config.defaults.columns, 10 ) ).trigger( 'input' );
					}

					// Output elements
					if ( display_config.defaults.hasOwnProperty( 'output' ) ) {
						// Loop through default output
						_.each( display_config.defaults.output, function ( output_element_properties, output_element ) {
							var $output_element = conductor_output.$output_list_items.filter( '[data-id="' + output_element + '"]' ),
								model = conductor_output.collection.findWhere( { id: output_element } );

							// If we have a model associated with this output element
							if ( model ) {
								// TODO: Support other properties other than visible and link

								// Toggle link if necessary
								if ( output_element_properties.hasOwnProperty( 'link' ) ) {
									// If the element should be linked and it isn't or vise-versa
									if ( ( output_element_properties.link && ! model.get( 'link' ) ) || ( ! output_element_properties.link && model.get( 'link' ) ) ) {
										// Trigger a click on the visibility icon
										$output_element.find( '.conductor-widget-link' ).click();
									}
								}

								// Show/hide this output element if necessary
								if ( output_element_properties.hasOwnProperty( 'visible' ) ) {
									// If the element should be visible and it isn't or vise-versa
									if ( ( output_element_properties.visible && ! model.get( 'visible' ) ) || ( ! output_element_properties.visible && model.get( 'visible' ) ) ) {
										// Trigger a click on the visibility icon
										$output_element.find( '.conductor-widget-visibility' ).click();
									}
								}
							}
						} );
					}

					// Featured image size (post thumbnails size)
					if ( display_config.defaults.hasOwnProperty( 'post_thumbnails_size' ) ) {
						var $featured_image_size_option = $featured_image_size.find( '[value="' + display_config.defaults.post_thumbnails_size + '"]' ); // Featured Image Size (thumbnail option)

						// Default to the featured image size (post thumbnails size)
						if ( $featured_image_size_option.length ) {
							$featured_image_size.val( $featured_image_size_option.val() );
						}
					}
				}
			}

			// Hide the widget size fields and remove the "hidden" CSS class
			$widget_parent.find( '.conductor-widget-size-field' ).hide().removeClass( 'conductor-hidden' );

			// Start a new thread; delay 1ms
			setTimeout( function() {
				// Show the widget size fields for this widget size
				$widget_parent.find( '.conductor-widget-size-field.conductor-widget-size-' + value ).show();

				// Start a new thread; delay 1ms
				setTimeout( function() {
					// Trigger change event on content type select element (this will in turn call conductor.widgets.conductor.setContentType())
					$widget_parent.find( '.conductor-select-content-type' ).trigger( 'change' );
				}, 1 );
			}, 1 );
		} );


		/*
		 * Output order - allow adjustment of Conductor Widget element output order
		 */

		// Initialize Backbone Views on Conductor Widgets (on initial page load)
		$widgets.filter( '[id*="conductor-widget"]' ).each( function( i, el ) {
			// Create a new output view and store it in widget data
			$( el ).data( 'conductor-output', new conductor.widgets.conductor.views.output( {
				el: $( el ).find( '.conductor-output' ), // Attach this view to the widgets output list
				collection: new conductor.widgets.conductor.collections.output() // New collection
			} ) );
		} );

		// Need to listen to the document for widget-added
		$document.on( 'widget-added', function( event, $widget ) {
			// Conductor Widgets
			if ( $widget.attr( 'id' ).indexOf( 'conductor-widget' ) ) {
				// Store the output view in widget data
				$widget.data( 'conductor-output', new conductor.widgets.conductor.views.output( {
					el: $widget.find( '.conductor-output' ), // Attach this view to the widgets output list
					collection: new conductor.widgets.conductor.collections.output() // New collection
				} ) );

				// Customizer (WordPress 4.4+; due to the deferring widget content until the widget control is expanded)
				if ( wp.hasOwnProperty( 'customize' ) && parseFloat( conductor.widgets.conductor.wp_version ) >= 4.4 ) {
					// Accordion Init
					conductor.widgets.conductor.accordionInit( $widget );
				}
			}
		} );

		// Need to listen to the document for widget-updated
		$document.on( 'widget-updated', function( event, $widget ) {
			var conductor_output_view = $widget.data( 'conductor-output' ),
				$sidebar = $widget.parents( '.widgets-sortables' ),
				sidebar_id = ( wp.hasOwnProperty( 'customize' ) ) ? api.Widgets.getSidebarWidgetControlContainingWidget( $widget.find( '.widget-id' ).val() ).params.sidebar_id : $sidebar.attr( 'id' ),
				$css_id = $widget.find( '.conductor-css-id' ),
				css_id = $css_id.val();

			// Only on Conductor widgets
			if ( conductor_output_view && conductor_output_view instanceof Backbone.View ) {
				// Destroy the current view (custom function to undelegateEvents prior to removal)
				conductor_output_view.destroy();

				// Store the new view in widget data
				$widget.data( 'conductor-output', new conductor.widgets.conductor.views.output( {
					el: $widget.find( '.conductor-output' ), // Attach this view to the widgets output list
					collection: new conductor.widgets.conductor.collections.output() // New collection
				} ) );

				// Add the CSS ID if we don't already have a value and we have sidebar data (adding a widget doesn't return any data so we're adding the CSS class on the widget-updated event)
				if ( ! css_id && sidebar_id && conductor.sidebars[sidebar_id] && conductor.sidebars[sidebar_id].before_widget ) {
					// Create a CSS ID
					css_id = '#' + conductor.sidebars[sidebar_id].before_widget.match( new RegExp( conductor.before_widget_regex ) )[1].replace( '%1$s', '' ) + $widget.find( '.id_base' ).val() + '-' + $widget.find( '.multi_number' ).val();

					// If we have a CSS ID
					if ( css_id ) {
						$css_id.val( css_id );
					}
				}
			}
		} );

		// Need to listen to sidebars for sortreceive
		$sidebars.on( 'sortreceive', function( event, ui ) {
			// Conductor Widgets
			if ( ui.item.attr( 'id' ).indexOf( 'conductor-widget' ) ) {
				// Store the output view in widget data
				ui.item.data( 'conductor-output', new conductor.widgets.conductor.views.output( {
					el: ui.item.find( '.conductor-output' ), // Attach this view to the widgets output list
					collection: new conductor.widgets.conductor.collections.output() // New collection
				} ) );
			}
		} );
	} );


	/*******************
	 * Backbone Models *
	 *******************/

	conductor.widgets.conductor.models = {
		output: Backbone.Model.extend( {
			// Model defaults
			defaults: {
				priority: conductor.widgets.conductor.output.priority_step_size, // Default is 10
				id: false,
				label: false,
				type: false,
				visible: true
			},
			// init
			initialize: function() {
				// Clear memory by destroying this model when it is removed from the collection
				//this.listenTo( this, 'remove', function() { this.destroy(); } );
				//this.listenTo( this, 'reset', function() { this.destroy(); } );

				// TODO: ? Set priority, type, label, etc...
			}
		} )
	};


	/************************
	 * Backbone Collections *
	 ************************/

	conductor.widgets.conductor.collections = {
		output: Backbone.Collection.extend( {
			model: conductor.widgets.conductor.models.output
		} )
	};


	/*********************************
	 * Backbone/Underscore Templates *
	 *********************************/
	conductor.widgets.conductor.templates = {};

	/******************
	 * Backbone Views *
	 ******************/

	// Conductor Output
	conductor.widgets.conductor.views = {
		output: Backbone.View.extend( {
			el: '.conductor-output',
			$output_list: false,
			$output_list_items: false,
			$widget: false,
			collection: new conductor.widgets.conductor.collections.output(),
			//template: wp.template(),
			events: {
				// Labels (editable input elements)
				'click .conductor-widget-output-element-label-editable.editable-input': 'editElementLabel', // TODO: This event is firing multiple times due to event "bubbling"
				'keypress .conductor-widget-output-element-label-editable.editable-input input': 'saveElementLabel',
				'click .conductor-widget-output-element-label-editable.editable-input .conductor-widget-save': 'saveElementLabel', // Save
				'click .conductor-widget-output-element-label-editable.editable-input .conductor-widget-discard': 'saveElementLabel', // Discard

				// Options (editable select elements)
				'click .conductor-widget-output-element-label-editable.editable-select': 'editElementOption', // TODO: This event is firing multiple times due to event "bubbling"
				'click .conductor-widget-output-element-label-editable.editable-select .conductor-widget-save': 'saveElementOption', // Save
				'click .conductor-widget-output-element-label-editable.editable-select .conductor-widget-discard': 'saveElementOption', // Discard

				// Content Display Type
				'change .conductor-widget-output-element-post_content .conductor-widget-output-element-label-select select' : 'toggleContentTypeElements',

				// Link
				'click .conductor-widget-link' : 'toggleLink', // Link

				// Visibility
				'click .conductor-widget-output-element-controls .conductor-widget-visibility' : 'toggleVisibility', // Visibility

				// Remove
				'click .conductor-widget-output-element-controls .conductor-widget-remove' : 'removeElement', // Remove

				// jQuery Sortable
				'sortstop .conductor-widget-output-list' : 'sortableStop' // jQuery Sortable Stop
			},
			// jQuery Sortable options
			sortable_options: {
				handle: '.dashicons-sort',
				axis: 'y', // Vertically
				cursor: 'move',
				placeholder: 'ui-state-placeholder'
			},
			initialize: function() {
				var self = this;

				// Bind "this" to all functions/callbacks
				_.bindAll( this,
					'render',
					'editElementLabel',
					'saveElementLabel',
					'editElementOption',
					'saveElementOption',
					'toggleContentTypeElements',
					'toggleLink',
					'toggleVisibility',
					'removeElement',
					'sortableStop',
					'destroy' );

				// Store a reference to the widget
				this.$widget = this.$el.parents( '.widget' );

				// Store a reference to the output element list
				this.$output_list = this.$el.find( '.conductor-widget-output-list' );

				// Store a reference to the output element list
				this.$output_list_items = this.$output_list.find( 'li' );

				/*
				 * Backbone Models
				 */
				this.$output_list_items.each( function() {
					var $self = $( this ), model = new conductor.widgets.conductor.models.output( {
						priority: ( ( $self.index() + 1 ) * conductor.widgets.conductor.output.priority_step_size ), // Default is 10
						id: $self.attr( 'data-id' ),
						label: $self.attr( 'data-label' ),
						type: $self.attr( 'data-type' ),
						link: ( $self.attr( 'data-link' ) === 'true' ),
						visible: ( $self.attr( 'data-visible' ) === 'true' )
					} );

					// Add the new model to the collection
					self.collection.add( model );
				} );

				/*
				 * jQuery Sortable - Initialize jQuery Sortable
				 */
				this.$output_list.sortable( this.sortable_options );

				/*
				 * listenTo()
				 */
				// TODO:
				//this.listenTo( this.collection, 'add', this.render );
				//this.listenTo( this.collection, 'remove', this.render );
			},
			// TODO: determine what the render function should accomplish
			render: function() {
				var self = this;

				return this;
			},
			editElementLabel: function( event ) {
				var $el = $( event.currentTarget );

				$el.addClass( 'editing' );
				$el.find( 'input' ).focus().attr( 'data-current', $el.find( 'input').val() );
			},
			editElementOption: function( event ) {
				var $el = $( event.currentTarget );

				$el.addClass( 'editing' );
				$el.find( 'select' ).attr( 'data-current', $el.find( 'select' ).val() );
			},
			saveElementLabel: function( event ) {
				var $el = $( event.currentTarget ),
					$output_element, $input, escaped_val, original;

				// Enter (save)
				if ( event.type === 'keypress' && event.which === 13 ) {
					$output_element = $el.closest( '.conductor-widget-output-element' );
					escaped_val = _.escape( $el.val() );

					// Remove editing class from label wrapper
					$el.parents( '.conductor-widget-output-element-label' ).removeClass( 'editing' );

					// Set the current label value
					if ( escaped_val.length ) {
						$output_element.attr( 'data-label', escaped_val ).find( '.label' ).html( escaped_val );
					}
					// No label entered, revert back to original
					else {
						original = $el.attr( 'data-original' );

						// Reset back to the original value
						$el.val( '' );
						$output_element.attr( 'data-label', original ).find( '.label' ).html( original );
					}

					// Update the sortable data
					this.sortableStop( false, false );

					//event.preventDefault();
				}

				// Click (save or discard)
				if ( event.type === 'click' ) {
					$output_element = $el.closest( '.conductor-widget-output-element' );
					$input = $el.parent().find( 'input' );
					escaped_val = _.escape( $input.val() );

					// Remove editing class from label wrapper
					$el.parents( '.conductor-widget-output-element-label' ).removeClass( 'editing' );

					// Save
					if ( $el.hasClass( 'conductor-widget-save' ) ) {
						// Set the current label value
						if ( escaped_val.length ) {
							$output_element.attr( 'data-label', escaped_val ).find( '.label' ).html( escaped_val );
						}
						// No label entered, revert back to original
						else {
							original = $input.attr( 'data-original' );

							// Reset back to the original value
							// TODO: does the following $el.val( '' ); work in this case since the $el is current target?
							$el.val( '' );
							$output_element.attr( 'data-label', original ).find( '.label' ).html( original );
						}

						// Update the sortable data
						this.sortableStop( false, false );
					}

					// Discard
					if ( $el.hasClass( 'conductor-widget-discard' ) ) {
						// Reset back to the original value
						$input.val( $input.attr( 'data-current' ) );
					}

					// Prevent Default and Propagation
					event.preventDefault();
					event.stopPropagation();
				}
			},
			saveElementOption: function( event ) {
				var $el = $( event.currentTarget ),
					$output_element = $el.closest( '.conductor-widget-output-element' ),
					$select = $el.parent().find( 'select' ),
					escaped_val = _.escape( $select.val()),
					$selected = $select.find( ':selected' );

					// Remove editing class from label wrapper
					$el.parents( '.conductor-widget-output-element-label' ).removeClass( 'editing' );

					// Save
					if ( $el.hasClass( 'conductor-widget-save' ) ) {
						// Set the current value
						if ( escaped_val.length ) {
							$output_element.attr( 'data-value', escaped_val ).attr( 'data-label', $selected.attr( 'data-label' ) ).find( '.label' ).html( $selected.attr( 'data-label' ) );
						}
						// No value entered, revert back to original
						else {
							var original = $select.attr( 'data-original' );

							// Reset back to the original value
							$select.val( original );
							$output_element.attr( 'data-value', original );
						}

						// Update the sortable data
						this.sortableStop( false, false );
					}

					// Discard
					if ( $el.hasClass( 'conductor-widget-discard' ) ) {
						// Reset back to the original value
						$select.attr( 'data-current', $output_element.attr( 'data-value' ) );
						$select.val( $select.attr( 'data-current' ) );
					}

					// Prevent Default and Propagation
					event.preventDefault();
					event.stopPropagation();
			},
			toggleContentTypeElements: function( event ) {
				var $el = $( event.currentTarget ),
					self = this,
					value = $el.val();

				// Hide the content type fields and remove the "hidden" CSS class
				this.$widget.find( '.conductor-display-content-type-field' ).hide().removeClass( 'conductor-hidden' );

				// Start a new thread (delay 1ms)
				setTimeout( function() {
					// Show the content type fields for this content type
					self.$widget.find( '.conductor-display-content-type-' + value ).show();
				}, 1 );
			},
			toggleLink: function( event ) {
				var $el = $( event.currentTarget ), $parent = $el.parents( '.conductor-widget-output-element' ),
					model = this.collection.findWhere( { id: $parent.attr( 'data-id' ) } );

				// Toggle the link class
				$parent.toggleClass( 'link' );

				// Update the model and the data-visible attr
				if ( $parent.hasClass( 'link' ) ) {
					$parent.attr( 'data-link', 'true' );
					model.set( 'link', true );
				}
				else {
					$parent.attr( 'data-link', 'false' );
					model.set( 'link', false );
				}

				// Update the sortable data
				this.sortableStop( false, false );
			},
			toggleVisibility: function( event ) {
				var $el = $( event.currentTarget ), $parent = $el.parents( '.conductor-widget-output-element' ),
					model = this.collection.findWhere( { id: $parent.attr( 'data-id' ) } );

				// Toggle the visible class
				$parent.toggleClass( 'visible' );

				// Update the model and the data-visible attr
				if ( $parent.hasClass( 'visible' ) ) {
					$parent.attr( 'data-visible', 'true' );
					model.set( 'visible', true );
				}
				else {
					$parent.attr( 'data-visible', 'false' );
					model.set( 'visible', false );
				}

				// Update the sortable data
				this.sortableStop( false, false );
			},
			removeElement: function( event ) {
				var $el = $( event.currentTarget ), $parent = $el.parents( '.conductor-widget-output-element' ),
					model = this.collection.findWhere( { id: $parent.attr( 'data-id' ) } );

				// Remove the model from the collection
				if ( model instanceof Backbone.Model ) {
					this.trigger( 'removeElement', model ); // Hook

					this.collection.remove( model );
				}

				// Remove the element from the DOM
				$parent.remove();

				// Update the sortable data
				this.sortableStop( false, false );

				// Prevent Default and Propagation
				event.preventDefault();
				event.stopPropagation();
			},
			// When jQuery Sortable has stopped
			sortableStop: function( event, ui ) {
				var $conductor_output_data = this.$el.find( '.conductor-output-data' ),
					data = {},
					json_data = '',
					self = this;

				// Clear the collection
				this.collection.reset();

				// Reset the output list items
				this.$output_list_items = this.$output_list.find( 'li' );

				// Each output element
				this.$output_list_items.each( function() {
					var $self = $( this ), model = new conductor.widgets.conductor.models.output( {
						priority: ( ( $self.index() + 1 ) * conductor.widgets.conductor.output.priority_step_size ), // Default is 10
						id: $self.attr( 'data-id' ),
						label: $self.attr( 'data-label' ),
						type: $self.attr( 'data-type' ),
						visible: ( $self.attr( 'data-visible' ) === 'true' )
					} ),
						index = $self.index(), priority = $self.attr( 'data-priority' ),
						new_priority = ( ( index + 1 ) * conductor.widgets.conductor.output.priority_step_size ),
						value = $self.attr( 'data-value' ),
						link = $self.attr( 'data-link' );

					// Adjust priority
					$self.attr( 'data-priority', new_priority );
					model.set( 'priority', new_priority );

					// Store data in array
					data[model.get( 'priority' ).toString()] = {
						'id': model.get( 'id' ),
						'priority': model.get( 'priority' ),
						'label': model.get( 'label' ),
						'type': model.get( 'type' ),
						'visible': model.get( 'visible' )
					};

					// Add value data
					if ( typeof value !== undefined && value !== false ) {
						model.set( 'value', value );

						data[model.get( 'priority' ).toString()].value = value;
					}

					// Add link data
					if ( typeof link !== undefined && link !== false ) {
						model.set( 'link', ( link !== 'false' ) ? link : false );

						data[model.get( 'priority' ).toString()].link = ( link !== 'false' ) ? link : false;
					}

					// Add the new model to the collection
					self.collection.add( model );
				} );

				// Setup the JSON data
				json_data = JSON.stringify( data );

				// Add data string to widget (hidden input elements do not automatically trigger the "change" method)
				$conductor_output_data.val( json_data ).trigger( 'change' );

				// Trigger an event on the widget parent element
				this.$widget.trigger( 'conductor-widget:sortable-stop', [
					data,
					json_data,
					self
				] );
			},
			// Completely destroy this view and all event handlers
			destroy: function() {
				this.undelegateEvents();
				this.remove();
			}
		} )
	};


	/**************
	 * Customizer *
	 **************/

	if ( wp.hasOwnProperty( 'customize' ) ) {
		// Extend the form sync handlers to include one for Conductor
		$.extend( api.Widgets.formSyncHandlers, {
			// Conductor Widget
			'conductor-widget': function( event, $widget, newForm ) {
				var $widget_content_type_select = $widget.find( '.conductor-select-content-type' ),
					$selected_content_type = $( ':selected', $widget_content_type_select ),
					$widget_content_type_options = $widget_content_type_select.find( 'option' ),
					widget_content_type = $widget.find( '.conductor-content-type' ).val();

				// Make sure the content type select box matches the selected content type
				if ( $selected_content_type.length !== 0 && $selected_content_type.attr( 'data-type' ) !== widget_content_type ) {
					// Loop through content types
					$widget_content_type_options.each( function() {
						var $this = $( this );

						// Found the correct content type, set it
						if ( $this.attr( 'data-type' ) === widget_content_type ) {
							$widget_content_type_select.val( $this.val() ); // Note: By default this will not trigger the "change" event and that's good for us in this case to prevent never ending updates of widget data
						}
					} );
				}
			}
		} );
	}
}( wp, jQuery ) );