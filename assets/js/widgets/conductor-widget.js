/**
 * Conductor Widget
 */

// TODO: Future: Trigger Conductor specific events for all actions within a widget
// TODO: Future: Minify

var conductor = conductor || {};

( function ( $, wp, conductor_widget ) {
	"use strict";

	// Defaults
	if ( ! conductor_widget.hasOwnProperty( 'Backbone' ) ) {
		conductor_widget.Backbone = {
			Views: {},
			Models: {},
			Collections: {},
			instances: {
				current_view_for_ajax: false,
				models: {},
				collections: {},
				views: {}
			}
		};
	}

	// Default functions
	if ( ! conductor_widget.hasOwnProperty( 'fn' ) ) {
		conductor_widget.fn = {
			/**
			 * Backbone
			 */
			Backbone: {
				/**
				 * Views
				 */
				views: {
					/**
					 * This function gets a view instance based on the widget ID.
					 */
					get: function( widget_id ) {
						return conductor_widget.Backbone.instances.views[widget_id];
					}
				}
			}
		};
	}


	/************
	 * Backbone *
	 ************/
	
	/**
	 * Conductor Widget View
	 */
	conductor_widget.Backbone.Views.Conductor_Widget = wp.Backbone.View.extend( {
		/**
		 * HTML and body element reference
		 */
		$html_body: false,
		/**
		 * Events
		 */
		events: {
			// AJAX message close click
			'click .conductor-widget-ajax-message-close': 'hideMessage',
			// Page numbers click
			'click a.page-numbers': 'navigate'
		},
		/**
		 * Message Timer
		 */
		message_timer: -1,
		/**
		 * Message Timer Delay
		 */
		message_timer_delay: 5000,
		/**
		 * AJAX
		 *
		 * AJAX data and functions.
		 */
		ajax: {
			/*
			 * Data
			 */
			data: {
				/*
				 * Default AJAX data
				 */
				default: {
					conductor_widget: true,
					_wpnonce: conductor_widget.rest.nonce
				},
				/**
				 * This function gets the AJAX data
				 */
				get: function() {
					var data = this.ajax.data.default;

					// Add the pagenum link to the data
					data.pagenum_link = this.$el.data( 'pagenum-link' );

					// Add the is front page flag to the data
					data.is_front_page = this.flags.is_front_page;

					// Add the is single flag to the data
					data.is_single = this.flags.is_single;

					return data;
				}
			},
			/**
			 * AJAX Queue
			 */
			queue: {
				processing: false,
				current_item: {},
				current_request: false,
				items: [],
				/**
				 * This function adds an item to the queue for processing.
				 */
				addItem: function( item ) {
					// Add this item to the end of the queue
					this.ajax.queue.items.push( item );
				},
				/**
				 * This function processes the queue.
				 */
				process: function() {
					// Bail if we're already processing or there are no items to process
					if ( this.ajax.queue.processing || this.ajax.queue.items.length === 0 ) {
						// If we don't have any items to process
						if ( this.ajax.queue.items.length === 0 ) {
							// Reset the current request reference
							this.ajax.queue.current_request = false;

							// Reset the current item
							this.ajax.queue.current_item = {};

							// Set all active spinners to inactive
							this.ajax.setActiveSpinnersInactive.call( this );

							// If we're processing
							if ( this.ajax.queue.processing ) {
								// Reset the processing status flag
								this.ajax.queue.setProcessingStatusFlag.call( this, false );
							}
						}

						// Remove the AJAX processing CSS classes
						this.$el.removeClass( conductor_widget.css_classes.ajax.processing );

						// Trigger the processing event on the Conductor Widget Backbone view element
						this.$el.trigger( 'conductor-widget-ajax-processing', [ false, this ] );

						// Trigger the processing event on the Conductor Widget Backbone view
						this.trigger( 'conductor-widget-ajax-processing', false, this );

						// Reset the current view reference
						conductor_widget.Backbone.instances.current_view_for_ajax = false;

						return;
					}

					// Set all active spinners to inactive
					this.ajax.setActiveSpinnersInactive.call( this );

					// Hide the message
					this.hideMessage( false );

					// Add the AJAX processing CSS classes
					this.$el.addClass( conductor_widget.css_classes.ajax.processing );

					// Set the current view reference
					conductor_widget.Backbone.instances.current_view_for_ajax = this;

					// Trigger the processing event on the Conductor Widget Backbone view element
					this.$el.trigger( 'conductor-widget-ajax-processing', [ true, this ] );

					// Trigger the processing event on the Conductor Widget Backbone view
					this.trigger( 'conductor-widget-ajax-processing', true, this );

					// Reset the current request reference
					this.ajax.queue.current_request = false;

					// Set the processing status flag
					this.ajax.queue.setProcessingStatusFlag.call( this, true );

					// Setup the current item
					this.ajax.queue.current_item = this.ajax.queue.items.shift();

					// Process the current item
					this.ajax.queue.processCurrentItem.call( this );
				},
				/**
				 * This function processes the current item in the queue.
				 *
				 * @uses this.ajax.queue.current_item
				 */
				processCurrentItem: function() {
					var item = this.ajax.queue.current_item,
						data = {};

					// Switch based on action
					switch ( item.action ) {
						// Conductor Widget REST API Query
						case conductor_widget.actions.rest.query:
							// Setup the base URL
							data.url = conductor_widget.urls.rest.base + conductor_widget.urls.rest[conductor_widget.actions.rest.query];

							// Add the widget number to the URL
							data.url += item.number + '/';

							// If the item has paged data
							if ( item.paged ) {
								// Add the paged number to the URL
								data.url += item.paged + '/'
							}
						break;

						// Default
						default:
							// TODO: ?
						break;
					}

					// If this item has parameters
					if ( item.parameters ) {
						// Set the data
						data.data = item.parameters;
					}

					// Make the AJAX request (POST)
					this.ajax.queue.current_request = wp.ajax.send( data ).done( item.success ).fail( item.fail );
				},
				/**
				 * This function sets the processing status flag.
				 */
				setProcessingStatusFlag: function( status ) {
					this.ajax.queue.processing = ( status ) ? true : false;
				}
			},
			/**
			 * This function sets up AJAX data.
			 */
			setupData: function( data, action, event ) {
				// Defaults
				action = action || false;
				event = event || false;

				// Trigger the setup data event on the Conductor Widget Backbone view element
				this.$el.trigger( 'conductor-widget-ajax-setup-data', [ data, action, event, this ] );

				// Trigger the setup data event on the Conductor Widget Backbone view
				this.trigger( 'conductor-widget-ajax-setup-data', data, action, event, this );

				return $.extend( data, this.ajax.data.get.call( this ) );
			},
			/**
			 * This function displays an AJAX response message if it exists.
			 */
			displayResponseStatusMessage: function( response ) {
				var message = '';

				// If we have a message or we have an error and the error isn't a function
				if ( response.message || ( response.error && ! _.isFunction( response.error ) ) ) {
					// Set the message from the response
					message = ( response.message ) ? response.message : response.error;
				}
				// Otherwise we don't have a message or we don't have an error
				else {
					// Set the message to the generic error message
					message = conductor_widget.l10n.ajax.error;
				}

				// Show the message
				this.showMessage.call( this, message );
			},
			/**
			 * This function sets all active spinners to inactive.
			 */
			setActiveSpinnersInactive: function() {
				var $spinner = this.$el.find( conductor_widget.css_selectors.spinner ),
					$spinner_overlay = this.$el.find( conductor_widget.css_selectors.spinner_overlay );

				// Hide the spinner and spinner overlay
				$spinner.add( $spinner_overlay ).removeClass( conductor_widget.css_classes.spinner.active );
			},
			/**
			 * These functions run on successful AJAX requests.
			 */
			success: {
				/**
				 * This function runs on all successful AJAX requests.
				 */
				all: function( response ) {
					var $conductor_widget_content_after_wrap = this.$el.find( conductor_widget.css_selectors.content_after_wrap ),
						$conductor_widget_title_after_wrap = this.$el.find( conductor_widget.css_selectors.title_after_wrap ),
						$conductor_widget_after_wrap = this.$el.find( conductor_widget.css_selectors.after_wrap ),
						$conductor_widget_content_before_wrap = this.$el.find( conductor_widget.css_selectors.content_before_wrap ),
						$conductor_widget_title_before_wrap = this.$el.find( conductor_widget.css_selectors.title_before_wrap ),
						$conductor_widget_before_wrap = this.$el.find( conductor_widget.css_selectors.before_wrap ),
						$conductor_widget_content_wrap = this.$el.find( conductor_widget.css_selectors.content_wrap ),
						$conductor_widget_pagination_wrap = this.$el.find( conductor_widget.css_selectors.pagination_wrap );

					// Trigger the before "all" event on the Conductor Widget Backbone View element
					this.$el.trigger( 'conductor-widget-ajax-success-all-before', [ response, this ] );

					// Trigger the before "all" event on the Conductor Widget Backbone View
					this.trigger( 'conductor-widget-ajax-success-all-before', response, this );

					// If we have Conductor Widget before
					if ( response.hasOwnProperty( 'conductor_widget_before' ) && response.conductor_widget_before ) {
						// Replace the Conductor Widget before
						$conductor_widget_before_wrap.html( response.conductor_widget_before );
					}

					// If we have Conductor Widget before content
					if ( response.hasOwnProperty( 'conductor_widget_content_before' ) ) {
						// Replace the Conductor Widget before content
						$conductor_widget_content_before_wrap.html( response.conductor_widget_content_before );
					}

					// If we have Conductor Widget before title
					if ( response.hasOwnProperty( 'conductor_widget_title_before' ) ) {
						// Replace the Conductor Widget before title
						$conductor_widget_title_before_wrap.html( response.conductor_widget_title_before );
					}

					// If we have Conductor Widget after title
					if ( response.hasOwnProperty( 'conductor_widget_title_after' ) ) {
						// Replace the Conductor Widget after title
						$conductor_widget_title_after_wrap.html( response.conductor_widget_title_after );
					}

					// If we have Conductor Widget content
					if ( response.hasOwnProperty( 'conductor_widget_content' ) ) {
						// Replace the Conductor Widget content
						$conductor_widget_content_wrap.html( response.conductor_widget_content );
					}

					// If we have Conductor Widget after content
					if ( response.hasOwnProperty( 'conductor_widget_content_after' ) ) {
						// Replace the Conductor Widget after content
						$conductor_widget_content_after_wrap.html( response.conductor_widget_content_after );
					}

					// If we have Conductor Widget pagination
					if ( response.hasOwnProperty( 'conductor_widget_pagination' ) ) {
						// Replace the Conductor Widget pagination
						$conductor_widget_pagination_wrap.html( response.conductor_widget_pagination );
					}

					// If we have Conductor Widget after
					if ( response.hasOwnProperty( 'conductor_widget_after' ) ) {
						// Replace the Conductor Widget after
						$conductor_widget_after_wrap.html( response.conductor_widget_after );
					}

					// Trigger the after "all" event on the Conductor Widget Backbone View element
					this.$el.trigger( 'conductor-widget-ajax-success-all-after', [ response, this ] );

					// Trigger the after "all" event on the Conductor Widget Backbone View
					this.trigger( 'conductor-widget-ajax-success-all-after', response, this );

					// Create mock-up pagination elements
					this.createMockPaginationElements();

					// Reset the processing status flag
					this.ajax.queue.setProcessingStatusFlag.call( this, false );

					// Process the AJAX queue
					this.ajax.queue.process.call( this );
				},
				/**
				 * This function runs on a successful navigate AJAX request.
				 */
				navigate: function( response ) {
					var widget_id = response.widget_id,
						view = widget_id && conductor_widget.fn.Backbone.views.get( widget_id ) || false;

					// If we have a view
					if ( view ) {
						// Call the "all" success function
						view.ajax.success.all.call( view, response );
					}
				}
			},
			/**
			 * These functions run on a failed AJAX requests.
			 */
			fail: {
				/**
				 * This function runs on all failed AJAX requests.
				 */
				all: function( response ) {
					var hidden_css_classes = conductor_widget.css_classes.hide,
						first_hidden_css_class = hidden_css_classes.split( ' ' )[0],
						$page_numbers = this.$el.find( 'ul' + conductor_widget.css_selectors.pagination.page_numbers ),
						$individual_page_numbers = $page_numbers.length && $page_numbers.find( conductor_widget.css_selectors.pagination.page_numbers + ':not(' + conductor_widget.css_selectors.pagination.dots + ')' + ':not(' + conductor_widget.css_selectors.pagination.next + ')' + ':not(' + conductor_widget.css_selectors.pagination.previous + ')' ),
						$mock_page_numbers = $individual_page_numbers.length && $individual_page_numbers.filter( conductor_widget.css_selectors.pagination.conductor_mock ),
						$previous_page_number = $page_numbers.length && $page_numbers.find( conductor_widget.css_selectors.pagination.previous ),
						$next_page_number = $page_numbers.length && $page_numbers.find( conductor_widget.css_selectors.pagination.next );

					// If we have mock page numbers
					if ( $mock_page_numbers && $mock_page_numbers.length ) {
						// Loop through the mock page numbers
						$mock_page_numbers.each( function() {
							var $this = $( this ),
								is_hidden = $this.hasClass( first_hidden_css_class );

							// If this mock page number isn't hidden
							if ( ! is_hidden ) {
								// Remove the Conductor hide CSS classes and remove the "current" data on this page number
								$this.prev().removeClass( conductor_widget.css_classes.hide + ' ' + conductor_widget.css_classes.current ).data( conductor_widget.css_classes.current, false ).removeAttr( conductor_widget.css_classes.current );

								// Add the Conductor Hide CSS classes from the current page number Conductor mock element
								$this.addClass( conductor_widget.css_classes.hide );
							}
						} );
					}

					// If we have a previous page number and the previous page number has the Conductor hide CSS classes
					if ( $previous_page_number.length && $previous_page_number.hasClass( first_hidden_css_class ) ) {
						// Remove the Conductor hide CSS classes to the previous page number
						$previous_page_number.removeClass( conductor_widget.css_classes.hide );
					}

					// If we have a next page number and the next page number has the Conductor hide CSS classes
					if ( $next_page_number.length && $next_page_number.hasClass( first_hidden_css_class ) ) {
						// Remove the Conductor hide CSS classes to the next page number
						$next_page_number.removeClass( conductor_widget.css_classes.hide );
					}

					// Display fail message
					this.ajax.displayResponseStatusMessage.call( this, response );

					// Trigger the "all" event on the Conductor Widget Backbone view element
					this.$el.trigger( 'conductor-widget-ajax-fail-all', [ response, this ] );

					// Trigger the "all" event on the Conductor Widget Backbone view
					this.trigger( 'conductor-widget-ajax-fail-all', response, this );

					// Set the processing status flag
					this.ajax.queue.setProcessingStatusFlag.call( this, ( response.statusText && response.statusText === 'abort' ) );

					// Process the AJAX queue
					this.ajax.queue.process.call( this );
				},
				/**
				 * This function runs on a failed navigate AJAX request.
				 */
				navigate: function( response ) {
					var widget_id = response.widget_id,
						view = widget_id && conductor_widget.fn.Backbone.views.get( widget_id ) || conductor_widget.Backbone.instances.current_view_for_ajax || false;

					// If we have a view
					if ( view ) {
						// Call the "all" fail function
						view.ajax.fail.all.call( view, response );
					}
				}
			}
		},
		/**
		 * This function runs on initialization of the view.
		 */
		initialize: function( options ) {
			var $conductor_widget_content_wrap = this.$el.find( conductor_widget.css_selectors.content_wrap );

			// If we have the html and body element reference
			if ( options.$html_body ) {
				// Set the html and body element reference
				this.$html_body = options.$html_body;
			}

			// If we have a Conductor Widget content wrap
			if ( $conductor_widget_content_wrap.length ) {
				// Create the flags
				this.flags = {
					is_rest_api_enabled: $conductor_widget_content_wrap.data( 'is-rest-api-enabled' ),
					has_ajax: $conductor_widget_content_wrap.data( 'has-ajax' ),
					has_pagination:$conductor_widget_content_wrap.data( 'has-pagination' ),
					has_permalink_structure: $conductor_widget_content_wrap.data( 'has-permalink-structure' ),
					is_front_page: $conductor_widget_content_wrap.data( 'is-front-page' ),
					is_single: $conductor_widget_content_wrap.data( 'is-single' ),
					is_user_logged_in: conductor_widget.flags.is_user_logged_in
				};
			}

			// Bind this to functions
			_.bindAll(
				this,
				'navigate',
				'createMockPaginationElements'
			);

			// Create mock-up pagination elements
			this.createMockPaginationElements();
		},
		/**
		 * This function navigates to a page.
		 */
		navigate: function( event ) {
			// Bail if the Conductor REST API isn't enabled, this Conductor Widget isn't enabled in the Conductor REST API, or AJAX isn't enabled on this Conductor Widget
			if ( ! conductor_widget.rest.enabled || ! this.flags.is_rest_api_enabled || ! this.flags.has_ajax ) {
				return;
			}

			var $this = $( event.currentTarget ),
				paged_regex = new RegExp( '^' + conductor_widget.urls.current.permalink + '(?:.+)?((?:[\\/?&]|%3[Ff])(page[d]?)(?:[\\/=]|%3[Dd]))(\\d+)[\\/]?|^' + conductor_widget.urls.current.permalink + '[\\/](\\d+)[\\/]?$' ),
				$page_number,
				$page_numbers = $this.parents( conductor_widget.css_selectors.pagination.page_numbers ),
				$previous_page_number = $page_numbers.find( conductor_widget.css_selectors.pagination.previous ),
				$current_page_number = $page_numbers.find( conductor_widget.css_selectors.pagination.current ),
				$next_page_number = $page_numbers.find( conductor_widget.css_selectors.pagination.next ),
				$last_page_number = ( $next_page_number.length ) ? $next_page_number.parent().prev().find( conductor_widget.css_selectors.pagination.page_numbers ) : $page_numbers.find( 'a' + conductor_widget.css_selectors.pagination.page_numbers + ':not(' + conductor_widget.css_selectors.pagination.conductor_mock + ')' ).last(),
				last_parsed_url = $last_page_number.length && $last_page_number.attr( 'href' ).match( paged_regex ),
				last_paged = last_parsed_url && parseInt( last_parsed_url[3], 10 ),
				parsed_url = $this.attr( 'href' ).match( paged_regex ),
				paged = parsed_url && parseInt( ( parsed_url[4] ) ? parsed_url[4] : parsed_url[3], 10 ) || 1; // Default to 1

			// Prevent default
			event.preventDefault();

			// Add the Conductor hide CSS classes, and set the "current" data on the current page number
			$current_page_number.addClass( conductor_widget.css_classes.hide + ' ' + conductor_widget.css_classes.current ).data( conductor_widget.css_classes.current, true ).attr( conductor_widget.css_classes.current, 'true' );

			// Remove the Conductor Hide CSS classes from the current page number Conductor mock element
			$current_page_number.next( conductor_widget.css_selectors.pagination.conductor_mock ).removeClass( conductor_widget.css_classes.hide );

			// If this page number has the "next" or "previous" CSS classes
			if ( $this.hasClass( conductor_widget.css_classes.pagination.next ) || $this.hasClass( conductor_widget.css_classes.pagination.previous ) ) {
				// Set the page number to the correct page number
				$page_number = $page_numbers.find( '[href="' + $this.attr( 'href' ) + '"]' ).not( $this );

				// Set the $this reference
				$this = $page_number;

				// Set the page number to the Conductor mock element
				$page_number = $page_number.next( conductor_widget.css_selectors.pagination.conductor_mock );
			}
			// Otherwise this page number doesn't have the "next" or "previous" CSS classes
			else {
				// Set the page number to the Conductor mock element
				$page_number = $this.next( conductor_widget.css_selectors.pagination.conductor_mock );
			}

			// Add the "current" CSS class on the page number, remove the Conductor hide CSS classes from the page number
			$page_number.addClass( conductor_widget.css_classes.pagination.current ).removeClass( conductor_widget.css_classes.hide );

			// Add the Conductor hide CSS classes to this element
			$this.addClass( conductor_widget.css_classes.hide );

			// If paged is equal to 1 and we have a previous page number
			if ( paged === 1 && $previous_page_number.length ) {
				// Add the Conductor hide CSS classes to the previous page number
				$previous_page_number.addClass( conductor_widget.css_classes.hide );
			}

			// If we have a last paged and paged is equal to the last paged and we have a next page number
			if ( last_paged && paged === last_paged && $next_page_number.length ) {
				// Add the Conductor hide CSS classes to the next page number
				$next_page_number.addClass( conductor_widget.css_classes.hide );
			}

			// Query content
			this.query.call( this, {
				number: parseInt( this.$el.data( 'widget-number' ), 10 ),
				paged: paged,
				success: this.ajax.success.navigate,
				fail: this.ajax.fail.navigate,
			} );
		},
		/**
		 * This function creates mock pagination elements.
		 */
		createMockPaginationElements: function() {
			var $page_numbers = this.$el.find( 'ul' + conductor_widget.css_selectors.pagination.page_numbers ),
				$individual_page_numbers = $page_numbers.length && $page_numbers.find( conductor_widget.css_selectors.pagination.page_numbers + ':not(' + conductor_widget.css_selectors.pagination.dots + ')' + ':not(' + conductor_widget.css_selectors.pagination.next + ')' + ':not(' + conductor_widget.css_selectors.pagination.previous + ')' );

			// If we have individual page numbers
			if ( $individual_page_numbers && $individual_page_numbers.length ) {
				// Loop through the individual page numbers
				$individual_page_numbers.each( function() {
					var $this = $( this ),
						$parent = $this.parent(),
						tag_name = $this.prop( 'tagName' ),
						$mock_el;

					// Switch based on tag name
					switch ( tag_name ) {
						// Anchor
						case 'A':
							// Create the mock element
							$mock_el = $( '<span></span>' );
						break;

						// Span
						case 'SPAN':
							// Create the mock element
							$mock_el = $( '<a></a>' );
						break;
					}

					// If we have a mock element
					if ( $mock_el && $mock_el.length ) {
						// Add CSS classes to the mock element
						$mock_el.addClass( conductor_widget.css_classes.pagination.page_numbers + ' ' + conductor_widget.css_classes.pagination.conductor_mock + ' ' + conductor_widget.css_classes.hide );

						// Set the mock element HTML to this element HTML
						$mock_el.html( $this.html() );

						// Append the mock element to the parent
						$parent.append( $mock_el );
					}
				} );
			}
		},
		/**
		 * This function queries content for this widget through the Conductor REST API endpoint.
		 */
		query: function( item, parameters ) {
			// Defaults
			parameters = this.ajax.setupData.call( this, parameters || {}, conductor_widget.actions.rest.query );

			// Set the item action
			item.action = conductor_widget.actions.rest.query;

			// If we have parameters
			if ( ! _.isEmpty( parameters ) ) {
				// Set the item parameters
				item.parameters = parameters;
			}

			// Default to page 1
			item.paged = item.paged || 1;

			var offset = this.$el.offset(),
				scrollTop = ( offset.top - 20 ),
				$spinner = this.$el.find( conductor_widget.css_selectors.spinner ),
				$spinner_overlay = this.$el.find( conductor_widget.css_selectors.spinner_overlay );

			// Add item to the AJAX queue
			this.ajax.queue.addItem.call( this, item );

			// Process the AJAX queue
			this.ajax.queue.process.call( this );

			// TODO: Future: Turn this spinner logic into a function so that other add-ons can use it (e.g. Calendar Add-On mimics this logic when fetching events via the JSON API)
			// Show the spinner and spinner overlay
			$spinner.add( $spinner_overlay ).addClass( conductor_widget.css_classes.spinner.active );

			// If the user is logged in
			if ( this.flags.is_user_logged_in ) {
				// Subtract 32px from the scroll top value to account for the admin bar
				scrollTop -= 32;
			}

			// Ensure the scroll top value is valid
			scrollTop = ( scrollTop >= 0 ) ? scrollTop : 0;

			/*
			 * Scroll the html and body elements to the top of this element.
			 *
			 * We have to use the html and body elements due to different browser behavior.
			 */
			this.$html_body.animate( {
				scrollTop: scrollTop
			} );
		},
		/**
		 * This function shows Conductor Widget messages.
		 */
		showMessage: function( message ) {
			var self = this,
				message_timer_delay = this.message_timer_delay,
				$conductor_widget_ajax_message_wrap = this.$el.find( conductor_widget.css_selectors.ajax.message.wrap ),
				$conductor_widget_ajax_message_message = $conductor_widget_ajax_message_wrap.find( conductor_widget.css_selectors.ajax.message.message );

			// Set the message
			$conductor_widget_ajax_message_message.html( message );

			// Show the message
			$conductor_widget_ajax_message_wrap.addClass( conductor_widget.css_classes.ajax.message.active );

			// Determine the message timer delay
			message_timer_delay += ( message.length > 160 ) ? this.message_timer_delay : 0;
			message_timer_delay += ( message.length > 20 && message.length <= 160 ) ? ( this.message_timer_delay * ( ( message.length - 20 ) / 140 ) ) : 0;

			// Start the timer
			this.message_timer = setTimeout( function() {
				// Hide the message
				self.hideMessage( false );
			}, message_timer_delay );
		},
		/**
		 * This function hides Conductor Widget messages.
		 */
		hideMessage: function( event ) {
			var has_event = ( event && ! _.isEmpty( event ) ),
				$conductor_widget_ajax_message_wrap = this.$el.find( conductor_widget.css_selectors.ajax.message.wrap );

			// If we have an event
			if ( has_event ) {
				// Prevent default
				event.preventDefault();
			}

			// Hide the message
			$conductor_widget_ajax_message_wrap.removeClass( conductor_widget.css_classes.ajax.message.active );

			// Clear the message
			this.clearMessage.call( this );

			// Stop the timer
			clearTimeout( this.message_timer );
		},
		/**
		 * This function clears Conductor Widget messages.
		 */
		clearMessage: function() {
			var $conductor_widget_ajax_message_wrap = this.$el.find( conductor_widget.css_selectors.ajax.message.wrap ),
				$conductor_widget_ajax_message_message = $conductor_widget_ajax_message_wrap.find( conductor_widget.css_selectors.ajax.message.message );

			// Clear the message
			$conductor_widget_ajax_message_message.html( '' );
		}
	} );

	/**
	 * Document Ready
	 */
	$( function() {
		var $html = $( 'html' ),
			$body = $( 'body' ),
			$html_body = $html.add( $body ),
			$conductor_widgets = $( conductor_widget.css_selectors.widget_wrap );

		// If we have Conductor Widgets
		if ( $conductor_widgets.length ) {
			// Loop through the Conductor Widgets
			$conductor_widgets.each( function() {
				var $this = $( this ),
					id = $this.attr( 'id' ),
					widget_id = $this.data( 'widget-id' );

					// Create a new Conductor Widget view
					conductor_widget.Backbone.instances.views[widget_id] = new conductor_widget.Backbone.Views.Conductor_Widget( {
						el: '#' + id,
						$html_body: $html_body
					} );
			} );
		}

		/**
		 * This function runs when the Conductor Widget initialize event is triggered on the body element.
		 */
		// TODO: Future: If we have a view, destroy it first if we're forcing a new init
		$body.on( 'conductor-widget-init', function( event, $widgets, force ) {
			// Defaults
			$widgets = $widgets || $( conductor_widget.css_selectors.widget_wrap );
			force = force || false;

			// If we have widgets
			if ( $widgets.length ) {
				// Loop through the widgets
				$widgets.each( function() {
					var $this = $( this ),
						id = $this.attr( 'id' ),
						widget_id = $this.data( 'widget-id' ),
						view = conductor_widget.fn.Backbone.views.get( widget_id );

					// If we're forcing or we don't have a view
					if ( force || ! view ) {
						// Create a new Conductor Widget view
						conductor_widget.Backbone.instances.views[widget_id] = new conductor_widget.Backbone.Views.Conductor_Widget( {
							el: '#' + id,
							$html_body: $html_body
						} );
					}
				} );
			}
		} );
	} );
}( jQuery, window.wp, window.conductor_widget ) );