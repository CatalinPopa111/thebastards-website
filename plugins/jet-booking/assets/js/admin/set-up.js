(function () {

	"use strict";

	new Vue( {
		el: '#jet-abaf-set-up-page',
		template: '#jet-abaf-set-up',
		data: {
			isSet: window.JetABAFConfig.setup.is_set,
			isReset: window.JetABAFConfig.reset.is_reset,
			resetURL: window.JetABAFConfig.reset.reset_url,
			postTypes: window.JetABAFConfig.post_types,
			dbFields: window.JetABAFConfig.db_fields,
			hasWoocommerce: window.JetABAFConfig.has_woocommerce,
			currentStep: 0,
			lastStep: 4,
			loading: false,
			setupData: {
				booking_mode: 'plain',
				apartment_post_type: [],
				create_single_form: true,
			},
			log: false,
			additionalDBColumns: [],
			resetDialog: false
		},
		methods: {
			nextStep: function () {
				const self = this;

				if ( self.currentStep === self.lastStep || 'wc_based' === self.setupData.booking_mode ) {
					self.loading = true;

					jQuery.ajax( {
						url: ajaxurl,
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'jet_abaf_setup',
							setup_data: self.setupData,
							db_columns: self.additionalDBColumns,
							nonce: window?.JetABAFConfig?.nonce
						},
					} ).done( function ( response ) {
						self.loading = false;

						if ( response.success ) {
							self.currentStep = self.lastStep + 1;
							self.log = response.data;
						}
					} ).fail( function ( jqXHR, textStatus, errorThrown ) {
						self.loading = false;

						self.$CXNotice.add( {
							message: errorThrown,
							type: 'error',
							duration: 7000,
						} );
					} );
				} else {
					self.currentStep++;
				}
			},
			prevStep: function () {
				if ( this.currentStep ) {
					this.currentStep--;
				}
			},
			addNewColumn: function () {
				this.additionalDBColumns.push( {
					column: '',
					collapsed: false,
				} );
			},
			setColumnProp: function ( index, key, value ) {
				const col = this.additionalDBColumns[ index ];

				col[ key ] = value;

				this.additionalDBColumns.splice( index, 1, col );
			},
			cloneColumn: function ( index ) {
				this.additionalDBColumns.splice( index + 1, 0, {
					'column': this.additionalDBColumns[ index ].column + '_copy',
				} );
			},
			deleteColumn: function ( index ) {
				this.additionalDBColumns.splice( index, 1 );
			},
			isCollapsed: function ( object ) {
				return undefined === object.collapsed || true === object.collapsed;
			},
			goToReset: function () {
				window.location = this.resetURL;
			}
		}
	} );

})();
