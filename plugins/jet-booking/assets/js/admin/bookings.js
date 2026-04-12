(function () {

	"use strict";

	const eventHub = new Vue();
	const { __, sprintf } = wp.i18n;
	const buildQuery = function ( params ) {
		return Object.keys( params ).map( function ( key ) {
			return key + '=' + params[ key ];
		} ).join( '&' );
	}

	window.jetBookingState = {
		isActive: false
	};

	const getCurrentMode = function () {
		let mode = window.location.hash ? window.location.hash.replace( '#', '' ) : false;

		if ( ! mode ) {
			return 'all';
		}

		return [ 'all', 'upcoming', 'past' ].includes( mode ) ? mode : 'all';
	}

	const statusMixin = {
		computed: Vuex.mapState( ['statuses_schema'] ),
		methods: {
			isFinished: function ( status ) {
				return ( 0 <= this.statuses_schema.finished.indexOf( status ) );
			},
			isInProgress: function ( status ) {
				return ( 0 <= this.statuses_schema.in_progress.indexOf( status ) );
			},
			isInvalid: function ( status ) {
				return ( 0 <= this.statuses_schema.invalid.indexOf( status ) );
			},
			statusClass: function ( status ) {
				return {
					'notice': true,
					'notice-alt': true,
					'notice-success': this.isFinished( status ),
					'notice-warning': this.isInProgress( status ),
					'notice-error': this.isInvalid( status )
				}
			},
		}
	};

	const itemsMethods = {
		computed: Vuex.mapState( [ 'bookings', 'bookings_units' ] ),
		methods: {
			getItemLabel: function ( item_id ) {
				return this.bookings[ item_id ] || item_id;
			},
			getItemUnitLabel: function ( item_id, unit_id ) {
				if ( ! unit_id ) {
					return;
				}

				if ( this.bookings_units[ item_id ] && this.bookings_units[ item_id ][ unit_id] ) {
					return this.bookings_units[ item_id ][ unit_id ] + ' (#' + unit_id + ')';
				} else {
					return unit_id;
				}
			},
			getOrderLink: function ( orderID ) {
				return window.JetABAFConfig.edit_link.replace( /\%id\%/, orderID );
			}
		},
	}

	// Mixin for handling booking fields.
	const fieldsManager = {
		data: function () {
			return {
				apartmentConfig: false,
				oldApartmentConfig: false
			};
		},
		computed: Vuex.mapState( {
			bookingItem: 'bookingItem',
			dateRangePickerConfig: 'dateRangePickerConfig',
			isDisabled: 'isDisabled',
			timeLoading: 'timeLoading',
			itemUnits: 'itemUnits',
			bookingPrice: 'bookingPrice',
			guestsSettings: 'guestsSettings',
			timepicker: state => state.timepicker,
			timepickerSlots: state => state.timepicker_slots,
		} ),
		methods: {
			initDateRangePicker: function () {
				let self = this;

				store.dispatch( 'getDateRangePickerConfig' ).then( function () {
					if ( jQuery( self.$refs.jetABAFDatePicker ).data( 'dateRangePicker' ) ) {
						jQuery( self.$refs.jetABAFDatePicker ).data( 'dateRangePicker' ).destroy();
					}

					jQuery( self.$refs.jetABAFDatePicker ).dateRangePicker( self.dateRangePickerConfig )
						.bind( 'datepicker-first-date-selected', () => {
							window.jetBookingState.isActive = true;
						} ).bind( 'datepicker-change', () => {
							window.jetBookingState.isActive = false;

							self.getTimepickerSlots();
						} );
				} );

				self.getTimepickerSlots();
			},
			getTimepickerSlots: function() {
				let self = this;

				if ( ! self.timepicker ) {
					return;
				}

				store.commit( 'setValue', {
					key: 'timeLoading',
					value: true
				} );

				jQuery.ajax( {
					url: window.JetABAFConfig.ajax_url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'jet_booking_get_available_timepicker_slots',
						item: self.bookingItem,
						nonce: window?.JetABAFConfig?.nonce
					},
				} ).done( function ( response ) {
					store.commit( 'setValue', {
						key: 'timepicker_slots',
						value: response.data
					} );

					store.commit( 'setValue', {
						key: 'timeLoading',
						value: false
					} );
				} ).fail( function ( _, _2, errorThrown ) {
					alert( errorThrown );
				} );
			},
			getBookingPrice: function ( booking ) {
				jQuery.ajax( {
					url: window.JetABAFConfig.ajax_url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'jet_booking_product_get_total_price',
						booking: booking,
						nonce: window?.JetABAFConfig?.nonce
					},
				} ).done( function ( response ) {
					store.commit( 'setValue', {
						key: 'bookingPrice',
						value: response.data.price
					} );
				} ).fail( function ( _, _2, errorThrown ) {
					alert( errorThrown );
				} );
			},
			getApartmentConfig: function ( id ) {
				return new Promise( ( resolve, reject ) => {
					jQuery.ajax( {
						url: window.JetABAFConfig.ajax_url,
						type: 'POST',
						dataType: 'json',
						data: {
							action: 'jet_booking_get_apartment_config',
							postId: id,
							nonce: window?.JetABAFConfig?.nonce
						},
					} ).done( function ( response ) {
						resolve( response.data.apartment_config );
					} ).fail( function ( _, _2, errorThrown ) {
						reject( errorThrown );
					} );
				} );
			},
			hasGuestsSettings: function() {
				return Object.keys( this.guestsSettings ).length;
			},
			getGuestsRange: function( start, stop, step ) {
				return Array.from( { length: ( stop - start ) / step + 1 }, ( _, i) => start + i * step );
			},
			validateEmail: function( email ) {
				const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				return emailPattern.test( email );
			},
			beVisible: function ( key ) {
				switch ( key ) {
					case 'booking_id':
					case 'booking_vendor':
					case 'status':
					case 'apartment_id':
					case 'apartment_unit':
					case 'check_in_date':
					case 'check_in_date_timestamp':
					case 'check_in_time':
					case 'check_out_date':
					case 'check_out_date_timestamp':
					case 'check_out_time':
					case 'user_id':
					case 'user_email':
					case 'order_id':
					case 'import_id':
					case 'attributes':
					case '__guests':
						return false;
					default:
						return true;
				}
			}
		}
	};

	const attributesManager = {
		computed: Vuex.mapState( {
			attributesList: 'attributesList'
		} ),
		methods: {
			hasAttributesList: function() {
				return Object.keys( this.attributesList ).length;
			},
			updateAttribute: function ( _, item ) {
				if ( ! +item.apartment_id || ! item.check_in_date.length || ! item.check_out_date.length ) {
					return false;
				}

				this.getBookingPrice( item );

				return true;
			}
		}
	};

	const store = new Vuex.Store( {
		state: {
			...window.JetABAFConfig,
			pageUrl: new URL( window.location.href ),
			perPage: 15,
			offset: 0,
			pageNumber: 1,
			totalItems: 0,
			itemsList: [],
			isLoading: true,
			overlappingBookings: false,
			bookingItem: {},
			bookingPrice: 0,
			dateRangePickerConfig: {},
			isDisabled: false,
			timeLoading: false,
			itemUnits: [],
			currentFilters: {},
			attributesList: {},
			itemAttributes: {},
			guestsSettings: {},
			sortBy: {
				orderby: 'booking_id',
				order: 'DESC'
			},
			currentView: 'list',
			views: {
				list: {
					label: __('List', 'jet-booking')
				},
				calendar: {
					label: __('Calendar', 'jet-booking')
				},
				timeline: {
					label: __('Timeline', 'jet-booking')
				},
			}
		},
		mutations: {
			setValue ( state, varObject ) {
				state[ varObject.key ] = varObject.value;
			},
		},
		actions: {
			getItems: function () {
				store.commit( 'setValue', {
					key: 'isLoading',
					value: true
				} );

				wp.apiFetch( {
					method: 'get',
					path: window.JetABAFConfig.api.bookings_list + '?' + buildQuery( {
						per_page: 'list' === store.state.currentView ? store.state.perPage : 0,
						offset: 'list' === store.state.currentView ? store.state.offset : 0,
						filters: JSON.stringify( store.state.currentFilters ),
						sort: JSON.stringify( store.state.sortBy ),
						mode: getCurrentMode(),
						view: store.state.currentView
					} ),
				} ).then( function ( response ) {
					if ( response.success ) {
						store.commit( 'setValue', {
							key: 'itemsList',
							value: response.data
						} );

						store.commit( 'setValue', {
							key: 'totalItems',
							value: +response.total
						} );
					}
				} ).catch( function ( e ) {
					eventHub.$CXNotice.add( {
						message: e.message,
						type: 'error',
						duration: 7000,
					} );
				} ).finally( function () {
					store.commit( 'setValue', {
						key: 'isLoading',
						value: false
					} );
				} );
			},
			getDateRangePickerConfig: async function () {

				store.commit( 'setValue', {
					key: 'isDisabled',
					value: true
				} );

				const bookingItem = store.state.bookingItem;

				if ( ! bookingItem.check_in_date ) {
					bookingItem.check_in_date = '';
				}

				if ( ! bookingItem.check_out_date ) {
					bookingItem.check_out_date = '';
				}

				await wp.apiFetch( {
					method: 'post',
					path: window.JetABAFConfig.api.booking_config,
					data: { item: bookingItem }
				} ).then( function ( response ) {
					if ( ! response.success ) {
						eventHub.$CXNotice.add( {
							message: response.data,
							type: 'error',
							duration: 7000,
						} );
					} else {
						const {
							per_nights: perNights,
							booked_dates: excludedDates,
							booked_next: excludedNext,
							disabled_days: disabledDays,
							check_in_days: checkInDays,
							checkout_only: checkoutOnly,
							labels: labels,
							start_day_offset: startDayOffset,
							end_date: rangeEndDate,
							min_days: minDays,
							max_days: maxDays,
							month_select: monthSelect,
							year_select: yearSelect
						} = response;

						window.JetABAFConfig = { ...window.JetABAFConfig, ...response };

						if ( bookingItem.check_in_date.length && bookingItem.check_out_date.length && 0 <= excludedDates.indexOf( bookingItem.check_in_date ) ) {
							let deleteCount = moment( bookingItem.check_out_date ).diff( moment( bookingItem.check_in_date ), 'days' );

							if ( ! perNights ) {
								deleteCount++;
							}

							excludedDates.splice( excludedDates.indexOf( bookingItem.check_in_date ), deleteCount );
							excludedDates.push( ...response.days_off )
						}

						let config = {
							autoClose: true,
							separator: ' - ',
							startDate: new Date(),
							startOfWeek: response.start_of_week,
							getValue: function () {
								if ( bookingItem.check_in_date && bookingItem.check_out_date ) {
									return bookingItem.check_in_date + ' - ' + bookingItem.check_out_date;
								} else {
									return '';
								}
							},
							setValue: function ( s, s1, s2 ) {
								if ( s === s1 ) {
									s2 = s1;
								}

								bookingItem.check_in_date = s1;
								bookingItem.check_out_date = s2;
							},
							minDays: minDays.length && +minDays ? +minDays : '',
							maxDays: maxDays.length && +maxDays ? +maxDays : '',
							perNights: perNights,
							container: '.jet-abaf-details__booking-dates',
							beforeShowDay: function ( t ) {
								const formatted = moment( t ).format('YYYY-MM-DD');

								let valid = true,
									_class = '',
									_tooltip = '';

								if ( disabledDays.length && 0 <= disabledDays.indexOf( t.getDay() ) ) {
									const disabledNext = moment( t ).add( 1, 'd' ).format('YYYY-MM-DD');

									if ( ! ( 0 <= excludedNext.indexOf( disabledNext ) ) ) {
										excludedNext.push( disabledNext );
									}

									if ( ! ( 0 <= excludedDates.indexOf( formatted ) ) ) {
										excludedDates.push( formatted );
									}

									valid = false;
								}

								if ( checkInDays.length && -1 === checkInDays.indexOf( t.getDay() ) ) {
									valid = window.jetBookingState.isActive && -1 === disabledDays.indexOf( t.getDay() );
								}

								if ( excludedDates.length && 0 <= excludedDates.indexOf( formatted ) ) {
									valid = false;
									_tooltip = response.custom_labels ? labels.booked : __( 'Sold out', 'jet-booking' );

									// Mark first day of booked period as checkout only
									if ( checkoutOnly ) {
										let next = moment( t ).add( 1, 'd' ).format('YYYY-MM-DD'),
											prev = moment( t ).subtract( 1, 'd' ).format('YYYY-MM-DD');

										if ( 0 <= excludedNext.indexOf( next ) || ( 0 <= excludedDates.indexOf( next ) && -1 === excludedDates.indexOf( prev ) ) ) {
											if ( window.jetBookingState.isActive ) {
												valid = true;
												_tooltip = '';
											} else {
												_class = 'only-checkout';
												_tooltip = response.custom_labels ? labels[ 'only-checkout' ] : __( 'Only checkout', 'jet-booking' );
											}
										}
									}
								}

								// If is single night booking - exclude next day for checkout only days.
								if ( checkoutOnly && window.jetBookingState.isActive && 0 <= excludedNext.indexOf( formatted ) ) {
									valid = false;
									_tooltip = response.custom_labels ? labels.booked : __( 'Sold out', 'jet-booking' );
								}

								return window.JetPlugins.hooks.applyFilters( 'jet-booking.date-range-picker.date-show-params', [ valid, _class, _tooltip ], t );
							},
							selectForward: true,
							monthSelect: monthSelect,
							yearSelect: yearSelect
						};

						if ( startDayOffset.length && +startDayOffset ) {
							config.startDate = moment().add( +startDayOffset, 'd' );
						}

						if ( rangeEndDate ) {
							config.endDate = moment( +rangeEndDate, 'X' ).format('YYYY-MM-DD');
						}

						if ( response.custom_labels ) {
							jQuery.dateRangePickerLanguages[ 'custom' ] = labels;
							config.language = 'custom';
						}

						if ( response.weekly_bookings && ! disabledDays.length ) {
							config.batchMode = 'week';
							config.showShortcuts = false;

							if ( response.week_offset ) {
								config.weekOffset = Number( response.week_offset );
							}
						} else if ( response.one_day_bookings ) {
							config.singleDate = true;
						}

						store.commit( 'setValue', {
							key: 'dateRangePickerConfig',
							value: window.JetPlugins.hooks.applyFilters( 'jet-booking.input.config', config )
						} );

						store.commit( 'setValue', {
							key: 'isDisabled',
							value: false
						} );

						if ( response?.guests_settings ) {
							store.commit( 'setValue', {
								key: 'guestsSettings',
								value: response.guests_settings
							} );
						}

						if ( response?.attributes_list ) {
							store.commit( 'setValue', {
								key: 'attributesList',
								value: response.attributes_list
							} );
						}

						store.commit( 'setValue', {
							key: 'itemUnits',
							value: response.units.length ? response.units.map( unit => ({
								value: unit.unit_id,
								label: unit.unit_title
							}) ) : []
						} );

						store.commit( 'setValue', {
							key: 'timepicker',
							value: response.timepicker
						} );
					}
				} ).catch( function( e ) {
					eventHub.$CXNotice.add( {
						message: e.message,
						type: 'error',
						duration: 7000,
					} );
				} );

			}
		}
	} );

	Vue.component( 'jet-abaf-bookings-list', {
		template: '#jet-abaf-bookings-list',
		mixins: [ statusMixin, itemsMethods ],
		data: function () {
			return {
				currentSort: 'booking_id'
			};
		},
		computed: {
			...Vuex.mapState( {
				pageUrl: 'pageUrl',
				sortBy: 'sortBy',
				itemsList: 'itemsList',
				perPage: 'perPage',
				offset: 'offset',
				pageNumber: 'pageNumber',
				totalItems: 'totalItems',
				statuses: state => state.all_statuses
			} )
		},
		watch: {
			itemsList( value ) {
				if ( this.pageUrl.searchParams.has( 'booking-details' ) ) {
					const bookingItem = value.find( item => +item.booking_id === +this.pageUrl.searchParams.get( 'booking-details' ) ) || false;

					if ( bookingItem ) {
						eventHub.$emit( 'call-popup', {
							item: bookingItem,
							state: 'info',
						} );

						this.pageUrl.searchParams.delete( 'booking-details' );
						window.history.pushState(null, '', this.pageUrl.toString());
					}
				}
			}
		},
		methods: {
			sortColumn: function ( column ) {
				this.currentSort = column;

				store.commit( 'setValue', {
					key: 'sortBy',
					value: {
						orderby: column,
						order: "DESC" === this.sortBy.order ? "ASC" : "DESC"
					}
				} );

				store.dispatch( 'getItems' );
			},
			classColumn: function ( column ) {
				return {
					'jet-abaf-active-column': column === this.currentSort,
					'jet-abaf-active-column-asc': column === this.currentSort && "DESC" === this.sortBy.order,
					'jet-abaf-active-column-desc': column === this.currentSort && "ASC" === this.sortBy.order
				};
			},
			changePage: function ( page ) {
				store.commit( 'setValue', {
					key: 'offset',
					value: this.perPage * ( page - 1 )
				} );

				store.commit( 'setValue', {
					key: 'pageNumber',
					value: page
				} );

				store.dispatch( 'getItems' );
			},
			callPopup: function ( state = false, item = false ) {
				eventHub.$emit( 'call-popup', {
					item: item,
					state: state
				} );
			}
		},
	} );

	Vue.component( 'jet-abaf-bookings-filter', {
		template: '#jet-abaf-bookings-filter',
		mixins: [ fieldsManager ],
		components: {
			vuejsDatepicker: window.vuejsDatepicker,
		},
		data () {
			return {
				currentMode: 'all',
				expandFilters: false,
				dateFormat: 'dd/MM/yyyy',
				showExportPopup: false,
				exportType: 'all',
				exportFormat: 'csv',
				exportDataReturnType: 'id',
				exportDateFormat: 'Y-m-d',
			}
		},
		computed: {
			...Vuex.mapState( [ 'filters', 'currentFilters', 'currentView', 'monday_first', 'export_nonce', 'export_url' ] ),
		},
		created: function () {
			this.currentMode = getCurrentMode();
		},
		methods: {
			setMode ( mode ) {
				window.location.hash = '#' + mode;
				this.currentMode = mode;

				store.commit( 'setValue', {
					key: 'offset',
					value: 0
				} );

				store.commit( 'setValue', {
					key: 'pageNumber',
					value: 1
				} );

				store.dispatch( 'getItems' );
			},
			modeButtonStyle ( mode ) {
				return this.currentMode === mode ? 'accent' : 'link-accent';
			},
			updateFilters: function ( value, name, type ) {
				let filterValue = value.target ? value.target.value : value;
				let currentFilter = {};

				if ( 'date-picker' === type ) {
					filterValue = value ? moment( filterValue ).format( 'MMMM DD YYYY' ) : '';
				}

				if ( filterValue.length ) {
					currentFilter = { [ name ]: filterValue };
				} else {
					delete this.currentFilters[ name ];
				}

				store.commit( 'setValue', {
					key: 'currentFilters',
					value: { ...this.currentFilters, ...currentFilter }
				} );

				store.commit( 'setValue', {
					key: 'offset',
					value: 0
				} );

				store.commit( 'setValue', {
					key: 'pageNumber',
					value: 1
				} );

				store.dispatch( 'getItems' );
			},
			clearFilter: function () {
				store.commit( 'setValue', {
					key: 'currentFilters',
					value: {}
				} );

				store.commit( 'setValue', {
					key: 'offset',
					value: 0
				} );

				store.commit( 'setValue', {
					key: 'pageNumber',
					value: 1
				} );

				store.dispatch( 'getItems' );
			},
			isVisible ( id, filter, type ) {
				if ( type !== filter.type ) {
					return false;
				}

				if ( 'select' === filter.type && ! Object.keys( filter.value ).length ) {
					return false;
				}

				if ( 'date-picker' === filter.type && 'list' !== this.currentView ) {
					return false;
				}

				return true;
			},
			prepareObjectForOptions: function ( input ) {
				let result = [ {
					'value': '',
					'label': __( 'Select...', 'jet-booking' ),
				} ];

				for ( const value in input ) {
					if ( input.hasOwnProperty( value ) ) {
						result.push( {
							'value': value,
							'label': input[ value ],
						} );
					}
				}

				return result;
			},
			doExport () {
				let urlParts = {
					type: this.exportType,
					format: this.exportFormat,
					return: this.exportDataReturnType,
					date_format: this.exportDateFormat,
					nonce: this.export_nonce,
				};

				if ( 'filtered' === this.exportType ) {
					urlParts = {
						...urlParts,
						...{
							filters: JSON.stringify( store.state.currentFilters ),
							sort: JSON.stringify( store.state.sortBy ),
							per_page: 0,
							mode: getCurrentMode(),
						}
					};
				}

				window.location = this.export_url + '&' + buildQuery( urlParts );
			},
		}
	} );

	Vue.component( 'jet-abaf-bookings-view', {
		template: '#jet-abaf-bookings-view',
		computed: { ...Vuex.mapState( [ 'pageUrl', 'currentFilters', 'currentView', 'views' ] ) },
		methods: {
			viewButtonStyle ( view ) {
				return this.currentView === view ? 'accent' : 'link-accent';
			},
			updateView: function ( view ) {
				store.commit( 'setValue', {
					key: 'currentView',
					value: view
				} );

				this.pageUrl.searchParams.set( 'view', view );
				this.pageUrl.hash = getCurrentMode();

				window.history.pushState( null, '', this.pageUrl.toString() );

				if ( 'calendar' === view ) {
					return;
				}

				store.commit( 'setValue', {
					key: 'currentFilters',
					value: {
						...this.currentFilters,
						check_in_date: 'list' === view  ? '' : moment().startOf( 'month' ).unix(),
						check_out_date: 'list' === view  ? '' : moment().endOf( 'month' ).unix(),
						date: ''
					}
				} );

				store.dispatch( 'getItems' );
			},
		},
	} );

	Vue.component('jet-abaf-bookings-calendar', {
		template: '#jet-abaf-bookings-calendar',
		mixins: [ dateMethods, statusMixin, itemsMethods ],
		data() {
			return {
				masks: { weekdays: 'WWW' },
				maxItemInCell: 3,
				timer: null
			}
		},
		components: {
			vCalendar: window.vCalendar,
		},
		computed: Vuex.mapState( {
			currentFilters: state => state.currentFilters,
			dayIndex: state => state.monday_first ? 2 : 1,
			itemsList: function ( state ) {
				const self = this;

				return state.itemsList.map( function ( item ) {
					return {
						key: item.booking_id,
						customData: { ...item },
						dates: self.createRange( item.check_in_date_timestamp, item.check_out_date_timestamp )
					}
				} );
			},
			pageUrl: 'pageUrl'
		} ),
		methods: {
			changePeriod: function ( page ) {
				const periodStart = moment( { year: page.year, month: page.month - 1 } ).startOf( 'month' ).unix();
				const periodEnd = moment( { year: page.year, month: page.month - 1 } ).endOf( 'month' ).unix();

				store.commit( 'setValue', {
					key: 'currentFilters',
					value: { ...this.currentFilters, check_in_date: periodStart, check_out_date: periodEnd, date: '' }
				} );

				if ( this.timer ) {
					clearTimeout( this.timer );
				}

				this.timer = setTimeout( () => {
					store.dispatch( 'getItems' );
				}, 500 );
			},
			callPopup: function ( state = false, item = false ) {
				eventHub.$emit( 'call-popup', {
					item: item,
					state: state
				} );
			},
			createRange: function ( start, end, step = 86400 ) {
				const range = [ this.timestampToDate( start, "MMM DD YYYY" ) ];

				if ( this.timestampToDate( start, "MMM DD YYYY" ) === this.timestampToDate( end, "MMM DD YYYY" ) ) {
					return range;
				}

				let newItem = +start;

				while ( newItem < +end ) {
					range.push( this.timestampToDate( newItem += step, "MMM DD YYYY" ) );
				}

				return range;
			},
			getRemainingItemCount: function ( attributes ) {
				return attributes && attributes.length > this.maxItemInCell ? attributes.length - this.maxItemInCell : 0;
			},
			showMore: function ( day ) {
				const date = this.objectTimeToTimestamp( new Date( day.year, day.month -1, day.day ) );

				store.commit( 'setValue', {
					key: 'currentFilters',
					value: { ...this.currentFilters, date: date }
				} );

				store.commit( 'setValue', {
					key: 'currentView',
					value: 'list'
				} );

				this.pageUrl.searchParams.set( 'view', 'list' );
				window.history.pushState( null, '', this.pageUrl.toString() );

				store.dispatch( 'getItems' );
			},
			mouseEnter: function ( event ) {
				jQuery( '.jet-abaf-calendar-day-booking' ).each( function() {
					if ( jQuery( event.target ).data( 'booking-id' ) === jQuery( this ).data( 'booking-id' ) ) {
						jQuery( this ).find( '.jet-abaf-booking-data' ).addClass( 'active' );
					}
				} );
			},
			mouseLeave: function ( event ) {
				jQuery( '.jet-abaf-calendar-day-booking' ).each( function() {
					if ( jQuery( event.target ).data( 'booking-id' ) === jQuery( this ).data( 'booking-id' ) ) {
						jQuery( this ).find( '.jet-abaf-booking-data' ).removeClass( 'active' );
					}
				} );
			},
		},
	});

	Vue.component( 'jet-abaf-bookings-timeline', {
		template: '#jet-abaf-bookings-timeline',
		mixins: [ dateMethods, itemsMethods, statusMixin ],
		components: {
			vuejsDatepicker: window.vuejsDatepicker,
		},
		data() {
			return {
				dateFormat: 'MMMM yyyy',
				selectedDate: moment().format("MMMM YYYY")
			}
		},
		computed: {
			startTime: function () {
				return moment( this.selectedDate, 'MMMM YYYY' ).startOf( 'month' ).format( 'YYYY-MM-DD' );
			},
			endTime: function () {
				return moment( this.selectedDate, 'MMMM YYYY' ).endOf( 'month' ).format( 'YYYY-MM-DD' );
			},
			...Vuex.mapState( {
				itemsList: function ( state ) {
					const self = this;
					const itemsList = [];

					state.itemsList.map( function ( item ) {
						const start = self.timestampToDate( item.check_in_date_timestamp, 'YYYY-MM-DD' );
						const end = self.timestampToDate( item.check_out_date_timestamp, 'YYYY-MM-DD' );

						itemsList.push( {
							id: item.booking_id,
							gtArray: [ {
								start: moment( start, 'YYYY-MM-DD' ).startOf( 'day' ).format( 'YYYY-MM-DD HH:mm:ss' ),
								end: moment( end, 'YYYY-MM-DD' ).endOf( 'day' ).format( 'YYYY-MM-DD HH:mm:ss' ),
								customData:{ ...item }
							} ]
						} );
					} );

					return itemsList;
				},
				currentFilters: state => state.currentFilters,
			} )
		},
		methods: {
			changePeriod: function ( date ) {
				this.$refs.gantt.scrollToPostionHandle( { x:0, y:0 } );

				store.commit( 'setValue', {
					key: 'currentFilters',
					value: {
						...this.currentFilters,
						check_in_date: moment( date ).startOf( 'month' ).unix(),
						check_out_date: moment( date ).endOf( 'month' ).unix()
					}
				} );

				store.dispatch( 'getItems' );
			},
			callPopup: function ( state = false, item = false ) {
				eventHub.$emit( 'call-popup', {
					item: item,
					state: state
				} );
			}
		},
	} );

	Vue.component( 'jet-abaf-add-new-booking', {
		template: '#jet-abaf-add-new-booking',
		mixins: [ fieldsManager, attributesManager ],
		data: function () {
			return {
				addDialog: false,
				newItem: {
					status: '',
					apartment_id: '',
					check_in_date: '',
					check_in_time: '',
					check_out_date: '',
					check_out_time: '',
					user_email: '',
					attributes: {},
					__guests: ''
				},
				dateMomentFormat: 'DD-MM-YYYY',
				createRelatedOrder: '',
				relatedOrderID: null,
				submitting: false
			}
		},
		computed: {
			...Vuex.mapState( {
				bookingMode: state => state.booking_mode,
				bookingInstances: state => state.instances_list,
				orderPostType: state => state.order_post_type,
				statuses: state => state.all_statuses,
				wcIntegration: state => state.wc_integration,
				overlappingBookings: 'overlappingBookings',
				fields: function ( state ) {
					return [ ...state.columns, ...state.additional_columns ];
				}
			} ),
			computedNewItem: function () {
				return Object.assign( {}, this.newItem );
			}
		},
		watch: {
			computedNewItem: {
				handler: async function ( value, oldValue ) {
					if ( ! Object.keys( value ).length || ! Object.keys( oldValue ).length ) {
						return;
					}

					if ( ! value.apartment_id.length || ! value?.check_in_date?.length || ! value?.check_out_date?.length ) {
						return;
					}

					if ( value.apartment_id !== oldValue.apartment_id ) {
						this.apartmentConfig = await this.getApartmentConfig( value.apartment_id );
						this.oldApartmentConfig = await this.getApartmentConfig( oldValue.apartment_id );

						if ( this.apartmentConfig || ! this.apartmentConfig && this.oldApartmentConfig ) {
							this.newItem.check_in_date = '';
							this.newItem.check_out_date = '';

							this.getBookingPrice( this.newItem );

							return;
						}
					}

					if (
						value.apartment_id !== oldValue.apartment_id
						|| value.check_in_date && value.check_in_date !== oldValue.check_in_date
						|| value.check_out_date && value.check_out_date !== oldValue.check_out_date
						|| value.__guests && +value.__guests !== +oldValue.__guests
					) {
						this.getBookingPrice( value );
					}
				},
				deep: true,
			},
			attributesList( value ) {
				for ( const key in value ) {
					this.newItem.attributes[ key ] = [];
				}
			},
			guestsSettings( value ) {
				this.newItem.__guests = value.min;
			},
			timepickerSlots( value ) {
				this.setInitialTimeValues( value );
			}
		},
		mounted: function () {
			this.setInitialTimeValues( this.timepickerSlots );
		},
		methods: {
			showAddDialog: function () {
				this.addDialog = true;

				store.commit( 'setValue', {
					key: 'overlappingBookings',
					value: false
				} );

				store.commit( 'setValue', {
					key: 'isDisabled',
					value: true
				} );

				store.commit( 'setValue', {
					key: 'bookingPrice',
					value: 0
				} );

				store.commit( 'setValue', {
					key: 'bookingItem',
					value: this.newItem
				} );

				if ( this.newItem.apartment_id.length ) {
					this.initDateRangePicker();
					this.getBookingPrice( this.newItem );
				} else {
					this.getTimepickerSlots();
				}
			},
			checkRequiredFields: function () {
				let requiredFields = [ 'status', 'apartment_id', 'check_in_date', 'check_out_date', 'user_email' ],
					emptyFields = [],
					invalidFields = [],
					message = '';

				for ( let field of requiredFields ) {
					if ( ! this.newItem[ field ] || ! this.newItem[ field ].length ) {
						switch ( field ) {
							case 'status':
								emptyFields.push( 'Status' );
								break;
							case 'apartment_id':
								emptyFields.push( 'Booking item' );
								break;
							case 'check_in_date':
								emptyFields.push( 'Check in date' );
								break;
							case 'check_out_date':
								emptyFields.push( 'Check out date' );
								break;
							case 'user_email':
								emptyFields.push( 'User E-mail' );
								break;
							default:
								emptyFields.push( field );
								break;
						}
					}
				}

				if ( this.timepicker ) {
					if ( ! this.newItem.check_in_time || ! this.newItem.check_in_time.length) {
						emptyFields.push( 'Check in time' );
					}

					if ( ! this.newItem.check_out_time || ! this.newItem.check_out_time.length) {
						emptyFields.push( 'Check out time' );
					}
				}

				if ( ! this.validateEmail( this.newItem.user_email ) ) {
					invalidFields.push( 'User E-mail' );
				}

				if ( emptyFields.length ) {
					message = sprintf( __( 'Empty fields: %s.', 'jet-booking' ), emptyFields.join( ', ' ) );
				} else if ( invalidFields.length ) {
					message = sprintf( __( 'Invalid value fields: %s.', 'jet-booking' ), invalidFields.join( ', ' ) );
				}

				if ( message.length ) {
					eventHub.$CXNotice.add( {
						message: message,
						type: 'error',
						duration: 7000,
					} );

					return false;
				}

				return true;
			},
			handleAdd: function () {
				let self = this;

				self.addDialog = true;

				if ( ! self.checkRequiredFields() ) {
					return;
				}

				store.commit( 'setValue', {
					key: 'overlappingBookings',
					value: false
				} );

				const data = {
					item: self.newItem
				};

				if ( self.createRelatedOrder || [ 'new', 'existing' ].includes( self.createRelatedOrder ) ) {
					if ( 'existing' === self.createRelatedOrder ) {
						data.relatedOrder = {
							orderID: self.relatedOrderID
						};
					}

					data.createRelatedOrder = true;
				}

				self.submitting = true;

				wp.apiFetch( {
					method: 'post',
					path: window.JetABAFConfig.api.add_booking,
					data: data
				} ).then( function ( response ) {
					if ( ! response.success ) {
						eventHub.$CXNotice.add( {
							message: response.data,
							type: 'error',
							duration: 7000,
						} );

						if ( response.overlapping_bookings ) {
							store.commit( 'setValue', {
								key: 'overlappingBookings',
								value: response.html
							} );

							self.initDateRangePicker();
						}

						self.submitting = false;

						return;
					} else {
						self.addDialog = false;

						eventHub.$CXNotice.add( {
							message: 'Done!',
							type: 'success',
							duration: 7000,
						} );

						store.dispatch( 'getItems' );
					}

					store.commit( 'setValue', {
						key: 'timepicker_slots',
						value: window.JetABAFConfig.timepicker_slots
					} );

					self.newItem = {
						status: '',
						apartment_id: '',
						check_in_date: '',
						check_in_time: self.timepickerSlots?.check_in_slots[0],
						check_out_date: '',
						check_out_time: self.timepickerSlots?.check_out_slots[0],
						attributes: {},
						__guests: ''
					};

					self.createRelatedOrder = '';
					self.relatedOrderID = null;
					self.submitting = false;
					self.apartmentConfig = false;
					self.oldApartmentConfig = false;
				} ).catch( function ( e ) {
					eventHub.$CXNotice.add( {
						message: e.message,
						type: 'error',
						duration: 7000,
					} );
				} );
			},
			cancelPopup: function () {
				this.addDialog = false;

				store.commit( 'setValue', {
					key: 'guestsSettings',
					value: {}
				} );

				store.commit( 'setValue', {
					key: 'attributesList',
					value: {}
				} );
			},
			setInitialTimeValues( timeSlots ) {
				this.newItem.check_in_time = timeSlots?.check_in_slots[0];
				this.newItem.check_out_time = timeSlots?.check_out_slots[0];
			}
		}
	} );

	Vue.component( 'jet-abaf-popup', {
		template: '#jet-abaf-popup',
		mixins: [ fieldsManager, itemsMethods, statusMixin, attributesManager ],
		data: function () {
			return {
				calculateTotals: false,
				currentItem: false,
				editDialog: false,
				isShow: false,
				popUpState: '',
				recalculateTotals: false,
				submitting: false
			};
		},
		computed: {
			...Vuex.mapState( {
				overlappingBookings: 'overlappingBookings',
				bookingInstances: state => state.instances_list,
				bookingMode: state => state.booking_mode,
				statuses: state => state.all_statuses,
				itemAttributes: 'itemAttributes'
			} ),
			computedCurrentItem: function () {
				return Object.assign( {}, this.currentItem );
			}
		},
		watch: {
			computedCurrentItem: {
				handler: async function ( value, oldValue ) {
					if ( ! Object.keys( value ).length || ! Object.keys( oldValue ).length ) {
						return;
					}

					if ( value.apartment_id !== oldValue.apartment_id ) {
						this.apartmentConfig = await this.getApartmentConfig( value.apartment_id );
						this.oldApartmentConfig = await this.getApartmentConfig( oldValue.apartment_id );

						if ( this.apartmentConfig || ! this.apartmentConfig && this.oldApartmentConfig ) {
							this.currentItem.check_in_date = '';
							this.currentItem.check_out_date = '';

							this.getBookingPrice( this.currentItem );

							return;
						}
					}

					if ( ! this.currentItem?.check_in_date?.length || ! this.currentItem?.check_out_date?.length ) {
						return;
					}

					if (
						+value.apartment_id !== +oldValue.apartment_id
						|| value.check_in_date && new Date( value.check_in_date ).toDateString() !== new Date( oldValue.check_in_date ).toDateString()
						|| value.check_out_date && new Date( value.check_out_date ).toDateString() !== new Date( oldValue.check_out_date ).toDateString()
					) {
						this.currentItem.check_in_time = this.timepickerSlots?.check_in_slots[0];
						this.currentItem.check_out_time = this.timepickerSlots?.check_out_slots[0];

						if ( 'wc_based' === this.bookingMode ) {
							this.recalculateTotals = true;
						}

						this.getBookingPrice( value );
					}

					if ( value.__guests && +value.__guests !== +oldValue.__guests ) {
						if ( 'wc_based' === this.bookingMode ) {
							this.recalculateTotals = true;
						}

						this.getBookingPrice( value );
					}
				},
				deep: true,
			},
			attributesList( value ) {
				if ( this.currentItem?.attributes ) {
					for ( const key in value ) {
						if ( ! this.currentItem.attributes.hasOwnProperty( key ) ) {
							this.currentItem.attributes[ key ] = [];
						}
					}
				}
			},
			guestsSettings( value ) {
				if ( this.currentItem?.__guests ) {
					if ( +this.currentItem.__guests < +value.min ) {
						this.currentItem.__guests = value.min;
					} else if ( +this.currentItem.__guests > +value.max ) {
						this.currentItem.__guests = value.max;
					}
				} else if ( this.currentItem ) {
					this.currentItem.__guests = value.min;
				}
			},
			editDialog ( value ) {
				if ( ! value ) {
					this.currentItem = false;
					this.apartmentConfig = false;
					this.oldApartmentConfig = false;
					this.recalculateTotals = false;
					this.calculateTotals = false;
				}
			}
		},
		mounted: function () {
			eventHub.$on( 'call-popup', this.callPopup );
			eventHub.$on( 'cancel-popup', this.cancelPopup );
		},
		methods: {
			callPopup: function ( { state, item } ) {
				this.isShow = true;
				this.popUpState = state;
				this.currentItem = item;

				if ( 'info' === state ) {
					this.getBookingPrice( item );

					if ( 'wc_based' === this.bookingMode ) {
						this.getAttributes();
					}
				} else if ( 'update' === state ) {
					this.openUpdatePopup( item );
				}
			},
			cancelPopup: function () {
				this.isShow = false ;
				this.popUpState = '';
				this.currentItem = false;
				this.apartmentConfig = false;
				this.oldApartmentConfig = false;
				this.recalculateTotals = false;

				store.commit( 'setValue', {
					key: 'guestsSettings',
					value: {}
				} );

				store.commit( 'setValue', {
					key: 'attributesList',
					value: {}
				} );

				store.commit( 'setValue', {
					key: 'itemAttributes',
					value: {}
				} );
			},
			updateDetailsItem: function ( item ) {
				this.popUpState = 'update';
				this.openUpdatePopup( item );
			},
			openUpdatePopup: function ( item ) {
				this.editDialog = true;

				store.commit( 'setValue', {
					key: 'overlappingBookings',
					value: false
				} );

				this.currentItem = JSON.parse( JSON.stringify( item ) );
				this.currentItem.check_in_date = moment.unix( this.currentItem.check_in_date_timestamp ).utc().format( 'YYYY-MM-DD' );
				this.currentItem.check_out_date = moment.unix( this.currentItem.check_out_date_timestamp ).utc().format( 'YYYY-MM-DD' );

				if ( item.hasOwnProperty( 'attributes' ) ) {
					this.currentItem.attributes = { ...item.attributes };
				}

				store.commit( 'setValue', {
					key: 'bookingPrice',
					value: 0
				} );

				store.commit( 'setValue', {
					key: 'bookingItem',
					value: this.currentItem
				} );

				this.initDateRangePicker();
				this.getBookingPrice( this.currentItem );
			},
			checkRequiredFields: function () {
				let emptyFields = [],
					invalidFields = [],
					message = '';

				if ( ! this.currentItem.check_in_date.length) {
					emptyFields.push( 'Check in date' );
				}

				if ( ! this.currentItem.check_out_date.length ) {
					emptyFields.push( 'Check out date' );
				}

				if ( this.timepicker ) {
					if ( ! this.currentItem.check_in_time || ! this.currentItem.check_in_time.length) {
						emptyFields.push( 'Check in time' );
					}

					if ( ! this.currentItem.check_out_time || ! this.currentItem.check_out_time.length) {
						emptyFields.push( 'Check out time' );
					}
				}

				if ( this.currentItem.user_email?.length && ! this.validateEmail( this.currentItem.user_email ) ) {
					invalidFields.push( 'User E-mail' );
				}

				if ( emptyFields.length ) {
					message = sprintf( __( 'Empty fields: %s.', 'jet-booking' ), emptyFields.join( ', ' ) );
				} else if ( invalidFields.length ) {
					message = sprintf( __( 'Invalid value fields: %s.', 'jet-booking' ), invalidFields.join( ', ' ) );
				}

				if ( message.length ) {
					eventHub.$CXNotice.add( {
						message: message,
						type: 'error',
						duration: 7000,
					} );

					return false;
				}

				return true;
			},
			updateItem: function () {
				let self = this;

				self.editDialog = true;

				if ( ! self.currentItem ) {
					return;
				}

				if ( ! self.checkRequiredFields() ) {
					return;
				}

				store.commit( 'setValue', {
					key: 'overlappingBookings',
					value: false
				} );

				if ( ! self.itemUnits.length ) {
					self.currentItem.apartment_unit = null;
				}

				const data = {
					item: self.currentItem
				}

				if ( 'wc_based' === self.bookingMode ) {
					data.calculateTotals = self.calculateTotals;
				}

				self.submitting = true;

				wp.apiFetch( {
					method: 'post',
					path: window.JetABAFConfig.api.update_booking + self.currentItem.booking_id + '/',
					data: data
				} ).then( function ( response ) {
					if ( ! response.success ) {
						self.$CXNotice.add( {
							message: response.data,
							type: 'error',
							duration: 7000,
						} );

						if ( response.overlapping_bookings ) {
							store.commit( 'setValue', {
								key: 'overlappingBookings',
								value: response.html
							} );

							self.initDateRangePicker();
						}

						self.submitting = false;

						return;
					} else {
						self.editDialog = false;

						self.$CXNotice.add( {
							message: 'Done!',
							type: 'success',
							duration: 7000,
						} );

						store.dispatch( 'getItems' );
					}

					self.recalculateTotals = false;
					self.calculateTotals = false;
					self.submitting = false;

					self.cancelPopup();
				} ).catch( function ( e ) {
					self.$CXNotice.add( {
						message: e.message,
						type: 'error',
						duration: 7000,
					} );

					self.recalculateTotals = false;
					self.calculateTotals = false;
					self.submitting = false;

					self.cancelPopup();
				} );
			},
			deleteDetailsItem: function () {
				this.popUpState = 'delete';
			},
			deleteItem: function () {
				const self = this;

				if ( ! self.currentItem ) {
					return;
				}

				self.submitting = true;

				wp.apiFetch( {
					method: 'delete',
					path: window.JetABAFConfig.api.delete_booking + self.currentItem.booking_id + '/',
				} ).then( function ( response ) {
					if ( ! response.success ) {
						self.$CXNotice.add( {
							message: response.data,
							type: 'error',
							duration: 7000,
						} );
					}

					store.commit( 'setValue', {
						key: 'offset',
						value: 0
					} );

					store.commit( 'setValue', {
						key: 'pageNumber',
						value: 1
					} );

					store.dispatch( 'getItems' );

					self.submitting = false;

					self.cancelPopup();
				} ).catch( function ( e ) {
					self.$CXNotice.add( {
						message: e.message,
						type: 'error',
						duration: 7000,
					} );

					self.cancelPopup();
				} );
			},
			hasGuests: function () {
				return this.currentItem?.__guests;
			},
			hasAttributes: function() {
				if ( ! this.currentItem.hasOwnProperty( 'attributes' ) ) {
					return false;
				}

				return this.currentItem.attributes.length || Object.keys( this.currentItem.attributes ).length;
			},
			updateItemAttribute: function( _, item ) {
				const attributeUpdated = this.updateAttribute( _, item );

				if ( ! attributeUpdated ) {
					return;
				}

				this.recalculateTotals = true;
			},
			getAttributes: function() {
				const self = this;

				jQuery.ajax( {
					url: window.JetABAFConfig.ajax_url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'jet_booking_get_attributes',
						booking: self.currentItem,
						nonce: window?.JetABAFConfig?.nonce
					},
				} ).done( function ( response ) {
					store.commit( 'setValue', {
						key: 'itemAttributes',
						value: response.data.attributes
					} );
				} ).fail( function ( _, _2, errorThrown ) {
					alert( errorThrown );
				} );
			}
		},
	} );

	new Vue( {
		el: '#jet-abaf-bookings-page',
		template: '#jet-abaf-bookings',
		store,
		computed: Vuex.mapState( {
			isSet: state => state.setup.is_set,
			bookingsList: state => state.bookings_list,
			currentView: state => `jet-abaf-bookings-${ state.currentView }`,
			currentFilters: state => state.currentFilters,
			isLoading: 'isLoading',
			pageUrl: 'pageUrl',
			perPage: 'perPage',
		} ),
		created: function () {
			if ( this.pageUrl.searchParams.has( 'booking-details' ) ) {
				const index = this.bookingsList.reverse().findIndex( item => +item.booking_id === +this.pageUrl.searchParams.get( 'booking-details' ) );
				const page = Math.ceil( ( index + 1 ) / this.perPage ) || 1;

				store.commit( 'setValue', {
					key: 'offset',
					value: this.perPage * ( page - 1 )
				} );

				store.commit( 'setValue', {
					key: 'pageNumber',
					value: page
				} );
			}

			let view = 'list';

			if ( this.pageUrl.searchParams.has( 'view' ) ) {
				view = [ 'list', 'calendar', 'timeline' ].includes( this.pageUrl.searchParams.get( 'view' ) ) ? this.pageUrl.searchParams.get( 'view' ) : 'list';

				store.commit( 'setValue', {
					key: 'currentView',
					value: view
				} );

				if ( 'timeline' === view ) {
					store.commit( 'setValue', {
						key: 'currentFilters',
						value: {
							...this.currentFilters,
							check_in_date: moment().startOf( 'month' ).unix(),
							check_out_date: moment().endOf( 'month' ).unix()
						}
					} );
				}
			}

			if ( this.pageUrl.searchParams.has( 'vendor' ) ) {
				store.commit( 'setValue', {
					key: 'currentFilters',
					value: { ...this.currentFilters, booking_vendor: this.pageUrl.searchParams.get( 'vendor' ) }
				} );
			}

			if ( 'calendar' !== view ) {
				store.dispatch( 'getItems' );
			}
		},
	} );

})();
