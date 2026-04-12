(function( $, unitsData ) {

	'use strict';

	window.JetABAFUnits = new Vue( {
		el: '#jet_abaf_apartment_units',
		template: '#jet-abaf-units-manager',
		data: {
			loading: false,
			loaded: false,
			unitsList: [],
			newUnitsNum: 1,
			newUnitsTitle: '',
			insertingUnits: false,
			unitToDelete: null,
			unitToEdit: null,
			savingUnits: false,
			deletingUnits: false,
		},
		methods: {
			loadUnits: function() {
				let self = this;

				self.loading = true;

				wp.apiFetch( {
					method: 'get',
					url: unitsData.get_units,
				} ).then( function( response ) {

					self.loading = false;

					if ( response.success ) {
						self.loaded    = true;
						self.unitsList = response.data.units;
					}

				} ).catch( function( e ) {
					self.loading = false;
					alert( e.message );
				} );
			},
			saveUnit: function() {

				var self = this;

				self.savingUnits = true;

				wp.apiFetch( {
					method: 'post',
					url: unitsData.update_unit,
					data: {
						apartment: unitsData.apartment,
						unit: self.unitToEdit,
						nonce: unitsData.nonce
					}
				} ).then( function( response ) {

					self.deletingUnits = false;

					if ( response.success ) {
						self.unitsList  = response.data.units;
						self.unitToEdit = null;
					}

				} ).catch( function( e ) {
					self.deletingUnits = false;
					alert( e.message );
				} );

			},
			deleteUnit: function() {

				var self = this;

				self.deletingUnits = true;

				wp.apiFetch( {
					method: 'post',
					url: unitsData.delete_unit,
					data: {
						apartment: unitsData.apartment,
						unit: self.unitToDelete,
						nonce: unitsData.nonce
					}
				} ).then( function( response ) {

					self.deletingUnits = false;

					if ( response.success ) {
						self.unitsList = response.data.units;
					}

				} ).catch( function( e ) {
					self.deletingUnits = false;
					alert( e.message );
				} );

			},
			insertUnits: function() {
				var self = this;

				self.insertingUnits = true;

				wp.apiFetch( {
					method: 'post',
					url: unitsData.insert_units,
					data: {
						number: self.newUnitsNum,
						title: self.newUnitsTitle,
						apartment: unitsData.apartment,
						nonce: unitsData.nonce
					}
				} ).then( function( response ) {

					self.insertingUnits = false;

					if ( response.success ) {
						self.unitsList = response.data.units;
					}

				} ).catch( function( e ) {
					self.insertingUnits = false;
					alert( e.message );
				} );

			}
		}
	} );

})( jQuery, window.JetABAFUnitsData );