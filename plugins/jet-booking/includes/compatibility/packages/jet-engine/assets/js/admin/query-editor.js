(function ($) {
    'use strict';

    Vue.component('jet-booking-query', {
        template: '#jet-booking-query',
        mixins: [
            window.JetQueryTabInUseMixin,
            window.JetQueryWatcherMixin,
            window.JetQueryRepeaterMixin,
            window.JetQueryDateParamsMixin,
            window.JetQueryMetaParamsMixin,
        ],
        props: ['value', 'dynamic-value'],
        data: function () {
            return {
                query: {},
                dynamicQuery: {},
                statuses: window.jet_query_component_jet_booking_query.statuses,
                vendors: window.jet_query_component_jet_booking_query.vendors,
                bookingInstances: window.jet_query_component_jet_booking_query.booking_instances,
                columns: window.jet_query_component_jet_booking_query.columns,
                additionalColumns: window.jet_query_component_jet_booking_query.additional_columns,
                operators: window.JetEngineQueryConfig.operators_list,
            };
        },
        computed: {
            availableColumns: function () {
                return [...this.columns, ...this.additionalColumns];
            },
            queryOperators: function () {
                return this.operators.filter(function (item) {
                    const disallowed = ['LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'EXISTS', 'NOT EXISTS', 'BETWEEN', 'NOT BETWEEN', 'REGEXP', 'NOT REGEXP'];
                    return !disallowed.includes(item.value);
                });
            }
        },
        created: function () {
            this.query = {...this.value};
            this.dynamicQuery = {...this.dynamicValue};

            if (!this.query.orderby) {
                this.$set(this.query, 'orderby', []);
            }

            this.presetDate();
            this.presetMeta();
        }
    });
})(jQuery);
