/**
 * Conductor Options Admin
 */
var conductor = conductor || {};

// TODO: Future: Minify

( function ( $ ) {
	"use strict";

	// Defaults
	conductor['content-layouts'] = {
		models: {},
		collection: false,
		views: {},
		run: {}
	};


	/**
	 * Content Layouts
	 */

	// Content Layout Model
	conductor['content-layouts'].models['content-layout'] = Backbone.Model.extend( {
		defaults: {
			field_id: '',
			field_type: '',
			field_num: 0,
			field_label: '',
			permalink: '',
			selected: false
		},
		initialize: function() {
			// Clear memory by destroying this model when it is removed from the collection
			this.listenTo( this, 'remove', function() { this.destroy(); } );

			// Set up the field label (if it wasn't already passed to this model)
			if ( ! this.get( 'field_label' ) ) {
				this.set( 'field_label', this.createLabel() );
			}
		},
		/**
		 * ucwords() JavaScript function - http://phpjs.org/functions/ucwords/
		 * License: MIT (GPL Compatible)
		 * Copyright: Kevin van Zonneveld - http://kvz.io/, contributors - http://phpjs.org/authors/
		 *
		 * Notes:
		 * discuss at: http://phpjs.org/functions/ucwords/
		 * original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
		 * improved by: Waldo Malqui Silva
		 * improved by: Robin
		 * improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		 * bugfixed by: Onno Marsman
		 *
		 * We've modified this to suit our needs.
		 */
		// TODO: Do we still need this function?
		ucwords: function( str ) {
			return ( str + '' ).replace( /^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function( $1 ) {
				return $1.toUpperCase();
			} );
		},
		// This function creates a label for this model based on the field type and id
		// TODO: Do we still need this function?
		createLabel: function() {
			var field_id = this.get( 'field_id' ),
				field_type = this.get( 'field_type'),
				field_name = this.get( 'field_name'),
				label = this.ucwords( field_type.replace( /_/g, ' ' ) ) + ' - '; // Initial label

			// Categories or Post Types
			switch ( field_type ) {
				// Post Types
				case 'post-type':
					label += this.ucwords( field_id.replace( /_/g, ' ' ) );
				break;

				// Default
				default:
					label += this.ucwords( field_name.replace( /_/g, ' ' ) );
				break;
			}

			return label;
		}
	} );

	// Content Layout collection
	conductor['content-layouts'].collection = Backbone.Collection.extend( {
		model: conductor['content-layouts'].models['content-layout']
	} );

	// Content Layout view
	conductor['content-layouts'].views['content-layout'] = Backbone.View.extend( {
		el: '.conductor-content-layouts',
		collection: new conductor['content-layouts'].collection(),
		template: wp.template( 'conductor-content-layout' ),
		events : {
			'click .conductor-remove-content-layout': 'removeContentLayout',
			'click .content-layout-label': 'setContentLayout'
		},
		initialize: function() {
			// Re-render whenever the collection changes
			this.listenTo( this.collection, 'add', this.render );
			this.listenTo( this.collection, 'remove', this.render );

			_.bindAll( this, 'render', 'setContentLayout', 'removeContentLayout', 'getFieldNumOffset' );

			// Determine the number of initial content layouts
			this.field_num_offset = this.getFieldNumOffset();
		},
		render: function() {
			var self = this;

			// Clear the content layouts first
			this.$( '.conductor-content-layout-wrap-js' ).remove();

			// Loop through the content layout models and add them before the controls
			_.each( this.collection.models, function( model ) {
				// Reset the field number before rendering
				model.set( 'field_num', self.field_num_offset + self.collection.indexOf( model ) );

				self.$el.append( self.template( model.toJSON() ) );
			} );

			this.trigger( 'render:complete' ); // Hook

			return this;
		},
		// This function sets the content layout on the model when the user clicks on one of the options
		setContentLayout: function( event ) {
			// Since the event bubbles to the input element inside of the label, make sure this is the click on the label
			if ( ! $( event.target ).is( 'input' ) ) {
				var $label = $( event.currentTarget ), field_num = parseInt( $label.attr( 'data-field-num' ), 10 ),
					selected = $label.find( 'input' ).val(), model = this.collection.findWhere( { 'field_num' : field_num } );

				// Set the selected value on the model
				if ( model ) {
					model.set( 'selected', selected );
				}
			}
		},
		// This function removes a content layout from the DOM
		removeContentLayout: function( event ) {
			var $remove_link = $( event.currentTarget ),
				model = this.collection.findWhere( { 'field_num' : parseInt( $remove_link.attr( 'data-field-num' ), 10 ) } );

			// Prevent default
			event.preventDefault();

			// Remove the content layout from the collection
			if ( model ) {
				this.collection.remove( model );
			}
			// Remove the content layout from the view as it does not exist in the collection
			else {
				$remove_link.parents( '.conductor-content-layout-wrap' ).remove();
			}
		},
		// This function gets the starting index for the models in this view's collection
		getFieldNumOffset: function() {
			return this.$( '.conductor-content-layout-wrap' ).length;
		}
	} );

	// Content Layout Controls View
	conductor['content-layouts'].views['content-layout-controls'] = Backbone.View.extend( {
		el: '.conductor-content-layouts-controls',
		controller_view: {},
		current_model: {},
		show_message_flag: true,
		add_content_layout_flag: true,
		events : {
			'click .conductor-content-layouts-add' : function( event ) {
				this.maybeAddContentLayout( event );
				this.maybeHideHelp( event );
			}
		},
		initialize: function() {
			// Set up the controller view (the view that this view controls by adding/removing models)
			this.controller_view = new conductor['content-layouts'].views['content-layout']();

			// TODO: need to _.bindAll();?
		},
		render: function() {
			this.$el.html( this.template() );

			return this;
		},
		// Determine whether or not to add the content layout to collection/DOM
		maybeAddContentLayout: function( event ) {
			var $content_types = this.$( '.conductor-content-types-select' ), $content_types_selected = $content_types.find( 'option:selected' ),
				content_type = {
					id: $content_types.val(),
					name: $content_types_selected.text(),
					type: $content_types_selected.attr( 'data-content-type' ),
					permalink: $content_types_selected.attr( 'data-permalink' )
				},
				model = {};

			// Prevent default
			event.preventDefault();

			// Validate data
			if ( ! content_type.id.length || ! content_type.name.length || ! content_type.type.length || ( conductor.hasOwnProperty( 'is_customizer' ) && conductor.is_customizer === '1' ) ) {
				this.showMessage( conductor.l10n.no_content_type, 'no-content-type', 'error' );

				return false;
			}

			// Check to make sure this choice doesn't already exist
			if ( ! this.controller_view.$( '.conductor-content-layout-' + content_type.type + '-' + content_type.id ).length ) {
				model = {
					field_id: content_type.id,
					field_name: content_type.name,
					field_type: content_type.type,
					permalink: content_type.permalink
				};

				this.trigger( 'add-content-layout-flag', this, model, content_type ); // Hook

				// Show the message based on message flag
				if ( this.getAddContentLayoutFlag() ) {
					// Add the new layout to the collection which will fire the render method in the controller view
					this.addContentLayout( model, content_type );

					this.current_model = {}; // Reset current model

					this.showMessage( conductor.l10n.content_layout_created, 'content-layout-created' );
				}
				// Reset the flag
				else {
					this.setAddContentLayoutFlag( true );
				}
			}
			else {
				this.showMessage( conductor.l10n.content_layout_exists, 'content-layout-exists', 'error' );
			}
		},
		addContentLayout: function( model, content_type ) {
			content_type = ( content_type !== undefined ) ? content_type : false;

			// Determine the field label
			if ( ( ! model.hasOwnProperty( 'field_label' ) || ! model.field_label ) && content_type.hasOwnProperty( 'name' ) ) {
				// Switch based on field type
				switch ( model.field_type ) {
					// Built-In
					case 'built-in':
						model.field_label = content_type.name;
					break;

					// Category
					case 'category':
						model.field_label = conductor.l10n.category_label_prefix + content_type.name;
					break;

					// Post Type
					case 'post-type':
						model.field_label = conductor.l10n.post_type_label_prefix + content_type.name;
					break;
				}
			}

			this.trigger( 'content-layout:add:properties', this, model ); // Hook
			model.edit_link_url = conductor.customize_url + '=' + model.permalink;
			this.current_model = model;

			this.controller_view.collection.add( model );
		},
		// Determine if the Conductor Help section should be hidden
		maybeHideHelp: function( event ) {
			var content_layouts_count = this.controller_view.getFieldNumOffset(), $content_layouts_help = this.$el.parent().find( '.conductor-content-layouts-help' );
			// Prevent default
			event.preventDefault();

			// Remove the help section after a content layout has been added
			if ( content_layouts_count > 0 && $content_layouts_help.length ) {
				$content_layouts_help.stop().slideUp();
			}
		},
		// This function displays an error/success message with a timeout
		showMessage: function( content, context, type, duration ) {
			var self = this,
				$message = this.$( '#setting-error-conductor-content-layouts' ),
				type = ( type !== undefined ) ? type : 'updated',
				duration = ( duration !== undefined ) ? duration : 4000;

			this.trigger( 'show-message-flag', this, content, context, type, duration ); // Hook

			// Show the message based on message flag
			if ( this.getShowMessageFlag() ) {
				// Set message content, remove existing CSS classes and styles, add new CSS class for type
				$message.html( '<p>' + content + '</p>' ).removeClass( 'updated error' ).attr( 'style', '' ).addClass( type );

				// Clear/Set message timeout
				clearTimeout( this.showMessageTimer );
				this.showMessageTimer = setTimeout( function() {
					$message.slideUp( 400, function() {
						self.$( this ).attr( 'style', 'display: none !important;' );
					} );
				}, duration );
			}
			// Reset the flag
			else {
				this.setShowMessageFlag( true );
			}
		},
		getShowMessageFlag: function() {
			return this.show_message_flag;
		},
		setShowMessageFlag: function( val ) {
			this.show_message_flag = val;
		},
		getAddContentLayoutFlag: function() {
			return this.add_content_layout_flag;
		},
		setAddContentLayoutFlag: function( val ) {
			this.add_content_layout_flag = val;
		}
	} );

	// This function is called on jQuery document ready
	conductor['content-layouts'].ready = {
		views: {},
		init: function() {
			// Set up the content layout views
			this.views['content-layouts'] = {};
			this.views['content-layouts'].controls = new conductor['content-layouts'].views['content-layout-controls']();
		}
	};



	/**
	 * Document Ready
	 */
	$( function() {
		// TODO: Combine the following two pieces of functionality into a function
		/**
		 * Navigation Tabs
		 */
		$( '.conductor-options-tab-wrap a' ).on( 'click', function ( e ) {
			var $this = $( this ), tab_id_prefix = $this.attr( 'href' );

			// Remove active classes
			$( '.conductor-tab-content' ).removeClass( 'conductor-tab-content-active' );
			$( '.conductor-tab' ).removeClass( 'nav-tab-active' );

			// Activate new tab
			$( tab_id_prefix + '-tab-content' ).addClass( 'conductor-tab-content-active' );
			$this.addClass( 'nav-tab-active' );
			$( '#conductor_options_tab' ).val( tab_id_prefix );
		} );

		/**
		 * Window Hash
		 */
		if ( window.location.hash && $( window.location.hash + '-tab-content' ).length ) {
			var tab_id_prefix = window.location.hash;

			// Remove active classes
			$( '.conductor-tab-content' ).removeClass( 'conductor-tab-content-active' );
			$( '.conductor-tab' ).removeClass( 'nav-tab-active' );

			// Activate tab
			$( tab_id_prefix + '-tab-content' ).addClass( 'conductor-tab-content-active' );
			$( tab_id_prefix + '-tab').addClass( 'nav-tab-active' );
			$( '#conductor_options_tab' ).val( tab_id_prefix );
		}

		/**
		 * BackboneJS Functionality
		 */
		conductor['content-layouts'].ready.init();
	} );
} )( jQuery );