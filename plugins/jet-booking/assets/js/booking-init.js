(function () {

	window.jetBookingState = {
		isActive: false,
		bookingCalendars: [],
		selectedUnit: null,
		filters: ( function () {
			return {
				add: function ( name, callback ) {
					window.JetPlugins.hooks.addFilter( name.replace(/[\/]/g, '.'), 'jetBooking', callback );
					console.warn( '`window.jetBookingState.filters.add(' + name + ')` is deprecated since 2.6.3 Use `window.JetPlugins.hooks.addFilter(' + name.replace(/[\/]/g, '.') + ')` instead.' );
				}
			};
		} )()
	};

	let {
		css_url: cssUrl,
		post_id: postID,
		base_price: basePrice,
		seasonal_price: seasonalPrice,
		labels: labels,
		custom_labels: customLabels,
		weekly_bookings: weeklyBookings,
		week_offset: weekOffset,
		start_day_offset: startDayOffset,
		end_date: rangeEndDate,
		min_days: minDays,
		max_days: maxDays,
		ajax_url: ajaxURL,
		booked_dates: excludedDates,
		booked_next: excludedNext,
		days_off: daysOff,
		disabled_days: disabledDays,
		check_in_days: checkInDays,
		checkout_only: checkoutOnly,
		calendar_price: calendarPrice,
		calendar_currency_sign: calendarCurrencySign,
		clear_button: clearButton,
		month_select: monthSelect,
		year_select: yearSelect,
		time_format: timeFormat,
	} = window.JetABAFData;

	const {
		layout: fieldLayout = 'single',
		start_of_week: startOfWeek = 'monday',
		field_format: fieldFormat = 'YYYY-MM-DD',
		options: fieldOptions = []
	} = window?.JetABAFInput ?? {};

	let separator = ' - ',
		namespace = 'JetFormBuilderMain',
		initialized = {
			JetEngine: false,
			JetFormBuilderMain: false
		},
		head = document.getElementsByTagName( 'head' )[0],
		link = document.createElement( 'link' );

	link.rel   = 'stylesheet';
	link.type  = 'text/css';
	link.href  = cssUrl;
	link.media = 'all';

	head.appendChild( link );

	let JetBooking = {
		setDynamicPrice: function ( field = false ) {
			jQuery( 'span[data-price-change="1"][data-post="' + postID + '"]' ).each( function () {
				let $this = jQuery( this ),
				    period,
				    price = basePrice.price;

				if ( $this.data( 'price-change' ) === 0 && field ) {
					return;
				}

				if ( ! field[ 0 ] ) {
					period = {
						start: new Date().valueOf() / 1000,
						end: new Date().valueOf() / 1000,
					}
				} else {
					period = JetBooking.stringToTimestamp( field[ 0 ].value, separator, field.data( 'format' ) );
				}

				let periodRange = JetBooking.createRange( period.start, period.end ),
				    currentSeason = {
					    price: false,
					    price_rates: [],
					    weekend_price: [],
				    },
					priceList = [],
					priceRates = basePrice.price_rates,
					ratePriceList,
					weekendPrices = basePrice.weekend_price,
					weekendPriceList,
					priceType = $this.data('show-price');

				if ( JSON.stringify( seasonalPrice ) !== JSON.stringify( {} ) ) {
					for ( let day of periodRange ) {
						for ( let key in seasonalPrice ) {
							if ( ! seasonalPrice.hasOwnProperty( key ) ) {
								continue;
							}

							let start = parseInt( seasonalPrice[ key ].start ),
							    end = parseInt( seasonalPrice[ key ].end );

							if ( day >= start && day <= end ) {
								currentSeason = seasonalPrice[ key ];
							}
						}
					}
				}

				if (currentSeason.price) {
					priceRates = currentSeason.price_rates;
					weekendPrices = currentSeason.weekend_price;

					priceList.push(Number(currentSeason.price));
				} else {
					priceList.push(Number(basePrice.price));
				}

				ratePriceList = priceRates.map(function (el) {
					return Number(el.value);
				});

				weekendPriceList = weekendPrices.filter(function (el) {
					return typeof el === 'number';
				});

				priceList.push(...ratePriceList, ...weekendPriceList);

				switch ( priceType ) {
					case 'min':
						price = Math.min( ...priceList );
						break;

					case 'max':
						price = Math.max( ...priceList );
						break;

					case 'range':
						price = `${ Math.min( ...priceList ) } - ${ Math.max( ...priceList ) }`;
						break;

					default:
						price = currentSeason.price || basePrice.price;
						break;
				}

				if ( priceRates.length ) {
					for ( let i = 0; i < priceRates.length; i++ ) {
						if ( periodRange.length >= parseInt( priceRates[ i ].duration, 10 ) ) {
							price = Number( priceRates[ i ].value );
						}
					}
				}

				if ( $this.data( 'currency' ) ) {
					let currencyPosition = $this.data( 'currency-position' );

					if ( 'before' === currencyPosition ) {
						price = $this.data( 'currency' ) + '' + price;
					} else {
						price = price + '' + $this.data( 'currency' );
					}
				}

				$this.text( price )

			} );
		},

		setAvailableUnitsCount: function ( field ) {
			jQuery( 'span[data-units-count][data-post="' + postID + '"]' ).each( function () {

				let $this = jQuery( this ),
				    period;

				if ( ! field[ 0 ] ) {
					period = {
						start: new Date().valueOf() / 1000,
						end: new Date().valueOf() / 1000,
					}
				} else {
					period = JetBooking.stringToTimestamp( field[ 0 ].value, separator, field.data( 'format' ) );
				}

				jQuery.ajax( {
					url: ajaxURL,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'jet_booking_check_available_units_count',
						booking: {
							apartment_id: $this.data( 'post' ),
							check_in_date: period.start,
							check_out_date: period.end
						},
						type: $this.data( 'units-type' )
					},
				} ).done( function ( response ) {
					if ( response.success ) {
						$this.text( response.data.count );
					}
				} ).fail( function ( _, _2, errorThrown ) {
					alert( errorThrown );
				} );

			} );
		},

		validateDay: function ( t ) {
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
				_tooltip = customLabels ? labels.booked : 'Sold out';

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
							_tooltip = customLabels ? labels[ 'only-checkout' ] : 'Only checkout';
						}
					}
				}
			}

			// If is single-night booking - exclude next day for checkout only days.
			if ( checkoutOnly && window.jetBookingState.isActive && 0 <= excludedNext.indexOf( formatted ) ) {
				valid = false;
				_tooltip = customLabels ? labels.booked : 'Sold out';
			}

			return window.JetPlugins.hooks.applyFilters( 'jet-booking.date-range-picker.date-show-params', [ valid, _class, _tooltip ], t );
		},

		calculatedFieldValue: function ( value, $field ) {
			if ( 'checkin-checkout' === $field.data( 'field' ) ) {
				return JetBooking.calcBookedDates( value, $field.data( 'format' ) );
			} else {
				return value;
			}
		},

		parseBookingMacros: function ( formula, $scope ) {
			if ( ! formula ) {
				return formula;
			}

			if ( -1 !== formula.search( new RegExp( 'ADVANCED_PRICE' ) ) ) {
				let regexp = /%ADVANCED_PRICE::([a-zA-Z0-9-_]+)%/g,
				    dateField;

				formula = formula.replace( regexp, function ( match1, match2 ) {
					dateField = $scope.closest( 'form' ).find( '[name="' + match2 + '"], [name="' + match2 + '[]"]' );

					return JetBooking.getApartmentPrice( dateField );
				} );
			}

			if ( -1 !== formula.search( new RegExp( 'BOOKING_TIME' ) ) ) {
				formula = formula.replace( /%BOOKING_TIME::([a-zA-Z0-9-_]+)%/g, function ( match1, match2 ) {
					const timeField = $scope.closest( 'form' ).find( '[name="' + match2 + '"], [name="' + match2 + '[]"]' );

					return moment( timeField[0].value, timeFormat ).diff( moment().startOf('day'), 'seconds' );
				} );
			}

			return formula;
		},

		getApartmentPrice: function ( field ) {

			let period = JetBooking.stringToTimestamp( field[0].value, separator, field.data( 'format' ) );

			if ( ! period ) {
				return 0;
			}

			let periodRange = JetBooking.createRange( period.start, period.end ),
				daysCount = ( window.JetEngineForms || window.JetFormBuilder )?.getFieldValue( field ),
				price = 0;

			if ( field.parents( 'form.cart' ) ) {
				daysCount = JetBooking.calcBookedDates( field[0].value, field );
			}

			for ( let day of periodRange ) {
				price += JetBooking.getOneDayPrice( day, daysCount );
			}

			return window.JetPlugins.hooks.applyFilters( 'jet-booking.apartment-price', price, field );

		},

		getOneDayPrice: function ( day, daysCount ) {

			let pricing = window.JetABAFData.base_price;

			const pricingSeasonal = window.JetABAFData.seasonal_price;

			if ( JSON.stringify( pricingSeasonal ) !== JSON.stringify( {} ) ) {
				for ( let key in pricingSeasonal ) {
					if ( ! pricingSeasonal.hasOwnProperty( key ) ) {
						continue;
					}
					let start = parseInt( pricingSeasonal[ key ].start ),
					    end = parseInt( pricingSeasonal[ key ].end );

					if ( day >= start && day <= end ) {
						pricing = pricingSeasonal[ key ];
					}
				}
			}

			let weekDay = moment.unix( day ).utc().day(),
			    weekendPrice = pricing.weekend_price[ weekDay ],
			    price = weekendPrice ? weekendPrice : pricing.price;

			if ( pricing.price_rates[ 0 ] ) {
				for ( let rate of pricing.price_rates ) {
					if ( daysCount >= Number( rate.duration ) ) {
						price = rate.value;
					}
				}
			}

			return Number( price );

		},

		createRange: function ( start, end, step = 86400 ) {

			let range = [ start ],
			    newItem = start;

			end = ! window.JetABAFData.per_nights ? end : end - step;

			while ( newItem < end ) {
				range.push( newItem += step );
			}

			return range;

		},

		stringToTimestamp: function ( string, separator, format = "YYYY-MM-DD" ) {

			if ( ! string ) {
				return false;
			}

			let output = {},
				startDate = ! window.JetABAFData.one_day_bookings ? string.slice( 0, string.indexOf( separator ) ) : string,
				endDate = ! window.JetABAFData.one_day_bookings ? string.slice( string.indexOf( separator ) + separator.length, string.length ) : string;

			format = `${ format } hh:mm:ss Z`;

			output.start = moment( `${ startDate } 00:00:00 +0000`, format ).unix();
			output.end = moment( `${ endDate } 00:00:00 +0000`, format ).unix();

			return output;

		},

		calcBookedDates: function ( value, dateFormat = "YYYY-MM-DD" ) {

			if ( window.JetABAFData.one_day_bookings ) {
				return 1;
			}

			if ( ! value.length || 1 >= value.length ) {
				return value;
			}

			value = value.split( ' - ' );

			if ( ! value[0] ) {
				return 0;
			}

			if ( fieldFormat ) {
				dateFormat = fieldFormat;
			}

			let startDate = moment( value[0], dateFormat ),
				endDate = moment( value[1], dateFormat );

			value = endDate.diff( startDate, 'days' );
			value = Number( value );

			if ( ! window.JetABAFData.per_nights ) {
				value++;
			}

			return value;

		},

		getTimepickerSlots: function ( start, end, field = '' ) {

			if ( ! window.JetABAFData.timepicker ) {
				return;
			}

			jQuery( '.jet-abaf-timepicker' ).addClass( 'loading' );

			jQuery.ajax( {
				url: ajaxURL,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'jet_booking_get_timepicker_slots',
					postID: postID,
					bookingID: field.length ? +jQuery( field ).data( 'booking-id' ) : '',
					checkInDate: moment( start ).format('YYYY-MM-DD'),
					checkOutDate: moment( end ).format('YYYY-MM-DD'),
				},
			} ).done( function ( response ) {
				jQuery( '#check-in-time' ).html( response.data.check_in_time_slots );
				jQuery( '#check-out-time' ).html( response.data.check_out_time_slots );

				jQuery( '.jet-abaf-timepicker' ).removeClass( 'loading' );
			} ).fail( function ( _, _2, errorThrown ) {
				alert( errorThrown );
			} );

		},

		setDateRangePickerConfig: function () {
			let config = {
				separator: separator,
				startDate: new Date(),
				startOfWeek: startOfWeek,
				minDays: minDays.length && +minDays ? +minDays : '',
				maxDays: maxDays.length && +maxDays ? +maxDays : '',
				selectForward: true,
				beforeShowDay: JetBooking.validateDay,
				perNights: window.JetABAFData.per_nights,
				monthSelect: !! monthSelect,
				yearSelect: !! yearSelect,
				language: 'zh' === document.documentElement.lang.slice( 0, 2 ) ? document.documentElement.lang : document.documentElement.lang.slice( 0, 2 )
			};

			if ( startDayOffset.length && +startDayOffset ) {
				config.startDate = moment().add( +startDayOffset, 'd' );
			}

			if ( customLabels ) {
				jQuery.dateRangePickerLanguages['custom'] = labels;
				config.language = 'custom';
			}

			if ( weeklyBookings ) {
				config.batchMode = 'week';
				config.showShortcuts = false;

				if ( weekOffset ) {
					config.weekOffset = +weekOffset;
				}
			} else if ( window.JetABAFData.one_day_bookings ) {
				config.singleDate = true;
			}

			if ( calendarPrice ) {
				config.showDateFilter = function( time, date ){
					const tzOffset = moment( time ).utcOffset();
					const day = moment( time ).startOf( 'day' ).add( tzOffset, 'minutes' ).unix();

					return '<span>' + date + '</span> <div class="day-price">'+ calendarCurrencySign + JetBooking.getOneDayPrice( day, 0 ) + '</div>';
				}
			}

			return config;
		},

		syncValuesWithCalendarWidget: function ( s1, s2 ) {
			window.jetBookingState.isActive = false;

			const $bookingCalendar = jQuery( '.jet-booking-calendar__input' );
			const $dateRangePicker = $bookingCalendar.data( 'dateRangePicker' );

			if ( $bookingCalendar.length && $dateRangePicker ) {
				if ( ! s1 || ! s2 ) {
					$dateRangePicker.redraw();

					return;
				}

				s1 = moment( s1, fieldFormat ).format( 'YYYY-MM-DD' );
				s2 = moment( s2, fieldFormat ).format( 'YYYY-MM-DD' );

				if ( window.JetABAFData.one_day_bookings ) {
					$dateRangePicker.setStart( s1, true );
				} else {
					$dateRangePicker.setDateRange( s1, s2, true );
				}
			}
		},

		getDateRangeString: function ( value ) {

			if ( ! value ) {
				return '';
			}

			value = value.split( separator );

			if ( ! value.length ) {
				return '';
			}

			if ( 1 === value.length ) {
				return value[0];
			}

			return value[0] + separator + value[1];

		},

		getDateRangeDates: function ( value ) {

			if ( ! value ) {
				return [];
			}

			let dates = value.split( separator );

			if ( dates.length === 1 ) {
				dates.push( dates[0] );
			}

			return dates;

		},

		resetInitializedNamespace: function () {
			initialized[ namespace ] = false;
		},

		maybeClearSelection: function($el, obj) {
			jQuery(document).keyup(function(event) {
				if (!window.jetBookingState.isActive) {
					window.jetBookingState.isActive = true;
				}

				if (window.jetBookingState.isActive && event.key === 'Escape') {
					window.jetBookingState.isActive = false;

					const selectedDay = moment(obj.date1).format('YYYY-MM-DD');

					$el.data('dateRangePicker').setDateRange(selectedDay, selectedDay, true);
					$el.data('dateRangePicker').clear();
					$el.data('dateRangePicker').close();
				}
			});
		},

		updateDaysPrice: function( $from, $to ) {
			const dates = $from + ' - ' + $to;
			const period = JetBooking.stringToTimestamp( dates, ' - ', fieldFormat  );

			if ( ! period ) {
				return;
			}

			const daysCount = JetBooking.calcBookedDates( dates );
			const range = JetBooking.createRange( period.start, period.end );

			for ( let day of range ) {
				const tzOffset = moment.unix( day ).utcOffset()
				const time = tzOffset > 0 ? moment.unix( day ).startOf( 'day' ).format( 'x' ) : moment.unix( day ).startOf( 'day' ).add( 1, 'days' ).format( 'x' );

				jQuery( ".day[time='"+ time +"'] .day-price" ).text( calendarCurrencySign + JetBooking.getOneDayPrice( day, daysCount ) );
			}
		},

		initializeCheckInOut: function ( event, $scope ) {

			if ( ! $scope ) {
				alert( 'Please update JetEngine to version 2.4.0 or higher' );
			}

			if ( ! jQuery( '.field-type-check_in_out, .field-type-check-in-out, .jet-abaf-product-check-in-out', $scope )[ 0 ] ) {
				return;
			}

			namespace = event?.data?.namespace || 'JetBookingProduct';

			if ( initialized[ namespace ] ) {
				return;
			}

			let config = {
				...JetBooking.setDateRangePickerConfig(),
				format: fieldFormat ? fieldFormat : 'YYYY-MM-DD',
				autoClose: true
			};

			if ( rangeEndDate ) {
				config.endDate = moment( +rangeEndDate, 'X' ).format( fieldFormat );
			}

			if ( clearButton ) {
				config.clearButton = true;
			}

			config = window.JetPlugins.hooks.applyFilters( 'jet-booking.input.config', config );

			let dateFieldValue = '';

			if ( 'single' === fieldLayout ) {
				let $checkInOut = jQuery( '#jet_abaf_field', $scope );

				config.container = '.jet-abaf-field';

				config.getValue = function () {
					return JetBooking.getDateRangeString( $checkInOut.val() );
				};

				config.setValue = function ( s, s1, s2 ) {
					$checkInOut.val( s ).trigger( 'change.' + namespace );

					JetBooking.syncValuesWithCalendarWidget( s1, s2 )
					JetBooking.setDynamicPrice( jQuery( 'input[data-field="checkin-checkout"]', $scope ) );
					JetBooking.setAvailableUnitsCount( jQuery( 'input[data-field="checkin-checkout"]', $scope ) );
				};

				dateFieldValue = $checkInOut.val();
			} else {
				let $checkInOut = jQuery( '#jet_abaf_field_range', $scope );

				config.container = '.jet-abaf-separate-fields';

				config.getValue = function () {
					return JetBooking.getDateRangeString( $checkInOut.val() );
				};

				config.setValue = function ( s, s1, s2 ) {
					if ( s === s1 ) {
						s2 = s1;
					}

					jQuery( '#jet_abaf_field_1', $scope ).val( s1 );
					jQuery( '#jet_abaf_field_2', $scope ).val( s2 );

					$checkInOut.val( s ).trigger( 'change.' + namespace );

					JetBooking.syncValuesWithCalendarWidget( s1, s2 );
					JetBooking.setDynamicPrice( jQuery( config.container + ' input[data-field="checkin-checkout"]', $scope ) );
					JetBooking.setAvailableUnitsCount( jQuery( config.container + ' input[data-field="checkin-checkout"]', $scope ) );
				};

				dateFieldValue = $checkInOut.val();
			}

			const dateFieldValues = dateFieldValue.length ? dateFieldValue.split( ' - ' ) : [];

			if ( dateFieldValues.length && !! dateFieldValues[0] && !! dateFieldValues[1] ) {
				const checkInDate = moment( dateFieldValues[0], fieldFormat ).format( 'YYYY-MM-DD' );
				const checkOutDate = moment( dateFieldValues[1], fieldFormat ).format( 'YYYY-MM-DD' );

				if ( 0 <= excludedDates.indexOf( checkInDate ) ) {
					let deleteCount = moment( checkOutDate ).diff( moment( checkInDate ), 'days' );

					if ( ! window.JetABAFData.per_nights ) {
						deleteCount++;
					}

					excludedDates.splice( excludedDates.indexOf( checkInDate ), deleteCount );
					excludedDates.push( ...daysOff )
				}
			}

			let $field = jQuery( config.container, $scope );

			window.jetBookingState.bookingCalendars.push( $field );

			$field.dateRangePicker(config).
				bind('datepicker-first-date-selected', (_, obj) => {
					window.jetBookingState.isActive = true;
					JetBooking.maybeClearSelection($field, obj);
				}).
				bind( 'datepicker-change', ( _, obj ) => {
					window.jetBookingState.isActive = false;

					JetBooking.getTimepickerSlots( obj.date1, obj.date2 );
				} ).
				bind( 'datepicker-open', ( event ) => {
					if ( ! calendarPrice ) {
						return;
					}

					const fieldValue = jQuery( event.target ).find('#jet_abaf_field, #jet_abaf_field_range' ).val();
					const dates = JetBooking.getDateRangeDates( fieldValue );

					if ( ! dates.length ) {
						return;
					}

					JetBooking.updateDaysPrice( dates[0], dates[1] );
				} ).
				bind( 'datepicker-close',function() {
					const fieldValue = jQuery( '#jet_abaf_field, #jet_abaf_field_range', $field ).val();

					if ( ! fieldValue.length ) {
						$field.data( 'dateRangePicker' ).clear();
					}

					window.jetBookingState.isActive = false;
				} );

			if ( ! initialized[ namespace ] ) {
				switch ( namespace ) {
					case 'JetEngine':
						JetEngine.filters.addFilter( 'forms/calculated-field-value', JetBooking.calculatedFieldValue );
						JetEngine.filters.addFilter( 'forms/calculated-formula-before-value', JetBooking.parseBookingMacros );
						break;

					case 'JetFormBuilderMain':
						if ( window.JetFormBuilderMain ) {
							JetFormBuilderMain.filters.addFilter( 'forms/calculated-field-value', JetBooking.calculatedFieldValue );
							JetFormBuilderMain.filters.addFilter( 'forms/calculated-formula-before-value', JetBooking.parseBookingMacros );
						}

						break;

					default:
						break;
				}
			}

			initialized[ namespace ] = true;

			jQuery(document).trigger( 'jet-booking/init-field', [ $field ] );

		},

		triggerWCBookingFromChange: function ( $scope ) {
			const $form = jQuery( $scope ).closest( 'form' );
			const formInstance = new FormData( $form[0] );
			const formData = {};

			// Iterate over the FormData entries.
			for ( let [ key, value ] of formInstance.entries() ) {
				if ( key.includes( '[]' ) ) {
					key = key.replace( '[]', '' );

					if ( undefined === formData[ key ] ) {
						formData[ key ] = [];
					}

					formData[ key ].push( value );
				} else {
					formData[ key ] = value;
				}
			}

			const $button = $form.find( '.single_add_to_cart_button' );
			const $total = jQuery( '.jet-abaf-product-total' );

			if ( formData.jet_abaf_units && window.jetBookingState.selectedUnit !== formData.jet_abaf_units ) {
				window.jetBookingState.selectedUnit = formData.jet_abaf_units;

				jQuery.ajax( {
					url: ajaxURL,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'jet_booking_product_get_unit_booked_dates',
						postID: postID,
						unitID: formData.jet_abaf_units
					},
				} ).done( function ( response ) {
					excludedDates = window.JetABAFData.booked_dates = response.data.booked_dates;
					excludedNext = window.JetABAFData.booked_next = response.data.booked_next;

					jQuery( '.jet-abaf-field, .jet-abaf-separate-fields' ).data( 'dateRangePicker' ).clear();

					const $bookingCalendar = jQuery( '.jet-booking-calendar__input' );
					const $calendarDateRangePicker = $bookingCalendar.data( 'dateRangePicker' );

					if ( $bookingCalendar.length && $calendarDateRangePicker ) {
						$calendarDateRangePicker.redraw();
					}

					$total.html('');
					$button.removeClass( 'disabled' ).prop( 'disabled', true );
				} ).fail( function ( _, _2, errorThrown ) {
					alert( errorThrown );
				} );
			}

			if ( ! formData.jet_abaf_field ) {
				return;
			}

			$button.removeClass( 'disabled' ).prop( 'disabled', false );

			jQuery.ajax( {
				url: ajaxURL,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'jet_booking_product_set_total_price',
					postID: postID,
					total: JetBooking.getApartmentPrice( $form.find( 'input[name="jet_abaf_field"]' ) ),
					formData: formData,
				},
			} ).done( function ( response ) {
				$total.html( response.data.html );
			} ).fail( function ( _, _2, errorThrown ) {
				alert( errorThrown );
			} );
		},

		wcInitializeCheckInOut: function () {
			const $bookingForm = jQuery( document ).find( '.jet-booking-form' );

			if ( ! $bookingForm.length ) {
				return;
			}

			JetBooking.initializeCheckInOut( null, '.jet-booking-form' );

			const $bookingButton = $bookingForm.find( '.single_add_to_cart_button' );
			const $dateField = $bookingForm.find( '#jet_abaf_field, #jet_abaf_field_range' );

			$bookingButton.addClass( 'disabled' ).prop( 'disabled', true );

			let triggerForm = false;

			if ( $dateField.val() && $dateField.val().length ) {
				$bookingButton.removeClass( 'disabled' ).prop( 'disabled', false );

				const dates = JetBooking.getDateRangeDates( $dateField.val() );

				if ( dates.length ) {
					JetBooking.getTimepickerSlots( moment( dates[0], fieldFormat ), moment( dates[1], fieldFormat ) );
				}

				triggerForm = true;
			}

			if ( $bookingForm.find( '#jet_abaf_units' ).val() ) {
				triggerForm = true;
			}

			if ( triggerForm ) {
				JetBooking.triggerWCBookingFromChange( $bookingForm );
			}

			jQuery( document ).on( 'change.JetBookingProduct', () => {
				JetBooking.triggerWCBookingFromChange( $bookingForm );
			} );
		},

		widgetCalendar: function( $scope ) {

			const $container = $scope.find( '.jet-booking-calendar__container' );
			const $el = $scope.find( '.jet-booking-calendar__input' );

			if ( $container.find( '.date-picker-wrapper' ).length ) {
				$el.data('dateRangePicker').destroy()
			}

			let	scrollToForm = $container.data( 'scroll-to-form' ),
				config = {
					...JetBooking.setDateRangePickerConfig(),
					inline: true,
					container: '#' + $container.attr( 'id' ),
					alwaysOpen: true,
					showTopbar: false
				};

			if ( rangeEndDate ) {
				config.endDate = moment( +rangeEndDate, 'X' ).format('YYYY-MM-DD');
			}

			config = window.JetPlugins.hooks.applyFilters( 'jet-booking.calendar.config', config );

			config.setValue = function( s, s1, s2 ) {

				let $formField = jQuery( '.field-type-check_in_out, .field-type-check-in-out, .jet-abaf-product-check-in-out' ),
					$result,
					format;

				if ( ! $formField.length ) {
					return;
				}

				if ( s === s1 ) {
					s2 = s1;
				}

				if ( $formField.find( '.jet-abaf-separate-fields' ).length ) {
					$result = $formField.find( '#jet_abaf_field_range' );
					format = $result.data( 'format' );

					$formField.find( '#jet_abaf_field_1' ).val( format ? moment( s1 ).format( format ) : s1 );
					$formField.find( '#jet_abaf_field_2' ).val( format ? moment( s2 ).format( format ) : s2 );
				} else if ( $formField.find( '.jet-abaf-field' ).length ) {
					$result = $formField.find( '#jet_abaf_field' );
					format = $result.data( 'format' );
				}

				if ( s.length && format ) {
					s1 = moment( s1 ).format( format );
					s2 = moment( s2 ).format( format );
					s = window.JetABAFData.one_day_bookings ? moment( s ).format( format ) : s1 + config.separator + s2;
				}

				$result.val( s ).trigger( 'change.' + namespace );

				if ( scrollToForm ) {
					jQuery( 'html, body' ).animate({
						scrollTop: $formField.closest( 'form' ).offset().top
					}, 500 );
				}

				JetBooking.setDynamicPrice( $result );
				JetBooking.setAvailableUnitsCount( $result );

			};

			window.jetBookingState.bookingCalendars.push( $el );

			$el.dateRangePicker(config).
				bind( 'datepicker-first-date-selected', ( _, obj ) => {
					window.jetBookingState.isActive = true;
					JetBooking.maybeClearSelection( $el, obj );
				} ).
				bind( 'datepicker-change', ( _, obj ) => {
					window.jetBookingState.isActive = false;

					let startDate = moment( obj.date1 );
					let endDate = moment( obj.date2 );

					if ( window.JetABAFData.one_day_bookings ) {
						endDate = startDate;
					}

					$el.data( 'dateRangePicker' ).setDateRange( startDate.format( 'YYYY-MM-DD' ), endDate.format( 'YYYY-MM-DD' ), true );

					if ( calendarPrice ) {
						JetBooking.updateDaysPrice( startDate.format( fieldFormat ), endDate.format( fieldFormat ) );
					}

					JetBooking.getTimepickerSlots( obj.date1, obj.date2 );
				} );

			if ( ! jQuery.isEmptyObject( fieldOptions ) ) {
				const startDate = moment( fieldOptions.checkin, fieldFormat ).format( 'YYYY-MM-DD' );
				const endDate = moment( fieldOptions.checkout, fieldFormat ).format( 'YYYY-MM-DD' );

				$el.data( 'dateRangePicker' ).setDateRange( startDate, endDate, true );
			}

			jQuery(document).trigger( 'jet-booking/init-calendar', [ $el ] );

		},

		commonInit: function () {
			jQuery( document )
				.on( 'jet-engine/booking-form/init', { namespace: 'JetEngine' }, JetBooking.initializeCheckInOut )
				.on( 'jet-form-builder/init', { namespace: 'JetFormBuilderMain' }, JetBooking.initializeCheckInOut )
				.on( 'jet-booking/init-field', function ( _, field ) {
					const fieldValue = jQuery( field ).find('#jet_abaf_field, #jet_abaf_field_range' ).val();
					const dates = JetBooking.getDateRangeDates( fieldValue );

					if ( ! dates.length ) {
						return;
					}

					JetBooking.getTimepickerSlots( moment( dates[0], fieldFormat ), moment( dates[1], fieldFormat ), field );
				} )
				.trigger( 'jet-booking/init' );
		},

		initElementor: function () {
			if ( ! window.elementorFrontend ) {
				return;
			}

			jQuery( document ).on( 'elementor/popup/hide', JetBooking.resetInitializedNamespace );

			const widgets = {
				'jet-booking-calendar.default': JetBooking.widgetCalendar
			};

			jQuery.each( widgets, function( widget, callback ) {
				window.elementorFrontend.hooks.addAction( 'frontend/element_ready/' + widget, callback );
			} );
		},

		initBricks: function( $scope ) {
			if ( window.bricksIsFrontend ) {
				return;
			}

			$scope = $scope || jQuery( 'body' );
			JetBooking.initBlocks( $scope );
		},

		initBlocks: function( $scope ) {
			$scope = $scope || jQuery( 'body' );

			window.JetPlugins.init( $scope, [
				{
					block: 'jet-booking/calendar',
					callback: JetBooking.widgetCalendar
				}
			] );
		}

	};

	JetBooking.commonInit();

	jQuery( window ).on( 'elementor/frontend/init', JetBooking.initElementor );

	window.addEventListener( 'DOMContentLoaded', function() {
		JetBooking.wcInitializeCheckInOut();
		JetBooking.initBlocks();
	} );

	window.jetBookingBricks = function() {
		JetBooking.initBricks();
	}

	window.JetBooking = JetBooking;

}() );
