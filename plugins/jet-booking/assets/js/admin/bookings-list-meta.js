( function ( $, bookingsListMetaData ) {
	'use strict';

	window.JetABAFUnits = new Vue( {
		el: '#jet_abaf_bookings_list_meta',
		template: '#jet-abaf-bookings-list-meta',
		data: {
			bookings: bookingsListMetaData.bookings,
			bookingsLink: bookingsListMetaData.bookings_link,
			isOrder: bookingsListMetaData.is_order
		},
		methods: {
			handleDelete: function ( id, index ) {
				if ( window.confirm( wp.i18n.__( 'Are you sure? Deleted booking can\'t be restored.', 'jet-booking' ) ) ) {
					const self = this;

					wp.apiFetch( {
						method: 'delete',
						path: bookingsListMetaData.api.delete_booking + id + '/',
					} ).then( function ( response ) {
						if ( ! response.success ) {
							self.$CXNotice.add( {
								message: response.data,
								type: 'error',
								duration: 7000,
							} );

							return;
						}

						self.bookings.splice( index, 1 );
					} ).catch( function ( error ) {
						self.$CXNotice.add( {
							message: error.message,
							type: 'error',
							duration: 7000,
						} );
					} );
				}
			},
			getInstanceLabel: function ( id ) {
				return bookingsListMetaData.booking_posts[ id ] || id;
			},
			getOrderLink: function ( id ) {
				return bookingsListMetaData.edit_link.replace( /\%id\%/, id );
			},
			getDetailsLink: function ( id ) {
				return this.bookingsLink + '&booking-details=' + id;
			},
		}
	} );
} )( jQuery, window.JetABAFBookingsListMetaData );