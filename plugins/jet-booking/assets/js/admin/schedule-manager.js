( function () {

	'use strict';

	window.scheduleManager = {
		data: function () {
			return {
				editDay: false,
				date: {
					start: null,
					startTimeStamp: null,
					end: null,
					endTimeStamp: null,
					name: null,
					type: null,
					editIndex: null,
				},
				deleteDayTrigger: null,
				dateMomentFormat: 'DD-MM-YYYY',
				disabledDate: {},
			};
		},
		computed: {
			disabledDaysStatusLabel() {
				const daysList = this.getDaysList( 'disable' );
				let label = 'Any day available for selection.';

				if ( 7 === daysList.length ) {
					label = 'All days are completely unavailable for selection.';
				} else if ( daysList.length ) {
					label = daysList.join( ', ' ) + ' completely unavailable for selection.';
				}

				return wp.i18n.__( label, 'jet-booking');
			}
		},
		components: {
			vuejsDatepicker: window.vuejsDatepicker
		},
		methods: {
			getDaysList: function( type ) {
				let list = [];

				if ( this.settings[ type + '_weekday_1' ] ) {
					list.push( 'Monday' );
				}

				if ( this.settings[ type + '_weekday_2' ] ) {
					list.push( 'Tuesday' );
				}

				if ( this.settings[ type + '_weekday_3' ] ) {
					list.push( 'Wednesday' );
				}

				if ( this.settings[ type + '_weekday_4' ] ) {
					list.push( 'Thursday' );
				}

				if ( this.settings[ type + '_weekday_5' ] ) {
					list.push( 'Friday' );
				}

				if ( this.settings[ type + '_weekend_1' ] ) {
					list.push( 'Saturday' );
				}

				if ( this.settings[ type + '_weekend_2' ] ) {
					list.push( 'Sunday' );
				}

				return list;
			},

			checkInOutDaysStatusLabel( type ) {
				const disabledDaysList = this.getDaysList( 'disable' );

				if ( 7 === disabledDaysList.length ) {
					return wp.i18n.__( 'No available days for check ' + type + '.', 'jet-booking');
				}

				let label = 'Any day available for check ' + type + '.';
				let daysList = this.getDaysList( 'check_' + type );

				daysList = daysList.filter( day => -1 === disabledDaysList.indexOf( day ) );

				if ( 7 === daysList.length ) {
					label = 'All days are available for check ' + type + '.';
				} else if ( daysList.length ) {
					label = daysList.join( ', ' ) + ' ' + wp.i18n._n( 'is', 'are', daysList.length, 'jet-booking' ) + ' available for check ' + type + '.';
				} else if ( disabledDaysList.length ) {
					label = 'Guests can check ' + type + ' any available day.';
				}

				return wp.i18n.__( label, 'jet-booking');
			},

			showEditDay: function( daysType = false , date = false ) {

				if ( date && daysType ) {
					let index = this.settings[ daysType ].indexOf( date );

					this.date = Object.assign( {}, date );
					this.date.editIndex = index;
				}

				this.updateDisabledDates( daysType, date );

				this.date.type = daysType;
				this.editDay = true;

			},

			updateDisabledDates: function( daysType = false, excludedDate = false ) {

				let newDisabledDates = [],
					_excludedDate = JSON.stringify( excludedDate );

				for ( let date in this.settings[ daysType ] ) {
					if ( this.settings[ daysType ].hasOwnProperty( date ) ) {
						if ( JSON.stringify( this.settings[ daysType ][ date ] )  === _excludedDate ) {
							continue;
						}

						let daysFrom = moment.unix( this.settings[ daysType ][ date ].startTimeStamp ).utc(),
							toFrom = moment.unix( this.settings[ daysType ][ date ].endTimeStamp ).utc().add( 1, 'days' );

						if ( excludedDate ) {
							daysFrom.add( -1, 'days' )
						}

						newDisabledDates.push( {
							from: daysFrom.toDate(),
							to: toFrom.toDate(),
						} );
					}
				}

				this.$set( this.disabledDate, 'ranges', newDisabledDates );

			},

			handleDayCancel: function() {

				for ( let key in this.date ) {
					if ( this.date.hasOwnProperty( key ) ) {
						this.$set( this.date, key, null );
					}
				}

				this.editDay = false;

			},

			handleDayOk: function() {
				this.editDay = true;

				if ( ! this.date.start ) {
					this.$CXNotice.add( {
						message: wp.i18n.__( 'The start date is required.', 'jet-booking' ),
						type: 'error',
						duration: 7000,
					} );

					return;
				}

				if ( this.date.end && this.date.startTimeStamp > this.date.endTimeStamp ) {
					this.$CXNotice.add( {
						message: wp.i18n.__( 'The end date must be after the start date.', 'jet-booking' ),
						type: 'error',
						duration: 7000,
					} );

					return;
				}

				const date = Object.assign( {}, this.date );
				const dates = this.settings[ date.type ] || [];
				const index = null !== date.editIndex ? date.editIndex : dates.length;

				this.$set( dates, index, date );
				this.updateSetting( dates, date.type );
				this.handleDayCancel();
			},

			selectedDate: function( date, daysType ) {

				let dateTimestamp = this.objectDateToTimestamp( date ),
					formattedDate = this.parseDate( date, this.dateMomentFormat );

				this.$set( this.date, daysType, formattedDate );
				this.$set( this.date, `${ daysType }TimeStamp`, dateTimestamp );

			},

			confirmDeleteDay: function( dateObject ) {
				this.deleteDayTrigger = dateObject;
			},

			deleteDay: function( daysType = false , date = false  ) {

				let index = this.settings[ daysType ].indexOf( date );

				this.$delete( this.settings[ daysType ], index );

				this.deleteDayTrigger = null;

				this.$nextTick( function() {
					this.saveSettings();
				} );

			},
		}
	};

} )();