/**
 * Conductor Note Widget
 *
 * TODO: Remove in a future version
 */
var conductor_note = conductor_note || {}, conductor = conductor || {};

( function ( $ ) {
	"use strict";

	/**
	 * Document Ready
	 */
	$( function() {
		var $document = $( document );

		// If we have a default Note Widget template
		if ( conductor_note.hasOwnProperty( 'default_note_template' ) ) {
			// Conductor Note Widget Template change
			$document.on( 'change', '#widgets-right .widget select.conductor-note-template', function() {
				var $this = $( this ),
					$widget_parent = $this.parents( '.widget' ), // Get widget instance
					$conductor_note_rows = $widget_parent.find( 'p.conductor-note-rows' );

				// If the value is not the Note Widget default, show Conductor Note Widget Rows
				if ( $this.val() !== conductor_note.default_note_template ) {
					$conductor_note_rows.fadeIn( 100 );
				}
				// Otherwise hide Conductor Note Widget Rows
				else {
					$conductor_note_rows.fadeOut( 100 );
				}
			} );
		}
	} );
}( jQuery ) );