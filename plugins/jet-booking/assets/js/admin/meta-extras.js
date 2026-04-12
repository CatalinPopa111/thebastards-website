( function() {
	'use strict';

	window.jetABAFPostMetaManager = {
		data: function() {
			return {
				meta: {},
				jqxhr: null,
				saving: false
			};
		},
		methods: {
			updateSetting: function( value ) {
				value = Object.assign( {}, this.meta, value );

				this.$set( this, 'meta', value );

				if( Object.keys( this.meta )[0] ){
					this.$nextTick( function() {
						this.saveSettings();
					} );
				}
			},
			saveSettings: function() {
				const self = this;

				self.saving = true;

				if ( self.jqxhr !== null ) {
					self.jqxhr.abort();
					self.jqxhr = null;
				}

				self.jqxhr = jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					dataType: "json",
					data: {
						action: this.meta.action,
						meta: this.meta,
						nonce: this.meta.nonce,
					},
				} ).done( function( response ) {
					self.$CXNotice.add( {
						message: response.data.message,
						type: response.success ? 'success' : 'error',
						duration: 7000,
					} );
				} ).fail( function( jqXHR, textStatus, errorThrown ) {
					if( 'abort' === textStatus ){
						return
					}

					self.$CXNotice.add( {
						message: errorThrown,
						type: 'error',
						duration: 7000,
					} );
				} ).always( function() {
					self.jqxhr = null;
					self.saving = false;
				} );
			}
		}
	}

	window.dateMethods = {
			methods: {
				parseDate: function ( date, format = 'MM DD YYYY' ) {
					return moment( date ).format( format );
				},
				timestampToDate: function ( timestamp, format = 'MM DD YYYY' ) {
					return moment.unix( timestamp ).utc().format( format );
				},
				timeToTimestamp: function ( time, format = 'hh:mm' ) {
					return moment( time, format ).valueOf() / 1000;
				},
				objectTimeToTimestamp: function ( date ) {
					return Date.UTC( date.getFullYear(), date.getMonth(), date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds() ) / 1000;
				},
				objectDateToTimestamp: function ( date ) {
					return Date.UTC( date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0 ) / 1000;
				},
				secondsToMilliseconds: function ( seconds ) {
					return seconds * 1000;
				}
			}
		};
} )();
