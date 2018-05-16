/**
 * Conductor Add-Ons
 */
var conductor = conductor || {};

// TODO: Future: Minify

( function ( $ ) {
	"use strict";

	var ConductorAddOns;

	// Defaults
	if ( ! conductor.hasOwnProperty( 'add_ons' ) ) {
		conductor.add_ons = {
			view: false,
			queue: {
				processing: false,
				current_item: {},
				items: []
			}
		};
	}

	/**
	 * Conductor Add-Ons View
	 */
	conductor.add_ons.view = Backbone.View.extend( {
		el: '.conductor-add-ons-wrapper',
		$body: false,
		$action_status_messages: false,
		action_status_messages_css_class: 'conductor-add-on-action-status-messages',
		$active_add_on: false,
		active_add_on_css_class: 'conductor-add-on',
		$active_button: false,
		add_on_status_css_class: 'conductor-add-on-status',
		active_button_css_classes: '',
		active_button_type: 'primary',
		button_css_classes: {
			primary: 'button-primary',
			secondary: 'button-secondary',
			disabled: 'disabled',
			processing: 'processing',
			installing: 'installing',
			activating: 'activating',
			deactivating: 'deactivating'
		},
		button_label_css_class: 'button-label',
		flags: {
			require_filesystem_credentials: false
		},
		events : {
			'click .install-add-on': 'installSingleAddOn',
			'click .activate-add-on': 'activateSingleAddOn',
			'click .deactivate-add-on': 'deactivateSingleAddOn'
		},
		/**
		 * Queue
		 * 
		 * A queue for actions performed with add-ons, such as installing, activating and deactivating.
		 */
		queue: {
			/**
			 * This function adds an item to the queue for processing.
			 */
			addItem: function( item ) {
				// Add this item to the end of the queue
				conductor.add_ons.queue.items.push( item );

				// Return this for chaining
				return this;
			},
			/**
			 * This function processes the queue.
			 */
			process: function() {
				// Bail if we're already processing or there are no items to process
				if ( conductor.add_ons.queue.processing || conductor.add_ons.queue.items.length === 0 ) {
					return;
				}

				// Set the processing status flag
				this.setProcessingStatusFlag( true );

				// Setup the current item
				conductor.add_ons.queue.current_item = conductor.add_ons.queue.items.shift();

				// Process the current item
				this.processCurrentItem();

				// Return this for chaining
				return this;
			},
			/**
			 * This function processes the current item in the queue
			 * 
			 * @uses conductor.add_ons.queue.current_item
			 */
			processCurrentItem: function() {
				var item = conductor.add_ons.queue.current_item;

				// Setup view references
				ConductorAddOns.setupViewReferences( item.$button, item.button_css_classes );

				// Clear any previously set action status messages
				ConductorAddOns.clearActiveAddOnActionStatusMessages();

				// Make the AJAX request (POST)
				wp.ajax.post( item.action, item.data ).done( ConductorAddOns.ajax.success ).fail( ConductorAddOns.ajax.fail );

				// Return this for chaining
				return this;
			},
			/**
			 * This function sets the processing status flag
			 */
			setProcessingStatusFlag: function( status ) {
				conductor.add_ons.queue.processing = ( status ) ? true : false;

				// Return this for chaining
				return this;
			}
		},
		/**
		 * AJAX
		 *
		 * AJAX data and functions.
		 */
		ajax: {
			// Default AJAX data
			data: {
				conductor: 1
			},
			/**
			 * This function sets up AJAX data from button HTML5 data attributes.
			 */
			setupDataFromButton: function( $button ) {
				return $.extend( {
					nonce: $button.data( 'nonce' ),
					nonce_action: $button.data( 'nonce-action' ),
					plugin_basename: $button.data( 'plugin-basename' ),
					plugin_slug: $button.data( 'plugin-slug' ),
					plugin_name: $button.data( 'plugin-name' )
				}, this.data );
			},
			/**
			 * This function runs on a successful AJAX request.
			 */
			success: function( response ) {
				// If the response isn't an object
				if ( ! _.isObject( response ) && conductor.hasOwnProperty( 'add_ons_l10n' ) ) {
					// Use the default success message
					response = {
						message: conductor.add_ons_l10n.success
					}
				}

				// Update the active button with response data
				ConductorAddOns.updateActiveButton( response, 'success' );

				// Add the success action status message
				ConductorAddOns.addActionStatusMessageToActiveAddOn( response.message, 'success' );

				// Update the active add-on status
				if ( _.isObject( response ) && response.hasOwnProperty( 'status' ) ) {
					ConductorAddOns.updateActiveAddOnStatus( response.status.message, response.status.css_class );
				}

				// Reset the view references
				ConductorAddOns.setupViewReferences( false, '', true );

				// Process queue
				ConductorAddOns.queue.setProcessingStatusFlag( false ).process();
			},
			/**
			 * This function runs on a failed AJAX request.
			 */
			fail: function( response ) {
				// If the response isn't an object
				if ( ! _.isObject( response ) && conductor.hasOwnProperty( 'add_ons_l10n' ) ) {
					// Use the default error message
					response = {
						error: conductor.add_ons_l10n.fail
					}
				}

				// Add the failed action status message
				ConductorAddOns.addActionStatusMessageToActiveAddOn( response.error );

				// Update the active button with response data
				ConductorAddOns.updateActiveButton( response, 'fail' );

				// Reset the view references
				ConductorAddOns.setupViewReferences( false, '', true );

				// Process queue
				ConductorAddOns.queue.setProcessingStatusFlag( false ).process();
			}
		},
		/**
		 * This function runs on initialization of the view.
		 */
		initialize: function( options ) {
			// Bind this to functions
			_.bindAll(
				this,
				'installSingleAddOn',
				'activateSingleAddOn',
				'deactivateSingleAddOn'
			);

			/*
			 * Setup element references
			 */
			this.$body = $( 'body' ); // Body element
			this.$action_status_messages = this.$el.find( '.' + this.action_status_messages_css_class ); // Action status messages

			/*
			 * Setup flags
			 */
			this.flags.require_filesystem_credentials = ( this.$body.find( '#request-filesystem-credentials-dialog' ).length !== 0 ); // Require filesystem credentials
		},
		/**
		 * This function installs a single add-on.
		 */
		installSingleAddOn: function( event ) {
			var $this = $( event.currentTarget ),
				button_css_classes = this.button_css_classes.disabled + ' ' + this.button_css_classes.processing + ' ' + this.button_css_classes.installing,
				data = this.ajax.setupDataFromButton( $this );

			// Prevent default
			event.preventDefault();

			// Bail if this button is disabled or if filesystem credentials required (let WordPress handle the install)
			if ( $this.hasClass( this.button_css_classes.disabled ) || this.flags.require_filesystem_credentials ) {
				return;
			}

			// Setup button for processing
			this.setupButtonForProcessing( $this, button_css_classes, $this.data( 'processing-button-label' ) );

			// Clear action messages
			this.clearActionStatusMessagesFromAddOn( $this );

			// Add item to queue and process
			this.queue.addItem( {
				$button: $this,
				button_css_classes: button_css_classes,
				action: 'conductor-add-ons-install-single',
				data: data
			} ).process();
		},
		/**
		 * This function activates a single add-on.
		 */
		activateSingleAddOn: function( event ) {
			var $this = $( event.currentTarget ),
				button_css_classes = this.button_css_classes.disabled + ' ' + this.button_css_classes.processing + ' ' + this.button_css_classes.activating,
				data = this.ajax.setupDataFromButton( $this );

			// Prevent default
			event.preventDefault();

			// Bail if this button is disabled
			if ( $this.hasClass( this.button_css_classes.disabled ) ) {
				return;
			}

			// Setup button for processing
			this.setupButtonForProcessing( $this, button_css_classes, $this.data( 'processing-button-label' ) );

			// Clear action messages
			this.clearActionStatusMessagesFromAddOn( $this );

			// Add item to queue and process
			this.queue.addItem( {
				$button: $this,
				button_css_classes: button_css_classes,
				action: 'conductor-add-ons-activate-single',
				data: data
			} ).process();
		},
		/**
		 * This function deactivates a single add-on.
		 */
		deactivateSingleAddOn: function( event ) {
			var $this = $( event.currentTarget ),
				button_css_classes = this.button_css_classes.disabled + ' ' + this.button_css_classes.processing + ' ' + this.button_css_classes.deactivating,
				data = this.ajax.setupDataFromButton( $this );

			// Prevent default
			event.preventDefault();

			// Bail if this button is disabled
			if ( $this.hasClass( this.button_css_classes.disabled ) ) {
				return;
			}

			// Setup button for processing
			this.setupButtonForProcessing( $this, button_css_classes, $this.data( 'processing-button-label' ) );

			// Clear action messages
			this.clearActionStatusMessagesFromAddOn( $this );

			// Add item to queue and process
			this.queue.addItem( {
				$button: $this,
				button_css_classes: button_css_classes,
				action: 'conductor-add-ons-deactivate-single',
				data: data
			} ).process();
		},
		/**
		 * This function updates the active button within the view. It toggles the CSS classes, adjusts the label,
		 * sets the button type, and various data attributes based on parameters.
		 */
		updateActiveButton: function( response, type ) {
			// Toggle the active button CSS classes
			this.$active_button.toggleClass( this.active_button_css_classes );

			// Switch based on type
			switch ( type ) {
				// Success
				case 'success':
					// Set button label (attribute and data)
					this.$active_button.find( '.' + this.button_label_css_class ).html( this.$active_button.data( 'success-label' ) ).attr( 'data-label', this.$active_button.data( 'success-label' ) ).data( 'label', this.$active_button.data( 'success-label' ) );

					// Set the button CSS class (attribute and data)
					this.$active_button.toggleClass( this.$active_button.data( 'css-class' ) + ' ' + this.$active_button.data( 'success-css-class' ) ).attr( 'data-css-class', this.$active_button.data( 'success-css-class' ) ).data( 'css-class', this.$active_button.data( 'success-css-class' ) );

					// Set the button type (CSS Class; attribute and data)
					if ( this.$active_button.data( 'button-type' ) !== this.$active_button.data( 'success-button-type' ) ) {
						this.$active_button.toggleClass( this.$active_button.data( 'button-type' ) + ' ' + this.$active_button.data( 'success-button-type' ) ).attr( 'data-button-type', this.$active_button.data( 'success-button-type' ) ).data( 'button-type', this.$active_button.data( 'success-button-type' ) );
					}

					// Set the button action (attribute and data)
					this.$active_button.attr( 'data-action', this.$active_button.data( 'success-action' ) ).data( 'action', this.$active_button.data( 'success-action' ) );

					// If we have attribute data in the response
					if ( response.attributes ) {
						// Loop through attributes
						for ( var key in response.attributes ) {
							// hasOwnProperty
							if ( response.attributes.hasOwnProperty( key ) ) {
								// Switch based on key
								switch ( key ) {
									// Data (data attributes)
									case 'data':
										// Loop through data attributes
										for ( var data_key in response.attributes[key] ) {
											// hasOwnProperty
											if ( response.attributes[key].hasOwnProperty( data_key ) ) {
												// Set attribute and data
												this.$active_button.attr( 'data-' + data_key, response.attributes[key][data_key] ).data( data_key, response.attributes[key][data_key] );
											}
										}
									break;

									// Default (regular attributes)
									default:
										// Set attribute
										this.$active_button.attr( key, response.attributes[key] );
									break;
								}

							}
						}
					}
				break;

				// Fail
				case 'fail':
					// Reset button label
					this.$active_button.find( '.' + this.button_label_css_class ).html( this.$active_button.data( 'label' ) );
				break;
			}

			// Reset "global" references
			this.$active_button = false;
			this.active_button_css_classes = '';
		},
		/**
		 * This function clears add-on action status messages from an add-on (based on action button).
		 */
		clearActionStatusMessagesFromAddOn: function( $button ) {
			$button.parents( '.' + this.active_add_on_css_class ).find( '.' + this.action_status_messages_css_class ).html( '' );
		},
		/**
		 * This function clears add-on action status messages from the current active add-on (based on action button).
		 */
		clearActiveAddOnActionStatusMessages: function() {
			this.$active_add_on.find( '.' + this.action_status_messages_css_class ).html( '' );
		},
		/**
		 * This function adds an action status messages to the active add-on (based on action button).
		 */
		addActionStatusMessageToActiveAddOn: function( message, type ) {
			type = type || 'fail';

			// If we have a message
			if ( message ) {
				this.$active_add_on.find( '.' + this.action_status_messages_css_class ).append( '<p class="' + type + '">' + message + '</p>' );
			}
		},
		/**
		 * This function updates the active add-on status based on parameters.
		 */
		updateActiveAddOnStatus: function( message, css_class ) {
			this.$active_add_on.find( '.' + this.add_on_status_css_class + ' p' ).html( message ).removeClass().addClass( css_class );
		},
		/**
		 * This function sets up a button for processing based on parameters
		 */
		setupButtonForProcessing: function( $button, css_classes, label ) {
			label = label || '';

			// Setup the CSS classes
			$button.toggleClass( css_classes );

			// Set the label value
			$button.find( '.' + this.button_label_css_class ).html( label );
		},
		/**
		 * This function sets "global" (global within the scope of this view) references on the view.
		 */
		setupViewReferences: function( $button, active_button_css_classes, reset ) {
			reset = reset || false;

			// Store this button in the "global" reference
			this.$active_button = ( ! reset ) ? $button : false;

			// Store the CSS classes in the "global" reference
			this.active_button_css_classes = ( ! reset ) ? active_button_css_classes : '';

			// Store this add-on in the "global" reference
			this.$active_add_on = ( ! reset ) ? $button.parents( '.' + this.active_add_on_css_class ) : false;
		}
	} );

	/**
	 * Document Ready
	 */
	$( function() {
		// Init Conductor Add-Ons
		ConductorAddOns = new conductor.add_ons.view();
	} );
} )( jQuery );