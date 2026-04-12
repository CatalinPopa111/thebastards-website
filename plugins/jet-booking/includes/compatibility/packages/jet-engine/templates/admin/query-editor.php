<?php
/**
 * JetBooking query component template
 *
 * @package JET_ABAF\Compatibility\Packages
 */

?>

<div class="jet-engine-edit-page__fields">
	<div class="cx-vui-collapse__heading">
		<h3 class="cx-vui-subtitle">
			<?php esc_html_e( 'JetBooking Query', 'jet-booking' ); ?>
		</h3>
	</div>

	<div class="cx-vui-panel">
		<cx-vui-tabs
			:in-panel="false"
			value="general"
			layout="vertical"
		>
			<cx-vui-tabs-panel
				name="general"
				:label="isInUseMark( [ 'status', 'include', 'exclude', 'apartment_id', 'apartment_unit', 'order_id' ] ) + '<?php esc_html_e( 'General', 'jet-booking' ); ?>'"
				key="general"
			>
				<cx-vui-f-select
					label="<?php esc_html_e( 'Status', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Select the status of the booking.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth' ]"
					:options-list="statuses"
					:multiple="true"
					name="query_status"
					v-model="query.status"
				></cx-vui-f-select>

				<cx-vui-input
					label="<?php esc_html_e( 'Include', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Specify bookings to retrieve. Comma-separated bookings IDs list. E.g.: 1, 10, 25.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth', 'has-macros' ]"
					size="fullwidth"
					name="query_include"
					v-model="query.include"
				>
					<jet-query-dynamic-args v-model="dynamicQuery.include"></jet-query-dynamic-args>
				</cx-vui-input>

				<cx-vui-input
					label="<?php esc_html_e( 'Exclude', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Specify bookings NOT to retrieve. Comma-separated bookings IDs list. E.g.: 1, 10, 25. <b>ATTENTION:</b> Ignored if you use `Include`.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth', 'has-macros' ]"
					size="fullwidth"
					name="query_exclude"
					v-model="query.exclude"
				>
					<jet-query-dynamic-args v-model="dynamicQuery.exclude"></jet-query-dynamic-args>
				</cx-vui-input>

				<cx-vui-f-select
					label="<?php esc_html_e( 'Vendor', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Select the vendor of the booking.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth', 'has-macros' ]"
					:options-list="vendors"
					:multiple="true"
					name="query_booking_vendor"
					v-model="query.booking_vendor"
				>
					<jet-query-dynamic-args v-model="dynamicQuery.booking_vendor"></jet-query-dynamic-args>
				</cx-vui-f-select>

				<cx-vui-f-select
					label="<?php esc_html_e( 'Instance', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Select the instance of the booking.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth', 'has-macros' ]"
					:options-list="bookingInstances"
					:multiple="true"
					name="query_apartment_id"
					v-model="query.apartment_id"
				>
					<jet-query-dynamic-args v-model="dynamicQuery.apartment_id"></jet-query-dynamic-args>
				</cx-vui-f-select>

				<cx-vui-input
					label="<?php esc_html_e( 'Instance Unit', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Specify the instance unit for which you want to get bookings. Comma-separated booking instance unit IDs list. E.g.: 1, 10, 25. <b>ATTENTION:</b> This option doesn\'t work without `Instance`.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth', 'has-macros' ]"
					size="fullwidth"
					name="query_apartment_unit"
					v-model="query.apartment_unit"
				>
					<jet-query-dynamic-args v-model="dynamicQuery.apartment_unit"></jet-query-dynamic-args>
				</cx-vui-input>

				<cx-vui-input
					label="<?php esc_html_e( 'Related Order IDs', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Specify the related order for which you want to get bookings. Comma-separated booking related orders IDs list. E.g.: 1, 10, 25.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth', 'has-macros' ]"
					size="fullwidth"
					name="query_order_id"
					v-model="query.order_id"
				>
					<jet-query-dynamic-args v-model="dynamicQuery.order_id"></jet-query-dynamic-args>
				</cx-vui-input>

				<cx-vui-input
					label="<?php esc_html_e( 'User ID', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Specify the user id for which you want to get bookings. Comma-separated booking users IDs list. E.g.: 1, 10, 25.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth', 'has-macros' ]"
					size="fullwidth"
					name="query_user_id"
					v-model="query.user_id"
				>
					<jet-query-dynamic-args v-model="dynamicQuery.user_id"></jet-query-dynamic-args>
				</cx-vui-input>
			</cx-vui-tabs-panel>

			<cx-vui-tabs-panel
				name="order"
				:label="isInUseMark( [ 'orderby' ] ) + '<?php esc_html_e( 'Order & Order By', 'jet-booking' ); ?>'"
				key="order"
			>
				<cx-vui-component-wrapper
					:wrapper-css="[ 'query-fullwidth' ]"
				>
					<div class="cx-vui-inner-panel query-panel">
						<div class="cx-vui-component__label">
							<?php esc_attr_e( 'Order & Order By', 'jet-booking' ); ?>
						</div>

						<cx-vui-repeater
							button-label="<?php esc_attr_e( 'Add new', 'jet-booking' ); ?>"
							button-style="accent"
							button-size="mini"
							v-model="query.orderby"
							@add-new-item="addNewField( $event, [], query.orderby )"
						>
							<cx-vui-repeater-item
								v-for="( order, index ) in query.orderby"
								:collapsed="isCollapsed( order )"
								:index="index"
								@clone-item="cloneField( $event, order._id, query.orderby )"
								@delete-item="deleteField( $event, order._id, query.orderby )"
								:key="order._id"
							>
								<cx-vui-select
									label="<?php esc_attr_e( 'Order By', 'jet-booking' ); ?>"
									description="<?php esc_attr_e( 'Select column to sort the result-set.', 'jet-booking' ); ?>"
									:wrapper-css="[ 'equalwidth' ]"
									:options-list="availableColumns"
									size="fullwidth"
									:value="query.orderby[ index ].orderby"
									@input="setFieldProp( order._id, 'orderby', $event, query.orderby )"
								></cx-vui-select>

								<cx-vui-select
									label="<?php esc_attr_e( 'Order', 'jet-booking' ); ?>"
									description="<?php esc_attr_e( 'Select order for the `Order By` parameter', 'jet-booking' ); ?>"
									:wrapper-css="[ 'equalwidth' ]"
									:options-list="[
										{
											value: 'ASC',
											label: '<?php esc_html_e( 'From lowest to highest values (1, 2, 3; a, b, c)', 'jet-booking' ); ?>',
										},
										{
											value: 'DESC',
											label: '<?php esc_html_e( 'From highest to lowest values (3, 2, 1; c, b, a)', 'jet-booking' ); ?>',
										},
									]"
									size="fullwidth"
									:value="query.orderby[ index ].order"
									@input="setFieldProp( order._id, 'order', $event, query.orderby )"
								></cx-vui-select>
							</cx-vui-repeater-item>
						</cx-vui-repeater>
					</div>
				</cx-vui-component-wrapper>
			</cx-vui-tabs-panel>

			<cx-vui-tabs-panel
				name="date_query"
				:label="isInUseMark( [ 'date_query' ] ) + '<?php esc_attr_e( 'Date Query', 'jet-booking' ); ?>'"
				key="date_query"
			>
				<cx-vui-component-wrapper
					:wrapper-css="[ 'query-fullwidth' ]"
				>
					<div class="cx-vui-inner-panel query-panel">
						<div class="cx-vui-component__label">
							<?php esc_attr_e( 'Date Query Clauses', 'jet-booking' ); ?>
						</div>

						<cx-vui-repeater
							button-label="<?php esc_attr_e( 'Add new', 'jet-booking' ); ?>"
							button-style="accent"
							button-size="mini"
							v-model="query.date_query"
							@add-new-item="addNewField( $event, [], query.date_query, newDynamicDate )"
						>
							<cx-vui-repeater-item
								v-for="( clause, index ) in query.date_query"
								:collapsed="isCollapsed( clause )"
								:index="index"
								@clone-item="cloneField( $event, clause._id, query.date_query, newDynamicDate )"
								@delete-item="deleteField( $event, clause._id, query.date_query, deleteDynamicDate )"
								:key="clause._id"
							>
								<cx-vui-select
									label="<?php esc_html_e( 'Date', 'jet-booking' ); ?>"
									description="<?php esc_html_e( 'Select date type to query against.', 'jet-booking' ); ?>"
									:wrapper-css="[ 'equalwidth' ]"
									:options-list="[
										{
											value: 'check_in_date',
											label: '<?php esc_html_e( 'Check in Date', 'jet-booking' ); ?>',
										},
										{
											value: 'check_out_date',
											label: '<?php esc_html_e( 'Check out Date', 'jet-booking' ); ?>',
										}
									]"
									size="fullwidth"
									:value="query.date_query[ index ].date"
									@input="setFieldProp( clause._id, 'date', $event, query.date_query )"
								></cx-vui-select>

								<cx-vui-select
									label="<?php esc_html_e( 'Compare', 'jet-booking' ); ?>"
									description="<?php esc_html_e( 'Select compare operator to test expression.', 'jet-booking' ); ?>"
									:wrapper-css="[ 'equalwidth' ]"
									:options-list="queryOperators"
									size="fullwidth"
									:value="query.date_query[ index ].compare"
									@input="setFieldProp( clause._id, 'compare', $event, query.date_query )"
								></cx-vui-select>

								<cx-vui-input
									label="<?php esc_html_e( 'Value', 'jet-booking' ); ?>"
									description="<?php esc_html_e( 'Set value for comparison. E.g.: January 1st 2020, Today, Tomorrow etc.', 'jet-booking' ); ?>"
									:wrapper-css="[ 'equalwidth', 'has-macros' ]"
									size="fullwidth"
									:value="query.date_query[ index ].value"
									@input="setFieldProp( clause._id, 'value', $event, query.date_query )"
								>
									<jet-query-dynamic-args v-model="dynamicQuery.date_query[ clause._id ].value"></jet-query-dynamic-args>
								</cx-vui-input>
							</cx-vui-repeater-item>
						</cx-vui-repeater>
					</div>
				</cx-vui-component-wrapper>

				<cx-vui-select
					v-if="1 < query.date_query.length"
					label="<?php esc_html_e( 'Relation', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'The logical relationship between date query clauses.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth' ]"
					:options-list="[
						{
							value: 'and',
							label: '<?php esc_html_e( 'And', 'jet-booking' ); ?>',
						},
						{
							value: 'or',
							label: '<?php esc_html_e( 'Or', 'jet-booking' ); ?>',
						},
					]"
					size="fullwidth"
					v-model="query.date_query_relation"
				></cx-vui-select>
			</cx-vui-tabs-panel>

			<cx-vui-tabs-panel
				name="pagination"
				:label="isInUseMark( [ 'limit' ] ) + '<?php esc_html_e( 'Pagination', 'jet-booking' ); ?>'"
				key="pagination"
			>
				<cx-vui-input
					label="<?php esc_html_e( 'Limit', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Limit number of records. Number of visible bookings. If using with JetSmartFilters pagination - number of bookings to show per page.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth', 'has-macros' ]"
					size="fullwidth"
					name="query_limit"
					v-model="query.limit"
				>
					<jet-query-dynamic-args v-model="dynamicQuery.limit"></jet-query-dynamic-args>
				</cx-vui-input>

				<cx-vui-input
					label="<?php esc_html_e( 'Offset', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'Number of bookings to displace or pass over. E.g.: `2` to skip over 2 posts. <b>ATTENTION:</b> This option doesn\'t work without `Limit`. <b>WARNING:</b> This parameter overrides/ignores the page parameter and breaks pagination.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth', 'has-macros' ]"
					size="fullwidth"
					name="query_offset"
					v-model="query.offset"
				>
					<jet-query-dynamic-args v-model="dynamicQuery.offset"></jet-query-dynamic-args>
				</cx-vui-input>
			</cx-vui-tabs-panel>

			<cx-vui-tabs-panel
				name="columns_query"
				:label="isInUseMark( [ 'meta_query' ] ) + '<?php esc_attr_e( 'Columns Query', 'jet-booking' ); ?>'"
				key="columns_query"
			>
				<cx-vui-component-wrapper
					:wrapper-css="[ 'query-fullwidth' ]"
				>
					<div v-if="additionalColumns.length" class="cx-vui-inner-panel query-panel">
						<div class="cx-vui-component__label">
							<?php esc_attr_e( 'Columns Query Clauses', 'jet-booking' ); ?>
						</div>

						<cx-vui-repeater
							button-label="<?php esc_attr_e( 'Add new', 'jet-booking' ); ?>"
							button-style="accent"
							button-size="mini"
							v-model="query.meta_query"
							@add-new-item="addNewField( $event, [], query.meta_query, newDynamicMeta )"
						>
							<cx-vui-repeater-item
								v-for="( clause, index ) in query.meta_query"
								:collapsed="isCollapsed( clause )"
								:index="index"
								@clone-item="cloneField( $event, clause._id, query.meta_query, newDynamicMeta )"
								@delete-item="deleteField( $event, clause._id, query.meta_query, deleteDynamicMeta )"
								:key="clause._id"
							>
								<cx-vui-select
									label="<?php esc_html_e( 'Column', 'jet-booking' ); ?>"
									description="<?php esc_html_e( 'Select additional table column to query against.', 'jet-booking' ); ?>"
									:wrapper-css="[ 'equalwidth' ]"
									:options-list="additionalColumns"
									size="fullwidth"
									:value="query.meta_query[ index ].column"
									@input="setFieldProp( clause._id, 'column', $event, query.meta_query )"
								></cx-vui-select>

								<cx-vui-select
									label="<?php esc_html_e( 'Compare', 'jet-booking' ); ?>"
									description="<?php esc_html_e( 'Select compare operator to test expression.', 'jet-booking' ); ?>"
									:wrapper-css="[ 'equalwidth' ]"
									:options-list="queryOperators"
									size="fullwidth"
									:value="query.meta_query[ index ].compare"
									@input="setFieldProp( clause._id, 'compare', $event, query.meta_query )"
								></cx-vui-select>

								<cx-vui-input
									label="<?php esc_html_e( 'Value', 'jet-booking' ); ?>"
									description="<?php esc_html_e( 'Set value for comparison.', 'jet-booking' ); ?>"
									:wrapper-css="[ 'equalwidth', 'has-macros' ]"
									size="fullwidth"
									:value="query.meta_query[ index ].value"
									@input="setFieldProp( clause._id, 'value', $event, query.meta_query )"
								>
									<jet-query-dynamic-args v-model="dynamicQuery.meta_query[ clause._id ].value"></jet-query-dynamic-args>
								</cx-vui-input>
							</cx-vui-repeater-item>
						</cx-vui-repeater>
					</div>
					<p v-else class="cx-vui-component__desc"><?php esc_html_e( 'There are no additional table columns.', 'jet-booking' ); ?></p>
				</cx-vui-component-wrapper>
				<cx-vui-select
					v-if="1 < query.meta_query.length"
					label="<?php esc_html_e( 'Relation', 'jet-booking' ); ?>"
					description="<?php esc_html_e( 'The logical relationship between columns query clauses.', 'jet-booking' ); ?>"
					:wrapper-css="[ 'equalwidth' ]"
					:options-list="[
						{
							value: 'and',
							label: '<?php esc_html_e( 'And', 'jet-booking' ); ?>',
						},
						{
							value: 'or',
							label: '<?php esc_html_e( 'Or', 'jet-booking' ); ?>',
						},
					]"
					size="fullwidth"
					v-model="query.meta_query_relation"
				></cx-vui-select>
			</cx-vui-tabs-panel>
		</cx-vui-tabs>
	</div>
</div>