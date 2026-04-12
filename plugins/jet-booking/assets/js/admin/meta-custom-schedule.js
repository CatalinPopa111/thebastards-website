( function () {

	'use strict';

	let metaBoxEventHub = new Vue(),
		settings = window.jet_abaf_custom_schedule;

	Vue.component( 'jet-abaf-custom-schedule', {
		template: '#jet-abaf-settings-schedule',
		mixins: [ scheduleManager, dateMethods ],
		data: function() {
			return {
				settings: settings.custom_schedule,
				timeSettings: false
			};
		},
		mounted: function () {
			if ( ! this.settings.days_off ) {
				this.settings.days_off = []
			}
		},
		methods: {
			updateSetting: function( value, key ) {
				this.$set( this.settings, key, value );

				this.$nextTick( function() {
					this.saveSettings();
				} );
			},
			saveSettings: function() {
				metaBoxEventHub.$emit( 'update-settings', this.settings, 'custom_schedule' );
			}
		}
	} );

	new Vue( {
		el: '#jet-abaf-custom-schedule-meta-box',
		mixins: [ jetABAFPostMetaManager ],
		data: function() {
			return {
				settings: settings,
			};
		},
		mounted: function () {
			this.$nextTick(function () {
				metaBoxEventHub.$on( 'update-settings', this.updateScheduleSettings, 'custom_schedule' );
			} )
		},
		methods: {
			updateScheduleSettings: function( settings, key ) {
				let updSettings = Object.assign( this.settings[ key ], settings );

				this.$set( this.settings, key, updSettings );
				this.$set( this, 'meta', this.settings );

				this.$nextTick( function () {
					this.saveSettings();
				} );
			},
			updateSetting: function( value, key ) {
				this.$set( this.settings.custom_schedule, key, value );
				this.$set( this, 'meta', this.settings );

				this.$nextTick( function() {
					this.saveSettings();
				} );
			},
		}
	} );

} )();