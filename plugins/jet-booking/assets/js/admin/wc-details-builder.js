(function( $, wcSettings ) {

	'use strict';

	window.JetABAFDetailsBuilder = new Vue( {
		el: '#jet_abaf_wc_details_builder_popup',
		template: '#jet-abaf-wc-details-builder',
		data: {
			saving: false,
			isActive: false,
			details: wcSettings.details,
			nonce: wcSettings.nonce,
			fieldsList: [],
		},
		created: function() {
			var self = this;

			self.fieldsList = JSON.parse( JSON.stringify( window.JEBookingFormBuilder.availableFields ) );

			$( document ).on( 'click', '#jet-booking-wc-details', function() {
				self.isActive = ! self.isActive;
			} );

		},
		methods: {
			moveItem: function( oldIndex, newIndex ) {

				var item = JSON.parse( JSON.stringify( this.details[ oldIndex ] ) ),
					replacedItem = JSON.parse( JSON.stringify( this.details[ newIndex ] ) );

				this.details.splice( newIndex, 1, item );
				this.details.splice( oldIndex, 1, replacedItem );

			},
			newItem: function() {
				this.details.push( {
					type: '',
					label: '',
				} );
			},
			deleteItem: function( index ) {
				if ( window.confirm( wcSettings.confirm_message ) ) {
					this.details.splice( index, 1 );
				}
			},
			save: function() {

				var self = this;

				self.saving = true;

				jQuery.ajax({
					url: window.ajaxurl,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'jet_booking_save_wc_details',
						post_id: wcSettings.apartment,
						nonce: self.nonce,
						details: self.details,
					},
				}).done( function( response ) {

					self.saving = false;

					if ( response.success ) {
						self.isActive = false;
					} else {
						alert( response.data.message );
					}

				} ).fail( function( jqXHR, textStatus, errorThrown ) {
					self.saving = false;
					alert( errorThrown );
				} );

			},
		}
	} );

})( jQuery, window.JetABAFWCDetails );