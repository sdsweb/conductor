/**
 * Conductor General Admin
 */
var conductor = conductor || {};

( function ( $ ) {
	"use strict";

	/**
	 * Document Ready
	 */
	$( function() {
		// TODO: Optimize the following
		/**
		 * Settings errors
		 *
		 * This functionality taken from /wp-admin/js/common.js and modified for H3s (smaller nav tabs).
		 */
		// Move .updated and .error alert boxes, don't move boxes designed to be inline, hide all boxes
		$( 'div.wrap h1:first' ).nextAll( 'div.updated, div.error' ).addClass( 'below-h1' );
		$( 'div.updated, div.error' ).not( '.below-h1, .inline' ).hide();

		// Only show errors associated with the Theme Options panel
		$( 'div.updated[id*="settings_updated"], div.updated[id*="conductor"], div.error[id*="conductor"]' ).show();
	} );
} )( jQuery );