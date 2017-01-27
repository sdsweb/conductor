/**
 * Conductor Note Widget Preview (Customizer Previewer)
 *
 * TODO: Remove in a future version
 */
( function ( wp, $ ) {
	'use strict';

	// Bail if the customizer isn't initialized
	if ( ! wp || ! wp.customize ) {
		return;
	}

	var api = wp.customize, OldPreview;

	// Conductor Note Widget Preview
	api.ConductorNotePreview = {
		preview: null, // Instance of the Previewer
		editors: [], // TinyMCE Editors
		editor_config: [],  // TinyMCE Editor configurations
		editor_selectors: [], // TinyMCE Editor selectors
		tinymce: window.tinymce,
		tinyMCE: window.tinyMCE,
		tinymce_config: {},
		note: window.note, // Reference to Note data
		conductor_note: window.conductor_note,
		widget_settings: ( window.conductor_note.hasOwnProperty( 'widgets' ) && window.conductor_note.widgets.hasOwnProperty( 'settings' ) ) ? window.conductor_note.widgets.settings : false,
		widget_templates: ( window.conductor_note.hasOwnProperty( 'widgets' ) && window.conductor_note.widgets.hasOwnProperty( 'templates' ) ) ? window.conductor_note.widgets.templates : false,
		$conductor_note_widgets: false,
		$document: false,
		transition_duration: 400, // CSS transition is 400ms
		// Initialization
		init: function () {
			var self = this;

			// Set TinyMCE Reference
			this.tinymce = window.tinymce;

			// Set the Conductor Note Widget jQuery reference
			this.$conductor_note_widgets = $( '.conductor-note-widget' );

			// Set the document jQuery reference
			this.$document = $( document );

			// When the previewer is active
			this.preview.bind( 'active', function() {
				// Setup default TinyMCE config settings
				self.tinymce_config = {
					// TinyMCE Setup
					setup: function( editor ) {
						// Add a Conductor Note object to the editor
						editor.conductor_note = {
							widget_id: editor.getParam( 'conductor_note' ).widget_id,
							editor_id: editor.getParam( 'conductor_note' ).editor_id
						};
						// Call Note TinyMCE Setup function first
						if ( self.note.tinymce.hasOwnProperty( 'setup' ) ) {
							// Call the Note TinyMCE setup function
							self.note.tinymce.setup.call( editor, editor );
						}

						// Set the parent CSS selector for noteinsert plugin
						editor.note.parent = '.conductor-col-has-editor';

						// Editor initialization
						editor.on( 'init', function( event ) {
							var $el = $( editor.getElement() ),
								$conductor_note_widget = $el.parents( '.conductor-note-widget' );

							// Store widget data on editor
							editor.note.widget_data = {
								widget: {
									number: $conductor_note_widget.find( '.widget-number' ).val(),
									id: $conductor_note_widget.find( '.widget-id' ).val()
								},
								sidebar: {
									name: $conductor_note_widget.find( '.sidebar-name' ).val(),
									id: $conductor_note_widget.find( '.sidebar-id' ).val()
								},
								// Pass updated selectors to allow note-widget-update to execute properly
								selectors: {
									// Content textareas are setup as 0-index
									widget_content: '.conductor-content-' + parseInt( editor.conductor_note.editor_id, 10 ), // Widget Content
									widget_content_data: 'conductor-note' // Widget Content Data Slug
								}
							};
						} );

						// Editor note-editor-focus
						editor.on( 'note-editor-focus', function( data ) {
							var editor_id = editor.id,
								body = editor.getBody(),
								$body = $( body ),
								$note_wrapper = $body.parents( '.note-wrapper' );

							// Conductor Note Widget
							if ( $note_wrapper.hasClass( 'conductor-note-wrapper' ) ) {

								// Loop through editors
								$note_wrapper.find( '.editor' ).each( function() {
									var $this = $( this ),
										id = $this.attr( 'id' ),
										the_editor,
										$media_placeholder;

										// Find the TinyMCE Editor associated with this element
										the_editor = self.tinyMCE.get( id );

										// If we have an editor
										if ( the_editor ) {
										$media_placeholder = $this.parent().find( '.mce-note-media-insert-panel' );

										// Ignore the original editor
										if ( id.indexOf( 'mce_' ) !== -1 && id !== editor_id ) {
											// Trigger our custom focus event
											the_editor.fire( 'conductor-note-editor-focus', data );
										}

										// If the media placeholder is visible, add the transition effect to it
										if ( $media_placeholder.length && $media_placeholder.is( ':visible' ) ) {
											// Add transition and Note edit focus CSS classes
											$media_placeholder.addClass( 'mce-edit-focus-transition mce-note-edit-focus' );

											// Remove Note edit focus CSS class after 400ms
											_.delay( function() {
												$media_placeholder.removeClass( 'mce-note-edit-focus' );

												// Remove the transition CSS class after another 400ms
												_.delay( function() {
													$media_placeholder.removeClass( 'mce-edit-focus-transition' );
												}, self.transition_duration );
											}, self.transition_duration ); // CSS transition is 400ms
										}
									}
								} );
							}
						} );

						// Editor Conductor Note Widget focus (mimic Note editor focus functionality)
						editor.on( 'conductor-note-editor-focus', function( data ) {
							// Add transition and Note edit focus CSS classes
							self.tinymce.DOM.addClass( editor.getBody(), 'mce-edit-focus-transition mce-note-edit-focus' );

							// Remove Note edit focus CSS class after 400ms
							_.delay( function() {
								self.tinymce.DOM.removeClass( editor.getBody(), 'mce-note-edit-focus' );

								// Remove the transition CSS class after another 400ms
								_.delay( function() {
									self.tinymce.DOM.removeClass( editor.getBody(), 'mce-edit-focus-transition' );
								}, self.transition_duration );
							}, self.transition_duration ); // CSS transition is 400ms
						} );
					}
				};

				// Loop through widgets/settings
				if ( ! _.isEmpty( self.widget_settings ) && ! _.isEmpty( self.widget_templates ) ) {
					// Loop through widget settings
					_.each( self.widget_settings, function( settings ) {
						var template = ( self.widget_templates.hasOwnProperty( settings.template ) ) ? self.widget_templates[settings.template] : false,
							template_config = ( template && template.hasOwnProperty( 'config' ) ) ? template.config : {},
							// TODO: Is there a more efficient way to find the correct widget element?
							$widget = $( _.find( self.$conductor_note_widgets, function( widget ) {
								return $( widget ).find( '.widget-id' ).val() === settings.widget_id;
							} ) ),
							widget_id = ( $widget ) ? $widget.attr( 'id' ) : false;

						// If we have a widget element
						if ( $widget.length ) {
							// Loop through template configurations
							_.each( template_config, function( config, editor_id ) {
								// Bail if this element doesn't exist
								if ( ! $( '#' + widget_id + ' .editor-' + editor_id ).length ) {
									return;
								}

								// Editor configuration
								var editor_config = _.defaults( {
									// Adjust the selector to match this particular widget and editor
									selector: '#' + widget_id + ' .editor-' + editor_id,
									// Conductor Note data
									conductor_note: {
										widget_id: widget_id,
										editor_id: editor_id,
										editor_id_prefixed: 'editor-' + editor_id
									}
								}, self.tinymce_config ), editor_tinymce_config;

								// Add the selector to array
								self.editor_selectors.push( '#' + widget_id + ' .editor-' + editor_id );

								// Add this editor config to array (will be populated with self.note.tinymce after TinyMCE init)
								self.editor_config.push( editor_config );

								// Setup TinyMCE config based on type
								if ( self.conductor_note.tinymce.hasOwnProperty( config.type ) && !_.isEmpty( self.conductor_note.tinymce[config.type] ) ) {
									editor_tinymce_config = self.conductor_note.tinymce[config.type];
								}
								else {
									editor_tinymce_config = self.note.tinymce;
								}

								// Init TinyMCE (using _.defaults() instead of _.extend() like Note to make sure we're not altering Note data)
								self.tinymce.init( _.defaults( editor_config, editor_tinymce_config ) );
							} );
						}
					} );
				}
			} );
		}
	};

	/**
	 * Capture the instance of the Preview since it is private
	 */
	OldPreview = api.Preview;
	api.Preview = OldPreview.extend( {
		initialize: function( params, options ) {
			api.ConductorNotePreview.preview = this;
			OldPreview.prototype.initialize.call( this, params, options );
		}
	} );

	/**
	 * Document Ready
	 */
	$( function() {
		var conductor_note = window.conductor_note;

		if ( ! conductor_note) {
			return;
		}

		$.extend( api.ConductorNotePreview, conductor_note );

		// Initialize our custom Preview
		api.ConductorNotePreview.init();
	} );
} )( wp, jQuery );