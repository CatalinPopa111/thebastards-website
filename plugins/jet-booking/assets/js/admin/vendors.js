( function () {
	"use strict";

	Vue.component( 'jet-abaf-vendors-list', {
		template: '#jet-abaf-vendors-list',
		data: function () {
			return {
				vendors: window.JetABAFConfig.vendors_list,
			};
		}
	} );

	new Vue( {
		el: '#jet-abaf-vendors-page',
		template: '#jet-abaf-vendors'
	} );
} )();
