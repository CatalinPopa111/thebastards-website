(function () {

	"use strict";

	Vue.component( 'jet-abaf-calendars-list', {
		template: '#jet-abaf-calendars-list',
		data: function() {
			return {
				itemsList: [],
				totalItems: 0,
				offset: 0,
				perPage: 30,
				currentItem: false,
				currentIndex: false,
				editDialog: false,
				synchDialog: false,
				iCalTemplateDialog: false,
				iCalTemplate: {},
				bookingInstances: window.JetABAFConfig.bookings,
				isLoading: false,
				synchLog: null,
				submitting: false
			};
		},
		mounted: function() {
			this.getItems();
		},
		methods: {
			showEditDialog: function( item, index ) {
				this.editDialog   = true;
				this.currentItem  = JSON.parse( JSON.stringify( item ) );
				this.currentIndex = index;

				if ( ! this.currentItem.import_url ) {
					this.$set( this.currentItem, 'import_url', [] );
				}
			},
			showSynchDialog: function( item ) {
				const self = this;

				self.synchDialog = true;
				self.synchLog    = null;

				wp.apiFetch( {
					method: 'post',
					path: window.JetABAFConfig.api.synch_calendar,
					data: { item: item }
				} ).then( function( response ) {
					self.synchLog = response.success  ? response.result : response.data;
				} ).catch( function( e ) {
					self.synchLog = e.message;
				} );
			},
			showICalTemplateDialog: function (  ) {
				this.iCalTemplateDialog = true;
			},
			addURL: function() {
				this.currentItem.import_url.push( '' );
			},
			removeURL: function ( index ) {
				this.currentItem.import_url.splice( index, 1 );
			},
			handleEdit: function() {
				let self = this;

				self.editDialog = true;

				if ( ! self.currentItem ) {
					return;
				}

				self.submitting = true;

				self.itemsList.splice( self.currentIndex, 1, self.currentItem );

				wp.apiFetch( {
					method: 'post',
					path: window.JetABAFConfig.api.update_calendar,
					data: { item: self.currentItem }
				} ).then( function( response ) {
					if ( ! response.success ) {
						self.$CXNotice.add( {
							message: response.data,
							type: 'error',
							duration: 7000,
						} );
					} else {
						self.$CXNotice.add( {
							message: 'Done!',
							type: 'success',
							duration: 7000,
						} );

						self.getItems();
					}

					self.currentItem = false;
					self.currentIndex = false;
					self.editDialog = false;
					self.submitting = false;
				} ).catch( function( e ) {
					self.$CXNotice.add( {
						message: e.message,
						type: 'error',
						duration: 7000,
					} );

					self.currentItem = false;
					self.currentIndex = false;
					self.editDialog = false;
					self.submitting = false;
				} );

			},
			handleICalTemplate: function () {
				const self = this;

				self.iCalTemplateDialog = true;
				self.submitting = true;

				wp.apiFetch( {
					method: 'post',
					path: window.JetABAFConfig.api.update_ical_template,
					data: { template: { ...self.iCalTemplate } }
				} ).then( function() {
					self.$CXNotice.add( {
						message: 'Done!',
						type: 'success',
						duration: 7000,
					} );

					self.iCalTemplateDialog = false;
					self.submitting = false;
				} ).catch( function( e ) {
					self.$CXNotice.add( {
						message: e.message,
						type: 'error',
						duration: 7000,
					} );

					self.iCalTemplateDialog = false;
					self.submitting = false;
				} );
			},
			addMacros( property, value ) {
				let currentValue = this.iCalTemplate[ property ];

				if ( currentValue ) {
					const control = this.$refs[ property ];

					if ( control ) {
						const start = control.selectionStart;
						const end = control.selectionEnd;

						if ( 0 <= start ) {
							currentValue = currentValue.substring( 0, start ) + value + currentValue.substring( end, currentValue.length );
						} else {
							currentValue = currentValue + ' ' + value;
						}
					} else {
						currentValue = currentValue + ' ' + value;
					}
				} else {
					currentValue = value;
				}

				this.$set( this.iCalTemplate, property, currentValue );
			},
			getItems: function() {
				const self = this;

				self.isLoading = true;

				wp.apiFetch( {
					method: 'get',
					path: window.JetABAFConfig.api.calendars_list,
				} ).then( function( response ) {
					self.isLoading = false;

					if ( response.success ) {
						self.itemsList = response.data.calendars;
						self.iCalTemplate = response.data.ical_template;
					}
				} ).catch( function( e ) {
					self.isLoading = false;

					self.$CXNotice.add( {
						message: e.message,
						type: 'error',
						duration: 7000,
					} );
				} );
			}
		},
	} );

	Vue.component( 'jet-abaf-settings-macros-inserter', {
		template: '#jet-abaf-settings-macros-inserter',
		mixins: [ jetABAFMacrosInserter ],
		directives: { clickOutside: window.JetVueUIClickOutside },
	} );

	new Vue({
		el: '#jet-abaf-ical-page',
		template: '#jet-abaf-calendars',
		data: {
			isSet: window.JetABAFConfig.setup.is_set,
		}
	});

})();
