(function () {

	"use strict";

	Vue.component( 'jet-abaf-go-to-setup', {
		template: '#jet-abaf-go-to-setup',
		data: function() {
			return {
				setupURL: window.JetABAFConfig.setup.setup_url,
			};
		}
	} );

})();