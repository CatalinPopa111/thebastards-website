( function( postMeta, assets, postMetaManager, vuejsDatepicker, dateMethods ) {

	'use strict';

	new Vue( {
		el: '#jet-abaf-price-meta-box',
		mixins: [ postMetaManager, dateMethods ],
		components: {
			vuejsDatepicker: vuejsDatepicker,
		},
		data: {
			...assets,
			meta: postMeta,
			popUpActive_rates: false,
			popUpActive_weekend: false,
			popUpData:{},
			saving: false,
			dateMomentFormat: "DD/MM/YYYY"
		},
		methods: {
			showPopUp: function( items, price, popupName ){
				this[ `popUpActive_${ popupName }` ] = true;
				this.popUpData   = {
					'price': price,
					'items': items
				};
			},
			hidePopUp: function( popupName ){
				this[ `popUpActive_${ popupName }` ] = false;
				this.popUpData   = {};
			},
			saveMeta: function() {
				this.$nextTick( function() {
					this.updateSetting( this.meta );
				} );
			},

			isWeekendPriceActive: function( items ) {

				let isActive = false;

				for ( const key in items ) {
					if ( ! items.hasOwnProperty( key ) ) {
						continue;
					}

					if ( items[ key ].active ) {
						isActive = items[ key ].active;

						break;
					}
				}

				return isActive;

			},

			//price rate
			newRate: function() {
				this.popUpData.items.push( {
					duration: 2,
					value: this.popUpData.price,
				} )
			},
			deleteRate: function( index ) {
				if ( window.confirm( this.confirm_message ) ) {
					this.popUpData.items.splice( index, 1 );
				}
			},

			//seasonal prices
			addSP: function() {
				let now = this.objectDateToTimestamp( new Date() ),
					item = {
						title: '',
						price: this.meta._apartment_price,
						startTimestamp: now,
						endTimestamp: now,
						repeatSeason: 'not_repeat',
						collapsed: false,
						enable_config: false,
						start_day_offset: 0,
						min_days: 0,
						max_days: 0,
						_pricing_rates: [],
						_weekend_prices: JSON.parse( JSON.stringify( this.default_weekend_prices ) ),
					};

				this.meta._seasonal_prices.push( item );
			},
			cloneSP: function( item, index ) {
				let newItem = JSON.parse( JSON.stringify( item ) );

				newItem.title += '_copy';

				this.meta._seasonal_prices.splice( index + 1, 0, newItem );
				this.saveMeta();
			},
			deleteSP: function( index ) {
				this.meta._seasonal_prices.splice( index, 1 );
				this.saveMeta();
			},
			changeSPValue: function( value, key, index ) {

				switch( key ) {
					case 'price':
						value = +event.target.value;
						break;

					case 'startTimestamp':
						let endValue = this.meta._seasonal_prices[ index ].endTimestamp;
						value = this.objectDateToTimestamp( value );

						if ( ! endValue || endValue < value ) {
							this.$set( this.meta._seasonal_prices[ index ], 'endTimestamp', value );
						}

						break;

					case 'endTimestamp':
						let startValue = this.meta._seasonal_prices[ index ].startTimestamp;
						value = this.objectDateToTimestamp( value );

						if ( ! startValue || startValue > value ) {
							this.$set( this.meta._seasonal_prices[ index ], 'startTimestamp', value );
						}

						break;

					case 'enable_config':
						break;

					default:
						value = event.target.value;
						break;
				}

				this.$set( this.meta._seasonal_prices[ index ], key, value );
				this.saveMeta();

			},
			isCollapsed: function( object ) {
				return object.collapsed ;
			},
			getRepeaterTitle: function( item ){
				let title = '';

				if( item.title ){
					title += `<strong style="color:#000">${item.title}</strong> - `;
				}

				if( item.startTimestamp ){
					title += `<strong style="color:#000">${this.season_label}</strong> <span style="color:#7b7e81">${this.timestampToDate( item.startTimestamp, this.dateMomentFormat )}</span>`;
				}

				if( item.endTimestamp ){
					title += `<span style="color:#7b7e81"> - ${this.timestampToDate( item.endTimestamp, this.dateMomentFormat )}</span> `;
				}

				if( item.price ){
					title += `<strong style="color:#000">${this.price_label}</strong> <span style="color:#7b7e81">${item.price}</span>`;
				}

				return title;
			}
		}
	} );

} )( jet_abaf_price, jetAbafAssets, jetABAFPostMetaManager, window.vuejsDatepicker, dateMethods );
