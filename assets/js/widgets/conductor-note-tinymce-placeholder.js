/**
 * Conductor Note TinyMCE Placeholder Plugin
 *
 * TODO: Remove in a future version
 */

/* global tinymce */

tinymce.PluginManager.add( 'conductornoteplaceholder', function( editor ) {
	'use strict';

	var DOM = tinymce.DOM,
		prev_node, // Reference to the previous node in the editor
		$prev_node,
		$ = jQuery,
		api = wp.customize, // Customizer API
		NotePreview = api.NotePreview, // NotePreview
		wp_media_active = false, // Flag to determine if wp.media modal was open
		$el = $( editor.getElement() ),
		$el_parent = $el.parent(),
		data_key = 'note',
		placeholder_class = 'note-has-placeholder',
		placeholder_el_class = 'note-placeholder',
		media_panel;


	/*******************
	 * Event Listeners *
	 *******************/

	// Editor PreInit
	editor.on( 'PreInit', function( event ) {
		var note_type = editor.getParam( 'note_type' );

		// Only on media editors
		if ( note_type && note_type === 'media' ) {
			// Create the Panel
			media_panel = tinymce.ui.Factory.create( {
				type: 'panel',
				layout: 'flow',
				classes: 'insert-panel note-insert-panel media-insert-panel note-media-insert-panel',
				ariaRoot: true,
				ariaRemember: true,
				items: editor.toolbarItems( editor.settings.media_blocks )
			} );

			/*
			 * This function sets the panel's position in the DOM.
			 */
			media_panel.setPosition = function() {
				var insert_el = this.getEl(), // Insert element
					body = editor.getBody(), // Editor body
					$body = $( body ),
					$parent = $body.parents( '.conductor-col' ),
					parent_pos = DOM.getPos( $parent[0] );

				// Set the styles on the insert element
				DOM.setStyles( insert_el, {
					//'left': parent_pos.x,
					//'top': parent_pos.y,
					//'width': $parent[0].offsetWidth,
					//'height': $parent[0].offsetHeight
				} );
	
				// Return this for chaining
				return this;
			};
		}
	} );

	// Editor Init
	editor.on( 'init', function( event ) {
		var note_type = editor.getParam( 'note_type' );

		// Note Placeholder
		if ( $el.hasClass( placeholder_class ) ) {
			// Loop through nodes // TODO: Optimize this call if possible (can we loop through children() only?)
			$el.find( '*' ).each( function() {
				var $this = $( this );

				// Add the Note Placeholder CSS Class
				$this.addClass( placeholder_el_class );

				// Add the Note Placeholder data attribute (set to current content value)
				$this.data( data_key, { placeholder: DOM.encode( $this.text() ) } );
			} );
		}
		else {
			// Loop through nodes // TODO: Optimize this call if possible (can we loop through children() only?)
			$el.find( '*' ).each( function() {
				var $this = $( this );

				// Add the Note Placeholder CSS Class
				$this.hasClass( placeholder_el_class );

				// Add the Note Placeholder data attribute (set to current content value)
				$this.data( data_key, { placeholder: DOM.encode( $this.text() ) } );
			} );
		}

		// Only on media editors
		if ( note_type && note_type === 'media' ) {
			// Render the panel to the editor
			media_panel.renderTo( $el_parent[0] );

			// Hide the media panel
			media_panel.hide();

			// Note Placeholder
			if ( $el.hasClass( placeholder_class ) ) {
				// Remove all content
				editor.setContent( '' );

				// Add CSS class to parent
				$el_parent.addClass( 'has-media-placeholder' );

				// Show the media panel
				media_panel.show();
			}
		}
	} );

	// Editor NodeChange
	editor.on( 'NodeChange', function( event ) {
		var node = editor.selection.getNode(),
			$node = $( node ),
			node_editor_id = $node.parents( '.editor' ).attr( 'id' ),
			text = $node.text(),
			note_data = $node.data( data_key ),
			placeholder = ( note_data && note_data.hasOwnProperty( 'placeholder' ) ) ? note_data.placeholder : false,
			note_type = editor.getParam( 'note_type' );

		// Reset the wp.media flag
		if ( wp_media_active ) {
			wp_media_active = false;
		}

		// Note Placeholder element
		if ( node_editor_id === editor.id && $node.hasClass( placeholder_el_class ) && text === placeholder ) {
			// Set flag to stop Note Widget updates
			editor.note.placeholder_el = true;
			editor.note.prevent_widget_update = true;

			// Remove the placeholder CSS
			$node.removeClass( placeholder_el_class );

			// Remove the placeholder content
			$node.html( '<br />' ); // Set a break to preserve the element editing capability, TinyMCE will handle the rest for us
		}
		// Otherwise we have normal element
		else {
			// Reset flag to stop Note Widget updates
			editor.note.placeholder_el = false;
			editor.note.prevent_widget_update = false;
		}

		// Determine if this node is different than the previous
		if ( node_editor_id === editor.id && ! compareNodes( node, prev_node ) ) {
			// Determine if previous node is empty and reset the placeholder
			// TODO: WP 4.0 doesn't like DOM.isEmpty (DOM.isEmpty( prev_node, { img : true } ) )
			if ( prev_node && $prev_node.length && ! $prev_node.text() && ! $prev_node.has( 'img' ).length ) {
				// Previous node placeholder
				note_data = $prev_node.data( data_key );
				placeholder = ( note_data && note_data.hasOwnProperty( 'placeholder' ) ) ? note_data.placeholder : false;

				// If we have placeholder data
				if ( placeholder ) {
					// Reset placeholder
					$prev_node.html( DOM.decode( placeholder ) ).addClass( placeholder_el_class );
				}
			}

			// Update the previous node
			prev_node = node;
			$prev_node = $( prev_node );
		}

		// Only on media editors
		if ( note_type && note_type === 'media' ) {
			// Adjust the position of the media panel
			media_panel.setPosition();
		}
	} );

	// Editor change & keypress
	editor.on( 'change keypress', function( event ) {
		// If the editor placeholder element flag is set
		if ( editor.note.hasOwnProperty( 'placeholder_el' ) && editor.note.placeholder_el ) {
			// Reset the placeholder element flag
			editor.note.placeholder_el = false;

			// Reset the widget update flag
			editor.note.prevent_widget_update = false;
		}
	} );

	// Editor wpLoadImageForm
	editor.on( 'wpLoadImageForm', function( event ) {
		// Set the wp.media flag
		wp_media_active = true;
	} );

	// Editor wpLoadImageForm once
	editor.once( 'wpLoadImageForm', function( event ) {
		// Listen for the close event on the frame
		event.frame.on( 'close', function() {
			var node = editor.selection.getNode(),
				$node = $( node ),
				node_editor_id = $node.parents( '.editor' ).attr( 'id' ),
				text = $node.text(),
				note_data = $node.data( data_key ),
				placeholder = ( note_data && note_data.hasOwnProperty( 'placeholder' ) ) ? note_data.placeholder : false;

			// Note Placeholder element
			if ( node_editor_id === editor.id && $node.hasClass( placeholder_el_class ) && text === placeholder ) {
				// Remove the placeholder CSS
				$node.removeClass( placeholder_el_class );

				// Remove the placeholder content
				$node.html( '<br />' ); // Set a break to preserve the element editing capability, TinyMCE will handle the rest for us

				// Focus the editor
				editor.focus();
			}
		} );

		// Listen for the insert event on the frame
		event.frame.on( 'insert', function() {
			// Remove the placeholder
			editor.dom.remove( editor.dom.select( '.note-placeholder' ) );

			// Remove CSS class from parent
			$el_parent.removeClass( 'has-media-placeholder' );

			// Hide the media panel
			if ( media_panel ) {
				media_panel.hide();
			}
		} );
	} );

	// Editor note-editor-focus
	editor.on( 'note-editor-focus', function() {
		var node = editor.selection.getNode(),
			$node = $( node ),
			node_editor_id = $node.parents( '.editor' ).attr( 'id' ),
			text = $node.text(),
			note_data = $node.data( data_key ),
			placeholder = ( note_data && note_data.hasOwnProperty( 'placeholder' ) ) ? note_data.placeholder : false;

		// Note Placeholder element
		if ( node_editor_id === editor.id && $node.hasClass( placeholder_el_class ) && text === placeholder ) {
			// Set flag to stop Note Widget updates
			editor.note.placeholder_el = true;
			editor.note.prevent_widget_update = true;

			// Remove the placeholder CSS
			$node.removeClass( placeholder_el_class );

			// Remove the placeholder content
			$node.html( '<br />' ); // Set a break to preserve the element editing capability, TinyMCE will handle the rest for us
		}
	} );

	// Editor blur
	editor.on( 'blur', function( event ) {
		var note_data,
			placeholder,
			note_type = editor.getParam( 'note_type' ),
			body = editor.getBody(),
			$body = $( editor.getBody() ),
			node = editor.selection.getNode(),
			$node = $( node );

		// Determine if previous node is empty and reset the placeholder
		// TODO: WP 4.0 doesn't like DOM.isEmpty (DOM.isEmpty( prev_node, { img : true } ) )
		if ( prev_node && $prev_node.length && ! $prev_node.text() && ! $prev_node.has( 'img' ).length ) {
			note_data = $prev_node.data( data_key );
			placeholder = ( note_data && note_data.hasOwnProperty( 'placeholder' ) ) ? note_data.placeholder : false;

			// If we have placeholder data
			if ( placeholder ) {
				// Reset placeholder (setTimeout fixes a bug where the previous node would remain empty when switching focus between editors)
				setTimeout( function() {
					// Reset the placeholder text
					$prev_node.html( DOM.decode( placeholder ) ).addClass( placeholder_el_class );
				}, 10 );

				// Note Widget update (setTimeout ensures placeholder element has finished inserting into element)
				setTimeout( function() {
					var content = editor.getContent(),
						// Deep copy
						data = $.extend( true, editor.note.widget_data, { widget: { content: content } } );

					// Trigger a Note Widget update event (after placeholder data has been put back into element)
					NotePreview.preview.send( 'note-widget-update', data );

					// Update the previous content reference
					editor.note.prev_content = content;
				}, 100 );
			}
		}

		// Only on media editors

		// TODO: WP 4.0 doesn't like DOM.isEmpty (DOM.isEmpty( editor.getBody(), { img : true } ) )
		if ( note_type && note_type === 'media' && ( ! $body.text() && ! $body.has( 'img' ).length ) ) {
			// Remove all content
			editor.setContent( '' );

			// Add CSS class to parent
			$el_parent.addClass( 'has-media-placeholder' );

			// Show the media panel
			media_panel.show();
		}
	} );


	/**********************
	 * Internal Functions *
	 **********************/

	/**
	 * Compares two nodes and checks if it's attributes and styles matches.
	 * This doesn't compare classes as items since their order is significant.
	 * We've modified this function to also compare the content of the nodes.
	 *
	 * Copyright, Moxiecode Systems AB
	 * Released under LGPL License.
	 *
	 * License: http://www.tinymce.com/license
	 * Contributing: http://www.tinymce.com/contributing
	 *
	 * @param node1
	 * @param node2
	 * @returns {boolean}
	 */
	function compareNodes(node1, node2) {
		// Not the same element (simple check first)
		if (node1 && node2 && node1 !== node2) {
			return false;
		}

		/**
		 * Returns all the nodes attributes excluding internal ones, styles and classes.
		 *
		 * @private
		 * @param {Node} node Node to get attributes from.
		 * @return {Object} Name/value object with attributes and attribute values.
		 */
		function getAttribs(node) {
			var attribs = {};

			tinymce.util.Tools.each(DOM.getAttribs(node), function(attr) {
				var name = attr.nodeName.toLowerCase();

				// Don't compare internal attributes or style
				if (name.indexOf('_') !== 0 && name !== 'style' && name !== 'data-mce-style') {
					attribs[name] = DOM.getAttrib(node, name);
				}
			});

			return attribs;
		}

		/**
		 * Compares two objects checks if it's key + value exists in the other one.
		 *
		 * @private
		 * @param {Object} obj1 First object to compare.
		 * @param {Object} obj2 Second object to compare.
		 * @return {boolean} True/false if the objects matches or not.
		 */
		function compareObjects(obj1, obj2) {
			var value, name;

			for (name in obj1) {
				// Obj1 has item obj2 doesn't have
				if (obj1.hasOwnProperty(name)) {
					value = obj2[name];

					// Obj2 doesn't have obj1 item
					if (typeof value == "undefined") {
						return false;
					}

					// Obj2 item has a different value
					if (obj1[name] != value) {
						return false;
					}

					// Delete similar value
					delete obj2[name];
				}
			}

			// Check if obj 2 has something obj 1 doesn't have
			for (name in obj2) {
				// Obj2 has item obj1 doesn't have
				if (obj2.hasOwnProperty(name)) {
					return false;
				}
			}

			return true;
		}

		// Attribs are not the same
		if (!compareObjects(getAttribs(node1), getAttribs(node2))) {
			return false;
		}

		// Styles are not the same
		if (!compareObjects(DOM.parseStyle(DOM.getAttrib(node1, 'style')), DOM.parseStyle(DOM.getAttrib(node2, 'style')))) {
			return false;
		}

		// Content (innerHTML) is not the same
		if( node1 && node2 && DOM.encode( node1.innerHTML ) !== DOM.encode( node2.innerHTML ) ) {
			return false;
		}

		return !tinymce.dom.BookmarkManager.isBookmarkNode(node1) && !tinymce.dom.BookmarkManager.isBookmarkNode(node2);
	}
} );