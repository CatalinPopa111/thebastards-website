( function () {

	'use strict';

	const eventHub = new Vue();

	Vue.component( 'jet-abaf-configuration', {
		template: '#jet-abaf-settings-common-config',
		data: function() {
			return {
				settings: window.jet_abaf_configuration.config,
			};
		},
		methods: {
			updateSetting: function( value, key ) {
				this.$nextTick( function() {
					eventHub.$emit( 'update-settings', value, key );
				} );
			}
		}
	} );

	new Vue( {
		el: '#jet-abaf-configuration-meta-box',
		mixins: [ jetABAFPostMetaManager ],
		data: function() {
			return {
				settings: window.jet_abaf_configuration,
			};
		},
		mounted: function () {
			this.$nextTick( function () {
				eventHub.$on( 'update-settings', this.updateSetting );
			} )
		},
		methods: {
			updateSetting: function( value, key ) {
				this.$set( this.settings.config, key, value );
				this.$set( this, 'meta', this.settings );

				this.$nextTick( function() {
					this.saveSettings();
				} );
			},
		}
	} );

} )();