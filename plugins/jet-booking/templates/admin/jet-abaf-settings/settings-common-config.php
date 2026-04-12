<div class="cx-vui-components">
	<cx-vui-select
		label="<?php esc_html_e( 'Booking period', 'jet-booking' ); ?>"
		description="<?php _e( 'Define how the booking period will be calculated – per night (without the last booked date) or per day (including the last booked date).</br><b>Note:</b> this option will affect price calculation.', 'jet-booking' ); // phpcs:ignore ?>"
		:options-list="[
			{
				value: 'per_nights',
				label: '<?php esc_html_e( 'Per Night (last booked date is not included)', 'jet-booking' ); ?>',
			},
			{
				value: 'per_days',
				label: '<?php esc_html_e( 'Per Day (last booked date is included)', 'jet-booking' ); ?>',
			}
		]"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		:value="settings.booking_period"
		@input="updateSetting( $event, 'booking_period' )"
	></cx-vui-select>

	<cx-vui-switcher
		label="<?php esc_html_e( 'Allow checkout only days', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'If this option is checked, the first day of the already booked period will be available for checkout only.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		v-if="'per_nights' === settings.booking_period"
		:value="settings.allow_checkout_only"
		@input="updateSetting( $event, 'allow_checkout_only' )"
	></cx-vui-switcher>

	<cx-vui-switcher
		label="<?php esc_html_e( 'One day bookings', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'If this option is checked only single days bookings are allowed. If Weekly bookings are enabled this option will not work.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		v-if="'per_nights' !== settings.booking_period"
		:value="settings.one_day_bookings"
		@input="updateSetting( $event, 'one_day_bookings' )"
	></cx-vui-switcher>

	<cx-vui-switcher
		label="<?php esc_html_e( 'Week-long bookings', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'If this option is checked, only week-long bookings are allowed.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:value="settings.weekly_bookings"
		@input="updateSetting( $event, 'weekly_bookings' )"
	></cx-vui-switcher>

	<cx-vui-input
		label="<?php esc_html_e( 'Weekday offset', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'Allows you to change the first booked day of the week.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.week_offset"
		v-if="settings.weekly_bookings"
		@on-input-change="updateSetting( $event.target.value, 'week_offset' )"
		type="number"
	></cx-vui-input>

	<cx-vui-input
		label="<?php esc_html_e( 'Starting day offset', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'This string defines offset for the earliest date which is available to the user.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.start_day_offset"
		@on-input-change="updateSetting( $event.target.value, 'start_day_offset' )"
		type="number"
	></cx-vui-input>

	<cx-vui-input
		label="<?php esc_html_e( 'Min days', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'This number defines the minimum days of the selected range. If it equals 0, it means minimum days are not limited.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.min_days"
		@on-input-change="updateSetting( $event.target.value, 'min_days' )"
		type="number"
	></cx-vui-input>

	<cx-vui-input
		label="<?php esc_html_e( 'Max days', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'This number defines the maximum days of the selected range. If it equals 0, it means maximum days are not limited.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		size="fullwidth"
		:value="settings.max_days"
		@on-input-change="updateSetting( $event.target.value, 'max_days' )"
		type="number"
	></cx-vui-input>

	<cx-vui-component-wrapper
		label="<?php esc_html_e( 'End date', 'jet-booking' ); ?>"
		description="<?php esc_html_e( 'This option defines the latest date which is allowed for the user to pick.', 'jet-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
	>
		<cx-vui-select
			:prevent-wrap="true"
			:options-list="[
				{
					value: 'none',
					label: '<?php esc_html_e( 'Any date', 'jet-booking' ); ?>'
				},
				{
					value: 'range',
					label: '<?php esc_html_e( 'Limited range', 'jet-booking' ); ?>'
				}
			]"
			:value="settings.end_date_type"
			@input="updateSetting( $event, 'end_date_type' )"
			:size="'fullwidth'"
			style="margin-bottom: 10px;"
		></cx-vui-select>

		<div
			v-if="'range' === settings.end_date_type"
			style="display: flex; gap: 5px;"
		>
			<cx-vui-input
				:prevent-wrap="true"
				type="number"
				min="1"
				:value="settings.end_date_range_number"
				@on-input-change="updateSetting( $event.target.value, 'end_date_range_number' )"
				style="width: 55px;"
			></cx-vui-input>

			<cx-vui-select
				:prevent-wrap="true"
				:options-list="[
					{
						value: 'day',
						label: '<?php esc_html_e( 'Day(s)', 'jet-booking' ); ?>'
					},
					{
						value: 'month',
						label: '<?php esc_html_e( 'Month(s)', 'jet-booking' ); ?>'
					},
					{
						value: 'year',
						label: '<?php esc_html_e( 'Year(s)', 'jet-booking' ); ?>'
					}
				]"
				:size="'fullwidth'"
				:value="settings.end_date_range_unit"
				@input="updateSetting( $event, 'end_date_range_unit' )"
				style="width: 100px;"
			></cx-vui-select>
		</div>
	</cx-vui-component-wrapper>
</div>